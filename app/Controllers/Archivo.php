<?php

namespace App\Controllers;

use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\SolicitudModel;
use App\Libraries\Status;
use App\Libraries\HttpStatus;
use App\Libraries\Rest;
use App\Libraries\SolicitudTipo;

class Archivo extends BaseController
{
    protected $api;
    public function __construct()
    {
        $this->api = new Rest();
    }
    public function index()
    {
        return view('formulario_subida');
    }

    public function subir()
    {
        $post = $this->request->getPost();

        $codigos = [];
        $productos = [];
        $cantidades = [];
        $importes = [];
        $tipo = null;
        $comentariosuser = null;

        // Determina el tipo de solicitud y prepara los arrays de datos
        if (isset($post['servicio'])) {
            // Solicitud de Servicio
            $tipo = SolicitudTipo::Servicios;
            $productos = $post['servicio']; // Descripciones de los servicios
            $importes = $post['importe'];
            // Para servicios, la cantidad es 1 por defecto y no hay código de producto
            $cantidades = array_fill(0, count($productos), 1);
            $codigos = array_fill(0, count($productos), null);
        } elseif (isset($post['sin_cotizar'])) {
            // Solicitud de Material sin Cotizar
            $tipo = SolicitudTipo::NoCotizacion;
            $productos = $post['producto'];
            $cantidades = $post['cantidad'];
            // No hay códigos ni importes para este tipo de solicitud
            $codigos = array_fill(0, count($productos), null);
            $importes = array_fill(0, count($productos), 0);
        } else {
            // Solicitud de Material con Cotización (estándar)
            $tipo = SolicitudTipo::Cotizacion;
            $codigos = $post['codigo'];
            $productos = $post['producto'];
            $cantidades = $post['cantidad'];
            $importes = $post['importe'];
        }

        $user = $this->api->getUserById(session('id'));

        $razon_social_id = isset($post['razon_social']) ? $post['razon_social'] : null;
        $razon = null;
        if (!empty($razon_social_id)) {
            $razon = $this->api->getProveedorById((int) $razon_social_id);
        }

        $fecha = $post['fecha'];
        $comentariosuser = isset($post['comentariosuser']) ? $post['comentariosuser'] : null;


        // Determinar el estado inicial basado en el tipo de login
        $estadoInicial = (session('login_type') === 'boss') ? Status::En_espera : Status::Aprobacion_pendiente;

        $datosSolicitud = [
            'ID_Usuario' => $user['ID_Usuario'],
            'ID_Dpto' => $user['ID_Dpto'],
            'ID_Proveedor' => $razon['ID_Proveedor'] ?? null,
            'IVA' => isset($post['iva']) ? true : false,
            'Fecha' => $fecha,
            'Estado' => $estadoInicial,
            'No_Folio' => null,
            'Tipo' => $tipo,
            'ComentariosUser' => $comentariosuser
        ];

        $datosProductos = [];

        try {
            $solicitud = new SolicitudModel();
            $solicitud->insert($datosSolicitud);
            $solicitudId = $solicitud->insertID();
            $solicitud->update($solicitudId, [
                'No_Folio' => 'mbsp-' . $solicitudId,
            ]);
            if ($tipo == SolicitudTipo::Cotizacion || $tipo == SolicitudTipo::NoCotizacion) {
                $solicitudProduct = new SolicitudProductModel();

                for ($i = 0; $i < count($productos); $i++) {
                    $datosProductos[] = [
                        'Codigo' => $codigos[$i] ?? null,
                        'Nombre' => $productos[$i],
                        'Cantidad' => $cantidades[$i],
                        'Importe' => $importes[$i],
                    ];
                }

                foreach ($datosProductos as $solproducto) {
                    $solproducto['ID_Solicitud'] = $solicitudId;
                    $solicitudProduct->insert($solproducto);
                }
            } else {
                $solicitudServicio = new SolicitudServiciosModel();
                for ($i = 0; $i < count($productos); $i++) {
                    $datosProductos[] = [
                        'Nombre' => $productos[$i],
                        'Importe' => $importes[$i],
                    ];
                }
                foreach ($datosProductos as $solproducto) {
                    $solproducto['ID_Solicitud'] = $solicitudId;
                    $solicitudServicio->insert($solproducto);
                }
            }

            $adjunto = $this->request->getFile('archivo');
            if ($adjunto && $adjunto->isValid()) {
                $nuevoNombre = 'solicitud_' . $solicitudId . '_' . $adjunto->getRandomName();
                $folder = WRITEPATH . 'uploads/solicitud/' . $fecha;
                $this->api->CreateFolder($folder);
                $adjunto->move($folder, $nuevoNombre);
                $solicitud->update($solicitudId, ['Archivo' => $nuevoNombre]);
            }
            return $this->response->setStatusCode(HttpStatus::OK)->setJSON([
                'success' => true,
                'message' => 'Solicitud registrada correctamente ✔',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $e->getMessage(),
            ]);
        }
    }

    public function descargar($idSolicitud)
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud || empty($solicitud['Archivo'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Archivo no encontrado para esta solicitud.',
            );
        }

        $filePath =
            WRITEPATH . 'uploads/solicitud/' . $solicitud['Fecha'] . '/' . $solicitud['Archivo'];

        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'El archivo físico no existe en el servidor.',
            );
        }

        // Envía el archivo al navegador para su descarga
        return $this->response->download($filePath, null);
    }
}