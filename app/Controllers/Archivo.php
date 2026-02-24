<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\SolicitudModel;
use App\Models\CotizacionModel;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Libraries\Status;
use App\Libraries\HttpStatus;
use App\Libraries\Rest;
use App\Libraries\SolicitudTipo;
use App\Libraries\MetodoPago;
use App\Controllers\GenerarPDF;
use App\Libraries\MBSMail;

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
            $tipo = SolicitudTipo::Servicios;
            $productos = $post['servicio'];
            $importes = $post['importe'];
            $cantidades = array_fill(0, count($productos), 1);
            $codigos = array_fill(0, count($productos), null);
        } elseif (isset($post['sin_cotizar'])) {
            $tipo = SolicitudTipo::NoCotizacion;
            $productos = $post['producto'];
            $cantidades = $post['cantidad'];
            $codigos = array_fill(0, count($productos), null);
            $importes = array_fill(0, count($productos), 0);
        } else {
            $tipo = SolicitudTipo::Cotizacion;
            $codigos = $post['codigo'];
            $productos = $post['producto'];
            $cantidades = $post['cantidad'];
            $importes = $post['importe'];
        }

        $user = $this->api->getUserById(session('id'));

        $razon_social_id = isset($post['razon_social']) ? $post['razon_social'] : null;
        $proveedor_id = isset($post['ID_Proveedor']) ? $post['ID_Proveedor'] : null;
        $cuenta_id = $post['cuenta_proveedor'] ?? ($post['ID_Cuenta'] ?? null);

        $razon = null;
        $proveedor = null;
        if (!empty($razon_social_id)) {
            $razon = $this->api->getRazonSocialByID((int) $razon_social_id);
        }
        if (!empty($proveedor_id)) {
            $proveedor = $this->api->getProveedorById((int) $proveedor_id);
        }

        $fecha = $post['fecha'];
        $comentariosuser = isset($post['comentariosuser']) ? $post['comentariosuser'] : null;

        $estadoInicial = Status::Aprobacion_pendiente;

        if (session('login_type') === 'boss') {
            if ($tipo == SolicitudTipo::Servicios) {
                $estadoInicial = Status::Cotizando;
            } else {
                $estadoInicial = Status::En_espera;
            }
        }

        $datosSolicitud = [
            'ID_Usuario' => $user['ID_Usuario'],
            'ID_Dpto' => $user['ID_Dpto'],
            'ID_Proveedor' => $proveedor['ID_Proveedor'] ?? null,
            'ID_Cuenta' => $cuenta_id,
            'ID_RazonSocial' => $razon['ID_RazonSocial'] ?? null,
            'IVA' => isset($post['iva']) ? true : false,
            'Fecha' => $fecha,
            'Estado' => $estadoInicial,
            'No_Folio' => null,
            'Tipo' => $tipo,
            'ComentariosUser' => $comentariosuser,
            'MetodoPago' => MetodoPago::EnEspera,
        ];

        $datosProductos = [];

        try {
            $solicitud = new SolicitudModel();
            $solicitud->insert($datosSolicitud);
            $solicitudId = $solicitud->insertID();
            $solicitud->update($solicitudId, [
                'No_Folio' => 'MBSP-' . $solicitudId,
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

            // CORRECCIÓN 1: Aseguramos la evaluación del estado
            if ($estadoInicial === Status::Cotizando || $estadoInicial === 'Cotizando') {
                $cotizacionModel = new CotizacionModel();

                // Calculamos el total
                $total = 0;

                // CORRECCIÓN 2: Cálculo total más seguro
                foreach ($datosProductos as $p) {
                    $cantidad = isset($p['Cantidad']) ? (float)$p['Cantidad'] : 1;
                    $importe = isset($p['Importe']) ? (float)$p['Importe'] : 0;
                    $total += ($cantidad * $importe);
                }

                // ==========================================
                // PASO A: CREACIÓN GARANTIZADA DE COTIZACIÓN
                // ==========================================
                if (!empty($proveedor_id)) {
                    $razonSocialModel = new RazonSocialModel();
                    $proveedorModel = new ProveedorModel();

                    $solicitudData = $solicitud->find($solicitudId);
                    $razon = $razonSocialModel->find($solicitudData['ID_RazonSocial']);
                    $razonNombre = $razon['Nombre'] ?? '';

                    // CORRECCIÓN 3: Aislar el PDF. Si falla por permisos en Linux, NO detiene la BD
                    try {
                        $pdf = new GenerarPDF();
                        $pdf->generarYGuardarRequisicion($solicitudId);
                    } catch (\Exception $e) {
                        log_message('error', '[PDF Error] Fallo al crear la requisición PDF: ' . $e->getMessage());
                    }

                    $mail = new MBSMail();
                    $idProveedores = is_array($proveedor_id) ? $proveedor_id : [$proveedor_id];

                    // CORRECCIÓN 4: Inserción directa sin transacción frágil y con reporte de errores
                    foreach ($idProveedores as $idProv) {
                        $idProveedor = (int) $idProv;

                        $cotizacionData = [
                            'ID_Solicitud' => $solicitudId,
                            'ID_Proveedor' => (int) $idProv,
                            'Total' => $total,
                            'ID_Usuario_Cotiza' => $user['ID_Usuario'],
                        ];

                        $inserted = $cotizacionModel->insert($cotizacionData);

                        // Si MySQL rechaza la inserción, detenemos todo y mostramos POR QUÉ
                        if (!$inserted) {
                            return $this->response->setStatusCode(400)->setJSON([
                                'success' => false,
                                'message' => 'Error de Base de Datos al guardar la cotización',
                                'errores' => $cotizacionModel->errors(),
                                'error_db' => $cotizacionModel->db->error()
                            ]);
                        }
                    }
                }
            }

            $adjunto = $this->request->getFile('archivo');
            if ($adjunto && $adjunto->isValid()) {
                $nuevoNombre = 'solicitud_' . $solicitudId . '_' . $adjunto->getRandomName();
                $folder = FPath::FSOLICITUD . $fecha;
                $this->api->CreateFolder($folder);
                $adjunto->move($folder, $nuevoNombre);
                $solicitud->update($solicitudId, ['Archivo' => $nuevoNombre]);
            }

            return $this->response->setStatusCode(HttpStatus::OK)->setJSON([
                'success' => true,
                'message' => 'Solicitud registrada correctamente',
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
    public function descargarCotizacion($idSolicitud, $file)
    {
        $solicitud = $this->api->getSolicitudWithCotizacion($idSolicitud);

        if (!$solicitud || empty($solicitud['cotizacion'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Archivo no encontrado para esta solicitud.',
            );
        }

        $filePath = FPath::FCOTIZACION . $solicitud['Fecha'] . '/' . $file;

        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'El archivo físico no existe en el servidor.',
            );
        }

        // Envía el archivo al navegador para su descarga
        return $this->response->download($filePath, null);
    }
}