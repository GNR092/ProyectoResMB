<?php

namespace App\Libraries;

use App\Models\SolicitudModel;
use App\Models\CotizacionModel;
use App\Models\OrdenCompraModel;
use App\Models\UsuariosModel;

class WhatsAppService
{
    private $config = [
        'host'       => 'http://172.28.0.1:2886',
        'session_id' => '46905a94-6e94-43c5-8e76-f8940653b6fa',
        'api_key'    => 'owa_k1_28ca20692322ec262bffba81d8c53f30ad2742b40f85e6b85f7d17dde266bb99',
    ];

    /**
     * Envía una notificación de cambio de estado a través de WhatsApp si está habilitado.
     *
     * @param int $idSolicitud
     * @return array|null
     */
    public function notificarCambioEstado(int $idSolicitud)
    {
        $solModel = new SolicitudModel();
        $solicitud = $solModel->find($idSolicitud);

        if (!$solicitud || !($solicitud['notificaciones_whatsapp'] ?? false)) {
            return null;
        }

        $userModel = new UsuariosModel();
        $usuario = $userModel->find($solicitud['ID_Usuario']);

        if (!$usuario || empty($usuario['Numero'])) {
            return ['status' => 'error', 'message' => 'Usuario no tiene número registrado.'];
        }

        $cotiModel = new CotizacionModel();
        $ocModel = new OrdenCompraModel();

        $id_solicitud = $solicitud['ID_Solicitud'];
        $cotizacion = $cotiModel->where('ID_Solicitud', $id_solicitud)->first();
        $monto = $cotizacion ? "$ " . number_format($cotizacion['Total'], 2) : "Pendiente";
        $id_coti = $cotizacion ? $cotizacion['ID_Cotizacion'] : null;

        $orden = $id_coti ? $ocModel->where('ID_Cotizacion', $id_coti)->first() : null;
        $id_oc = $orden ? $orden['ID_OrdenCompra'] : "No generada";
        $estatus = $orden ? $orden['Estado'] : $solicitud['Estado'];
        $fecha_pago = ($orden && $orden['FechaPagoRealizado']) ? $orden['FechaPagoRealizado'] : "Sin fecha";

        $reporte = "📋 *ACTUALIZACIÓN DE REQUISICIÓN*\n\n";
        $reporte .= "🔹 *Folio:* " . $solicitud['No_Folio'] . "\n";
        $reporte .= "🆔 *ID Solicitud:* " . $id_solicitud . "\n";
        $reporte .= "💰 *Monto:* " . $monto . "\n";
        $reporte .= "📊 *Estatus:* " . strtoupper($estatus) . "\n";
        $reporte .= "📦 *ID Orden:* " . $id_oc . "\n";
        $reporte .= "📅 *Pago Realizado:* " . $fecha_pago . "\n\n";

        if (!empty($solicitud['ComentariosAdmin'])) {
            $rawComment = $solicitud['ComentariosAdmin'];
            // Limpiar formato [Nombre]: Comentario si existe
            $mensajeMostrar = preg_replace('/^\[.*?\]:\s*/', '', $rawComment);
            $reporte .= "💬 *Comentarios:* " . $mensajeMostrar . "\n\n";
        }

        $reporte .= "_Mensaje automático enviado vía Centro de Operaciones TI._ \n";
        $reporte .= "*NO RESPONDER*";
        return $this->sendWhatsApp($reporte, $usuario['Numero']);
    }

    /**
     * Realiza la petición cURL a la API de OpenWA.
     */
    private function sendWhatsApp($text, $target)
    {
        $url = "{$this->config['host']}/api/sessions/{$this->config['session_id']}/messages/send-text";
        $digits = preg_replace('/[^0-9]/', '', $target);
        
        // Regla Móvil México: 10 dígitos -> prefijo 521 + número + @c.us
        $chat_id = (strlen($digits) === 10 ? "521$digits" : $digits) . "@c.us";
        
        $payload = json_encode([
            'chatId' => $chat_id,
            'text'   => $text
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout corto para no bloquear la UI demasiado
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->config['api_key']
        ]);
        
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status'   => $status,
            'response' => $response,
            'chat_id'  => $chat_id
        ];
    }
}
