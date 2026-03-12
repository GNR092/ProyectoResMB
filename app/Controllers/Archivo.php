<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\SolicitudModel;
use App\Models\CotizacionModel;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Models\DepartamentosModel;
use App\Models\PlacesModel;
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
        try {
            $post = $this->request->getPost();

            $codigos = [];
            $productos = [];
            $cantidades = [];
            $importes = [];
            $grupos_presupuestales = [];
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
                $grupos_presupuestales = $post['id_grupo_presupuestal'] ?? [];
                $codigos = array_fill(0, count($productos), null);
                $importes = array_fill(0, count($productos), 0);
            } else {
                $tipo = SolicitudTipo::Cotizacion;
                $codigos = $post['codigo'];
                $productos = $post['producto'];
                $cantidades = $post['cantidad'];
                $importes = $post['importe'];
                $grupos_presupuestales = $post['id_grupo_presupuestal'] ?? [];
            }

            $user = $this->api->getUserById((int)session('id'));

            if (empty($user)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Sesión expirada o usuario no encontrado. Por favor, inicie sesión de nuevo.',
                ]);
            }

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

            // --- NUEVA LÓGICA DE INTEGRIDAD PRESUPUESTAL Y TRAZABILIDAD ---
            $idDptoFinal = $user['ID_Dpto'] ?? null;
            $etiquetaTrazabilidad = "";

            // 1. Obtener datos del origen del usuario (independientemente de a dónde cargue)
            $departamentoModel = new DepartamentosModel();
            $idDptoOriginal = $user['ID_Dpto'] ?? null;
            $idRSOriginal = $user['ID_RazonSocial'] ?? null;
            $deptoUsuario = $idDptoOriginal ? $departamentoModel->find($idDptoOriginal) : null;
            
            if ($deptoUsuario) {
                $nombreDeptoOrig = $deptoUsuario['Nombre'] ?? 'Desconocido';
                
                $razonSocialModel = new RazonSocialModel();
                $placeModel = new PlacesModel();
                $idUnidadOrig = $deptoUsuario['ID_UnidadOperativa'] ?? 0;
                $uniModel = new \App\Models\UnidadOperativaModel();
                $unidadOrig = $uniModel->find($idUnidadOrig);
                $placeOrig = $placeModel->find($unidadOrig['ID_Place'] ?? 0);
                
                $nombreRSOrig = 'RS Desconocida';
                if ($placeOrig) {
                    $razonOrig = $razonSocialModel->find($placeOrig['ID_RazonSocial'] ?? 0);
                    $nombreRSOrig = $razonOrig['Nombre'] ?? 'RS Desconocida';
                    // Actualizamos la RS original del usuario si no la teníamos
                    if (!$idRSOriginal) $idRSOriginal = $placeOrig['ID_RazonSocial'] ?? null;
                }

                // SOLO generamos etiqueta si la RS seleccionada es DISTINTA a la del usuario
                if (!empty($razon_social_id) && $razon_social_id != $idRSOriginal) {
                    $etiquetaTrazabilidad = "Solicitud originada por: [$nombreDeptoOrig - $nombreRSOrig].";
                }

                // 2. Buscar el ID_Dpto en la Razón Social seleccionada (Destino)
                if (!empty($razon_social_id)) {
                    $deptoDestino = $departamentoModel
                        ->select('Departamentos.ID_Dpto')
                        ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Departamentos.ID_UnidadOperativa')
                        ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
                        ->where('Places.ID_RazonSocial', $razon_social_id)
                        ->where('Departamentos.Nombre', $nombreDeptoOrig)
                        ->first();

                    if ($deptoDestino) {
                        $idDptoFinal = $deptoDestino['ID_Dpto'];
                    }
                }
            }

            // 3. Inyectar trazabilidad en los comentarios (Solo si existe etiqueta)
            $comentariosFinal = $etiquetaTrazabilidad 
                ? ($etiquetaTrazabilidad . ($comentariosuser ? " | Comentario: " . $comentariosuser : ""))
                : $comentariosuser;

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
                'ID_Dpto' => $idDptoFinal,
                'ID_UnidadOperativa' => $deptoUsuario['ID_UnidadOperativa'] ?? null,
                'ID_Proveedor' => $proveedor['ID_Proveedor'] ?? null,
                'ID_Cuenta' => $cuenta_id,
                'ID_RazonSocial' => $razon['ID_RazonSocial'] ?? null,
                'IVA' => isset($post['iva']) ? true : false,
                'Fecha' => $fecha,
                'Estado' => $estadoInicial,
                'No_Folio' => null,
                'Tipo' => $tipo,
                'ComentariosUser' => $comentariosFinal,
                'MetodoPago' => MetodoPago::EnEspera,
            ];

            $solicitud = new SolicitudModel();
            if (!$solicitud->insert($datosSolicitud)) {
                throw new \Exception('No se pudo insertar la solicitud: ' . json_encode($solicitud->errors()));
            }
            
            $solicitudId = $solicitud->insertID();
            $solicitud->update($solicitudId, [
                'No_Folio' => 'MBSP-' . $solicitudId,
            ]);

            $datosProductos = [];
            if ($tipo == SolicitudTipo::Cotizacion || $tipo == SolicitudTipo::NoCotizacion) {
                $solicitudProduct = new SolicitudProductModel();

                for ($i = 0; $i < count($productos); $i++) {
                    $item = [
                        'ID_Solicitud' => $solicitudId,
                        'Codigo' => $codigos[$i] ?? null,
                        'Nombre' => $productos[$i],
                        'Cantidad' => $cantidades[$i],
                        'Importe' => $importes[$i],
                        'ID_GrupoPresupuestal' => $grupos_presupuestales[$i] ?? null,
                    ];
                    $solicitudProduct->insert($item);
                    $datosProductos[] = $item;
                }
            } else {
                $solicitudServicio = new SolicitudServiciosModel();
                for ($i = 0; $i < count($productos); $i++) {
                    $item = [
                        'ID_Solicitud' => $solicitudId,
                        'Nombre' => $productos[$i],
                        'Importe' => $importes[$i],
                    ];
                    $solicitudServicio->insert($item);
                    $datosProductos[] = $item;
                }
            }

            // CORRECCIÓN 1: Aseguramos la evaluación del estado
            if ($estadoInicial === Status::Cotizando || $estadoInicial === 'Cotizando') {
                $cotizacionModel = new CotizacionModel();
                $total = 0;
                foreach ($datosProductos as $p) {
                    $cantidad = isset($p['Cantidad']) ? (float)$p['Cantidad'] : 1;
                    $importe = isset($p['Importe']) ? (float)$p['Importe'] : 0;
                    $total += ($cantidad * $importe);
                }

                if (!empty($proveedor_id)) {
                    $pdf = new GenerarPDF();
                    try {
                        $pdf->generarYGuardarRequisicion($solicitudId);
                    } catch (\Exception $e) {
                        log_message('error', '[PDF Error] ' . $e->getMessage());
                    }

                    $idProveedores = is_array($proveedor_id) ? $proveedor_id : [$proveedor_id];
                    foreach ($idProveedores as $idProv) {
                        $cotizacionModel->insert([
                            'ID_Solicitud' => $solicitudId,
                            'ID_Proveedor' => (int) $idProv,
                            'Total' => $total,
                            'ID_Usuario_Cotiza' => $user['ID_Usuario'],
                        ]);
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

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Solicitud registrada correctamente.',
                'id' => $solicitudId,
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Archivo::subir] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al registrar la solicitud: ' . $e->getMessage(),
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

        $filePath = WRITEPATH . 'uploads/solicitud/' . $solicitud['Fecha'] . '/' . $solicitud['Archivo'];

        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'El archivo físico no existe en el servidor.',
            );
        }

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

        return $this->response->download($filePath, null);
    }
}
