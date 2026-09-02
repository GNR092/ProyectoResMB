<?php
namespace App\Libraries;
use App\Models\CotizacionModel;
use App\Models\CuentasModel;
use App\Models\DepartamentosModel;
use App\Models\GrupoPresupuestalModel;
use App\Models\OrdenCompraModel;
use App\Models\PagoModel;
use App\Models\PlacesModel;
use App\Models\ProductoModel;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\TokenModel;
use App\Models\CatalogoProductosModel;
use App\Models\UsuarioProductoFavoritoModel;
use App\Models\UsuariosModel;
use App\Libraries\HttpStatus;
use App\Libraries\ImageProcessor;
use App\Libraries\SolicitudTipo;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

/**
 * Clase Rest
 *
 * Proporciona métodos para interactuar con la base de datos y realizar operaciones relacionadas con usuarios, productos, proveedores, departamentos y otros.
 */
class Rest
{
    protected $db;
    /**
     * Constructor de la clase Rest.
     * Inicializa la conexión a la base de datos.
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    //region Tokens
    /**
     * Genera un nuevo token API para un usuario.
     *
     * @param int $userid El ID del usuario para el cual se generará el token.
     * @return bool True si el token se generó y guardó correctamente, false en caso contrario.
     */
    public function generateUserToken(int $userid): bool
    {
        $tokenmodel = new TokenModel();
        $tokenhash = $this->generatetoken($userid);

        if (!$tokenhash) {
            return false;
        }

        $usertoken = [
            'ID_Usuario' => $userid,
            'token' => $tokenhash,
        ];

        return $tokenmodel->insert($usertoken) !== false;
    }

    /**
     * Crea un hash de token único para un usuario.
     *
     * Elimina cualquier token existente del usuario antes de crear uno nuevo.
     *
     * @param int $userid El ID del usuario.
     * @return string El hash del token generado, o una cadena vacía si el usuario no existe.
     */
    public function generatetoken(int $userid): string
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userid);

        if (!$user) {
            return '';
        }

        $tokenmodel = new TokenModel();

        $tokenmodel->where('ID_Usuario', $userid)->delete();

        do {
            $tokenhash = bin2hex(random_bytes(32));
        } while ($tokenmodel->where('token', $tokenhash)->first());

        return $tokenhash;
    }

    /**
     * Actualiza el token de un usuario.
     *
     * @param int $userid El ID del usuario.
     * @param string|null $token El nuevo token a guardar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function updateToken(int $userid, ?string $token): bool
    {
        $tokenModel = new TokenModel();
        $tokenData = $tokenModel->where('ID_Usuario', $userid)->first();

        if (!$tokenData) {
            return false;
        }

        $dataToUpdate = [
            'token' => $token,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $tokenModel->update($tokenData['ID_Token'], $dataToUpdate);
    }
    /**
     * Borra un token API de un usuario.
     *
     * @param int $userId El ID del usuario cuyo token se eliminará.
     * @return bool True si el token se eliminó correctamente, false en caso contrario.
     */
    public function deleteToken(int $userId): bool
    {
        $tokenModel = new TokenModel();
        $tokenData = $tokenModel->where('ID_Usuario', $userId)->first();

        if (!$tokenData) {
            return false;
        }

        if ($tokenModel->delete($tokenData['ID_Token'])) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Obtiene todos los tokens de la base de datos.
     * @return array Un array con todos los tokens.
     */
    private function getTokens(): array
    {
        $tokenModel = new TokenModel();
        $tokens = $tokenModel->findall();
        return $tokens;
    }
    //endregion

    //region cotizacione
    /**
     * Obtiene una cotización por su ID.
     *
     * @param int $id El ID de la cotización.
     * @return array|null Los datos de la cotización o null si no se encuentra.
     */
    public function getCotizacionById(int $id): ?array
    {
        $cotizacionModel = new CotizacionModel();
        $result = $cotizacionModel->find($id);
        return $result ?: null;
    }

    /**
     * Obtiene una cotización por el ID de la solicitud.
     *
     * @param int $solicitudId El ID de la solicitud.
     * @return array|null La cotización encontrada o null si no se encuentra.
     */
    public function getCotizacionBySolicitudID(int $solicitudId): ?array
    {
        $cotizacionModel = new CotizacionModel();
        return $cotizacionModel->where('ID_Solicitud', $solicitudId)->first();
    }
    /**
     * Obtiene todas las cotizaciones.
     *
     * @return array Un array con todas las cotizaciones.
     */
    public function getCotizaciones(): array
    {
        $cotizacionModel = new CotizacionModel();
        $results = $cotizacionModel->findAll();
        return $results ?: [];
    }

    /**
     * Actualiza una cotización por su ID.
     *
     * @param int|null $id El ID de la cotización a actualizar.
     * @param array|null $row Los datos a actualizar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function updateCotizacionById(?int $id, ?array $row): bool
    {
        if ($id === null || $row === null) {
            return false;
        }
        $cotizacionModel = new CotizacionModel();
        return $cotizacionModel->update($id, $row);
    }
    //endregion

    //region solicitudes
    /**
     * Obtiene todas las solicitudes, excluyendo ciertos estados o filtrando por declinadas.
     *
     * @param bool $onlyDeclined Si es true, solo obtiene las rechazadas/canceladas.
     * @return array Un array de solicitudes con el nombre del departamento.
     */
    public function getAllSolicitud(bool $onlyDeclined = false)
    {
        // 1. Definir estados declinados
        $declined_statuses = [
            Status::Dept_Rechazada,
            Status::Rechazada,
            'Cancelada'
        ];

        // 2. Definir estados excluidos para la vista principal
        $excluded_statuses = array_merge($declined_statuses, [Status::Aprobacion_pendiente]);

        $solicitudModel = new \App\Models\SolicitudModel();
        $cotizacionModel = new \App\Models\CotizacionModel();
        $ordenCompraModel = new \App\Models\OrdenCompraModel();

        // ---------------------------------------------------------
        // PASO 1: Obtener Solicitudes (Datos Base)
        // ---------------------------------------------------------
        $builder = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre, Proveedor.RazonSocial as ProveedorNombre, Razon_Social.Nombre as Complejo')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left');

        if ($onlyDeclined) {
            $builder->whereIn('Solicitud.Estado', $declined_statuses);
        } else {
            $builder->whereNotIn('Solicitud.Estado', $excluded_statuses);
        }

        $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->limit(500)
            ->findAll();
        log_message('debug', print_r($solicitudes[0] ?? [], true));
        if (empty($solicitudes)) {
            return []; // Retornamos array vacío, no response
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        // ---------------------------------------------------------
        // PASO 2: Obtener Cotizaciones (Monto y Relación)
        // ---------------------------------------------------------
        // Traemos los totales calculados en PHP para evitar errores de SQL GroupBy
        $cotizaciones = $cotizacionModel
            ->select('ID_Cotizacion, ID_Solicitud, Total')
            ->whereIn('ID_Solicitud', $solicitudIds)
            ->findAll();

        $mapaMontos = [];
        $mapaRelacionCotizacion = [];
        $cotizacionIds = [];

        foreach ($cotizaciones as $cot) {
            $idSol = $cot['ID_Solicitud'];

            // Sumar montos en PHP (seguro y rápido)
            if (!isset($mapaMontos[$idSol])) {
                $mapaMontos[$idSol] = 0;
            }
            $mapaMontos[$idSol] += (float)$cot['Total'];

            // Guardamos referencia para buscar ordenes
            $mapaRelacionCotizacion[$idSol] = $cot['ID_Cotizacion'];
            $cotizacionIds[] = $cot['ID_Cotizacion'];
        }

        // ---------------------------------------------------------
        // PASO 3: Obtener Órdenes de Compra (Estados)
        // ---------------------------------------------------------
        $mapaOrdenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel
                ->select('ID_Cotizacion, Estado')
                ->whereIn('ID_Cotizacion', $cotizacionIds)
                ->findAll();

            foreach ($ordenes as $orden) {
                $mapaOrdenes[$orden['ID_Cotizacion']] = $orden['Estado'];
            }
        }

        // ---------------------------------------------------------
        // PASO 4: Unificar Datos
        // ---------------------------------------------------------
        foreach ($solicitudes as &$solicitud) {
            $idSol = $solicitud['ID_Solicitud'];

            // 1. Asignar Monto (Si no existe, es 0)
            $solicitud['MontoTotal'] = $mapaMontos[$idSol] ?? 0;

            // 2. Actualizar Estado basado en Orden de Compra
            if ($solicitud['Estado'] === \App\Libraries\Status::Aprobada) {
                if (isset($mapaRelacionCotizacion[$idSol])) {
                    $idCot = $mapaRelacionCotizacion[$idSol];
                    if (isset($mapaOrdenes[$idCot]) && !empty($mapaOrdenes[$idCot])) {
                        $solicitud['Estado'] = $mapaOrdenes[$idCot];
                    }
                }
            }
        }

        return $solicitudes; // <--- Retornamos el ARRAY limpio
    }

    /**
     * Obtiene todas las solicitudes de un departamento específico o de un usuario específico.
     *
     * @param int $id El ID del departamento.
     * @param int|null $userId El ID del usuario (opcional).
     * @param bool $onlyDeclined Si es true, solo obtiene las rechazadas/canceladas.
     * @return array Un array de solicitudes filtradas.
     */
    public function getSolicitudByDepartment(int $id, ?int $userId = null, bool $onlyDeclined = false)
    {
        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();
        $proveedorModel = new ProveedorModel();

        // 1. Definir estados declinados
        $declined_statuses = [
            Status::Dept_Rechazada,
            Status::Rechazada,
            'Cancelada'
        ];

        // 2. Definir estados excluidos para la vista principal
        $excluded_statuses = array_merge($declined_statuses, [Status::Aprobacion_pendiente]);

        $builder = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre, Razon_Social.Nombre as Complejo')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left');

        if ($userId) {
            $builder->groupStart()
                    ->where('Solicitud.ID_Dpto', $id)
                    ->orWhere('Solicitud.ID_Usuario', $userId)
                    ->groupEnd();
        } else {
            $builder->where('Solicitud.ID_Dpto', $id);
        }

        if ($onlyDeclined) {
            $builder->whereIn('Solicitud.Estado', $declined_statuses);
        } else {
            $builder->whereNotIn('Solicitud.Estado', $excluded_statuses);
        }

        $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->limit(500)
            ->findAll();
        log_message('debug', print_r($solicitudes[0] ?? [], true));
        if (empty($solicitudes)) {
            return [];
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        $cotizaciones = $cotizacionModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();

        $cotizacionIds = array_column($cotizaciones, 'ID_Cotizacion');

        $ordenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel->whereIn('ID_Cotizacion', $cotizacionIds)->findAll();
        }

        // --- NUEVO: Extraemos los IDs de los proveedores y los buscamos ---
        $proveedorIds = array_filter(array_column($cotizaciones, 'ID_Proveedor'));
        $proveedores = [];
        if (!empty($proveedorIds)) {
            $proveedores = $proveedorModel->whereIn('ID_Proveedor', $proveedorIds)->findAll();
        }

        $cotizacionesMap = [];
        foreach ($cotizaciones as $cot) {
            if (!isset($cotizacionesMap[$cot['ID_Solicitud']])) {
                $cotizacionesMap[$cot['ID_Solicitud']] = $cot;
            }
        }

        $ordenesMap = [];
        foreach ($ordenes as $orden) {
            $ordenesMap[$orden['ID_Cotizacion']] = $orden;
        }

        // --- NUEVO: Creamos el mapa de proveedores ---
        $proveedoresMap = [];
        foreach ($proveedores as $prov) {
            $proveedoresMap[$prov['ID_Proveedor']] = $prov;
        }

        foreach ($solicitudes as &$solicitud) {
            // Inicializamos las variables por defecto para evitar errores en JS
            $solicitud['ProveedorNombre'] = null;
            $solicitud['MontoTotal'] = 0;

            // Si la solicitud tiene una cotización
            if (isset($cotizacionesMap[$solicitud['ID_Solicitud']])) {
                $cotizacion = $cotizacionesMap[$solicitud['ID_Solicitud']];

                // 1. Asignamos el Monto Total
                $solicitud['MontoTotal'] = $cotizacion['Total'] ?? 0;

                // 2. Asignamos el Proveedor
                if (isset($proveedoresMap[$cotizacion['ID_Proveedor']])) {
                    $solicitud['ProveedorNombre'] = $proveedoresMap[$cotizacion['ID_Proveedor']]['RazonSocial'] ?? null;
                }

                // 3. Lógica original del Estado
                if ($solicitud['Estado'] === Status::Aprobada) {
                    if (isset($ordenesMap[$cotizacion['ID_Cotizacion']])) {
                        $orden = $ordenesMap[$cotizacion['ID_Cotizacion']];
                        if (!empty($orden['Estado'])) {
                            $solicitud['Estado'] = $orden['Estado'];
                        }
                    }
                }
            }
        }

        return $solicitudes;
    }

    /**
     * Obtiene solicitudes paginadas con filtros del lado del servidor.
     * Sin límite de registros, escala a miles de solicitudes.
     *
     * @param int      $page      Número de página (1-indexed).
     * @param int      $perPage   Registros por página.
     * @param array    $filters   Filtros: vista, estado, fecha, por_mes, folio, tipo, proveedores, razones_sociales, departamentos, dept_id, is_exception.
     * @param int|null $userId    ID del usuario para filtrar por sus solicitudes.
     * @return array Con 'data', 'total', 'page', 'perPage'.
     */
    public function getSolicitudPaginated(int $page = 1, int $perPage = 10, array $filters = [], ?int $userId = null): array
    {
        $solicitudModel = new \App\Models\SolicitudModel();
        $cotizacionModel = new \App\Models\CotizacionModel();
        $ordenCompraModel = new \App\Models\OrdenCompraModel();
        $proveedorModel = new \App\Models\ProveedorModel();

        $declinedStatuses = [Status::Dept_Rechazada, Status::Rechazada, 'Cancelada'];
        $excludedStatuses = array_merge($declinedStatuses, [Status::Aprobacion_pendiente]);
        $onlyDeclined = ($filters['vista'] ?? '') === 'declinadas';
        $deptId = $filters['dept_id'] ?? null;
        $isException = !empty($filters['is_exception']);

        $hasFechaProg = $this->db->fieldExists('FechaProgramacion', 'OrdenCompra');
        $selectFechaProg = $hasFechaProg ? 'OrdenCompra.FechaProgramacion as FechaProgramacion' : 'NULL as FechaProgramacion';
        $selectFields = 'Solicitud.ID_Solicitud, Solicitud.No_Folio, Solicitud.Fecha as FechaSolicitud, Solicitud.Fecha_Aprobacion, ' . $selectFechaProg . ', OrdenCompra.Fecha_Comprobante as FechaComprobante, OrdenCompra.FechaPagoRealizado, OrdenCompra.Fecha as FechaOrden, Solicitud.Estado, Solicitud.MetodoPago, Solicitud.Tipo, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre, Proveedor.RazonSocial as ProveedorNombre, Razon_Social.Nombre as Complejo';

        // JOINs base comunes (LEFT JOINs directos - funcionan igual en PG y MySQL)
        $baseJoins = function ($query) {
            $query->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                  ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                  ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
                  ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
                  ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
                  ->join('OrdenCompra', 'OrdenCompra.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left');
        };

        // Factoría de filtros: recibe el nombre de la columna OC estado según el join usado
        $makeApplyFilters = function (string $ocEstadoCol) use ($declinedStatuses, $excludedStatuses, $onlyDeclined, $filters, $deptId, $userId, $isException) {
            return function ($query) use ($declinedStatuses, $excludedStatuses, $onlyDeclined, $filters, $deptId, $userId, $isException, $ocEstadoCol) {
                $selectedEstado = $filters['estado'] ?? '';

                if ($onlyDeclined) {
                    if (!empty($selectedEstado) && in_array($selectedEstado, $declinedStatuses)) {
                    } else {
                        $query->whereIn('Solicitud.Estado', $declinedStatuses);
                    }
                } else {
                    $currentExclusions = $excludedStatuses;
                    if (!empty($selectedEstado)) {
                        $currentExclusions = array_diff($currentExclusions, [$selectedEstado]);
                    }
                    if (!empty($currentExclusions)) {
                        $query->whereNotIn('Solicitud.Estado', $currentExclusions);
                    }
                }

                if ($deptId && $userId && !$isException) {
                    $query->groupStart()
                          ->where('Solicitud.ID_Dpto', $deptId)
                          ->orWhere('Solicitud.ID_Usuario', $userId)
                          ->groupEnd();
                } elseif ($deptId && !$isException) {
                    $query->where('Solicitud.ID_Dpto', $deptId);
                }

                if (!empty($filters['estado'])) {
                    $orderStatuses = [
                        Status::Espera_Programacion,
                        Status::Programada,
                        Status::Por_Pagar,
                        Status::Pagada
                    ];

                    if (in_array($filters['estado'], $orderStatuses)) {
                        $query->where('Solicitud.Estado', Status::Aprobada);
                        $query->where($ocEstadoCol, $filters['estado']);
                    } elseif ($filters['estado'] === Status::Aprobada) {
                        $query->where('Solicitud.Estado', Status::Aprobada);
                        $query->groupStart()
                              ->where($ocEstadoCol . ' IS NULL')
                              ->orWhere($ocEstadoCol, '')
                              ->groupEnd();
                    } else {
                        $query->where('Solicitud.Estado', $filters['estado']);
                    }
                }

                if (!empty($filters['fecha'])) {
                    if (!empty($filters['por_mes'])) {
                        $mes = substr($filters['fecha'], 0, 7);
                        $inicio = $mes . '-01';
                        $fin = date('Y-m-t', strtotime($inicio));
                        $query->where('Solicitud.Fecha >=', $inicio)
                              ->where('Solicitud.Fecha <=', $fin);
                    } else {
                        $query->where('Solicitud.Fecha', $filters['fecha']);
                    }
                }

                if (!empty($filters['folio'])) {
                    $query->like('Solicitud.No_Folio', $filters['folio']);
                }

                if (!empty($filters['tipo'])) {
                    if ($filters['tipo'] === 'Producto') {
                        $query->whereIn('Solicitud.Tipo', [0, 1]);
                    } elseif ($filters['tipo'] === 'Servicio') {
                        $query->where('Solicitud.Tipo', 2);
                    }
                }

                if (isset($filters['metodo']) && $filters['metodo'] !== '') {
                    $query->where('Solicitud.MetodoPago', $filters['metodo']);
                }

                if (!empty($filters['proveedores'])) {
                    $provs = is_array($filters['proveedores']) ? $filters['proveedores'] : explode(',', $filters['proveedores']);
                    $provs = array_filter(array_map('trim', $provs));
                    if (!empty($provs)) {
                        $query->whereIn('Proveedor.RazonSocial', $provs);
                    }
                }

                if (!empty($filters['razones_sociales'])) {
                    $razones = is_array($filters['razones_sociales']) ? $filters['razones_sociales'] : explode(',', $filters['razones_sociales']);
                    $razones = array_filter(array_map('trim', $razones));
                    if (!empty($razones)) {
                        $query->whereIn('Razon_Social.Nombre', $razones);
                    }
                }

                if (!empty($filters['departamentos'])) {
                    $deptos = is_array($filters['departamentos']) ? $filters['departamentos'] : explode(',', $filters['departamentos']);
                    $deptos = array_filter(array_map('trim', $deptos));
                    if (!empty($deptos)) {
                        $query->groupStart();
                        $first = true;
                        foreach ($deptos as $dpto) {
                            $parts = explode('|', $dpto);
                            $nombre = trim($parts[0]);
                            $place = isset($parts[1]) ? trim($parts[1]) : '';
                            if ($first) {
                                $query->where('Departamentos.Nombre', $nombre);
                                if ($place !== '') {
                                    $query->where('Places.Nombre_Corto', $place);
                                }
                                $first = false;
                            } else {
                                $query->orGroupStart()->where('Departamentos.Nombre', $nombre);
                                if ($place !== '') {
                                    $query->where('Places.Nombre_Corto', $place);
                                }
                                $query->groupEnd();
                            }
                        }
                        $query->groupEnd();
                    }
                }
            };
        };

        // COUNT query: LEFT JOINs + WHERE para replicar semántica INNER JOIN (solo solicitudes con Cotización Y OrdenCompra)
        // Compatible PostgreSQL + MySQL: sin subquery, solo condiciones WHERE
        $applyFiltersCount = $makeApplyFilters('OrdenCompra.Estado');
        $countBuilder = $solicitudModel->select('COUNT(*) as total');
        $baseJoins($countBuilder);
        $applyFiltersCount($countBuilder);
        // Replicar INNER JOIN semántica: solo contar si EXISTE Cotizacion Y OrdenCompra
        $countBuilder->where('Cotizacion.ID_Cotizacion IS NOT NULL')
                     ->where('OrdenCompra.ID_OrdenCompra IS NOT NULL');
        $total = (int) $countBuilder->get()->getRow()->total;

        // DATA query: LEFT JOINs directos (acceso a campos Cotización, incluye solicitudes sin OC)
        $applyFiltersData = $makeApplyFilters('OrdenCompra.Estado');
        $builder = $solicitudModel->select($selectFields);
        $baseJoins($builder);
        $applyFiltersData($builder);

        $offset = ($page - 1) * $perPage;
        $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->findAll();

        if (empty($solicitudes)) {
            return ['data' => [], 'total' => $total, 'page' => $page, 'perPage' => $perPage];
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        $cotizaciones = $cotizacionModel
            ->select('ID_Cotizacion, ID_Solicitud, Total, ID_Proveedor')
            ->whereIn('ID_Solicitud', $solicitudIds)
            ->findAll();

        $mapaMontos = [];
        $mapaRelacion = [];
        $cotizacionIds = [];
        $proveedorIds = [];

        foreach ($cotizaciones as $cot) {
            $idSol = $cot['ID_Solicitud'];
            if (!isset($mapaMontos[$idSol])) {
                $mapaMontos[$idSol] = 0;
            }
            $mapaMontos[$idSol] += (float) $cot['Total'];
            $mapaRelacion[$idSol] = $cot['ID_Cotizacion'];
            $cotizacionIds[] = $cot['ID_Cotizacion'];
            if (!empty($cot['ID_Proveedor'])) {
                $proveedorIds[] = $cot['ID_Proveedor'];
            }
        }

        $proveedoresMap = [];
        if (!empty($proveedorIds)) {
            $proveedores = $proveedorModel->whereIn('ID_Proveedor', array_unique($proveedorIds))->findAll();
            foreach ($proveedores as $p) {
                $proveedoresMap[$p['ID_Proveedor']] = $p['RazonSocial'];
            }
        }

        $mapaOrdenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel
                ->select('ID_Cotizacion, Estado')
                ->whereIn('ID_Cotizacion', $cotizacionIds)
                ->findAll();
            foreach ($ordenes as $orden) {
                $mapaOrdenes[$orden['ID_Cotizacion']] = $orden['Estado'];
            }
        }

        $data = [];
        foreach ($solicitudes as $sol) {
            $idSol = $sol['ID_Solicitud'];
            $sol['MontoTotal'] = $mapaMontos[$idSol] ?? 0;

            if ($sol['Estado'] === Status::Aprobada && isset($mapaRelacion[$idSol])) {
                $idCot = $mapaRelacion[$idSol];
                if (isset($mapaOrdenes[$idCot]) && !empty($mapaOrdenes[$idCot])) {
                    $sol['Estado'] = $mapaOrdenes[$idCot];
                }
            }

            $data[] = $sol;
        }

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Obtiene una solicitud por su ID.
     *
     * @param int $id El ID de la solicitud.
     * @return array|null La solicitud encontrada o null si no se encuentra.
     */
    public function getSolicitudById(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        return $solicitudModel->find($id) ?: null;
    }

    /**
     * Actualiza una solicitud por su ID.
     *
     * @param int|null $id El ID de la solicitud a actualizar.
     * @param array|null $row Los datos a actualizar.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function updateSolicitudById(?int $id, ?array $row): bool
    {
        if ($id === null || $row === null) {
            return false;
        }
        $solicitudModel = new SolicitudModel();
        return $solicitudModel->update($id, $row);
    }

    /**
     * Obtiene una solicitud específica con todos sus productos y detalles asociados.
     *
     * Realiza un control de acceso opcional para restringir los resultados a un usuario
     * o departamento específico.
     *
     * @param int      $id         El ID de la solicitud a obtener.
     * @return array|null Un array con los datos de la solicitud y sus productos,
     *                    o null si la solicitud no se encuentra o el acceso es denegado.
     */
    public function getSolicitudWithProducts(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Departamentos.ID_UnidadOperativa as DeptoUnidadOperativa',
                'Proveedor.RazonSocial as RazonSocialNombre',
                'Razon_Social.Nombre as Complejo',
                'Places.ID_Place as DeptoPlaceId',
                'Places.Nombre_Corto as PlaceNombre',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }

        // --- CORRECCIÓN CRÍTICA: Obtener el ID_Place real del destino (Unidad Operativa) ---
        // Prioridad 1: Desde la Unidad Operativa asignada a la solicitud
        if (!empty($solicitud['ID_UnidadOperativa'])) {
            $unidadDestino = (new \App\Models\UnidadOperativaModel())->find($solicitud['ID_UnidadOperativa']);
            if ($unidadDestino && !empty($unidadDestino['ID_Place'])) {
                $solicitud['ID_Place'] = $unidadDestino['ID_Place'];
            }
        }

        // Fallback para ID_Place si no se obtuvo del destino, usamos el del departamento del solicitante
        if (empty($solicitud['ID_Place']) && !empty($solicitud['DeptoPlaceId'])) {
            $solicitud['ID_Place'] = $solicitud['DeptoPlaceId'];
        }

        // Cargar nombre del Place si se encontró un ID (para consistencia en UI)
        if (!empty($solicitud['ID_Place'])) {
            $place = (new PlacesModel())->find($solicitud['ID_Place']);
            if ($place) {
                $solicitud['PlaceNombre'] = $place['Nombre_Corto'];
            }
        }

        if (!empty($solicitud['ID_Cuenta'])) {
            $cuentasModel = new CuentasModel();
            $solicitud['cuenta_details'] = $cuentasModel->find($solicitud['ID_Cuenta']);
        }

        $razonSocialTmp = !empty($solicitud['ID_RazonSocial']) ? $razonSocialModel->find($solicitud['ID_RazonSocial']) : null;
        $solicitud['ComplejoRFC'] = $razonSocialTmp ? $razonSocialTmp['RFC'] : 'N/A';

        // Obtener grupos presupuestales asociados a la Unidad Operativa de la solicitud (Prioridad destino)
        $idUnidad = !empty($solicitud['ID_UnidadOperativa']) ? $solicitud['ID_UnidadOperativa'] : ($solicitud['DeptoUnidadOperativa'] ?? 0);
        $grupoModel = new GrupoPresupuestalModel();

        // Detección del departamento especial (Operacion o variantes)
        $nombreDepto = $solicitud['DepartamentoNombre'] ?? '';
        $deptoLower = mb_strtolower(trim($nombreDepto));
        $isDeptoEspecial = (
            strpos($deptoLower, 'operacion') !== false || 
            strpos($deptoLower, 'operación') !== false || 
            strpos($deptoLower, 'compras') !== false ||
            strpos($deptoLower, 'contaduría') !== false ||
            strpos($deptoLower, 'contaduria') !== false
        );

        if ($isDeptoEspecial) {
            // Si es el departamento especial, enviamos TODAS las partidas activas
            // Unimos con UnidadOperativa para obtener el ID_Place
            $solicitud['grupos_presupuestales'] = $grupoModel
                ->select('GrupoPresupuestal.*, UnidadOperativa.ID_Place')
                ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = GrupoPresupuestal.ID_UnidadOperativa', 'left')
                ->where('GrupoPresupuestal.activo', true)
                ->where('GrupoPresupuestal.es_manual', false)
                ->orderBy('GrupoPresupuestal.Nombre', 'ASC')
                ->findAll();
        } else {
            // Lógica normal: filtrado por Complejo (Place)
            // Esto asegura que todos los grupos del mismo lugar estén disponibles, evitando pérdidas por cambios de unidad.
            $builder = $grupoModel
                ->select('GrupoPresupuestal.*, UnidadOperativa.ID_Place')
                ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = GrupoPresupuestal.ID_UnidadOperativa', 'left')
                ->where('GrupoPresupuestal.activo', true)
                ->where('GrupoPresupuestal.es_manual', false);

            if (!empty($solicitud['ID_Place'])) {
                // Filtramos por el complejo (Place) para dar flexibilidad en la edición
                $builder->where('UnidadOperativa.ID_Place', $solicitud['ID_Place']);
            } else {
                // Si no hay lugar definido (raro), intentamos por unidad si existe
                $idUnidad = !empty($solicitud['ID_UnidadOperativa']) ? $solicitud['ID_UnidadOperativa'] : ($solicitud['DeptoUnidadOperativa'] ?? 0);
                if (!empty($idUnidad)) {
                    $builder->where('GrupoPresupuestal.ID_UnidadOperativa', $idUnidad);
                } else {
                    // Si no hay nada, forzamos un resultado vacío para evitar traer todo por error
                    $builder->where('1 = 0', null, false);
                }
            }

            $solicitud['grupos_presupuestales'] = $builder->orderBy('GrupoPresupuestal.Nombre', 'ASC')->findAll();
        }

        $productos = [];

        if (
            $solicitud['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitud['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel
                ->select('Solicitud_Producto.*, GrupoPresupuestal.Nombre as GrupoPresupuestalNombre, Catalogo_Productos.ID_GrupoPresupuestal as ID_GrupoSugerido')
                ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Solicitud_Producto.ID_GrupoPresupuestal', 'left')
                ->join('Catalogo_Productos', 'Catalogo_Productos.ID_CatalogoProd = Solicitud_Producto.ID_CatalogoProd', 'left')
                ->where('ID_Solicitud', $id)
                ->findAll();
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $productos = $solicitudServicioModel
                ->select('Solicitud_Servicios.*, GrupoPresupuestal.Nombre as GrupoPresupuestalNombre, Catalogo_Productos.ID_GrupoPresupuestal as ID_GrupoSugerido')
                ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Solicitud_Servicios.ID_GrupoPresupuestal', 'left')
                ->join('Catalogo_Productos', 'Catalogo_Productos.ID_CatalogoProd = Solicitud_Servicios.ID_CatalogoProd', 'left')
                ->where('ID_Solicitud', $id)
                ->findAll();
        }

        // FIX: Asegurar que la partida asignada a cada producto siempre aparezca
        // en grupos_presupuestales, aunque no pase el filtro de Place/es_manual
        if (!empty($solicitud['grupos_presupuestales']) && !empty($productos)) {
            $gruposIds = array_column($solicitud['grupos_presupuestales'], 'ID_GrupoPresupuestal');
            foreach ($productos as $p) {
                $idGrupo = $p['ID_GrupoPresupuestal'] ?? null;
                if ($idGrupo && !in_array($idGrupo, $gruposIds)) {
                    $grupoFaltante = $grupoModel
                        ->select('GrupoPresupuestal.*, UnidadOperativa.ID_Place')
                        ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = GrupoPresupuestal.ID_UnidadOperativa', 'left')
                        ->where('GrupoPresupuestal.ID_GrupoPresupuestal', $idGrupo)
                        ->first();
                    if ($grupoFaltante) {
                        $solicitud['grupos_presupuestales'][] = $grupoFaltante;
                        $gruposIds[] = $idGrupo;
                    }
                }
            }
        }

        $ivaValue = $solicitud['IVA'] ?? false;
        $ivaHabilitado = ($ivaValue === 't' || $ivaValue === '1' || $ivaValue === 1 || $ivaValue === true);
        $factorIVA = $ivaHabilitado ? 1.16 : 1.0;

        // --- LÓGICA DE PRESUPUESTO MULTI-GRUPO PARA DICTAMEN ---
        $impactoPorGrupo = [];
        foreach ($productos as $p) {
            if (!empty($p['ID_GrupoPresupuestal'])) {
                $idG = $p['ID_GrupoPresupuestal'];
                if (!isset($impactoPorGrupo[$idG])) {
                    $impactoPorGrupo[$idG] = [
                        'ID_GrupoPresupuestal' => $idG,
                        'Nombre' => $p['GrupoPresupuestalNombre'] ?? 'S/N',
                        'MontoImpacto' => 0
                    ];
                }
                // Sumamos el importe del producto/servicio al impacto de este grupo (incluyendo IVA y Cantidad)
                // Para servicios, la cantidad suele ser 1 si no está definida
                $cantidad = (float)($p['Cantidad'] ?? 1);
                $montoItem = $cantidad * (float)($p['Importe'] ?? 0) * $factorIVA;
                $impactoPorGrupo[$idG]['MontoImpacto'] += $montoItem;
            }
        }

        if (!empty($impactoPorGrupo)) {
            $presupuestoMensualModel = new \App\Models\PresupuestoMensualModel();
            $fechaSolicitud = $solicitud['Fecha'] ?? date('Y-m-d');
            $anio = date('Y', strtotime($fechaSolicitud));
            $mes = date('n', strtotime($fechaSolicitud));

            $solicitud['presupuestos_detallados'] = [];

            foreach ($impactoPorGrupo as $idG => $info) {
                $presupuesto = $presupuestoMensualModel
                    ->where('ID_GrupoPresupuestal', $idG)
                    ->where('Anio', $anio)
                    ->where('Mes', $mes)
                    ->first();
                
                if ($presupuesto) {
                    $presupuesto['GrupoNombre'] = $info['Nombre'];
                    $presupuesto['ImpactoActual'] = $info['MontoImpacto'];
                    $presupuesto['SinPresupuesto'] = false;
                    $solicitud['presupuestos_detallados'][] = $presupuesto;
                } else {
                    // Si no hay presupuesto asignado, devolvemos un objeto marcado
                    $solicitud['presupuestos_detallados'][] = [
                        'ID_GrupoPresupuestal' => $idG,
                        'GrupoNombre' => $info['Nombre'],
                        'ImpactoActual' => $info['MontoImpacto'],
                        'SinPresupuesto' => true,
                        'Monto_Asignado' => 0,
                        'Monto_Comprometido' => 0,
                        'Monto_Ejecutado' => 0
                    ];
                }
            }
        }
        // -------------------------------------------------------

        $solicitud['productos'] = $productos;

        $cotizacionModel = new CotizacionModel();
        $cotizaciones = $cotizacionModel
            ->select('Cotizacion.*, Proveedor.RazonSocial as ProveedorNombre')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('ID_Solicitud', $id)
            ->findAll();

        if (!empty($cotizaciones)) {
            $solicitud['cotizaciones'] = $cotizaciones;
            $solicitud['cotizacion'] = $cotizaciones[0];

            $ordenCompraModel = new OrdenCompraModel();
            $orden = $ordenCompraModel
                ->where('ID_Cotizacion', $cotizaciones[0]['ID_Cotizacion'])
                ->first();

            if ($orden) {
                $solicitud['EstadoOrden'] = $orden['Estado'];
                $solicitud['OrdenCompra'] = $orden; // <--- ¡ESTA ES LA LÍNEA MÁGICA QUE FALTA!
            }
        }

        return $solicitud ? $solicitud : [];
    }

    /**
     * Obtiene una solicitud específica con todos sus servicios y detalles asociados.
     * Es similar a getSolicitudWithProducts, pero enfocado en servicios.
     *
     * @param int      $id         El ID de la solicitud a obtener.
     * @return array|null Un array con los datos de la solicitud y sus servicios,
     *                    o null si la solicitud no se encuentra.
     */
    public function getSolicitudWithServiceDetails(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $proveedorModel = new ProveedorModel();

        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Proveedor.RazonSocial as ProveedorRazonSocial',
                'Proveedor.RFC as ProveedorRFC',
                // 'Proveedor.Direccion as ProveedorDireccion',
                // 'Proveedor.MetodoPago as ProveedorMetodoPago', // Para forma de pago
                'Razon_Social.Nombre as Complejo',
                'Solicitud.Fecha_Aprobacion', // Necesario para la fecha de la factura
                'Places.ID_Place',
                'Places.Nombre_Corto as PlaceNombre',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }

        $razonSocialTmp = !empty($solicitud['ID_RazonSocial']) ? $razonSocialModel->find($solicitud['ID_RazonSocial']) : null;
        $solicitud['ComplejoRFC'] = $razonSocialTmp ? $razonSocialTmp['RFC'] : 'N/A';
        $solicitud['UsuarioRazon'] = $razonSocialModel->find($solicitud['ID_RazonSocial']); // Detalles completos de la razón social del complejo

        $solicitudServicioModel = new SolicitudServiciosModel();
        $servicios = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
        $solicitud['servicios'] = $servicios;

        // Si hay un proveedor asignado a la solicitud (para el servicio)
        if (!empty($solicitud['ID_Proveedor'])) {
            $solicitud['Proveedor'] = $proveedorModel->find($solicitud['ID_Proveedor']);
        }

        return $solicitud ? $solicitud : [];
    }
    /**
     * Obtiene una solicitud específica con su cotización y detalles asociados.
     *
     * @param int $id El ID de la solicitud a obtener.
     * @return array|null Un array con los datos de la solicitud, sus productos y su cotización,
     *                    o null si la solicitud no se encuentra.
     */
    public function getSolicitudWithCotizacion(int $id): ?array
    {
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'Proveedor.RazonSocial as RazonSocialNombre',
                'Razon_Social.Nombre as Complejo',
                'Solicitud.Fecha_Aprobacion',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }

        $depto = $this->getDepartmentById($solicitud['ID_Dpto'] ?? 0);
        $idPlace = null;

        if ($depto) {
            if (!empty($depto['ID_Place'])) {
                $idPlace = $depto['ID_Place'];
            } elseif (!empty($depto['ID_UnidadOperativa'])) {
                $uniModel = new \App\Models\UnidadOperativaModel();
                $unidad = $uniModel->find($depto['ID_UnidadOperativa']);
                $idPlace = $unidad['ID_Place'] ?? null;
            }
        }

        $place = $idPlace ? $placesModel->find($idPlace) : null;
        $solicitud['ID_Place'] = $idPlace ?? 0;
        $solicitud['PlaceNombre'] = $place['Nombre_Corto'] ?? 'N/A';
        
        $razonSocial = $razonSocialModel->find($solicitud['ID_RazonSocial'] ?? 0);
        $solicitud['ComplejoRFC'] = $razonSocial['RFC'] ?? 'N/A';

        $productos = [];

        if (
            $solicitud['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitud['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel
                ->select('Solicitud_Producto.*, GrupoPresupuestal.Nombre as GrupoPresupuestalNombre, Catalogo_Productos.ID_GrupoPresupuestal as ID_GrupoSugerido')
                ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Solicitud_Producto.ID_GrupoPresupuestal', 'left')
                ->join('Catalogo_Productos', 'Catalogo_Productos.ID_CatalogoProd = Solicitud_Producto.ID_CatalogoProd', 'left')
                ->where('ID_Solicitud', $id)
                ->findAll();
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $productos = $solicitudServicioModel
                ->select('Solicitud_Servicios.*, GrupoPresupuestal.Nombre as GrupoPresupuestalNombre, Catalogo_Productos.ID_GrupoPresupuestal as ID_GrupoSugerido')
                ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Solicitud_Servicios.ID_GrupoPresupuestal', 'left')
                ->join('Catalogo_Productos', 'Catalogo_Productos.ID_CatalogoProd = Solicitud_Servicios.ID_CatalogoProd', 'left')
                ->where('ID_Solicitud', $id)
                ->findAll();
        }

        $ivaValue = $solicitud['IVA'] ?? false;
        $ivaHabilitado = ($ivaValue === 't' || $ivaValue === '1' || $ivaValue === 1 || $ivaValue === true);
        $factorIVA = $ivaHabilitado ? 1.16 : 1.0;

        // --- LÓGICA DE PRESUPUESTO MULTI-GRUPO PARA DICTAMEN ---
        $impactoPorGrupo = [];
        foreach ($productos as $p) {
            if (!empty($p['ID_GrupoPresupuestal'])) {
                $idG = $p['ID_GrupoPresupuestal'];
                if (!isset($impactoPorGrupo[$idG])) {
                    $impactoPorGrupo[$idG] = [
                        'ID_GrupoPresupuestal' => $idG,
                        'Nombre' => $p['GrupoPresupuestalNombre'] ?? 'S/N',
                        'MontoImpacto' => 0
                    ];
                }
                // Sumamos el importe del producto/servicio al impacto de este grupo (incluyendo IVA y Cantidad)
                // Para servicios, la cantidad suele ser 1 si no está definida
                $cantidad = (float)($p['Cantidad'] ?? 1);
                $montoItem = $cantidad * (float)($p['Importe'] ?? 0) * $factorIVA;
                $impactoPorGrupo[$idG]['MontoImpacto'] += $montoItem;
            }
        }

        if (!empty($impactoPorGrupo)) {
            $presupuestoMensualModel = new \App\Models\PresupuestoMensualModel();
            $fechaSolicitud = $solicitud['Fecha'] ?? date('Y-m-d');
            $anio = date('Y', strtotime($fechaSolicitud));
            $mes = date('n', strtotime($fechaSolicitud));

            $solicitud['presupuestos_detallados'] = [];

            foreach ($impactoPorGrupo as $idG => $info) {
                $presupuesto = $presupuestoMensualModel
                    ->where('ID_GrupoPresupuestal', $idG)
                    ->where('Anio', $anio)
                    ->where('Mes', $mes)
                    ->first();
                
                if ($presupuesto) {
                    $presupuesto['GrupoNombre'] = $info['Nombre'];
                    $presupuesto['ImpactoActual'] = $info['MontoImpacto'];
                    $presupuesto['SinPresupuesto'] = false;
                    $solicitud['presupuestos_detallados'][] = $presupuesto;
                } else {
                    // Si no hay presupuesto asignado, devolvemos un objeto marcado
                    $solicitud['presupuestos_detallados'][] = [
                        'ID_GrupoPresupuestal' => $idG,
                        'GrupoNombre' => $info['Nombre'],
                        'ImpactoActual' => $info['MontoImpacto'],
                        'SinPresupuesto' => true,
                        'Monto_Asignado' => 0,
                        'Monto_Comprometido' => 0,
                        'Monto_Ejecutado' => 0
                    ];
                }
            }
        }
        // -------------------------------------------------------

        $solicitud['productos'] = $productos;

        $cotizacionModel = new CotizacionModel();
        $cotizacion = $cotizacionModel
            ->select(
                'Cotizacion.*, Cotizacion.Cotizacion_Files, Proveedor.RazonSocial as ProveedorNombre',
            )
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('ID_Solicitud', $id)
            ->first();

        if ($cotizacion) {
            $solicitud['cotizacion'] = $cotizacion;
        }

        return $solicitud ? $solicitud : [];
    }

    /**
     * Obtiene una orden de compra específica con todos sus detalles asociados.
     *
     * @param int $id El ID de la solicitud para la que se genera la orden de compra.
     * @return array|null Un array con los datos de la orden de compra, incluyendo el EstadoOrden,
     *                    o null si la solicitud no se encuentra.
     */
    public function getOrdenCompra(int $id): ?array
    {
        log_message('debug', 'Iniciando getOrdenCompra para Solicitud ID: ' . $id);
        $solicitudModel = new SolicitudModel();
        $razonSocialModel = new RazonSocialModel();
        $cuentasModel = new CuentasModel();
        $proveedorModel = new ProveedorModel();

        $solicitud = $solicitudModel
            ->select([
                'Solicitud.*',
                'Usuarios.Nombre as UsuarioNombre',
                'Departamentos.Nombre as DepartamentoNombre',
                'RS.Nombre as Complejo',
                'Solicitud.TipoComentarioAdmin',
                // CAMPOS CLAVE AGREGADOS:
                'OC.ID_OrdenCompra',
                'OC.Estado as EstadoOrden',
                'OC.Fecha as FechaOrden',
                'OC.FechaRefPago',
                'OC.FechaPagoRealizado',
                'UsuarioCotiza.Nombre as UsuarioCotizaNombre',
                'UsuarioAutoriza.Nombre as UsuarioAutorizaNombre',
                'Places.Nombre_Corto as ID_Place',
                // Cotizacion
                'Cotizacion.ID_Cotizacion',
                'Cotizacion.Total as CotizacionTotal',
                'Cotizacion.ID_Proveedor as CotizacionIDProveedor',
                'Cotizacion.Cotizacion_Files',
                'Prov.RazonSocial as ProveedorNombreCotizacion',
                // Archivos Orden
                'OC.File_Factura',
                'OC.File_Comprobante',
                'OC.File_Complemento',
                'OC.File_ReqPag',
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Razon_Social RS', 'RS.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('OrdenCompra OC', 'OC.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left')
            ->join('Proveedor Prov', 'Prov.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->join('Usuarios UsuarioCotiza', 'UsuarioCotiza.ID_Usuario = Cotizacion.ID_Usuario_Cotiza', 'left')
            ->join('Usuarios UsuarioAutoriza', 'UsuarioAutoriza.ID_Usuario = Solicitud.ID_Usuario_Autoriza', 'left')
            ->find($id);

        if (!$solicitud) {
            return null;
        }

        $solicitudData = $solicitud;

        // Detalles adicionales
        if (!empty($solicitudData['ID_Cuenta'])) {
            $solicitudData['cuenta_details'] = $cuentasModel->find($solicitudData['ID_Cuenta']);
        }

        if (!empty($solicitudData['ID_Proveedor'])) {
            $proveedor = $proveedorModel->find($solicitudData['ID_Proveedor']);
            if ($proveedor) {
                unset($proveedor['Correo']);
                unset($proveedor['Tel_Contacto']);
            }
            $solicitudData['proveedor'] = $proveedor;
        }

        $razonSocial = !empty($solicitudData['ID_RazonSocial']) ? $razonSocialModel->find($solicitudData['ID_RazonSocial']) : null;
        $solicitudData['ComplejoRFC'] = $razonSocial ? $razonSocial['RFC'] : 'N/A';

        // Productos
        $productos = [];
        if (
            $solicitudData['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitudData['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel
                ->select('Solicitud_Producto.*, GrupoPresupuestal.Nombre as GrupoPresupuestalNombre')
                ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Solicitud_Producto.ID_GrupoPresupuestal', 'left')
                ->where('ID_Solicitud', $id)
                ->findAll();
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $productos = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
        }
        $solicitudData['productos'] = $productos;

        // Cotización
        if (!empty($solicitudData['ID_Cotizacion'])) {
            $solicitudData['cotizacion'] = [
                'ID_Cotizacion' => $solicitudData['ID_Cotizacion'],
                'Total' => $solicitudData['CotizacionTotal'],
                'ID_Proveedor' => $solicitudData['CotizacionIDProveedor'],
                'ProveedorNombre' => $solicitudData['ProveedorNombreCotizacion'],
                'Cotizacion_Files' => $solicitudData['Cotizacion_Files'],
            ];
            unset($solicitudData['CotizacionTotal']);
            unset($solicitudData['CotizacionIDProveedor']);
            unset($solicitudData['ProveedorNombreCotizacion']);
            unset($solicitudData['Cotizacion_Files']);
        }

        // --- CORRECCIÓN FINAL ---
        // Si existe ID de Orden o Estado de Orden, construimos el objeto.
        if (!empty($solicitudData['ID_OrdenCompra']) || !empty($solicitudData['EstadoOrden'])) {
            $solicitudData['OrdenCompra'] = [
                'ID_OrdenCompra'   => $solicitudData['ID_OrdenCompra'] ?? null,
                'Estado'           => $solicitudData['EstadoOrden'] ?? 'Generada',
                'Fecha'            => $solicitudData['FechaOrden'] ?? null,
                'FechaRefPago'       => $solicitudData['FechaRefPago'] ?? null,
                'FechaPagoRealizado' => $solicitudData['FechaPagoRealizado'] ?? null,
                'File_Factura'     => $solicitudData['File_Factura'],
                'File_Comprobante' => $solicitudData['File_Comprobante'],
                'File_Complemento' => $solicitudData['File_Complemento'] ?? null,
                'File_ReqPag'      => $solicitudData['File_ReqPag'],
            ];

            unset($solicitudData['ID_OrdenCompra']);
            unset($solicitudData['EstadoOrden']);
            unset($solicitudData['FechaOrden']);
            unset($solicitudData['File_Factura']);
            unset($solicitudData['File_Comprobante']);
            unset($solicitudData['File_Complemento']);
            unset($solicitudData['File_ReqPag']);
        }

        log_message('debug', 'Finalizando getOrdenCompra con éxito.');
        return $solicitudData ?: [];
    }

    /**
     * Obtiene los datos de una orden de compra, incluyendo información de la cotización y la solicitud asociada.
     *
     * @param int $id El ID de la Orden de Compra.
     * @return array|null Un array con los datos de la orden de compra o null si no se encuentra.
     */
    public function getOrdenCompraData(int $id): ?array
    {
        $ordenCompraModel = new OrdenCompraModel();

        $result = $ordenCompraModel
            ->select(
                'OrdenCompra.ID_OrdenCompra, Cotizacion.ID_Cotizacion, Solicitud.ID_Solicitud, Solicitud.MetodoPago, OrdenCompra.Estado as EstadoOrden',
            )
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud')
            ->where('OrdenCompra.ID_OrdenCompra', $id)
            ->first();

        return $result ?: null;
    }

    /**
     * Obtiene una orden de compra por el ID de la solicitud.
     *
     * @param int $solicitudId El ID de la solicitud.
     * @return array|null La orden de compra encontrada o null si no existe.
     */
    public function getOrdenByIDSolicitud(int $solicitudId): ?array
    {
        $cotizacionModel = new CotizacionModel();
        $cotizacion = $cotizacionModel->where('ID_Solicitud', $solicitudId)->first();

        if (!$cotizacion) {
            return null;
        }

        $ordenCompraModel = new OrdenCompraModel();
        $orden = $ordenCompraModel->where('ID_Cotizacion', $cotizacion['ID_Cotizacion'])->first();

        return $orden ?: null;
    }

    /**
     * Obtiene todas las órdenes de compra con su información asociada.
     *
     * @return array Un array con todas las órdenes de compra.
     */
    public function getAllOrdenCompraData(): array
    {
        $ordenCompraModel = new OrdenCompraModel();

        $results = $ordenCompraModel
            ->select(
                'OrdenCompra.ID_OrdenCompra, Cotizacion.ID_Cotizacion, Solicitud.ID_Solicitud, Solicitud.MetodoPago, OrdenCompra.Estado as EstadoOrden',
            )
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud')
            ->findAll();

        return $results ?: [];
    }

    /**
     * Obtiene solicitudes filtrando por estado y departamento, opcionalmente excluyendo a un usuario.
     *
     * @param string $status El estado de la solicitud a buscar.
     * @param int $departmentId El ID del departamento.
     * @param int|null $excludeUserId El ID del usuario a excluir de los resultados (ej. el jefe).
     * @return array Un array con las solicitudes encontradas.
     */
    public function getSolicitudesByStatusAndDept(
        string $status,
        int $departmentId,
        ?int $excludeUserId = null,
    ): array {
        $solicitudModel = new SolicitudModel();
        $builder = $solicitudModel
            ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario')
            ->where('Solicitud.Estado', $status)
            ->where('Solicitud.ID_Dpto', $departmentId);

        if ($excludeUserId !== null) {
            $builder->where('Solicitud.ID_Usuario !=', $excludeUserId);
        }

        return $builder->orderBy('Solicitud.Fecha', 'DESC')->findAll();
    }

    //endregion

    //region Solicitudes Cotizadas
    /**
     * Obtiene un resumen de las solicitudes que ya han sido cotizadas.
     *
     * @return array Un array con los datos formateados de las solicitudes cotizadas.
     */
    public function getSolicitudesCotizadas()
    {
        $solicitudModel = new SolicitudModel();

        $solicitudes = $solicitudModel
            ->select(
                'Solicitud.ID_Solicitud as ID, Solicitud.No_Folio as Folio, Solicitud.Estado, Solicitud.IVA,
                Usuarios.Nombre as Usuario, Departamentos.Nombre as Departamento,
                Proveedor.RazonSocial as Proveedor, Cotizacion.Total as Monto, Cotizacion.ID_Cotizacion, Cotizacion.ID_Proveedor, Solicitud.ID_Proveedor as SolicitudProveedorID'
            )
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('Solicitud.Estado', Status::Cotizando)
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        $result = [];
        foreach ($solicitudes as $solicitud) {
            $montoFinal = (float) $solicitud['Monto'];
            if ($solicitud['IVA'] === true) {
                $montoFinal *= 1.16;
            }

            $proveedorNombre = $solicitud['Proveedor']; // This is Proveedor.RazonSocial from the LEFT JOIN (via Cotizacion)

            $idProveedorToLookup = null;
            if (!empty($solicitud['SolicitudProveedorID'])) { // Prioritize Solicitud's ID_Proveedor
                $idProveedorToLookup = $solicitud['SolicitudProveedorID'];
            } elseif (!empty($solicitud['ID_Proveedor'])) { // Fallback to Cotizacion's ID_Proveedor
                $idProveedorToLookup = $solicitud['ID_Proveedor'];
            }

            if (empty($proveedorNombre) && !empty($idProveedorToLookup)) {
                $proveedorModel = new ProveedorModel();
                $foundProveedor = $proveedorModel->find($idProveedorToLookup);
                if ($foundProveedor) {
                    $proveedorNombre = $foundProveedor['RazonSocial'];
                } else {
                    $proveedorNombre = 'Proveedor no encontrado (' . $idProveedorToLookup . ')';
                }
            } else if (empty($proveedorNombre)) {
                 $proveedorNombre = 'N/A'; // Final fallback if no ID was found or no name was resolved
            }

            $result[] = [
                'ID' => $solicitud['ID'],
                'ID_Solicitud' => $solicitud['ID'],
                'Folio' => $solicitud['Folio'],
                'Usuario' => $solicitud['Usuario'],
                'Departamento' => $solicitud['Departamento'],
                'Proveedor' => $proveedorNombre,
                'Monto' => $montoFinal,
                'Estado' => $solicitud['Estado'],
                'ID_Cotizacion' => $solicitud['ID_Cotizacion'],
            ];
        }

        return $result;
    }

    /**
     * Obtiene las solicitudes de un departamento que están pendientes de aprobación.
     *
     * @param int $departmentId El ID del departamento.
     * @return array Un array de solicitudes pendientes.
     */
    public function getSolicitudesUsersByDepartment(int $departmentId)
    {
        $solicitudModel = new SolicitudModel();
        $results = $solicitudModel
            ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario')
            ->where('Solicitud.ID_Dpto', $departmentId)
            ->where('Solicitud.Estado', Status::Aprobacion_pendiente)

            //->where('Solicitud.ID_Usuario !=', session('id'))
            ->orderBy('Solicitud.Fecha', 'DESC')
            ->findAll();
        return $results ?: [];
    }

    /**
     * Obtiene un resumen de las solicitudes que se encuentran "En revisión".
     *
     * @return array Un array con los datos formateados de las solicitudes en revisión.
     */
    public function getSolicitudesEnRevision()
    {
        $solicitudModel = new SolicitudModel();

        $results = $solicitudModel
            ->select([
                'Solicitud.ID_Solicitud as ID',
                'Solicitud.No_Folio as Folio',
                'Usuarios.Nombre as Usuario',
                'Departamentos.Nombre as Departamento',
                'Proveedor.RazonSocial as Proveedor',
                'Cotizacion.Total as Monto',
                'Solicitud.Estado',
                'Solicitud.Fecha',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Cotizacion.ID_Proveedor', 'left')
            ->where('Solicitud.Estado', 'En revision')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $results ?: [];
    }

    /**
     * Obtiene las solicitudes aprobadas que no tienen una orden de compra asociada.
     *
     * @return array Un array de solicitudes.
     */
    public function getSolicitudesSinOrdenPago(): array
    {
        $solicitudModel = new SolicitudModel();

        $results = $solicitudModel
            ->select(
                'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
            )
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('OrdenCompra', 'OrdenCompra.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left')
            ->where('Solicitud.Estado', 'Aprobada')
            ->where('OrdenCompra.ID_OrdenCompra IS NULL')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $results ?: [];
    }

    //endregion

    //region Usuarios
    /**
     * Obtiene un usuario por su ID, opcionalmente con detalles de departamento y ubicación.
     *
     * @param int $id El ID del usuario.
     * @param bool $withDetails Si es true, incluye el nombre del departamento y de la ubicación.
     * @return array|null El usuario encontrado o null si no se encuentra.
     */
    public function getUserById(int $id, bool $withDetails = false): ?array
    {
        $usuariosModel = new UsuariosModel();
        $usuarios = [];

        if ($withDetails) {
            $usuarios = $usuariosModel
                ->select(
                    'Usuarios.*, Departamentos.Nombre as departamento_nombre, Places.Nombre_Corto as place_nombre',
                )
                ->join('Departamentos', 'Departamentos.ID_Dpto = Usuarios.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                ->find($id);
            return $usuarios ? $usuarios : [];
        } else {
            return $usuariosModel->find($id) ?: [];
        }
    }
    public function getSignB64ByUserID(int $id): ?string
    {
        $user = $this->getUserById($id);
        $SignaturePath = null;
        $userFolder = FPath::FUSER . $id;

        if (!$user) {
            return null;
        }
        if (!empty($user['Firma_digital'])) {
            $SignaturePath = $userFolder . DIRECTORY_SEPARATOR . $user['Firma_digital'];
            if (is_readable($SignaturePath)) {
                $signdata = file_get_contents($SignaturePath);
                $sign64 = base64_encode($signdata);
                $signtype = pathinfo($SignaturePath, PATHINFO_EXTENSION);
                $signb64 = 'data:image/' . $signtype . ';base64,' . $sign64;
                return $signb64;
            }
        }
        return null;
    }
    public function getSignByUserID(int $id)
    {
        $user = $this->getUserById($id);
        $SignaturePath = null;
        $userFolder = FPath::FUSER . $id;

        if (!$user) {
            return null;
        }
        if (!empty($user['Firma_digital'])) {
            $SignaturePath = $userFolder . DIRECTORY_SEPARATOR . $user['Firma_digital'];
            $signdata = file_get_contents($SignaturePath);
            return $signdata;
        }
        return null;
    }
    /**
     * Obtiene un usuario por su nombre.
     *
     * @param string $name El nombre del usuario.
     * @return array|null El usuario encontrado o null si no se encuentra.
     */
    public function getUserByName(string $name): ?array
    {
        $usuariosModel = new UsuariosModel();
        $result = $usuariosModel->where('Nombre', $name)->first();
        return $result ?: null;
    }
    /**
     * Obtiene un usuario por su correo electrónico.
     *
     * @param string $email El correo electrónico del usuario.
     * @return array|null El usuario encontrado o null si no se encuentra.
     */
    public function getUserByEmail(string $email): ?array
    {
        $usuariosModel = new UsuariosModel();
        $result = $usuariosModel->where('Correo', $email)->first();
        return $result ?: null;
    }
    /**
     * Obtiene todos los usuarios de un departamento específico.
     *
     * @param int $departmentId El ID del departamento.
     * @return array Los usuarios encontrados.
     */
    public function getUsersByDepartament(int $departmentId): array
    {
        $usuariosModel = new UsuariosModel();
        $results = $usuariosModel->where('ID_Dpto', $departmentId)->findAll();
        return $results ?: [];
    }
    /**
     * Obtiene todos los usuarios con detalles de su departamento y ubicación.
     *
     * @return array Los usuarios encontrados.
     */
    public function getAllUsers(): array
    {
        $usuariosModel = new UsuariosModel();
        $results = $usuariosModel
            ->select(
                'Usuarios.*, Departamentos.Nombre as departamento_nombre, Places.Nombre_Corto as place_nombre',
            )
            ->join('Departamentos', 'Departamentos.ID_Dpto = Usuarios.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->orderBy('Usuarios.Nombre', 'ASC')
            ->findAll();
        return $results ?: [];
    }
    /**
     * Agrega un nuevo usuario a la base de datos.
     *
     * @param array $data Los datos del usuario a agregar.
     * @return bool True si el usuario se agregó correctamente, false en caso contrario.
     */
    public function addUser(array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->insert($data) !== false;
    }
    /**
     * Actualiza un usuario existente por su ID.
     *
     * @param int $id El ID del usuario a actualizar.
     * @param array $data Los nuevos datos para el usuario.
     * @return bool True si el usuario se actualizó correctamente, false en caso contrario.
     */
    public function updateUser(int $id, array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->update($id, $data);
    }

    /**
     * Actualiza un usuario existente por su correo electrónico.
     *
     * @param string $email El correo electrónico del usuario a actualizar.
     * @param array $data Los nuevos datos para el usuario.
     * @return bool True si el usuario se actualizó correctamente, false en caso contrario.
     */
    public function updateUserByEmail(string $email, array $data): bool
    {
        $usuariosModel = new UsuariosModel();
        $user = $this->getUserByEmail($email);
        if (!$user) {
            return false;
        }
        return $usuariosModel->update($user['ID_Usuario'], $data);
    }
    /**
     * Elimina un usuario por su ID.
     *
     * @param int $id El ID del usuario a eliminar.
     * @return bool True si el usuario se eliminó correctamente, false en caso contrario.
     */
    public function deleteUser(int $id): bool
    {
        $usuariosModel = new UsuariosModel();
        return $usuariosModel->delete($id);
    }

    /**
     * Guarda la firma digital de un usuario.
     *
     * @param int $userId El ID del usuario.
     * @param object $file El archivo de la firma a guardar.
     * @return array Un array con el resultado de la operación.
     */
    public function save_signature(int $userId, object $file): array
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userId);

        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado.'];
        }

        $userFolder = FPath::FUSER . $userId;

        if (!empty($user['Firma_digital'])) {
            $oldSignaturePath = $userFolder . DIRECTORY_SEPARATOR . $user['Firma_digital'];
            if (file_exists($oldSignaturePath)) {
                unlink($oldSignaturePath);
            }
        }

        $baseFileName = 'signature_' . $userId . '_' . uniqid();
        $fileName = ImageProcessor::processAndSave($file, $userFolder, $baseFileName);

        if (!$fileName) {
            return ['success' => false, 'message' => 'No se pudo guardar la firma.'];
        }

        $data = ['Firma_digital' => $fileName];
        if ($usuariosModel->update($userId, $data)) {
            return ['success' => true, 'message' => 'Firma guardada correctamente.'];
        } else {
            return ['success' => false, 'message' => 'No se pudo actualizar la base de datos.'];
        }
    }
    //endregion

    //region productos
    /**
     * Obtiene productos buscando por código o por nombre.
     *
     * @param string $query La cadena de búsqueda.
     * @param string $type El tipo de búsqueda ('Código' o 'Producto').
     * @return array Los productos encontrados.
     */
    public function getProductsByQuery(string $query, string $type): array
    {
        $results = [];
        if ($type === 'Código' || $type === 'Codigo' || $type === 'codigo') {
            $results = $this->getProductsByCode($query, 10);
        } elseif ($type === 'Producto' || $type === 'producto') {
            $results = $this->getProductsByName($query, 10);
        }
        return $results;
    }
    /**
     * Obtiene un producto por su ID.
     *
     * @param int $id El ID del producto.
     * @return array|null El producto encontrado o null si no se encuentra.
     */
    public function getProductById(int $id): ?array
    {
        $producto = new ProductoModel();
        $result = $producto->find($id);
        return $result ?: null;
    }
    /**
     * Obtiene productos que coinciden con un código.
     *
     * @param string $code El código a buscar.
     * @param int $limit El número máximo de resultados a devolver.
     * @return array Los productos encontrados.
     */
    public function getProductsByCode(string $code, int $limit = 0): array
    {
        $producto = new ProductoModel();
        $results = $producto->like('Codigo', $code, 'both', null, true)->findAll($limit);
        return $results;
    }
    /**
     * Obtiene productos por nombre (búsqueda insensible a mayúsculas/minúsculas).
     *
     * @param string $name El nombre del producto a buscar.
     * @param int $limit El número máximo de resultados a devolver.
     * @return array Los productos encontrados.
     */
    public function getProductsByName(string $name, int $limit = 0): array
    {
        $builder = $this->db->table('Producto');
        $builder->like('Nombre', $name, 'after', null, true); // true for case-insensitive

        if ($limit > 0) {
            $builder->limit($limit);
        }

        return $builder->get()->getResultArray();
    }
    /**
     * Registra un nuevo producto a partir de un array de datos.
     *
     * @param array $data Los datos del producto a registrar.
     * @return bool True si el producto se registró correctamente, false en caso contrario.
     */
    public function registrarProductoArray(array $data): bool
    {
        $producto = new ProductoModel();
        return $producto->insert($data) !== false;
    }
    /**
     * Registra un nuevo producto con sus propiedades individuales.
     *
     * @param string $codigo El código del producto.
     * @param string $nombre El nombre del producto.
     * @param int $existencia La cantidad de existencia inicial.
     * @return bool True si el producto se registró correctamente, false en caso contrario.
     */
    public function registrarProducto($codigo, $nombre, $existencia): bool
    {
        $producto = new ProductoModel();
        $data = [
            'Codigo' => $codigo,
            'Nombre' => $nombre,
            'Existencia' => $existencia,
        ];
        return $producto->insert($data) !== false;
    }

    /**
     * Resuelve el ID_Place a partir de la Razón Social y el nombre del departamento si el ID_Place viene vacío.
     */
    private function resolvePlaceId(?int $idPlace, ?int $idRS, ?string $nombreDepto): ?int
    {
        if (!empty($idPlace)) {
            return $idPlace;
        }

        if (empty($idRS) || empty($nombreDepto)) {
            return null;
        }

        $colUoNombre = $this->db->protectIdentifiers('uo.Nombre');
        $valDeptoSearch = $this->db->escape(strtolower($nombreDepto));

        $placeEncontrado = $this->db->table('UnidadOperativa uo')
            ->select('uo.ID_Place')
            ->join('Places p', 'p.ID_Place = uo.ID_Place')
            ->where('p.ID_RazonSocial', $idRS)
            ->groupStart()
                ->where('uo.Nombre', $nombreDepto)
                ->orWhere("LOWER($colUoNombre) = $valDeptoSearch", null, false)
            ->groupEnd()
            ->get()
            ->getRowArray();

        if ($placeEncontrado) {
            return $placeEncontrado['ID_Place'];
        }

        // Fallback: el primer place de esa RS
        $firstPlace = $this->db->table('Places')
            ->where('ID_RazonSocial', $idRS)
            ->get()
            ->getRowArray();

        return $firstPlace['ID_Place'] ?? null;
    }

    /**
     * Verifica si un departamento es "especial" (tiene acceso a todo el catálogo del complejo).
     */
    private function isDeptoEspecial(?string $nombreDepto): bool
    {
        if (empty($nombreDepto)) return false;
        
        $deptoLower = mb_strtolower(trim($nombreDepto));
        return (
            strpos($deptoLower, 'operacion') !== false || 
            strpos($deptoLower, 'operación') !== false || 
            strpos($deptoLower, 'compras') !== false ||
            strpos($deptoLower, 'contaduría') !== false ||
            strpos($deptoLower, 'contaduria') !== false ||
            strpos($deptoLower, 'mantenimiento') !== false
        );
    }

    /**
     * Busca productos en el catálogo maestro aplicando los filtros de área/departamento y priorizando favoritos.
     */
    public function buscarProductosCatalogo(string $query, ?int $idPlace, ?int $idRS, string $nombreDepto, ?int $idUsuario = null): array
    {
        $idPlace = $this->resolvePlaceId($idPlace, $idRS, $nombreDepto);
        if (empty($idPlace)) return [];

        $catalogoModel = new CatalogoProductosModel();
        $builder = $catalogoModel->select('Catalogo_Productos.*, UnidadOperativa.Nombre as UnidadNombre')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left');

        if ($this->isDeptoEspecial($nombreDepto)) {
            $builder->where('Catalogo_Productos.ID_Place', $idPlace);
        } else {
            $colNombre = $this->db->protectIdentifiers('uo.Nombre');
            $valDepto = $this->db->escape(strtolower($nombreDepto));
            $valDeptoLike = $this->db->escape('%' . strtolower($nombreDepto) . '%');

            $unidad = $this->db->table('UnidadOperativa uo')
                ->where('uo.ID_Place', $idPlace)
                ->where('uo.activo', true)
                ->groupStart()
                    ->where('uo.Nombre', $nombreDepto)
                    ->orWhere("LOWER($colNombre) = $valDepto", null, false)
                    ->orWhere("LOWER($colNombre) LIKE $valDeptoLike", null, false)
                ->groupEnd()
                ->get()
                ->getRowArray();

            if ($unidad) {
                $builder->where('Catalogo_Productos.ID_Dpto', $unidad['ID_UnidadOperativa']);
            } else {
                $builder->where('Catalogo_Productos.ID_Place', $idPlace);
            }
        }

        // Si hay una búsqueda, aplicamos el filtro
        if (!empty($query)) {
            $builder->groupStart()
                    ->like('Catalogo_Productos.Nombre', $query, 'both', null, true)
                    ->orLike('CAST(Catalogo_Productos.ID_CatalogoProd AS VARCHAR)', $query, 'both', null, false)
                ->groupEnd();
        }

        $productos = $builder->orderBy('Catalogo_Productos.Nombre', 'ASC')->findAll();

        // Si tenemos el usuario, marcamos cuáles son favoritos y ordenamos
        if ($idUsuario) {
            log_message('debug', "[Rest::buscarProductosCatalogo] Buscando favoritos para usuario: $idUsuario");
            $favModel = new UsuarioProductoFavoritoModel();
            $favoritos = $favModel->where('id_usuario', $idUsuario)->findAll();
            $favIds = array_column($favoritos, 'alias_personal', 'id_catalogoprod');
            
            log_message('debug', "[Rest::buscarProductosCatalogo] Favoritos encontrados: " . count($favoritos));

            foreach ($productos as &$p) {
                // Verificar ambas variantes de nombre de campo por si acaso
                $prodId = $p['ID_CatalogoProd'] ?? $p['id_catalogoprod'] ?? null;
                $p['es_favorito'] = array_key_exists($prodId, $favIds);
                $p['alias_personal'] = $favIds[$prodId] ?? null;
            }

            // Ordenar: Favoritos primero
            usort($productos, function($a, $b) {
                if (($a['es_favorito'] ?? false) && !($b['es_favorito'] ?? false)) return -1;
                if (!($a['es_favorito'] ?? false) && ($b['es_favorito'] ?? false)) return 1;
                return strcmp($a['Nombre'] ?? $a['nombre'] ?? '', $b['Nombre'] ?? $b['nombre'] ?? '');
            });
        } else {
            log_message('debug', "[Rest::buscarProductosCatalogo] No hay ID de usuario en la sesión.");
        }

        return $productos;
    }

    /**
     * Obtiene los favoritos del usuario que cumplan con los filtros de área.
     */
    public function getFavoritosUsuario(int $idUsuario, ?int $idPlace, ?int $idRS, string $nombreDepto): array
    {
        $idPlace = $this->resolvePlaceId($idPlace, $idRS, $nombreDepto);
        if (empty($idPlace)) return [];

        $favModel = new UsuarioProductoFavoritoModel();
        $builder = $favModel->select('usuarios_productos_favoritos.*, Catalogo_Productos.Nombre, Catalogo_Productos.ID_GrupoPresupuestal, UnidadOperativa.Nombre as UnidadNombre')
            ->join('Catalogo_Productos', 'Catalogo_Productos.ID_CatalogoProd = usuarios_productos_favoritos.id_catalogoprod')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left')
            ->where('usuarios_productos_favoritos.id_usuario', $idUsuario);

        if ($this->isDeptoEspecial($nombreDepto)) {
            $builder->where('Catalogo_Productos.ID_Place', $idPlace);
        } else {
             $colNombre = $this->db->protectIdentifiers('uo.Nombre');
             $valDepto = $this->db->escape(strtolower($nombreDepto));
             $valDeptoLike = $this->db->escape('%' . strtolower($nombreDepto) . '%');

             $unidad = $this->db->table('UnidadOperativa uo')
                 ->where('uo.ID_Place', $idPlace)
                 ->where('uo.activo', true)
                 ->groupStart()
                     ->where('uo.Nombre', $nombreDepto)
                     ->orWhere("LOWER($colNombre) = $valDepto", null, false)
                     ->orWhere("LOWER($colNombre) LIKE $valDeptoLike", null, false)
                 ->groupEnd()
                 ->get()
                 ->getRowArray();

             if ($unidad) {
                 $builder->where('Catalogo_Productos.ID_Dpto', $unidad['ID_UnidadOperativa']);
             } else {
                 $builder->where('Catalogo_Productos.ID_Place', $idPlace);
             }
        }

        return $builder->orderBy('usuarios_productos_favoritos.frecuencia', 'DESC')
                       ->orderBy('Catalogo_Productos.Nombre', 'ASC')
                       ->findAll();
    }

    /**
     * Agrega o quita un producto de favoritos.
     */
    public function toggleFavorito(int $idUsuario, int $idCatalogoProd, ?string $alias = null): array
    {
        $favModel = new UsuarioProductoFavoritoModel();
        $existente = $favModel->where([
            'id_usuario' => $idUsuario,
            'id_catalogoprod' => $idCatalogoProd
        ])->first();

        if ($existente) {
            if (!empty($alias)) {
                // Si existe y enviamos alias, lo actualizamos
                $favModel->update($existente['id'], ['alias_personal' => $alias]);
                return ['status' => 'updated', 'message' => 'Alias de favorito actualizado.'];
            } else {
                // Si existe y no enviamos alias, es un "quitar de favoritos"
                $favModel->delete($existente['id']);
                return ['status' => 'removed', 'message' => 'Producto eliminado de favoritos.'];
            }
        } else {
            // No existe, lo agregamos
            $favModel->insert([
                'id_usuario' => $idUsuario,
                'id_catalogoprod' => $idCatalogoProd,
                'alias_personal' => $alias,
                'frecuencia' => 1
            ]);
            return ['status' => 'added', 'message' => 'Producto agregado a favoritos.'];
        }
    }

    /**
     *  Elimina un producto por su ID.
     * @param int $id El ID del producto a eliminar.
     * @return bool True si el producto se eliminó correctamente, false en caso contrario.
     */
    public function eliminarProductoById(int $id): bool
    {
        $producto = new ProductoModel();
        return $producto->delete($id);
    }
    /**
     * Actualiza un producto existente por su ID.
     *
     * @param int $id El ID del producto a actualizar.
     * @param array $data Los nuevos datos para el producto.
     * @return bool True si el producto se actualizó correctamente, false en caso contrario.
     */
    public function actualizarProducto(int $id, array $data): bool
    {
        $producto = new ProductoModel();
        return $producto->update($id, $data);
    }
    /**
     * Obtiene todos los productos de la base de datos.
     *
     * @return array Los productos encontrados.
     */
    public function getAllProducts(): array
    {
        $producto = new ProductoModel();
        $results = $producto->findAll();
        if (empty($results)) {
            return [];
        }
        return $results;
    }
    //endregion

    //region Proveedor

    /**
     * Obtiene un proveedor por su ID sin incluir datos de contacto sensibles.
     *
     * @param int $id El ID del proveedor.
     * @return array|null El proveedor encontrado o null si no se encuentra.
     */
    public function getProveedorByID(int $id): ?array
    {
        $proveedorModel = new ProveedorModel();
        $proveedor = $proveedorModel->find($id);

        if ($proveedor) {
            unset($proveedor['Correo']);
            unset($proveedor['Tel_Contacto']);
        }

        return $proveedor ?: [];
    }
    /**
     * Obtiene todos los proveedores.
     *
     * @return array Los proveedores encontrados.
     */
    public function getAllProveedores(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->findAll();
        return $results ?: [];
    }

    /**
     * Obtiene proveedores paginados con filtros server-side.
     *
     * Compatible con PostgreSQL y MariaDB/MySQL (sin funciones mono-motor:
     * la búsqueda insensible a mayúsculas se resuelve con LOWER() portable).
     *
     * @param int   $page    Página actual (inicia en 1).
     * @param int   $perPage Registros por página.
     * @param array $filters Filtros opcionales: razon_social, rfc.
     * @return array ['data' => [], 'total' => int, 'page' => int, 'perPage' => int]
     */
    public function getProveedoresPaginated(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $builder = $this->db->table('Proveedor');

        $applyFilters = function (BaseBuilder $query) use ($filters): void {
            if (!empty($filters['razon_social'])) {
                $query->where($this->likeInsensitive('RazonSocial', $filters['razon_social']));
            }
            if (!empty($filters['rfc'])) {
                $query->where($this->likeInsensitive('RFC', $filters['rfc']));
            }
            if (!empty($filters['servicio'])) {
                $query->where($this->likeInsensitive('Servicio', $filters['servicio']));
            }
        };

        $applyFilters($builder);

        $totalBuilder = clone $builder;
        $total = (int) $totalBuilder->select('COUNT(*) as total')->get()->getRow()->total;

        $rows = $builder->select('ID_Proveedor, RazonSocial, RFC, Banco, Cuenta, Clabe, Tel_Contacto, Nombre_Contacto, Servicio, Correo, Dias_Credito, Monto_Credito')
            ->orderBy('RazonSocial', 'ASC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }
    /**
     * Obtiene el ID y Nombre de todos los proveedores.
     *
     * @return array Un array de proveedores con solo su ID y Razón Social.
     */
    public function getAllProveedorName(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->findAll();
        return $results;
    }

    /**
     * Obtiene el ID y Razón Social de todos los proveedores.
     *
     * @return array Un array de proveedores con solo su ID y Razón Social.
     */
    public function getProveedorIdAndRazonSocial(): array
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel
            // ¡Aquí está la magia! Agregamos Dias_Credito y Monto_Credito
            ->select('ID_Proveedor, RazonSocial, Tel_Contacto, RFC, Dias_Credito, Monto_Credito')
            ->orderBy('RazonSocial', 'ASC')
            ->findAll();
        return $results;
    }
    //endregion

    //region Grupos presupuestales
    /**
     * Obtiene partidas presupuestales paginadas con filtros server-side.
     *
     * Compatible con PostgreSQL y MariaDB/MySQL (sin funciones mono-motor:
     * la búsqueda insensible a mayúsculas se resuelve con LOWER() portable).
     *
     * @param int   $page    Página actual (inicia en 1).
     * @param int   $perPage Registros por página.
     * @param array $filters Filtros opcionales: nombre, lugares[], unidades[].
     * @return array ['data' => [], 'total' => int, 'page' => int, 'perPage' => int]
     */
    public function getGruposPresupuestalesPaginated(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $builder = $this->db->table('GrupoPresupuestal')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = GrupoPresupuestal.ID_UnidadOperativa', 'left')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left');

        if (!empty($filters['nombre'])) {
            $builder->where($this->likeInsensitive('GrupoPresupuestal.Nombre', $filters['nombre']));
        }
        if (!empty($filters['lugares'])) {
            $lugares = array_filter((array) $filters['lugares'], fn($v) => $v !== '' && $v !== null);
            if (!empty($lugares)) {
                $builder->whereIn('Places.Nombre_Corto', $lugares);
            }
        }
        if (!empty($filters['unidades'])) {
            $unidades = array_filter((array) $filters['unidades'], fn($v) => $v !== '' && $v !== null);
            if (!empty($unidades)) {
                $builder->whereIn('UnidadOperativa.Nombre', $unidades);
            }
        }

        $totalBuilder = clone $builder;
        $total = (int) $totalBuilder->select('COUNT(*) as total')->get()->getRow()->total;

        $rows = $builder->select('GrupoPresupuestal.*, UnidadOperativa.Nombre as UnidadNombre, Places.Nombre_Corto as PlaceNombre')
            ->orderBy('GrupoPresupuestal.Nombre', 'ASC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Obtiene el catálogo de productos y servicios con paginación server-side.
     *
     * Compatible PostgreSQL + MariaDB/MySQL (filtros insensibles a mayúsculas
     * con LOWER(), igual que el resto de listados paginados).
     *
     * @param int   $page    Página actual (inicia en 1).
     * @param int   $perPage Registros por página.
     * @param array $filters Filtros opcionales: nombre, departamento, grupo.
     * @return array ['data' => [], 'total' => int, 'page' => int, 'perPage' => int]
     */
    public function getCatalogoPaginated(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $builder = $this->db->table('Catalogo_Productos')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Catalogo_Productos.ID_RazonSocial', 'left')
            ->join('Places', 'Places.ID_Place = Catalogo_Productos.ID_Place', 'left')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left')
            ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Catalogo_Productos.ID_GrupoPresupuestal', 'left');

        if (!empty($filters['nombre'])) {
            $builder->where($this->likeInsensitive('Catalogo_Productos.Nombre', $filters['nombre']));
        }
        if (!empty($filters['departamento'])) {
            $builder->where($this->likeInsensitive('UnidadOperativa.Nombre', $filters['departamento']));
        }
        if (!empty($filters['grupo'])) {
            $builder->where($this->likeInsensitive('GrupoPresupuestal.Nombre', $filters['grupo']));
        }

        $totalBuilder = clone $builder;
        $total = (int) $totalBuilder->select('COUNT(*) as total')->get()->getRow()->total;

        $rows = $builder->select('Catalogo_Productos.ID_CatalogoProd, Catalogo_Productos.ID_RazonSocial, Catalogo_Productos.id_segmento, Catalogo_Productos.ID_Place, Catalogo_Productos.ID_Dpto, Catalogo_Productos.ID_GrupoPresupuestal, Catalogo_Productos.Nombre, Razon_Social.Nombre as RazonSocial_Nombre, Places.Nombre_Corto as Place_Nombre, UnidadOperativa.Nombre as Departamento_Nombre, GrupoPresupuestal.Nombre as GrupoPresupuestal_Nombre')
            ->orderBy('Catalogo_Productos.Nombre', 'ASC')
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get()
            ->getResultArray();

        return [
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Construye una condición LIKE insensible a mayúsculas portable
     * (PostgreSQL + MariaDB/MySQL).
     *
     * El identificador se escapa por driver (comillas dobles / backticks) y el
     * valor se compara en minúsculas con LOWER(). Se evita like(..., true)
     * porque el driver Postgre de CI4 no escapa el prefijo de tabla en ILIKE.
     *
     * @return string Condición SQL completa, lista para where().
     */
    private function likeInsensitive(string $column, string $value): string
    {
        $col = $this->db->escapeIdentifiers($column);

        return 'LOWER(' . $col . ') LIKE ' . $this->db->escape('%' . mb_strtolower($value) . '%');
    }
    //endregion

    //region departamentos
    /**
     * Obtiene todos los departamentos con el nombre corto de su ubicación.
     *
     * @return array Los departamentos encontrados.
     */
    public function getAllDepartments(): array
    {
        $departamentosModel = new DepartamentosModel();
        $results = $departamentosModel
            ->select(
                'Departamentos.ID_Dpto, Departamentos.Nombre, Departamentos.ID_UnidadOperativa, Places.Nombre_Corto as Place, Places.ID_RazonSocial',
            )
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->findAll();

        if (empty($results)) {
            return [];
        }

        return $results;
    }
    public function getDepartmentById(int $id): ?array

    {
        $departamentosModel = new DepartamentosModel();
        $result = $departamentosModel->find($id);
        return $result ?: null;
    }
    /**
     * Obtiene el nombre de una ubicación (Place) por su ID.
     *
     * @param int $id El ID de la ubicación.
     * @param bool $long Si es true, devuelve el nombre completo; de lo contrario, el nombre corto.
     * @return string|null El nombre de la ubicación o null si no se encuentra.
     */
    public function getPlaceById(int $id, bool $long = false): ?string
    {
        $places = new PlacesModel();
        $result = $places->find($id);
        if ($result) {
            return $long ? $result['Nombre_Completo'] : $result['Nombre_Corto'];
        }

        return null;
    }
    //endregion

    //region Razón social
    public function getRazonSocialByID(int $id): ?array
    {
        $razonSocialModel = new RazonSocialModel();
        return $razonSocialModel->find($id) ?: null;
    }

    /**
     * Obtiene la razón social asociada a un usuario.
     *
     * @param int $userid El ID del usuario.
     * @return array|null La razón social encontrada o null si no se encuentra.
     */
    public function getRazonSocialByUserID(int $userid): ?array
    {
        $usuariosModel = new UsuariosModel();
        $user = $usuariosModel->find($userid);

        if (!$user || !isset($user['ID_RazonSocial'])) {
            return null;
        }

        $razonSocialModel = new RazonSocialModel();
        return $razonSocialModel->find($user['ID_RazonSocial']) ?: null;
    }
    //endregion

    //region Limpiar Almacenamiento
    /**
     * Obtiene el contenido de un directorio dentro de 'writable/uploads'.
     *
     * @param string $relativePath La ruta relativa dentro de uploads.
     * @return array Lista de archivos y carpetas.
     * @throws \Exception Si el directorio no es válido o no se puede leer.
     */
    public function getStorageContent(string $relativePath = ''): array
    {
        $basePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;

        $cleanPath = str_replace(['../', '..\\'], '', $relativePath);
        $cleanPath = trim($cleanPath, '/\\');

        $targetPath = realpath($basePath . $cleanPath);

        if ($targetPath === false || strpos($targetPath, realpath($basePath)) !== 0) {
            $targetPath = realpath($basePath);
            $cleanPath = '';
        }

        if (!is_dir($targetPath)) {
            throw new \Exception('El directorio no existe: ' . $cleanPath);
        }

        $items = [];
        try {
            $iterator = new \DirectoryIterator($targetPath);
            $disallowedExtensions = ['html', 'htm', 'js', 'css', 'json'];

            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isDot()) {
                    continue;
                }

                if (
                    $fileinfo->isFile() &&
                    in_array(strtolower($fileinfo->getExtension()), $disallowedExtensions, true)
                ) {
                    continue;
                }

                $isDir = $fileinfo->isDir();
                $itemRelativePath = $cleanPath
                    ? $cleanPath . '/' . $fileinfo->getFilename()
                    : $fileinfo->getFilename();

                $items[] = [
                    'name' => $fileinfo->getFilename(),
                    'type' => $isDir ? 'folder' : 'file',
                    'path' => $itemRelativePath,
                    'size' => $isDir ? '-' : $this->formatBytes($fileinfo->getSize()),
                ];
            }

            usort($items, function ($a, $b) {
                if ($a['type'] === $b['type']) {
                    return strcasecmp($a['name'], $b['name']);
                }
                return $a['type'] === 'folder' ? -1 : 1;
            });
        } catch (\Exception $e) {
            throw new \Exception('Error al leer el directorio: ' . $e->getMessage());
        }

        return $items;
    }

    /**
     * Formatea bytes a una cadena legible por humanos (KB, MB, GB).
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    //endregion

    //region misceláneos
    public static function ShowDebug($data, $json = false)
    {
        if ($json) {
            return ['success' => true, 'debug' => $data];
        }
        return "<pre>Debug Info:\n" . print_r($data, true) . '</pre>';
    }
    /**
     * Crea una carpeta en la ruta especificada si no existe.
     *
     * @param string $path La ruta completa de la carpeta a crear.
     * @return bool True si la carpeta ya existe o fue creada exitosamente, false si hubo un error.
     */
    public function CreateFolder(string $path): bool
    {
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene los datos completos de una solicitud de pago para generar un PDF de requisición.
     *
     * Incluye información de usuario, departamento, proveedor, razón social, y calcula el importe total
     * y la descripción de los productos/servicios asociados a la solicitud.
     *
     * @param int $id El ID de la solicitud de la cual se obtendrán los datos de pago.
     * @return array|null Un array con todos los datos formateados para la requisición de pago, o null si la solicitud no se encuentra.
     */
    public function getSolicitudPago(int $id): ?array
    {
        log_message('debug', 'Iniciando getSolicitudPago para ID: ' . $id);
        $solicitudModel = new SolicitudModel();
        $placesModel = new PlacesModel();
        $razonSocialModel = new RazonSocialModel();
        $proveedorModel = new ProveedorModel();

        $solicitud = $solicitudModel
            ->select('Solicitud.*')
            ->select('Solicitud.ID_Proveedor as SolicitudProveedorID')
            ->select('Usuarios.Nombre as UsuarioNombre')
            ->select('Departamentos.Nombre as DepartamentoNombre')
            ->select('Proveedor.RazonSocial as ProveedorNombre')
            ->select('Proveedor.Banco as ProveedorBanco')
            ->select('Proveedor.Cuenta as ProveedorCuenta')
            ->select('Proveedor.Clabe as ProveedorClabe')

            ->select('Razon_Social.Nombre as Complejo')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->find($id);

        if (!$solicitud) {
            log_message('debug', 'Solicitud no encontrada para ID: ' . $id);
            return null;
        }

        if (!empty($solicitud['ID_Cuenta'])) {
            $cuentasModel = new CuentasModel();
            $solicitud['cuenta_details'] = $cuentasModel->find($solicitud['ID_Cuenta']);
        }

        log_message('debug', 'Solicitud encontrada: ' . json_encode($solicitud));

        $depto = $this->getDepartmentById($solicitud['ID_Dpto']);
        $idPlace = null;

        if ($depto) {
            if (!empty($depto['ID_Place'])) {
                $idPlace = $depto['ID_Place'];
            } elseif (!empty($depto['ID_UnidadOperativa'])) {
                $uniModel = new \App\Models\UnidadOperativaModel();
                $unidad = $uniModel->find($depto['ID_UnidadOperativa']);
                $idPlace = $unidad['ID_Place'] ?? null;
            }
        }

        $place = $idPlace ? $placesModel->find($idPlace) : null;
        $solicitud['ID_Place'] = $idPlace ?? 0;
        $solicitud['PlaceNombre'] = $place['Nombre_Corto'] ?? 'N/A';
        log_message('debug', 'PlaceNombre: ' . $solicitud['PlaceNombre']);

        $importeTotal = 0;
        $descripcionPago = '';

        if (
            $solicitud['Tipo'] == SolicitudTipo::Cotizacion ||
            $solicitud['Tipo'] == SolicitudTipo::NoCotizacion
        ) {
            $solicitudProductModel = new SolicitudProductModel();
            $productos = $solicitudProductModel->where('ID_Solicitud', $id)->findAll();
            foreach ($productos as $producto) {
                $importeTotal += $producto['Cantidad'] * $producto['Importe'];
                $descripcionPago .= $producto['Cantidad'] . ' ' . $producto['Nombre'] . ', ';
            }
            log_message('debug', 'Productos encontrados: ' . json_encode($productos));
        } else {
            $solicitudServicioModel = new SolicitudServiciosModel();
            $servicios = $solicitudServicioModel->where('ID_Solicitud', $id)->findAll();
            foreach ($servicios as $servicio) {
                $importeTotal += $servicio['Importe'];
                $descripcionPago .= $servicio['Nombre'] . ', ';
            }
            log_message('debug', 'Servicios encontrados: ' . json_encode($servicios));
        }

        $solicitud['ImporteTotal'] = $importeTotal;
        $solicitud['DescripcionPago'] = rtrim($descripcionPago, ', ');
        log_message(
            'debug',
            'ImporteTotal: ' . $importeTotal . ', DescripcionPago: ' . $descripcionPago,
        );

        $tipoPagoMap = [
            MetodoPago::Efectivo => 'Efectivo',
            MetodoPago::Credito => 'Crédito',
        ];

        $solicitud['TipoPagoTexto'] = $tipoPagoMap[$solicitud['Tipo']] ?? 'Desconocido';
        log_message('debug', 'TipoPagoTexto: ' . $solicitud['TipoPagoTexto']);

        $solicitud['Banco'] = $solicitud['ProveedorBanco'] ?? '';
        $solicitud['Cuenta'] = $solicitud['ProveedorCuenta'] ?? '';
        $solicitud['Clabe'] = $solicitud['ProveedorClabe'] ?? '';
        log_message(
            'debug',
            'Datos bancarios: ' .
                json_encode(['Banco' => $solicitud['Banco'], 'Cuenta' => $solicitud['Cuenta']]),
        );

        $solicitud['Solicita'] = $solicitud['UsuarioNombre'] ?? '';
        $solicitud['VoBo'] = 'Administracion';
        $solicitud['Autoriza'] = 'Direccion General';
        $solicitud['NotificarA'] = '';
        log_message(
            'debug',
            'Campos de firma: ' .
                json_encode(['Solicita' => $solicitud['Solicita'], 'VoBo' => $solicitud['VoBo']]),
        );

        log_message('debug', 'Finalizando getSolicitudPago con éxito.');
        log_message('debug', print_r($solicitud, true));
        return $solicitud ?: [];
    }
    
    /**
     * Obtiene todas las solicitudes con la información detallada de sus claves foráneas
     * para el reporte de Pagado/Por Pagar Detallado.
     *
     * @return array Un array con los datos completos de las solicitudes.
     */
    public function getMovimientosProveedor(): array
    {
        $solicitudModel = new SolicitudModel();

        // Join con todas las FK de SolicitudModel:
        // ID_Usuario, ID_Dpto, ID_UnidadOperativa, ID_Proveedor, ID_Cuenta, ID_RazonSocial
        // Y extensión hacia OrdenCompra a través de Cotización
        $solicitudes = $solicitudModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Solicitud.Fecha',
                'Solicitud.Estado',
                'Solicitud.Tipo',
                'Solicitud.MetodoPago',
                'Solicitud.IVA',
                'Solicitud.Fecha_Aprobacion',
                'Solicitud.ID_UnidadOperativa',
                'Usuarios.Nombre as UsuarioSolicita',
                'Departamentos.Nombre as DepartamentoNombre',
                'UnidadOperativa.Nombre as UnidadOperativaNombre',
                'UnidadOperativa.ID_Place',
                'Places.Nombre_Corto as PlaceNombre',
                'Proveedor.RazonSocial as ProveedorNombre',
                'Proveedor.RFC as ProveedorRFC',
                'Proveedor.Banco as CuentaBanco',
                'Proveedor.Cuenta as Cuenta',
                'Proveedor.Clabe as Clabe',
                'Proveedor.Nombre_Contacto as Nombre_Contacto',
                'Proveedor.Tel_Contacto as Tel_Contacto',
                'Razon_Social.Nombre as RazonSocialNombre',
                'UsuarioAutoriza.Nombre as UsuarioAutorizaNombre',
                'Cotizacion.Total as MontoTotal',
                'Cotizacion.ID_Cotizacion',
                // Campos de Orden de Compra
                'OrdenCompra.ID_OrdenCompra',
                'OrdenCompra.Estado as OrdenEstado',
                'OrdenCompra.Fecha as OrdenFecha',
                'OrdenCompra.File_Factura',
                'OrdenCompra.File_Comprobante',
                'OrdenCompra.FechaRefPago',
                'OrdenCompra.FechaPagoRealizado'
            ])
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Usuarios as UsuarioAutoriza', 'UsuarioAutoriza.ID_Usuario = Solicitud.ID_Usuario_Autoriza', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Solicitud.ID_UnidadOperativa', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('OrdenCompra', 'OrdenCompra.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left')
            ->groupBy([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Solicitud.Fecha',
                'Solicitud.Estado',
                'Solicitud.Tipo',
                'Solicitud.MetodoPago',
                'Solicitud.IVA',
                'Solicitud.Fecha_Aprobacion',
                'Solicitud.ID_UnidadOperativa',
                'Usuarios.Nombre',
                'Departamentos.Nombre',
                'UnidadOperativa.Nombre',
                'UnidadOperativa.ID_Place',
                'Places.Nombre_Corto',
                'Proveedor.RazonSocial',
                'Proveedor.RFC',
                'Proveedor.Banco',
                'Proveedor.Cuenta',
                'Proveedor.Clabe',
                'Proveedor.Nombre_Contacto',
                'Proveedor.Tel_Contacto',
                'Razon_Social.Nombre',
                'UsuarioAutoriza.Nombre',
                'Cotizacion.Total',
                'Cotizacion.ID_Cotizacion',
                'OrdenCompra.ID_OrdenCompra',
                'OrdenCompra.Estado',
                'OrdenCompra.Fecha',
                'OrdenCompra.File_Factura',
                'OrdenCompra.File_Comprobante',
                'OrdenCompra.FechaRefPago',
                'OrdenCompra.FechaPagoRealizado'
            ])
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        return $solicitudes ?: [];
    }

    /**
     * Obtiene solicitudes filtradas por varios criterios.
     *
     * @param string|null $fecha La fecha para filtrar (YYYY-MM-DD).
     * @param bool $porMes Si es true, filtra por mes (YYYY-MM).
     * @param string|null $estado El estado de la solicitud para filtrar.
     * @param array|null $departamentos Un array de strings "Departamento|Lugar" para filtrar.
     * @return array Un array de solicitudes filtradas.
     */
    public function getFilteredSolicitudes(?string $fecha, bool $porMes, ?string $estado, ?array $departamentos): array
    {
        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();

        $builder = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre, Usuarios.Nombre as UsuarioNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left');

        // Excluir estados por defecto si no se especifica un estado de filtro.
        // Si el estado es 'Todos los estados' (vacío), aplicamos la exclusión de getAllSolicitud.
        if (empty($estado)) {
            $excluded_statuses = [Status::Dept_Rechazada, Status::Aprobacion_pendiente];
            $builder->whereNotIn('Solicitud.Estado', $excluded_statuses);
        } elseif ($estado !== 'Todos los estados') { // Aplicar el estado específico si no es 'Todos los estados'
            $builder->where('Solicitud.Estado', $estado);
        }

        // Filtro por Fecha
        if (!empty($fecha)) {
            if ($porMes) {
                $builder->like('Solicitud.Fecha', substr($fecha, 0, 7), 'after'); // 'YYYY-MM%'
            } else {
                $builder->where('Solicitud.Fecha', $fecha);
            }
        }

        // Filtro por Departamentos
        if (!empty($departamentos)) {
            $builder->groupStart();
            foreach ($departamentos as $dptoPlace) {
                list($dpto, $place) = explode('|', $dptoPlace);
                $builder->orGroupStart();
                $builder->where('Departamentos.Nombre', $dpto);
                // Si 'Todos los lugares' (vacío) se selecciona para un departamento, no filtrar por PlaceNombre
                if (!empty($place)) {
                    $builder->where('Places.Nombre_Corto', $place);
                }
                $builder->groupEnd();
            }
            $builder->groupEnd();
        }

        $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')->findAll();

        if (empty($solicitudes)) {
            return [];
        }

        $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

        // Obtener cotizaciones y órdenes de compra para actualizar el estado
        $cotizaciones = $cotizacionModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();
        $cotizacionIds = array_column($cotizaciones, 'ID_Cotizacion');

        $ordenes = [];
        if (!empty($cotizacionIds)) {
            $ordenes = $ordenCompraModel->whereIn('ID_Cotizacion', $cotizacionIds)->findAll();
        }

        $cotizacionesMap = [];
        foreach ($cotizaciones as $cot) {
            if (!isset($cotizacionesMap[$cot['ID_Solicitud']])) {
                $cotizacionesMap[$cot['ID_Solicitud']] = $cot;
            }
        }

        $ordenesMap = [];
        foreach ($ordenes as $orden) {
            $ordenesMap[$orden['ID_Cotizacion']] = $orden;
        }

        foreach ($solicitudes as &$solicitud) {
            if ($solicitud['Estado'] === Status::Aprobada) {
                if (isset($cotizacionesMap[$solicitud['ID_Solicitud']])) {
                    $cotizacion = $cotizacionesMap[$solicitud['ID_Solicitud']];
                    if (isset($ordenesMap[$cotizacion['ID_Cotizacion']])) {
                        $orden = $ordenesMap[$cotizacion['ID_Cotizacion']];
                        if (!empty($orden['Estado'])) {
                            $solicitud['Estado'] = $orden['Estado'];
                        }
                    }
                }
            }
        }

        return $solicitudes;
    }
    
    /**
     * Obtiene todas las órdenes de compra pendientes de pago con sus detalles.
     *
     * @return array Un array con los datos de las órdenes.
     */
    public function getPagosPendientes(): array
    {

        $ordenCompraModel = new OrdenCompraModel();

        $ordenes = $ordenCompraModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Solicitud.MetodoPago',
                'Solicitud.Fecha_Aprobacion',
                'Departamentos.Nombre as DepartamentoNombre',
                'Razon_Social.Nombre as Complejo',
                'Proveedor.RazonSocial',
                'Proveedor.Banco',
                'Proveedor.Dias_Credito',
                'Cotizacion.Total',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->where('OrdenCompra.Estado', Status::Por_Pagar)
            ->orderBy('Solicitud.Fecha_Aprobacion', 'ASC')
            ->findAll();

        // Estructurar los datos para que coincidan con lo que espera el frontend.
        $results = [];
        foreach ($ordenes as $orden) {
            $results[] = [
                'ID_Solicitud' => $orden['ID_Solicitud'],
                'No_Folio' => $orden['No_Folio'],
                'MetodoPago' => $orden['MetodoPago'],
                'Fecha_Aprobacion' => $orden['Fecha_Aprobacion'],
                'DepartamentoNombre' => $orden['DepartamentoNombre'],
                'Complejo' => $orden['Complejo'],
                'EstadoOrden' => Status::Por_Pagar, // Se asume este estado por la consulta
                'proveedor' => [
                    'RazonSocial' => $orden['RazonSocial'],
                    'Banco' => $orden['Banco'],
                    'Dias_Credito' => $orden['Dias_Credito'],
                ],
                'cotizacion' => [
                    'Total' => $orden['Total'],
                ],
            ];
        }

        return $results;
    }

    /**
     * Obtiene todas las órdenes de compra en proceso de pago con sus detalles.
     *
     * @return array Un array con los datos de las órdenes.
     */
    public function getFichasPago(): array
    {
        $ordenCompraModel = new OrdenCompraModel();

        $ordenes = $ordenCompraModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Solicitud.MetodoPago',
                'Solicitud.Fecha_Aprobacion',
                'Departamentos.Nombre as DepartamentoNombre',
                'Razon_Social.Nombre as Complejo',
                'Proveedor.RazonSocial',
                'Proveedor.Banco',
                'Proveedor.Dias_Credito',
                'Cotizacion.Total',
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->where('OrdenCompra.Estado', Status::En_Proceso_Pago) // Changed status
            ->orderBy('Solicitud.Fecha_Aprobacion', 'ASC')
            ->findAll();

        $results = [];
        foreach ($ordenes as $orden) {
            $results[] = [
                'ID_Solicitud' => $orden['ID_Solicitud'],
                'No_Folio' => $orden['No_Folio'],
                'MetodoPago' => $orden['MetodoPago'],
                'Fecha_Aprobacion' => $orden['Fecha_Aprobacion'],
                'DepartamentoNombre' => $orden['DepartamentoNombre'],
                'Complejo' => $orden['Complejo'],
                'EstadoOrden' => Status::En_Proceso_Pago,
                'proveedor' => [
                    'RazonSocial' => $orden['RazonSocial'],
                    'Banco' => $orden['Banco'],
                    'Dias_Credito' => $orden['Dias_Credito'],
                ],
                'cotizacion' => [
                    'Total' => $orden['Total'],
                ],
            ];
        }

        return $results;
    }
    //endregion

    //region Bitacora
    /**
     * Obtiene registros de la bitácora con filtros opcionales.
     */
    public function getBitacora($filters = [], $limit = 50, $offset = 0)
    {
        $builder = $this->db->table('bitacora b');
        $builder->select('
            b.*, 
            u.Nombre as nombre_usuario_real, 
            d.Nombre as departamento_nombre, 
            p.Nombre_Corto as complejo_nombre, 
            rs.Nombre as razon_social_nombre,
            s.No_Folio as solicitud_folio,
            gp.Nombre as grupo_presupuestal_nombre,
            uo.Nombre as unidad_operativa_nombre
        ');
        $builder->join('Usuarios u', 'u.ID_Usuario = b.usuario_id', 'left');
        $builder->join('Departamentos d', 'd.ID_Dpto = b.departamento_id', 'left');
        $builder->join('Places p', 'p.ID_Place = b.complejo_id', 'left');
        $builder->join('Razon_Social rs', 'rs.ID_RazonSocial = b.razon_social_id', 'left');
        $builder->join('Solicitud s', 's.ID_Solicitud = b.solicitud_id', 'left');

        // --- COMPATIBILIDAD MULTI-DB (JSON EXTRACTION) ---
        // Detectamos el driver para aplicar la sintaxis correcta de extracción de JSON
        $dbDriver = $this->db->DBDriver;
        if ($dbDriver === 'Postgre') {
            $extractGrupo  = 'CAST(b.valores_nuevos->>\'ID_GrupoPresupuestal\' AS INTEGER)';
            $extractUnidad = 'CAST(b.valores_nuevos->>\'ID_UnidadOperativa\' AS INTEGER)';
        } else {
            // Sintaxis para MySQL (MySQLi)
            $extractGrupo  = 'CAST(JSON_UNQUOTE(JSON_EXTRACT(b.valores_nuevos, "$.ID_GrupoPresupuestal")) AS UNSIGNED)';
            $extractUnidad = 'CAST(JSON_UNQUOTE(JSON_EXTRACT(b.valores_nuevos, "$.ID_UnidadOperativa")) AS UNSIGNED)';
        }

        // Joins adicionales para Presupuestos usando la extracción dinámica
        $builder->join('GrupoPresupuestal gp', "gp.ID_GrupoPresupuestal = $extractGrupo", 'left');
        $builder->join('UnidadOperativa uo', "uo.ID_UnidadOperativa = $extractUnidad", 'left');
        if (!empty($filters['usuario_id'])) {
            $builder->where('b.usuario_id', $filters['usuario_id']);
        }
        if (!empty($filters['modulo'])) {
            $builder->where('b.modulo', $filters['modulo']);
        }
        if (!empty($filters['tipo_accion'])) {
            $builder->where('b.tipo_accion', $filters['tipo_accion']);
        }
        if (!empty($filters['departamento_id'])) {
            $builder->where('b.departamento_id', $filters['departamento_id']);
        }
        if (!empty($filters['fecha_inicio'])) {
            $builder->where('b.fecha_hora >=', $filters['fecha_inicio'] . ' 00:00:00');
        }
        if (!empty($filters['fecha_fin'])) {
            $builder->where('b.fecha_hora <=', $filters['fecha_fin'] . ' 23:59:59');
        }

        // Para contar el total sin el límite
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();

        $builder->orderBy('b.id', 'DESC');
        $builder->limit($limit, $offset);

        return [
            'data' => $builder->get()->getResultArray(),
            'total' => $total
        ];
    }
    //endregion
}