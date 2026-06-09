<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\SolicitudModel;
use App\Models\CotizacionModel;
use App\Models\OrdenCompraModel;

class MbsReport extends BaseCommand
{
    protected $group       = 'MBS';
    protected $name        = 'mbs:report';
    protected $description = 'Envía un reporte de requisición vía WhatsApp.';
    protected $usage       = 'whatsapp:report [folio]';
    protected $arguments   = ['folio' => 'El folio de la requisición (ej. MBSP-120)'];

    public function run(array $params)
    {
        $folio = array_shift($params);

        if (empty($folio)) {
            $folio = CLI::prompt('Introduce el Folio o ID de la requisición');
        }

        CLI::write("Generando reporte para: $folio...", 'yellow');

        $solModel = new SolicitudModel();
        $cotiModel = new CotizacionModel();
        $ocModel = new OrdenCompraModel();

        $solicitud = $solModel->groupStart()
                              ->where('ID_Solicitud', is_numeric($folio) ? $folio : -1)
                              ->orWhere('No_Folio', $folio)
                              ->groupEnd()
                              ->first();

        if (!$solicitud) {
            CLI::error("No se encontró la requisición: $folio");
            return;
        }

        $id_solicitud = $solicitud['ID_Solicitud'];
        $cotizacion = $cotiModel->where('ID_Solicitud', $id_solicitud)->first();
        $monto = $cotizacion ? "$ " . number_format($cotizacion['Total'], 2) : "Pendiente";
        $id_coti = $cotizacion ? $cotizacion['ID_Cotizacion'] : null;

        $orden = $id_coti ? $ocModel->where('ID_Cotizacion', $id_coti)->first() : null;
        $id_oc = $orden ? $orden['ID_OrdenCompra'] : "No generada";
        $estatus = $orden ? $orden['Estado'] : $solicitud['Estado'];
        $fecha_pago = ($orden && $orden['FechaPagoRealizado']) ? $orden['FechaPagoRealizado'] : "Sin fecha";

        $reporte = "📋 *REPORTE DE REQUISICIÓN*\n\n";
        $reporte .= "🔹 *Folio:* " . $solicitud['No_Folio'] . "\n";
        $reporte .= "🆔 *ID Solicitud:* " . $id_solicitud . "\n";
        $reporte .= "💰 *Monto:* " . $monto . "\n";
        $reporte .= "📊 *Estatus:* " . strtoupper($estatus) . "\n";
        $reporte .= "🧾 *ID Cotización:* " . ($id_coti ?? 'N/A') . "\n";
        $reporte .= "📦 *ID Orden:* " . $id_oc . "\n";
        $reporte .= "📅 *Pago Realizado:* " . $fecha_pago . "\n\n";
        $reporte .= "_Mensaje enviado vía MB Command Center._";

        CLI::write("Mensaje preparado:", 'cyan');
        CLI::write($reporte);

        // --- DATOS DE CONEXIÓN WHATSAPP ---
        $config = [
            'session_id' => "5ca87175-7fe6-4fae-8435-e2e1e5c68e4c",
            'api_key'    => "owa_k1_28ca20692322ec262bffba81d8c53f30ad2742b40f85e6b85f7d17dde266bb99",
            'host'       => "http://172.28.0.1:2886",
            'target'     => "9993544891"
        ];

        if (CLI::confirm("¿Enviar este reporte a WhatsApp?")) {
            $result = $this->sendWhatsApp($reporte, $config);
            if ($result['status'] == 201) {
                CLI::write("✅ Reporte enviado exitosamente.", 'green');
            } else {
                CLI::error("Fallo al enviar: " . $result['status']);
                CLI::error($result['response']);
            }
        }
    }

    private function sendWhatsApp($text, $config) {
        $url = "{$config['host']}/api/sessions/{$config['session_id']}/messages/send-text";
        $digits = preg_replace('/[^0-9]/', '', $config['target']);
        $chat_id = (strlen($digits) === 10 ? "521$digits" : $digits) . "@c.us";
        $payload = json_encode(['chatId' => $chat_id, 'text' => $text]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $config['api_key']
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'response' => $response];
    }
}
