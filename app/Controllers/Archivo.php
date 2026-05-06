<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\CatalogoProductosModel;
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
use App\Libraries\PdfValidator;

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
                $grupos_presupuestales = $post['id_grupo_presupuestal'] ?? [];
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
                \CodeIgniter\Events\Events::trigger('auditoria', [
                    'tipo_accion'    => 'FALLO_SESION_SUBIDA',
                    'modulo'         => 'Solicitudes',
                    'estado'         => 'fallido',
                    'valores_nuevos' => json_encode(['mensaje' => 'Sesión expirada al intentar subir solicitud'])
                ]);
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
            $idUnidadFinal = null;
            $etiquetaTrazabilidad = "";

            // 1. Obtener datos del origen del usuario (independientemente de a dónde cargue)
            $departamentoModel = new DepartamentosModel();
            $unidadOperativaModel = new \App\Models\UnidadOperativaModel();
            $idDptoOriginal = $user['ID_Dpto'] ?? null;
            $idRSOriginal = $user['ID_RazonSocial'] ?? null;
            $deptoUsuario = $idDptoOriginal ? $departamentoModel->find($idDptoOriginal) : null;
            
            if ($deptoUsuario) {
                $nombreDeptoOrig = (string) ($deptoUsuario['Nombre'] ?? 'Desconocido');
                $idUnidadFinal = $deptoUsuario['ID_UnidadOperativa'] ?? null; // Por defecto es la del usuario
                
                $placeModel = new PlacesModel();
                $idPlaceOrig = $deptoUsuario['ID_Place'] ?? 0;
                $placeOrig = $placeModel->find($idPlaceOrig);
                
                if ($placeOrig) {
                    // Actualizamos la RS original del usuario si no la teníamos
                    if (!$idRSOriginal) $idRSOriginal = $placeOrig['ID_RazonSocial'] ?? null;
                }

                // SOLO generamos etiqueta si la RS seleccionada es DISTINTA a la del usuario
                if (!empty($razon_social_id) && $razon_social_id != $idRSOriginal) {
                    $etiquetaTrazabilidad = "Solicitud originada por: [$nombreDeptoOrig].";
                }

                // Verificamos si es el departamento especial
                $deptoLower = mb_strtolower(trim($nombreDeptoOrig));
                $isDeptoEspecial = (
                    strpos($deptoLower, 'operacion') !== false || 
                    strpos($deptoLower, 'operación') !== false || 
                    strpos($deptoLower, 'compras') !== false ||
                    strpos($deptoLower, 'contaduría') !== false ||
                    strpos($deptoLower, 'contaduria') !== false
                );

                // 2. Buscar el ID_Dpto en la Razón Social seleccionada (Destino)
                if (!empty($razon_social_id)) {
                    if ($isDeptoEspecial && !empty($post['id_place'])) {
                        // Lógica especial para 'Operacion': Usar el Place seleccionado para determinar la Unidad Operativa
                        $idPlaceDestino = $post['id_place'];
                        // Buscamos la primera unidad operativa asociada a este Place (ya que la real se definirá por la partida)
                        $unidadDestino = $unidadOperativaModel->where('ID_Place', $idPlaceDestino)->where('activo', true)->first();
                        
                        if ($unidadDestino) {
                            $idUnidadFinal = $unidadDestino['ID_UnidadOperativa'];
                            // El departamento sigue siendo el de Operacion, no buscamos homónimo
                        }
                    } else {
                        // Lógica normal: Buscar homónimo
                        $deptoDestino = $departamentoModel
                            ->select('Departamentos.ID_Dpto, Departamentos.ID_UnidadOperativa')
                            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place')
                            ->where('Places.ID_RazonSocial', $razon_social_id)
                            ->where('Departamentos.Nombre', $nombreDeptoOrig)
                            ->first();

                        if ($deptoDestino) {
                            $idDptoFinal = $deptoDestino['ID_Dpto'];
                            $idUnidadFinal = $deptoDestino['ID_UnidadOperativa'];
                        }
                    }
                }
            }

            // 2.1 Sanitizar y normalizar unidad operativa final
            // Regla: si llega 0/no existe, intentamos resolver por departamento; si no, NULL.
            $idUnidadFinal = !empty($idUnidadFinal) ? (int) $idUnidadFinal : null;
            if ($idUnidadFinal !== null && $idUnidadFinal <= 0) {
                $idUnidadFinal = null;
            }

            if ($idUnidadFinal === null && !empty($idDptoFinal)) {
                $deptoResolucion = $departamentoModel->find((int) $idDptoFinal);
                $unidadDesdeDpto = $deptoResolucion['ID_UnidadOperativa'] ?? null;
                $idUnidadFinal = !empty($unidadDesdeDpto) ? (int) $unidadDesdeDpto : null;
            }

            if ($idUnidadFinal !== null) {
                $unidadValida = (new \App\Models\UnidadOperativaModel())->find($idUnidadFinal);
                if (!$unidadValida) {
                    $idUnidadFinal = null;
                }
            }

            // 3. Preparar comentarios finales con trazabilidad y responsabilidad
            $enviarDireccion = isset($post['enviar_direccion']) && $post['enviar_direccion'] == '1';
            $comentariosFinal = "";

            // La cláusula de responsabilidad solo se agrega si es envío a dirección
            if ($enviarDireccion) {
                $nombreUsuarioSesion = $user['Nombre'] ?? 'Usuario';
                $comentariosFinal .= "La responsabilidad del contenido de esta requisición recae en " . $nombreUsuarioSesion . ". ";
            }

            if ($etiquetaTrazabilidad) {
                $comentariosFinal .= $etiquetaTrazabilidad . " ";
            }

            if ($comentariosuser) {
                $comentariosFinal .= (!empty($comentariosFinal) ? "| " : "") . "Comentario: " . $comentariosuser;
            }

            $estadoInicial = Status::Aprobacion_pendiente;
            $metodoPagoFinal = MetodoPago::EnEspera;

            if ($enviarDireccion) {
                $estadoInicial = Status::En_Revision;
                $metodoPagoFinal = ($post['tipo_pago_dir'] ?? '') === 'credito' ? MetodoPago::Credito : MetodoPago::Efectivo;
            } else if (session('login_type') === 'boss') {
                if ($tipo == SolicitudTipo::Servicios) {
                    $estadoInicial = Status::Cotizando;
                } else {
                    $estadoInicial = Status::En_espera;
                }
            }

            $datosSolicitud = [
                'ID_Usuario' => $user['ID_Usuario'],
                'ID_Dpto' => $idDptoFinal,
                'ID_UnidadOperativa' => $idUnidadFinal,
                'ID_Proveedor' => $proveedor['ID_Proveedor'] ?? null,
                'ID_Cuenta' => $cuenta_id,
                'ID_RazonSocial' => $razon['ID_RazonSocial'] ?? null,
                'IVA' => isset($post['iva']) ? true : false,
                'Fecha' => $fecha,
                'Estado' => $estadoInicial,
                'No_Folio' => null,
                'Tipo' => $tipo,
                'ComentariosUser' => $comentariosFinal,
                'MetodoPago' => $metodoPagoFinal,
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
            $catalogoModel = new CatalogoProductosModel();

            if ($tipo == SolicitudTipo::Cotizacion || $tipo == SolicitudTipo::NoCotizacion) {
                $solicitudProduct = new SolicitudProductModel();

                for ($i = 0; $i < count($productos); $i++) {
                    $idCatalogo = is_numeric($productos[$i]) ? (int)$productos[$i] : null;
                    $nombreReal = $productos[$i];
                    
                    if ($idCatalogo) {
                        $prodCatalogo = $catalogoModel->find($idCatalogo);
                        if ($prodCatalogo) {
                            $nombreReal = $prodCatalogo['Nombre'];
                        }
                    }

                    $item = [
                        'ID_Solicitud' => $solicitudId,
                        'ID_CatalogoProd' => $idCatalogo,
                        'Codigo' => $codigos[$i] ?? ($idCatalogo ? (string)$idCatalogo : null),
                        'Nombre' => $nombreReal,
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
                    $idCatalogo = is_numeric($productos[$i]) ? (int)$productos[$i] : null;
                    $nombreReal = $productos[$i];

                    if ($idCatalogo) {
                        $prodCatalogo = $catalogoModel->find($idCatalogo);
                        if ($prodCatalogo) {
                            $nombreReal = $prodCatalogo['Nombre'];
                        }
                    }

                    $item = [
                        'ID_Solicitud' => $solicitudId,
                        'ID_CatalogoProd' => $idCatalogo,
                        'Nombre' => $nombreReal,
                        'Importe' => $importes[$i],
                        'ID_GrupoPresupuestal' => $grupos_presupuestales[$i] ?? null,
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

            $archivos = $this->request->getFiles();
            $nombresArchivos = [];
            $folder = FPath::FSOLICITUD . $fecha;
            $this->api->CreateFolder($folder);

            // 1. Procesar archivos normales (Se omiten si es envío directo a dirección para priorizar evidencia obligatoria)
            if (!$enviarDireccion && isset($archivos['archivo'])) {
                foreach ($archivos['archivo'] as $adjunto) {
                    if ($adjunto->isValid() && !$adjunto->hasMoved()) {
                        $nuevoNombre = 'solicitud_' . $solicitudId . '_' . $adjunto->getRandomName();
                        $adjunto->move($folder, $nuevoNombre);
                        $nombresArchivos[] = $nuevoNombre;
                    }
                }
            }

            // 2. Procesar evidencia de autorización externa
            if ($enviarDireccion && isset($archivos['archivo_evidencia'])) {
                foreach ($archivos['archivo_evidencia'] as $evidencia) {
                    if ($evidencia->isValid() && !$evidencia->hasMoved()) {
                        $nuevoNombreEvidencia = 'evidencia_' . $solicitudId . '_' . $evidencia->getRandomName();
                        $evidencia->move($folder, $nuevoNombreEvidencia);
                        $nombresArchivos[] = $nuevoNombreEvidencia;
                    }
                }
            }

            if (!empty($nombresArchivos)) {
                $solicitud->update($solicitudId, ['Archivo' => implode(',', $nombresArchivos)]);

                // Auditoría de subida de archivos
                \CodeIgniter\Events\Events::trigger('auditoria', [
                    'tipo_accion'  => 'SUBIR_ARCHIVOS_SOLICITUD',
                    'modulo'       => 'Solicitudes',
                    'solicitud_id' => $solicitudId,
                    'estado'       => 'exito',
                    'valores_nuevos' => json_encode(['archivos' => $nombresArchivos, 'cantidad' => count($nombresArchivos)])
                ]);
            }

            // 3. Procesar cotización de la requisición (para saltar paso)
            if ($enviarDireccion && isset($archivos['archivo_cotizacion'])) {
                $nombresCotizaciones = [];
                $folderCot = FPath::FCOTIZACION . $fecha;
                $this->api->CreateFolder($folderCot);

                foreach ($archivos['archivo_cotizacion'] as $cotArchivo) {
                    if ($cotArchivo->isValid() && !$cotArchivo->hasMoved()) {
                        $nuevoNombreCot = 'cotizacion_dir_' . $solicitudId . '_' . $cotArchivo->getRandomName();
                        $cotArchivo->move($folderCot, $nuevoNombreCot);
                        $nombresCotizaciones[] = $nuevoNombreCot;
                    }
                }

                if (!empty($nombresCotizaciones)) {
                    $cotizacionModel = new CotizacionModel();
                    $total = 0;
                    foreach ($datosProductos as $p) {
                        $cantidad = isset($p['Cantidad']) ? (float)$p['Cantidad'] : 1;
                        $importe = isset($p['Importe']) ? (float)$p['Importe'] : 0;
                        $total += ($cantidad * $importe);
                    }
                    if (isset($post['iva'])) {
                        $total *= 1.16;
                    }

                    $cotizacionModel->insert([
                        'ID_Solicitud' => $solicitudId,
                        'ID_Proveedor' => (int) ($proveedor_id ?? $post['ID_Proveedor']),
                        'Total' => $total,
                        'ID_Usuario_Cotiza' => $user['ID_Usuario'],
                        'Cotizacion_Files' => implode(',', $nombresCotizaciones)
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Solicitud registrada correctamente.',
                'id' => $solicitudId,
            ]);

        } catch (\Exception $e) {
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'FALLO_REGISTRO_SOLICITUD',
                'modulo'         => 'Solicitudes',
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode([
                    'error'   => $e->getMessage(),
                    'archivo' => $e->getFile(),
                    'linea'   => $e->getLine()
                ])
            ]);
            log_message('error', '[Archivo::subir] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al registrar la solicitud: ' . $e->getMessage(),
            ]);
        }
    }

    public function descargar($idSolicitud, $fileName = null)
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud || empty($solicitud['Archivo'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Archivo no encontrado para esta solicitud.',
            );
        }

        $archivos = explode(',', $solicitud['Archivo']);

        if ($fileName === null) {
            // Si no se especifica, tomamos el primero para mantener compatibilidad
            $fileName = $archivos[0];
        } else {
            // Verificamos que el archivo solicitado realmente pertenezca a la solicitud
            if (!in_array($fileName, $archivos)) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                    'El archivo solicitado no pertenece a esta solicitud.',
                );
            }
        }

        $filePath = WRITEPATH . 'uploads/solicitud/' . $solicitud['Fecha'] . '/' . $fileName;

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

        $safeDate = explode(' ', $solicitud['Fecha'])[0];
        $filePath = FPath::FCOTIZACION . $safeDate . DIRECTORY_SEPARATOR . $file;

        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'El archivo físico no existe en el servidor.',
            );
        }

        return $this->response->download($filePath, null);
    }
}
