<?php

namespace App\Controllers;

use App\Libraries\FPath;
use App\Models\CotizacionModel;
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\OrdenCompraModel;
use App\Models\PagoModel;
use App\Models\ProductoModel;
use App\Models\HistorialProductosModel;
use CodeIgniter\RESTful\ResourceController;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;
use App\Libraries\ImageProcessor;
use App\Libraries\SolicitudTipo;
use App\Libraries\Status;
use App\Libraries\MBSMail;
use App\Libraries\MetodoPago;
use App\Controllers\GenerarPDF;
use App\Models\ProveedorModel;
use App\Models\RazonSocialModel;
use App\Models\UsuariosModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class Api extends ResourceController
{
    protected $format = 'json';
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }

    /**
     * Lanza un error de validación y lo registra en la bitácora como fallido.
     */
    private function failValidationAudit($message, $modulo = 'API')
    {
        \CodeIgniter\Events\Events::trigger('auditoria', [
            'tipo_accion'    => 'FALLO_VALIDACION',
            'modulo'         => $modulo,
            'estado'         => 'fallido',
            'valores_nuevos' => json_encode(['mensaje' => $message, 'url' => $this->request->getUri()->getPath()])
        ]);
        return $this->failValidationErrors($message);
    }

    public function test($id)
    {
        return $this->respond($this->api->getSolicitudPago($id));
    }

    //region Productos

    /**
     * Busca productos por consulta y tipo.
     * @return \CodeIgniter\HTTP\Response
     */
    public function search()
    {
        $query = $this->request->getVar('query');
        $type = $this->request->getVar('type');

        if (empty($query)) {
            return $this->fail('La consulta no puede estar vacía.', HttpStatus::BAD_REQUEST);
        }
        $results = $this->api->getProductsByQuery($query, $type);
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene todos los productos.
     * @return \CodeIgniter\HTTP\Response
     */
    public function allProducts()
    {
        $results = $this->api->getAllProducts();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene un producto por su ID.
     * @param int|null $id El ID del producto.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getProductById($id)
    {
        $result = $this->api->getProductById($id);
        if ($result === null) {
            return $this->failNotFound('Producto no encontrado.');
        }
        return $this->respond($result, HttpStatus::OK);
    }
    //endregion

    //region Proveedores

    /**
     * Obtiene todos los proveedores con solo ID y Nombre.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getAllProviders()
    {
        $results = $this->api->getProveedorIdAndRazonSocial();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene la lista completa de proveedores con todos sus campos.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getFullProvidersList()
    {
        $proveedorModel = new ProveedorModel();
        $results = $proveedorModel->orderBy('RazonSocial', 'ASC')->findAll();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Exporta la lista de proveedores a Excel.
     */
    public function exportarProveedoresExcel()
    {
        $proveedorModel = new ProveedorModel();
        $proveedores = $proveedorModel->orderBy('RazonSocial', 'ASC')->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Proveedores');

        $headers = [
            'ID', 'Razón Social', 'Correo', 'RFC', 'Banco', 'Cuenta', 'Clabe', 
            'Tel. Contacto', 'Nombre Contacto', 'Servicio', 'Días Crédito', 'Monto Crédito'
        ];
        
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Estilo encabezado
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($proveedores as $p) {
            $sheet->setCellValue('A' . $row, $p['ID_Proveedor']);
            $sheet->setCellValue('B' . $row, $p['RazonSocial']);
            $sheet->setCellValue('C' . $row, $p['Correo']);
            $sheet->setCellValue('D' . $row, $p['RFC']);
            $sheet->setCellValue('E' . $row, $p['Banco']);
            $sheet->setCellValue('F' . $row, $p['Cuenta']);
            $sheet->setCellValue('G' . $row, $p['Clabe']);
            $sheet->setCellValue('H' . $row, $p['Tel_Contacto']);
            $sheet->setCellValue('I' . $row, $p['Nombre_Contacto']);
            $sheet->setCellValue('J' . $row, $p['Servicio']);
            $sheet->setCellValue('K' . $row, $p['Dias_Credito']);
            $sheet->setCellValue('L' . $row, $p['Monto_Credito']);
            $row++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="lista_proveedores.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Obtiene un proveedor por su ID.
     * @param int|null $id El ID del proveedor.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getProviderById(int $id)
    {
        $result = $this->api->getProveedorByID($id);
        if ($result === null) {
            return $this->failNotFound('Proveedor no encontrado.');
        }
        return $this->respond($result, HttpStatus::OK);
    }
    //endregion

    //region Departamentos

    /**
     * Obtiene todos los departamentos.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getDepartments()
    {
        $results = $this->api->getAllDepartments();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene todos los grupos presupuestales con su ID_Dpto.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getBudgetGroups()
    {
        $grupoModel = new \App\Models\GrupoPresupuestalModel();
        $dptoModel = new \App\Models\DepartamentosModel();
        
        $idDpto = session('id_departamento_usuario');
        $dpto = $dptoModel->find($idDpto);
        $idUnidad = $dpto['ID_UnidadOperativa'] ?? 0;

        $results = $grupoModel
            ->select('ID_GrupoPresupuestal, Nombre, ID_UnidadOperativa')
            ->where('ID_UnidadOperativa', $idUnidad)
            ->where('activo', true)
            ->orderBy('Nombre', 'ASC')
            ->findAll();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene todos los presupuestos mensuales.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getMonthlyBudgets()
    {
        $presupuestoMensualModel = new \App\Models\PresupuestoMensualModel();
        $results = $presupuestoMensualModel->findAll();
        return $this->respond($results, HttpStatus::OK);
    }
    //endregion

    //region Solicitudes (Consultas)

    /**
     * Obtiene todo el historial de solicitudes.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getHistorial()
    {
        $result = $this->api->getAllSolicitud();
        return $this->respond($result, HttpStatus::OK);
    }

    /**
     * Obtiene el historial de solicitudes por departamento.
     * @param int $id El ID del departamento.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getHistorialByDepartment($id)
    {
        $results = $this->api->getSolicitudByDepartment($id);
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene los detalles de una solicitud específica.
     * @param int|null $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudDetails($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de solicitud numérico.');
        }

        $details = $this->api->getSolicitudWithProducts((int) $id);

        if (empty($details)) {
            return $this->failNotFound(
                'No se encontraron detalles para la solicitud con ID: ' . $id,
            );
        }

        return $this->respond($details);
    }

    /**
     * Obtiene todas las solicitudes cotizadas.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudesCotizadas()
    {
        $results = $this->api->getSolicitudesCotizadas();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene todas las solicitudes en revisión.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudesEnRevision()
    {
        $results = $this->api->getSolicitudesEnRevision();
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene las solicitudes pendientes de aprobación para el departamento del jefe actual.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getPendientesAprobacionJefe()
    {
        if (session('login_type') !== 'boss') {
            return $this->failForbidden('Acceso denegado. Solo para jefes de departamento.');
        }

        $idDepartamento = session('id_departamento_usuario');
        $idJefe = session('id');

        $results = $this->api->getSolicitudesByStatusAndDept(
            Status::Aprobacion_pendiente,
            $idDepartamento,
            $idJefe,
        );
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene el historial completo de solicitudes con detalles de proveedores (Movimientos).
     * @return \CodeIgniter\HTTP\Response
     */
    public function getMovimientosProveedor()
    {
        $results = $this->api->getMovimientosProveedor();
        if (empty($results)) {
            return $this->respond([], HttpStatus::OK); // Return empty array if no results
        }
        return $this->respond($results, HttpStatus::OK);
    }

    /**
     * Obtiene el reporte de vencimientos para solicitudes a crédito (MetodoPago = 1).
     * @return \CodeIgniter\HTTP\Response
     */
    public function getReporteVencimientos()
    {
        try {
            $db = \Config\Database::connect();

            // Consultamos órdenes que sean a CRÉDITO (1) y que NO estén pagadas totalmente.
            // Incluimos tanto 'Espera_Programacion' como 'Por Pagar' para el reporte de vencimientos global.
            $data = $db->table('OrdenCompra')
                ->select([
                    'Solicitud.ID_Solicitud',
                    'Solicitud.No_Folio',
                    'Solicitud.MetodoPago',
                    'Solicitud.ID_RazonSocial',
                    'Solicitud.ID_Dpto',
                    'Solicitud.Fecha as FechaSolicitud',
                    'Solicitud.Fecha_Aprobacion',
                    'OrdenCompra.Fecha as FechaOrden',
                    'OrdenCompra.FechaRefPago',
                    'OrdenCompra.Estado as EstadoOrden',
                    'Departamentos.Nombre as DepartamentoNombre',
                    'Razon_Social.Nombre as Complejo',
                    'UnidadOperativa.Nombre as UnidadOperativaNombre',
                    'UnidadOperativa.ID_Place',
                    'Places.Nombre_Corto as PlaceNombre',
                    'Proveedor.ID_Proveedor',
                    'Proveedor.RazonSocial',
                    'Proveedor.RFC',
                    'Proveedor.Banco',
                    'Proveedor.Cuenta',
                    'Proveedor.Clabe',
                    'Proveedor.Nombre_Contacto',
                    'Proveedor.Tel_Contacto',
                    'Proveedor.Monto_Credito',
                    'Proveedor.Dias_Credito',
                    'Cotizacion.Total'
                ])
                ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'inner')
                ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'inner')
                ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
                ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Solicitud.ID_UnidadOperativa', 'left')
                ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
                ->where('Solicitud.MetodoPago', '1')
                ->where('OrdenCompra.Estado !=', 'Pagada')
                ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON($data);

        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $th.getMessage()
            ]);
        }
    }

    /**
     * Obtiene las solicitudes de un usuario por su ID.
     * @param int|null $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function getSolicitudesUsers($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de usuario numérico.');
        }

        return $this->respond(
            $this->api->getSolicitudesUsersByDepartment((int) $id),
            HttpStatus::OK,
        );
    }
    //endregion

    //region Solicitudes (Acciones)

    /**
     * Cancela una solicitud, cambiando su estado a 'Cancelada'.
     * Requiere el ID de la solicitud y opcionalmente comentarios del administrador.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function cancelarSolicitud()
    {
        $json = $this->request->getJSON();

        if (!isset($json->ID_Solicitud)) {
            return $this->failValidationAudit('Se requiere el ID de la solicitud.', 'Compras');
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $comentarios = $json->ComentariosAdmin ?? null;

        if (empty($comentarios)) {
            return $this->failValidationAudit('Se requiere un comentario.', 'Compras');
        }

        // 1. OBTENER USUARIO
        $session = session();
        $nombreUsuario = $session->get('nombre_usuario');
        if (empty($nombreUsuario)) {
            $nombreUsuario = 'Usuario';
        }
        $comentarioFinal = "[$nombreUsuario]: " . $comentarios;

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        $db = \Config\Database::connect();
        $db->transException(true)->transStart();

        try {
            // 1. PROTECCIÓN: No cancelar solicitudes PAGADAS
            if ($solicitud['Estado'] === Status::Pagada || $solicitud['Estado'] === 'Pagada') {
                return $this->failValidationAudit('No se puede cancelar una solicitud que ya ha sido pagada (movimiento bancario realizado).');
            }

            // 2. ACTUALIZAR LA SOLICITUD
            $updateData = [
                'Estado' => 'Cancelada',
                'ComentariosAdmin' => $comentarioFinal,
                'TipoComentarioAdmin' => 'Cancelacion',
            ];
            $solicitudModel->update($idSolicitud, $updateData);

            // 3. LIBERACIÓN INTEGRAL DE PRESUPUESTO
            $this->liberarPresupuestoIntegral($idSolicitud, $solicitud['Estado']);

            // 4. ACTUALIZAR LA ORDEN DE COMPRA
            $ordenModel = new OrdenCompraModel();

            // Usamos 'Cotizacion' en singular (como en tu base de datos)
            $orden = $ordenModel->select('OrdenCompra.ID_OrdenCompra')
                ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion')
                ->where('Cotizacion.ID_Solicitud', $idSolicitud)
                ->first();

            if ($orden) {
                $ordenModel->update($orden['ID_OrdenCompra'], ['Estado' => 'Cancelada']);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                // Esto captura el error real si falla la DB
                throw new \Exception($db->error()['message'] ?? 'Error desconocido en transacción');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Solicitud y Orden de Compra canceladas correctamente.',
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'CANCELAR_SOLICITUD',
                'modulo'         => 'Compras',
                'solicitud_id'   => $idSolicitud,
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            log_message('error', '[cancelarSolicitud] ' . $e->getMessage());
            return $this->failServerError('Error: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza los montos y comentarios de una solicitud.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function actualizarMontos()
    {
        $json = $this->request->getJSON();

        if (!isset($json->id_solicitud) || !isset($json->productos) || !is_array($json->productos)) {
            return $this->failValidationAudit('Se requiere ID de solicitud y un array de productos/servicios.', 'Compras');
        }

        $idSolicitud = (int) $json->id_solicitud;
        $itemsPayload = $json->productos;
        $comentarios = $json->comentarios ?? null;
        $idCuenta = $json->id_cuenta ?? null;

        // --- [CAMBIO 1: UNIVERSAL] ---
        // NO usamos "1" ni "'t'". Usamos el booleano nativo de PHP (true/false).
        // El "Model" de CodeIgniter se encargará de traducirlo al idioma de la base de datos que estés usando.
        $iva = filter_var($json->iva ?? 0, FILTER_VALIDATE_BOOLEAN);

        $idCotizacionSeleccionada = $json->id_cotizacion_seleccionada ?? null;

        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();

        $solicitud = $solicitudModel->find($idSolicitud);
        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        $db = \Config\Database::connect();
        $db->transException(true)->transStart();

        try {
            $updateData = [];

            // CodeIgniter detecta el driver:
            // En MySQL guardará 1. En Postgres guardará 't' o TRUE.
            $updateData['IVA'] = $iva;

            if ($idCotizacionSeleccionada) {
                $cotizacionSeleccionada = $cotizacionModel->find($idCotizacionSeleccionada);
                if ($cotizacionSeleccionada) {
                    $updateData['ID_Proveedor'] = $cotizacionSeleccionada['ID_Proveedor'];
                    // Inyectamos el ID de cotización para que el AuditTrait lo detecte
                    $updateData['ID_Cotizacion'] = (int) $idCotizacionSeleccionada;
                }
            }

            if ($idCuenta) {
                $updateData['ID_Cuenta'] = $idCuenta;
            }

            if ($comentarios) {
                $updateData['ComentariosUser'] = $comentarios;
            }

            $solicitudModel->update($idSolicitud, $updateData);

            // Actualización de productos (Lógica idéntica a la anterior)
            $esServicio = (int) $solicitud['Tipo'] === 2;
            
            // --- NUEVA LÓGICA DE SINCRONIZACIÓN DE PRESUPUESTO ---
            // Solo si ya está en un estado que afectó presupuesto
            $estadosFinancieros = ['Aprobada', 'Por Pagar', 'En Proceso de Pago', Status::Pagada, 'Pagada'];
            $afectaPresupuesto = in_array($solicitud['Estado'], $estadosFinancieros);
            $montosViejosPorGrupo = [];
            
            if ($afectaPresupuesto) {
                if ($esServicio) {
                    $itemModelPrev = new SolicitudServiciosModel();
                } else {
                    $itemModelPrev = new SolicitudProductModel();
                }

                $itemsPrevios = $itemModelPrev->where('ID_Solicitud', $idSolicitud)->findAll();
                foreach ($itemsPrevios as $pp) {
                    if ($pp['ID_GrupoPresupuestal']) {
                        $montosViejosPorGrupo[$pp['ID_GrupoPresupuestal']] = ($montosViejosPorGrupo[$pp['ID_GrupoPresupuestal']] ?? 0) + (float)$pp['Monto_Comprometido_Original'];
                    }
                }
            }

            $factorIvaActual = ($iva === true) ? 1.16 : 1.0;

            if ($esServicio) {
                $solicitudServiciosModel = new SolicitudServiciosModel();
                $solicitudItemsDB = $solicitudServiciosModel->where('ID_Solicitud', $idSolicitud)->findAll();
                foreach ($itemsPayload as $index => $item) {
                    if(isset($solicitudItemsDB[$index])) {
                        $nuevoMontoComprometido = 1 * (float)$item->importe * $factorIvaActual;

                        $updateDataServ = [
                            'Nombre' => (string) $item->nombre,
                            'Importe' => (float) $item->importe,
                            'Monto_Comprometido_Original' => $nuevoMontoComprometido
                        ];

                        if (isset($item->id_grupo_presupuestal)) {
                            $updateDataServ['ID_GrupoPresupuestal'] = (int) $item->id_grupo_presupuestal;
                        }

                        $solicitudServiciosModel->update($solicitudItemsDB[$index]['ID_SolicitudServ'], $updateDataServ);
                    }
                }
            } else {
                $solicitudProductModel = new SolicitudProductModel();
                $solicitudItemsDB = $solicitudProductModel->where('ID_Solicitud', $idSolicitud)->findAll();
                
                foreach ($itemsPayload as $index => $p) {
                    if(isset($solicitudItemsDB[$index])) {
                        $nuevoMontoComprometido = (float)$p->cantidad * (float)$p->importe * $factorIvaActual;
                        
                        $updateProd = [
                            'Codigo' => (string) $p->codigo,
                            'Nombre' => (string) $p->nombre,
                            'Cantidad' => (int) $p->cantidad,
                            'Importe' => (float) $p->importe,
                            'Monto_Comprometido_Original' => $nuevoMontoComprometido // Actualizamos el respaldo
                        ];

                        if (isset($p->id_grupo_presupuestal)) {
                            $updateProd['ID_GrupoPresupuestal'] = (int) $p->id_grupo_presupuestal;
                        }

                        $solicitudProductModel->update($solicitudItemsDB[$index]['ID_SolicitudProd'], $updateProd);
                    }
                }
            }

            // --- APLICAR AJUSTE AL PRESUPUESTO ATÓMICO Y CORRECTO ---
            if ($afectaPresupuesto) {
                if ($esServicio) {
                    $itemModelNew = new SolicitudServiciosModel();
                } else {
                    $itemModelNew = new SolicitudProductModel();
                }

                $itemsNuevos = $itemModelNew->where('ID_Solicitud', $idSolicitud)->findAll();
                
                $montosNuevosPorGrupo = [];
                foreach ($itemsNuevos as $pn) {
                    if ($pn['ID_GrupoPresupuestal']) {
                        $montosNuevosPorGrupo[$pn['ID_GrupoPresupuestal']] = ($montosNuevosPorGrupo[$pn['ID_GrupoPresupuestal']] ?? 0) + (float)$pn['Monto_Comprometido_Original'];
                    }
                }

                // USAR LA FECHA DE APROBACIÓN PARA ENCONTRAR EL PRESUPUESTO CORRECTO
                $fechaSolStr = $solicitud['Fecha_Aprobacion'] ?? $solicitud['Fecha'] ?? date('Y-m-d');
                $fechaSol = strtotime($fechaSolStr);
                $mes = (int)date('n', $fechaSol);
                $anio = (int)date('Y', $fechaSol);
                
                // USAR LA UNIDAD GUARDADA EN LA SOLICITUD (Snapshot de creación)
                $idUnidad = $solicitud['ID_UnidadOperativa'] ?? 0;

                // Comparar y ajustar por cada grupo presupuestal afectado
                $todosLosGrupos = array_unique(array_merge(array_keys($montosViejosPorGrupo), array_keys($montosNuevosPorGrupo)));
                $grupoModel = new \App\Models\GrupoPresupuestalModel();
                
                foreach ($todosLosGrupos as $idGrupo) {
                    $viejo = (float)($montosViejosPorGrupo[$idGrupo] ?? 0);
                    $nuevo = (float)($montosNuevosPorGrupo[$idGrupo] ?? 0);
                    $diferencia = $nuevo - $viejo;

                    if (abs($diferencia) > 0.0001) { 
                        // REPARACIÓN: Detección robusta de nivel Pagada (8)
                        $esPagada = (trim($solicitud['Estado']) === 'Pagada' || $solicitud['Estado'] === Status::Pagada);
                        $campoAjustar = $esPagada ? 'Monto_Ejecutado' : 'Monto_Comprometido';
                        
                        // Encontrar la Unidad Operativa del grupo para el ajuste
                        $grupoInfo = $grupoModel->find($idGrupo);
                        $idUnidadDelGrupo = $grupoInfo['ID_UnidadOperativa'] ?? 0;

                        $db->table('PresupuestoMensual')
                           ->set($campoAjustar, "\"$campoAjustar\" + ($diferencia)", false)
                           ->where([
                                'ID_UnidadOperativa' => $idUnidadDelGrupo,
                                'ID_GrupoPresupuestal' => $idGrupo,
                                'Mes' => $mes,
                                'Anio' => $anio
                           ])
                           ->update();
                        
                        log_message('debug', "Presupuesto Ajustado Atómico por Edición: Grupo $idGrupo (Unidad $idUnidadDelGrupo) - Diferencia: $diferencia en $campoAjustar");
                    }
                }
            }

            $solicitudModel->update($idSolicitud, ['ComentariosUser' => $comentarios]);

            // Recálculo de totales
            $apiLib = new \App\Libraries\Rest();
            $details = $apiLib->getSolicitudWithProducts($idSolicitud);

            $nuevoTotal = 0;
            if (!empty($details['productos'])) {
                if ($esServicio) {
                    foreach ($details['productos'] as $item) {
                        $nuevoTotal += (float) $item['Importe'];
                    }
                } else {
                    foreach ($details['productos'] as $p) {
                        $nuevoTotal += (float) $p['Cantidad'] * (float) $p['Importe'];
                    }
                }
            }

            // --- [CAMBIO 2: LÓGICA DE CÁLCULO] ---
            // Al ser $iva un booleano real (true/false), la condición es mucho más limpia.
            // Funciona igual en local y en servidor.
            if ($iva === true) {
                $nuevoTotal = $nuevoTotal * 1.16;
            }

            $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
            if ($cotizacion) {
                $cotizacionModel->update($cotizacion['ID_Cotizacion'], ['Total' => $nuevoTotal]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            // Regeneración de PDFs
            try {
                $pdfController = new \App\Controllers\GenerarPDF();
                $pdfController->generarYGuardarRequisicion($idSolicitud, 0, 1);

                $userId = session()->get('id');
                if ($userId) {
                    $pdfController->generarYGuardarOrden($idSolicitud, $userId);
                }
            } catch (\Exception $e) {
                log_message('error', 'Error PDF: ' . $e->getMessage());
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Solicitud actualizada correctamente.'
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'ACTUALIZAR_MONTOS',
                'modulo'         => 'Compras',
                'solicitud_id'   => $idSolicitud,
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            log_message('critical', '[actualizarMontos Error]: ' . $e->getMessage());
            return $this->failServerError('Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Permite a un jefe de departamento aprobar o rechazar una solicitud de un empleado.
     * @return \CodeIgniter\HTTP\Response
     */
    public function dictaminarSolicitudJefe()
    {
        if (session('login_type') !== 'boss') {
            return $this->failForbidden('Acceso denegado. Solo para jefes de departamento.');
        }

        $json = $this->request->getJSON();
        // Validamos que exista ID y acción
        if (!isset($json->ID_Solicitud) || !isset($json->accion)) {
            return $this->failValidationAudit(
                'Se requiere ID de solicitud y una acción (aprobar/rechazar).',
            );
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $accion = $json->accion;
        $comentarios = $json->comentarios ?? null;

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        // Verificaciones de seguridad
        if ($solicitud['ID_Dpto'] != session('id_departamento_usuario')) {
            return $this->failForbidden('Esta solicitud no pertenece a su departamento.');
        }

        // Verificar estado actual
        if (
            $solicitud['Estado'] !== 'Aprobacion pendiente' &&
            $solicitud['Estado'] !== Status::Aprobacion_pendiente
        ) {
            return $this->fail(
                'La solicitud ya ha sido procesada o no está pendiente de aprobación.',
                HttpStatus::BAD_REQUEST,
            );
        }

        try {
            // Si la acción es aprobar, pasa a 'En espera'.
            // Si es rechazar, pasa explícitamente a 'Rechazada'.
            $nuevoEstado =
                $accion === 'aprobar' || $accion === Status::Aprobar
                    ? Status::En_espera
                    : 'Rechazada';

            $solicitudModel->update($idSolicitud, [
                'Estado' => $nuevoEstado,
                'ComentariosAdmin' => $comentarios,
            ]);

            return $this->respondUpdated([
                'success' => true,
                'message' =>
                    'La solicitud ha sido ' .
                    ($nuevoEstado === 'Rechazada' ? 'rechazada.' : 'aprobada y enviada a Compras.'),
            ]);
        } catch (\Exception $e) {
            log_message('error', '[dictaminarSolicitudJefe] ' . $e->getMessage());
            return $this->failServerError(
                'Ocurrió un error inesperado al actualizar la solicitud.',
            );
        }
    }

    public function aprobarYCotizar()
    {
        if (session('login_type') !== 'boss') {
            return $this->failForbidden('Acceso denegado. Solo para jefes de departamento.');
        }

        $json = $this->request->getJSON();
        if (!isset($json->ID_Solicitud)) {
            return $this->failValidationAudit('Se requiere ID de solicitud.');
        }

        $idSolicitud = (int) $json->ID_Solicitud;

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['ID_Dpto'] != session('id_departamento_usuario')) {
            return $this->failForbidden('Esta solicitud no pertenece a su departamento.');
        }
        if ($solicitud['Estado'] !== Status::Aprobacion_pendiente) {
            return $this->fail('La solicitud ya ha sido procesada.', HttpStatus::BAD_REQUEST);
        }

        $db = \Config\Database::connect();
        $db->transException(true)->transStart();

        try {
            $nuevoEstado = Status::En_espera;

            // Si la solicitud es de Servicios
            if ($solicitud['Tipo'] == SolicitudTipo::Servicios) {
                $nuevoEstado = 'Cotizando';
            }

            // Actualizamos con el estado dinámico
            $solicitudModel->update($idSolicitud, ['Estado' => $nuevoEstado]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            // Mensaje dinámico según el estado resultante
            $mensajeExito =
                $nuevoEstado == 'Cotizando'
                    ? 'Solicitud aprobada y enviada a etapa de Cotización.'
                    : 'Solicitud aprobada y enviada a Compras para su cotización.';

            // Auditoría de acción relacionada a cotización
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'  => 'APROBAR_Y_COTIZAR',
                'modulo'       => 'Cotizacion',
                'solicitud_id' => $idSolicitud,
                'estado'       => 'exito',
                'valores_nuevos' => json_encode(['nuevo_estado' => $nuevoEstado, 'mensaje' => $mensajeExito])
            ]);

            return $this->respondUpdated([
                'success' => true,
                'message' => $mensajeExito,
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[aprobarYCotizar] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Crea una nueva cotización para una solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function crearCotizacion()
    {
        // Instanciar modelos
        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $razonSocialModel = new RazonSocialModel();
        $proveedorModel = new ProveedorModel();

        // 1. Obtener y Validar JSON
        $json = $this->request->getJSON();

        // Validación básica de existencia
        if (!$json) {
            return $this->fail('Invalid JSON.', HttpStatus::BAD_REQUEST);
        }

        // Validación de campos requeridos
        if (
            !isset($json->ID_Solicitud) ||
            !isset($json->ID_Proveedores) ||
            !is_array($json->ID_Proveedores) ||
            empty($json->ID_Proveedores) ||
            !isset($json->ID_Usuario)
        ) {
            return $this->failValidationAudit(
                'Se requiere ID de solicitud, un array de IDs de proveedor y el ID de usuario.',
                'Compras'
            );
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $idProveedores = $json->ID_Proveedores; // Array
        $idUsuarioCotiza = (int) $json->ID_Usuario;

        // 2. Validar Estado de la Solicitud
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        // Permitimos 'En espera' y también 'Cotizando' (para recotizaciones)
        if (
            $solicitud['Estado'] !== Status::En_espera &&
            $solicitud['Estado'] !== Status::Cotizando
        ) {
            return $this->fail(
                'La solicitud no está en estado "En espera". Estado actual: ' .
                    $solicitud['Estado'],
                HttpStatus::BAD_REQUEST,
            );
        }

        // 3. Preparar datos generales
        $details = $this->api->getSolicitudWithProducts($idSolicitud);
        $razon = $razonSocialModel->find($solicitud['ID_RazonSocial']);
        $razonNombre = $razon['Nombre'] ?? 'Empresa';

        // Calcular Total (Referencia)
        $total = 0;
        $productos = $details['productos'] ?? [];

        if (!empty($productos)) {
            foreach ($productos as $p) {
                if ($solicitud['Tipo'] != SolicitudTipo::Servicios) {
                    $total += (float) $p['Cantidad'] * (float) $p['Importe'];
                } else {
                    $total += (float) $p['Importe'];
                }
            }
        }

        // 4. Generar PDF
        try {
            $pdf = new GenerarPDF();
            $pdf->generarYGuardarRequisicion($idSolicitud);
            $attachmentPath = FPath::FPDF . 'Requisicion-MBSP-' . $idSolicitud . '.pdf';

            // Verificación opcional de existencia del archivo
            if (!file_exists($attachmentPath)) {
                log_message('error', "El PDF no se encontró en: $attachmentPath");
                // No lanzamos excepción aquí para no detener el flujo si el PDF falla, pero es ideal revisar logs.
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error al generar PDF: ' . $e->getMessage());
            return $this->failServerError('Error al generar el PDF de la requisición.');
        }

        // 5. Iniciar Transacción y Bucle de Proveedores
        $db = \Config\Database::connect();
        $db->transException(true)->transStart();

        try {
            $mail = new MBSMail();

            // --- BUCLE: Crear una cotización por cada proveedor ---
            foreach ($idProveedores as $idProveedor) {
                $idProveedor = (int) $idProveedor;
                $proveedor = $proveedorModel->find($idProveedor);

                // A. Insertar Cotización
                $cotizacionData = [
                    'ID_Solicitud' => $idSolicitud,
                    'ID_Proveedor' => $idProveedor,
                    'Total' => $total,
                    'ID_Usuario_Cotiza' => $idUsuarioCotiza,
                ];
                $cotizacionModel->insert($cotizacionData);

                // B. Enviar Correo
                // Lógica de destinatario
                $to = getenv('EMAIL_TO_TEST');
                if (empty($to)) {
                    if (!$proveedor || empty($proveedor['Correo'])) {
                        // Si el proveedor no tiene correo, lo saltamos pero NO rompemos el bucle
                        log_message(
                            'warning',
                            "Proveedor ID $idProveedor no tiene correo. Se creó la cotización pero no se envió email.",
                        );
                        continue;
                    }
                    $to = $proveedor['Correo'];
                }

                $proveedorNombre = $proveedor ? esc($proveedor['RazonSocial']) : 'Proveedor';
                $folio = esc($solicitud['No_Folio']);
                $fecha = esc($solicitud['Fecha']);
                $razonSocialEsc = esc($razonNombre);

                $subject = "Solicitud de Cotización - Folio {$folio} - {$razonSocialEsc}";

                // Construcción del HTML (Incluido aquí para evitar errores de funciones faltantes)
                $message =
                    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Solicitud de Cotización</title>';
                $message .=
                    '<style>body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f8f9fa; } .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; background-color: #ffffff; } .header { background-color: #004a99; color: #ffffff; text-align: center; padding: 15px; } .content { padding: 20px; } .footer { margin-top: 20px; font-size: 0.85em; text-align: center; color: #6c757d; }</style></head><body>';
                $message .= '<div class="container">';
                $message .= '<div class="header"><h2>Solicitud de Cotización</h2></div>';
                $message .= '<div class="content">';
                $message .= "<p>Estimado proveedor <strong>{$proveedorNombre}</strong>,</p>";
                $message .= "<p>Por medio de la presente, <strong>{$razonSocialEsc}</strong> le solicita amablemente la cotización de los productos/servicios adjuntos.</p>";
                $message .= "<ul><li><strong>Folio:</strong> {$folio}</li><li><strong>Fecha:</strong> {$fecha}</li></ul>";
                $message .= '</div>';
                $message .= "<div class=\"footer\"><p><strong>{$razonSocialEsc}</strong></p></div>";
                $message .= '</div></body></html>';

                $option = [
                    'attachments' => [$attachmentPath],
                    'fromName' => $razonNombre,
                ];

                // Enviar
                $mail->send_email($to, $subject, $message, $option);
            }

            // 6. Actualizar Estado Global de la Solicitud
            // IMPORTANTE: Ponemos ID_Proveedor en NULL porque ahora hay múltiples cotizando
            $solicitudModel->update($idSolicitud, [
                'Estado' => Status::Cotizando,
                'ID_Proveedor' => null,
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('Error en la transacción de la base de datos.');
            }

            // Auditoría de éxito
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'  => 'CREAR_COTIZACION_MASIVA',
                'modulo'       => 'Cotizacion',
                'solicitud_id' => $idSolicitud,
                'estado'       => 'exito',
                'valores_nuevos' => json_encode(['mensaje' => 'Cotizaciones enviadas a proveedores', 'data' => $json])
            ]);

            return $this->respondCreated([
                'success' => true,
                'message' => 'Cotizaciones generadas correctamente.',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'CREAR_COTIZACION',
                'modulo'         => 'Compras',
                'solicitud_id'   => $idSolicitud,
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            // ESTO ES CLAVE: Escribir el error real en el log para que puedas verlo si vuelve a fallar
            log_message(
                'critical',
                '[Error crearCotizacion]: ' .
                    $e->getMessage() .
                    ' en ' .
                    $e->getFile() .
                    ':' .
                    $e->getLine(),
            );

            return $this->failServerError('Error interno: ' . $e->getMessage());
        }
    }

    /**
     * Envía una solicitud a revisión.
     * @return \CodeIgniter\HTTP\Response
     */
    public function enviarSolicitudARevision()
    {
        $request = $this->request->getPost();
        log_message('debug', "API: enviarSolicitudARevision llamada. Datos: " . json_encode($request));

        if (!isset($request['ID_Solicitud'])) {
            return $this->failValidationAudit('Se requiere ID de solicitud.');
        }

        $idSolicitud = (int) $request['ID_Solicitud'];
        $idCotizacionSeleccionada = $request['id_cotizacion_seleccionada'] ?? null;

        // === CAPTURA DEL NUEVO CAMPO ===
        // Capturamos el valor que envía el JS. Si viene vacío, asignamos null.
        $comentarioCotizacion = $request['ComentarioCotizacion'] ?? null;
        if (empty($comentarioCotizacion)) {
            $comentarioCotizacion = null;
        }
        // ===============================

        $solicitud = $this->api->getSolicitudById($idSolicitud);
        log_message('debug', "API: Solicitud recuperada: " . json_encode($solicitud));
        
        $cotizacionModel = new CotizacionModel();
        $tipoPago = MetodoPago::EnEspera;

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        // Validar estado
        if ($solicitud['Estado'] !== Status::Cotizando) {
            log_message('debug', "API: Solicitud $idSolicitud no está en estado Cotizando. Estado actual: " . $solicitud['Estado']);
            return $this->fail('La solicitud no está en estado "Cotizado".', HttpStatus::BAD_REQUEST);
        }

        switch ($request['tipo_pago'] ?? '') {
            case 'efectivo': $tipoPago = MetodoPago::Efectivo; break;
            case 'credito': $tipoPago = MetodoPago::Credito; break;
        }

        try {
            $idProveedorGanador = null;

            // Lógica de selección de proveedor (Igual que tu código original)
            if ($idCotizacionSeleccionada) {
                $cotizacionGanadora = $cotizacionModel->find($idCotizacionSeleccionada);
                if ($cotizacionGanadora) {
                    $idProveedorGanador = $cotizacionGanadora['ID_Proveedor'];
                    // Eliminar las no seleccionadas
                    $cotizacionModel->where('ID_Solicitud', $idSolicitud)
                        ->where('ID_Cotizacion !=', $idCotizacionSeleccionada)
                        ->delete();
                }
            } else {
        // Caso único proveedor
        $cotizacionUnica = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();

        if ($cotizacionUnica) {
            $idProveedorGanador = $cotizacionUnica['ID_Proveedor'];
            $idCotizacionSeleccionada = $cotizacionUnica['ID_Cotizacion'];
        } else if (!empty($solicitud['ID_Proveedor'])) {
            // EL PARCHE 2: Red de seguridad para solicitudes sin tabla Cotizacion
            $idProveedorGanador = $solicitud['ID_Proveedor'];
            $idCotizacionSeleccionada = null;
        }
    }

            if (!$idProveedorGanador) {
                return $this->failNotFound('No se pudo identificar el proveedor ganador.');
            }

            // === VALIDACIÓN Y ACTUALIZACIÓN DE PARTIDAS PRESUPUESTALES ===
            $esServicio = (int)$solicitud['Tipo'] === (int)SolicitudTipo::Servicios;
            if ($esServicio) {
                $itemModel = new SolicitudServiciosModel();
                $primaryKey = 'ID_SolicitudServ';
            } else {
                $itemModel = new SolicitudProductModel();
                $primaryKey = 'ID_SolicitudProd';
            }

            $itemsActuales = $itemModel->where('ID_Solicitud', $idSolicitud)->findAll();
            $nuevosGrupos = $request['id_grupo_presupuestal'] ?? [];

            foreach ($itemsActuales as $item) {
                $idItem = $item[$primaryKey];
                
                // Actualizar solo si viene un nuevo valor en el request
                if (isset($nuevosGrupos[$idItem]) && !empty($nuevosGrupos[$idItem])) {
                    $itemModel->update((int)$idItem, [
                        'ID_GrupoPresupuestal' => (int)$nuevosGrupos[$idItem]
                    ]);
                }
            }

            // === ACTUALIZAR LA SOLICITUD ===
            $this->api->updateSolicitudById($idSolicitud, [
                'Estado'               => 'En revision',
                'MetodoPago'           => $tipoPago,
                'ID_Proveedor'         => $idProveedorGanador,
                'ComentarioCotizacion' => $comentarioCotizacion, // <--- CAMPO AGREGADO
            ]);

            // === PROCESAMIENTO DE ARCHIVOS ===
            $files = $this->request->getFiles();
            $safeDate = explode(' ', $solicitud['Fecha'])[0];
            $folder = FPath::FCOTIZACION . $safeDate;

            if ($files && isset($files['cotizacion_files'])) {
                $idCotizacion = $idCotizacionSeleccionada;

                // CORRECCIÓN: Evitar el error si $cot es nulo
                if (!$idCotizacion) {
                    $cot = $this->api->getCotizacionBySolicitudID($idSolicitud);
                    $idCotizacion = $cot ? $cot['ID_Cotizacion'] : null;
                }

                // SI SIGUE SIENDO NULL, CREAMOS EL REGISTRO DE COTIZACIÓN
                if (!$idCotizacion && $idProveedorGanador) {
                    $idCotizacion = $cotizacionModel->insert([
                        'ID_Solicitud' => $idSolicitud,
                        'ID_Proveedor' => $idProveedorGanador,
                        'Total' => 0, // Se puede actualizar después
                        'ID_Usuario_Cotiza' => session('id') ?? 1
                    ]);
                }

                $tmp = [];
                $count = 0;
                $archivosAProcesar = is_array($files['cotizacion_files']) ? $files['cotizacion_files'] : [$files['cotizacion_files']];

                foreach ($archivosAProcesar as $file) {
                    // Usamos un identificador único (uniqid) para evitar colisiones de archivos
                    $baseFileName = 'cotizacion_' . ($idCotizacion ?? 'directa') . '_' . $safeDate . '_' . $count++ . '_' . uniqid();
                    $savedFileName = ImageProcessor::processAndSave($file, $folder, $baseFileName);
                    if ($savedFileName) {
                        $tmp[] = $savedFileName;
                    }
                }

                // SI SE ENVIARON ARCHIVOS PERO NINGUNO SE PUDO GUARDAR
                if (empty($tmp) && !empty($archivosAProcesar)) {
                    // Verificamos el primer archivo para ver si el error fue por tamaño
                    $primerArchivo = $archivosAProcesar[0];
                    if ($primerArchivo && !$primerArchivo->isValid()) {
                         return $this->fail('No se pudo guardar el archivo: ' . $primerArchivo->getErrorString() . '. Verifique que el archivo no exceda los 2MB.', HttpStatus::BAD_REQUEST);
                    }
                    return $this->fail('No se pudieron procesar los archivos adjuntos.', HttpStatus::BAD_REQUEST);
                }

                // CORRECCIÓN: Solo actualizamos la tabla cotizaciones si realmente tenemos un ID válido
                if (!empty($tmp) && $idCotizacion) {
                    $cfls = ['Cotizacion_Files' => implode(',', $tmp)];
                    $this->api->updateCotizacionById((int)$idCotizacion, $cfls);
                }
            }

            return $this->respondUpdated(['success' => true, 'message' => 'Solicitud enviada a revisión.']);

        } catch (\Exception $e) {
            log_message('error', '[enviarSolicitudARevision] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado.');
        }
    }

    /**
     * Mueve los montos de una solicitud de 'Comprometido' a 'Ejecutado'.
     * @param int $idSolicitud
     */
    private function actualizarPresupuestoEjecucion($idSolicitud)
    {
        $db = \Config\Database::connect();
        try {
            $solicitudModel = new SolicitudModel();
            $solicitud = $solicitudModel->find($idSolicitud);

            if (!$solicitud) {
                return;
            }

            // --- PROTECCIÓN: Evitar doble ejecución si ya estaba en un estado final ---
            $ordenModel = new OrdenCompraModel();
            $cotModel = new CotizacionModel();
            $cot = $cotModel->where('ID_Solicitud', $idSolicitud)->first();
            if ($cot) {
                $ordenExistente = $ordenModel->where('ID_Cotizacion', $cot['ID_Cotizacion'])->first();
                if ($ordenExistente && in_array($ordenExistente['Estado'], [Status::Por_Pagar, Status::Pagada, 'Por Pagar', 'Pagada'])) {
                    log_message('debug', "Presupuesto: Solicitud $idSolicitud ya ejecutada anteriormente (Estado: {$ordenExistente['Estado']}). Saltando.");
                    return;
                }
            }

            $esServicio = (int)$solicitud['Tipo'] === (int)SolicitudTipo::Servicios;
            if ($esServicio) {
                $itemModel = new SolicitudServiciosModel();
            } else {
                $itemModel = new SolicitudProductModel();
            }

            $items = $itemModel->where('ID_Solicitud', $idSolicitud)->findAll();
            
            $montosADescontar = []; 
            $montosAEjecutar = [];   

            $ivaValue = $solicitud['IVA'] ?? false;
            $ivaHabilitado = ($ivaValue === 't' || $ivaValue === '1' || $ivaValue === 1 || $ivaValue === true);
            $factorIVA = $ivaHabilitado ? 1.16 : 1.0;

            foreach ($items as $p) {
                $idGrupo = $p['ID_GrupoPresupuestal'];
                if (!$idGrupo) continue;

                $original = (float)($p['Monto_Comprometido_Original'] ?? 0);
                $montosADescontar[$idGrupo] = ($montosADescontar[$idGrupo] ?? 0) + $original;

                $cantidad = (float)($p['Cantidad'] ?? 1);
                $actual = $cantidad * (float)$p['Importe'] * $factorIVA;
                $montosAEjecutar[$idGrupo] = ($montosAEjecutar[$idGrupo] ?? 0) + $actual;
            }

            // USAR LA FECHA DE APROBACIÓN PARA ENCONTRAR EL PRESUPUESTO CORRECTO
            $fechaSolStr = $solicitud['Fecha_Aprobacion'] ?? $solicitud['Fecha'] ?? date('Y-m-d');
            $fechaSol = strtotime($fechaSolStr);
            $mes = (int)date('n', $fechaSol);
            $anio = (int)date('Y', $fechaSol);
            $idUnidad = $solicitud['ID_UnidadOperativa'] ?? 0;

            $todosLosGrupos = array_unique(array_merge(array_keys($montosADescontar), array_keys($montosAEjecutar)));
            $grupoModel = new \App\Models\GrupoPresupuestalModel();

            $db->transStart();
            foreach ($todosLosGrupos as $idGrupo) {
                if (!$idGrupo) continue;

                $montoRestar = (float)($montosADescontar[$idGrupo] ?? 0);
                $montoSumar = (float)($montosAEjecutar[$idGrupo] ?? 0);

                // Encontrar la Unidad Operativa a la que pertenece este grupo (camino inverso)
                $grupoInfo = $grupoModel->find($idGrupo);
                $idUnidadDelGrupo = $grupoInfo['ID_UnidadOperativa'] ?? 0;

                $db->table('PresupuestoMensual')
                   ->set('Monto_Comprometido', "GREATEST(0, \"Monto_Comprometido\" - $montoRestar)", false)
                   ->set('Monto_Ejecutado', "\"Monto_Ejecutado\" + $montoSumar", false)
                   ->where([
                        'ID_UnidadOperativa' => $idUnidadDelGrupo,
                        'ID_GrupoPresupuestal' => $idGrupo,
                        'Mes' => $mes,
                        'Anio' => $anio
                   ])
                   ->update();
            }
            $db->transComplete();

        } catch (\Exception $e) {
            if ($db->transStatus() === false) $db->transRollback();
            log_message('error', "Error al actualizar presupuesto ejecutado: " . $e->getMessage());
        }
    }

    /**
     * Libera el presupuesto (Comprometido o Ejecutado) según el estado de la solicitud al cancelar.
     * @param int $idSolicitud
     * @param string $estadoAnterior
     */
    private function liberarPresupuestoIntegral($idSolicitud, $estadoAnterior)
    {
        $db = \Config\Database::connect();
        try {
            $solicitudModel = new SolicitudModel();
            $solicitud = $solicitudModel->find($idSolicitud);

            if (!$solicitud) {
                return;
            }

            // Definir qué campos afectar según el estado en que estaba la solicitud
            $estadosComprometidos = ['Aprobada', 'En espera', 'Cotizando', 'En revision', 'Por Pagar', 'En Proceso de Pago', Status::En_espera, Status::Cotizando, Status::En_Revision, Status::Por_Pagar, Status::En_Proceso_Pago];
            $esEjecutado = ($estadoAnterior === 'Pagada' || $estadoAnterior === Status::Pagada);
            $esComprometido = in_array($estadoAnterior, $estadosComprometidos);

            if (!$esComprometido && !$esEjecutado) {
                return; // Estaba en un estado inicial que no bloqueaba dinero
            }

            $esServicio = (int)$solicitud['Tipo'] === (int)SolicitudTipo::Servicios;
            if ($esServicio) {
                $itemModel = new SolicitudServiciosModel();
            } else {
                $itemModel = new SolicitudProductModel();
            }

            $items = $itemModel->where('ID_Solicitud', $idSolicitud)->findAll();

            $montosPorGrupo = [];
            foreach ($items as $p) {
                $idGrupo = $p['ID_GrupoPresupuestal'];
                if (!$idGrupo) continue;

                $montoAReversar = (float)($p['Monto_Comprometido_Original'] ?? 0);
                $montosPorGrupo[$idGrupo] = ($montosPorGrupo[$idGrupo] ?? 0) + $montoAReversar;
            }

            // USAR LA FECHA DE APROBACIÓN PARA ENCONTRAR EL PRESUPUESTO CORRECTO
            $fechaSolStr = $solicitud['Fecha_Aprobacion'] ?? $solicitud['Fecha'] ?? date('Y-m-d');
            $fechaSol = strtotime($fechaSolStr);
            $mes = (int)date('n', $fechaSol);
            $anio = (int)date('Y', $fechaSol);
            $idUnidad = $solicitud['ID_UnidadOperativa'] ?? 0;

            $db->transStart();
            $grupoModel = new \App\Models\GrupoPresupuestalModel();

            foreach ($montosPorGrupo as $idGrupo => $monto) {
                $campo = $esEjecutado ? 'Monto_Ejecutado' : 'Monto_Comprometido';

                // Encontrar la Unidad Operativa a la que pertenece este grupo (camino inverso)
                $grupoInfo = $grupoModel->find($idGrupo);
                $idUnidadDelGrupo = $grupoInfo['ID_UnidadOperativa'] ?? 0;

                $db->table('PresupuestoMensual')
                   ->set($campo, "GREATEST(0, \"$campo\" - $monto)", false)
                   ->where([
                        'ID_UnidadOperativa' => $idUnidadDelGrupo,
                        'ID_GrupoPresupuestal' => $idGrupo,
                        'Mes' => $mes,
                        'Anio' => $anio
                   ])
                   ->update();
                
                log_message('debug', "Presupuesto Liberado Atómico: Grupo $idGrupo (Unidad $idUnidadDelGrupo) - Monto: $monto ($campo)");
            }
            $db->transComplete();

        } catch (\Exception $e) {
            if ($db->transStatus() === false) $db->transRollback();
            log_message('error', '[liberarPresupuestoIntegral] ' . $e->getMessage());
        }
    }

    /**
     * Dictamina una solicitud (aprueba o rechaza).
     * @return \CodeIgniter\HTTP\Response
     */
    public function dictaminarSolicitud()
    {
        $json = $this->request->getJSON();

        if (!isset($json->ID_Solicitud) || !isset($json->Estado)) {
            return $this->failValidationAudit('Se requiere ID de solicitud y el nuevo estado.');
        }

        $idSolicitud = (int) $json->ID_Solicitud;
        $nuevoEstado = (string) $json->Estado;
        $comentarios = $json->ComentariosAdmin ?? null;

        if (!in_array($nuevoEstado, ['Aprobada', 'Rechazada'])) {
            return $this->fail('El estado proporcionado no es válido.', HttpStatus::BAD_REQUEST);
        }
        if ($nuevoEstado === Status::Rechazada && empty(trim((string) $comentarios))) {
            return $this->fail(
                'Para rechazar una solicitud, los comentarios son obligatorios.',
                HttpStatus::BAD_REQUEST,
            );
        }

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }
        if ($solicitud['Estado'] !== Status::En_Revision) {
            return $this->fail(
                'La solicitud no está en estado "En revision".',
                HttpStatus::BAD_REQUEST,
            );
        }

        try {
            $db = \Config\Database::connect();
            $db->transStart();

            $dataToUpdate = ['Estado' => $nuevoEstado, 'ComentariosAdmin' => $comentarios];
            if ($nuevoEstado === Status::Rechazada) {
                $dataToUpdate['TipoComentarioAdmin'] = 'Rechazo';
            } elseif ($nuevoEstado === Status::Aprobada && !empty(trim((string) $comentarios))) {
                $dataToUpdate['TipoComentarioAdmin'] = 'Observacion';
            }

            if ($nuevoEstado === Status::Aprobada) {
                $fechaAprobacionActual = date('Y-m-d H:i:s');
                $dataToUpdate['Fecha_Aprobacion'] = $fechaAprobacionActual;
                $dataToUpdate['ID_Usuario_Autoriza'] = session('id');

                // === LÓGICA DE PRESUPUESTO COMPROMETIDO ATÓMICA ===
                $ivaValue = $solicitud['IVA'] ?? false;
                $ivaHabilitado = ($ivaValue === 't' || $ivaValue === '1' || $ivaValue === 1 || $ivaValue === true);
                $factorIVA = $ivaHabilitado ? 1.16 : 1.0;
                $esServicio = (int)$solicitud['Tipo'] === (int)SolicitudTipo::Servicios;

                if ($esServicio) {
                    $itemModel = new SolicitudServiciosModel();
                } else {
                    $itemModel = new SolicitudProductModel();
                }

                $presupuestoModel = new \App\Models\PresupuestoMensualModel();
                $items = $itemModel->where('ID_Solicitud', $idSolicitud)->findAll();
                
                $montosPorGrupo = [];
                foreach ($items as $p) {
                    $idGrupo = $p['ID_GrupoPresupuestal'] ?? null;
                    if (!$idGrupo) continue;

                    $cantidad = (float)($p['Cantidad'] ?? 1);
                    $montoItem = $cantidad * (float)$p['Importe'] * $factorIVA;
                    $montosPorGrupo[$idGrupo] = ($montosPorGrupo[$idGrupo] ?? 0) + $montoItem;
                }

                if (!empty($montosPorGrupo)) {
                    // --- USAR EL MES Y AÑO DE LA APROBACIÓN, NO DE LA SOLICITUD ---
                    $mes = (int)date('n');
                    $anio = (int)date('Y');
                    
                    $grupoModel = new \App\Models\GrupoPresupuestalModel();

                    // --- VALIDACIÓN DE PRESUPUESTO EXISTENTE Y ASIGNADO ---
                    foreach ($montosPorGrupo as $idGrupo => $montoAComprometer) {
                        $grupoInfo = $grupoModel->find($idGrupo);
                        $idUnidadDelGrupo = $grupoInfo['ID_UnidadOperativa'] ?? 0;

                        $presupuesto = $presupuestoModel->where([
                            'ID_UnidadOperativa' => $idUnidadDelGrupo,
                            'ID_GrupoPresupuestal' => $idGrupo,
                            'Mes' => $mes,
                            'Anio' => $anio
                        ])->first();

                        if (!$presupuesto || (float)$presupuesto['Monto_Asignado'] <= 0) {
                            $db->transRollback(); // Revertimos antes de salir
                            $nombreGrupo = $grupoInfo ? $grupoInfo['Nombre'] : "ID $idGrupo";
                            return $this->respond([
                                'success' => false,
                                'message' => "No se puede aprobar la solicitud porque la partida presupuestal '$nombreGrupo' no tiene presupuesto mensual asignado para " . date('F Y') . "."
                            ]);
                        }
                    }

                    // Si la validación pasa, guardamos los respaldos y aplicamos el compromiso
                    foreach ($items as $p) {
                        $idGrupo = $p['ID_GrupoPresupuestal'] ?? null;
                        if (!$idGrupo) continue;
                        
                        $cantidad = (float)($p['Cantidad'] ?? 1);
                        $montoItem = $cantidad * (float)$p['Importe'] * $factorIVA;
                        
                        $primaryKey = $esServicio ? 'ID_SolicitudServ' : 'ID_SolicitudProd';
                        $itemModel->update($p[$primaryKey], [
                            'Monto_Comprometido_Original' => $montoItem
                        ]);
                    }

                    foreach ($montosPorGrupo as $idGrupo => $montoAComprometer) {
                        $grupoInfo = $grupoModel->find($idGrupo);
                        $idUnidadDelGrupo = $grupoInfo['ID_UnidadOperativa'] ?? 0;

                        $presupuesto = $presupuestoModel->where([
                            'ID_UnidadOperativa' => $idUnidadDelGrupo,
                            'ID_GrupoPresupuestal' => $idGrupo,
                            'Mes' => $mes,
                            'Anio' => $anio
                        ])->first();

                        if ($presupuesto) {
                            $db->table('PresupuestoMensual')
                               ->set('Monto_Comprometido', "\"Monto_Comprometido\" + $montoAComprometer", false)
                               ->where('ID_PresupuestoMensual', $presupuesto['ID_PresupuestoMensual'])
                               ->update();
                        }
                    }
                }
            }

            $solicitudModel->update($idSolicitud, $dataToUpdate);
            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error al completar la transacción de dictamen.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'El dictamen de la solicitud se ha guardado correctamente.',
            ]);
        } catch (\Exception $e) {
            if (isset($db) && $db->transStatus() === false) $db->transRollback();
            log_message('error', '[dictaminarSolicitud] ' . $e->getMessage());

            return $this->failServerError('Ocurrió un error inesperado al guardar el dictamen: ' . $e->getMessage());
        }
    }

    //endregion

    //region Cotizaciones

    /**
     * Obtiene los detalles de una cotizacion específica.
     * @param int|null $id El ID de la cotizacion.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getCotizacionDetails($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de cotizacion numérico.');
        }

        $details = $this->api->getSolicitudWithCotizacion((int) $id);

        if (empty($details)) {
            return $this->failNotFound(
                'No se encontraron detalles para la cotizacion con ID: ' . $id,
            );
        }

        return $this->respond($details);
    }
    //endregion

    //region Ordenes de Compra

    /**
     * Genera una nueva Orden de Compra a partir de una solicitud aprobada.
     * @param int $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function GenerarOrden($id)
    {
        if (!is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de solicitud numérico.');
        }

        $solicitudModel = new SolicitudModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();

        $solicitud = $solicitudModel->find($id);

        if (!$solicitud) {
            return $this->failNotFound('La solicitud no existe.');
        }

        if ($solicitud['Estado'] !== Status::Aprobada) {
            return $this->fail(
                'Solo se puede generar una orden de compra para solicitudes aprobadas. Estado actual: ' .
                    $solicitud['Estado'],
                HttpStatus::BAD_REQUEST,
            );
        }

        $cotizacion = $cotizacionModel->where('ID_Solicitud', $id)->first();
        if (!$cotizacion) {
            return $this->failNotFound(
                'No se encontró una cotización asociada a esta solicitud para obtener los datos del proveedor y el total.',
            );
        }

        $db = \Config\Database::connect();
        $db->transException(true)->transStart();

        try {
            $ordenData = [
                'ID_Cotizacion' => $cotizacion['ID_Cotizacion'],
                'ID_Proveedor' => $cotizacion['ID_Proveedor'],
                'Estado' => Status::Por_Pagar,
                'Fecha' => date('Y-m-d'),
            ];

            $ordenCompraModel->insert($ordenData);
            $idOrdenCompra = $ordenCompraModel->getInsertID();

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', '[GenerarOrden] Falla en la transacción de base de datos.');
                return $this->failServerError(
                    'No se pudo completar la transacción para generar la orden.',
                );
            }

            // Registro en Bitácora
            Events::trigger('auditoria', [
                'tipo_accion' => 'GENERAR_ORDEN',
                'clasificacion' => 'Compras',
                'modulo'      => 'Compras',
                'solicitud_id'=> $id,
                'orden_compra_id' => $idOrdenCompra,
                'cotizacion_id'   => $cotizacion['ID_Cotizacion'] ?? null,
                'estado'      => 'exito'
            ]);

            return $this->respondCreated([
                'success' => true,
                'message' => 'Orden de Compra generada exitosamente.',
                'id_orden_compra' => $idOrdenCompra,
            ]);
        } catch (\Exception $e) {
            log_message('error', '[GenerarOrden] ' . $e->getMessage());

            // Registro de fallo en Bitácora
            Events::trigger('auditoria', [
                'tipo_accion' => 'GENERAR_ORDEN',
                'clasificacion' => 'Compras',
                'modulo'      => 'Compras',
                'solicitud_id'=> $id ?? null,
                'estado'      => 'fallido',
                'valores_nuevos' => ['error' => $e->getMessage()]
            ]);

            return $this->failServerError(
                'Ocurrió un error inesperado al generar la Orden de Compra.',
            );
        }
    }

    /**
     * Obtiene los datos de una orden de compra, incluyendo información de la cotización y la solicitud asociada.
     *
     * @param int|null $id El ID de la Orden de Compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenCompraData($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de orden de compra numérico.');
        }

        $data = $this->api->getOrdenCompraData((int) $id);

        if (empty($data)) {
            return $this->failNotFound(
                'No se encontraron datos para la orden de compra con ID: ' . $id,
            );
        }

        return $this->respond($data);
    }

    /**
     * Obtiene una orden de compra por el ID de la solicitud.
     *
     * @param int|null $id El ID de la solicitud.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenBySolicitudID($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de solicitud numérico.');
        }

        $data = $this->api->getOrdenByIDSolicitud((int) $id);

        if (empty($data)) {
            return $this->failNotFound(
                'No se encontraron datos para la orden de compra con ID de solicitud: ' . $id,
            );
        }

        return $this->respond($data);
    }

    /**
     * Obtiene todas las órdenes de compra con su información asociada.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function getAllOrdenCompraData()
    {
        $data = $this->api->getAllOrdenCompraData();

        if (empty($data)) {
            return $this->failNotFound('No se encontraron órdenes de compra.');
        }

        return $this->respond($data);
    }

    /**
     * Obtiene los detalles de una orden de compra específica.
     * @param int|null $id El ID de la solicitud para la orden de compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenCompra($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            return $this->failValidationAudit('Se requiere un ID de solicitud numérico.');
        }

        $details = $this->api->getOrdenCompra((int) $id);

        if (empty($details)) {
            return $this->failNotFound(
                'No se encontraron detalles para la orden de compra con ID de solicitud: ' . $id,
            );
        }

        return $this->respond($details);
    }

    /**
     * Obtiene los detalles necesarios para no saturar el servidor
     * @param int|null $id El ID de la solicitud para la orden de compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getOrdenesParaProgramacion()
    {
        try {
            $db = \Config\Database::connect();

            $data = $db->table('OrdenCompra')
                ->select([
                    'Solicitud.ID_Solicitud',
                    'Solicitud.No_Folio',
                    'Solicitud.MetodoPago',
                    'Solicitud.Fecha', // Mantenemos esta por si se usa en la vista HTML

                    // --- NUEVOS CAMPOS AGREGADOS ---
                    'OrdenCompra.Fecha as FechaOrden',
                    'OrdenCompra.FechaRefPago',
                    // -------------------------------

                    'OrdenCompra.Estado as EstadoOrden',
                    'Departamentos.Nombre as DepartamentoNombre',
                    'Razon_Social.Nombre as Complejo',
                    'Proveedor.RazonSocial',
                    'Proveedor.Banco',
                    'Proveedor.Monto_Credito',
                    'Proveedor.Dias_Credito',
                    'Cotizacion.Total'
                ])
                // 1. Unimos Orden con Cotización (Pivote principal)
                ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'inner')

                // 2. Unimos Cotización con Solicitud (De aquí sacamos el ID del proveedor)
                ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'inner')

                // 3. CORRECCIÓN: Unimos Proveedor usando la SOLICITUD (como hace tu Rest.php)
                ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')

                // 4. Resto de tablas informativas
                ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')

                ->where('OrdenCompra.Estado', 'Espera_Programacion')
                ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON($data);

        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    public function getFacturasPorPagar()
    {
        try {
            $db = \Config\Database::connect();

            $data = $db->table('OrdenCompra')
                ->select([
                    'Solicitud.ID_Solicitud',
                    'Solicitud.No_Folio',
                    'Solicitud.MetodoPago',
                    // Necesitamos Fecha_Aprobacion para el semáforo.
                    // Si no existe esa columna exacta, usa 'OrdenCompra.Fecha' o la que uses para calcular vencimiento.
                    'Solicitud.Fecha_Aprobacion',
                    'OrdenCompra.Estado as EstadoOrden',
                    'Departamentos.Nombre as DepartamentoNombre',
                    'Razon_Social.Nombre as Complejo',

                    // Datos Proveedor
                    'Proveedor.RazonSocial',
                    'Proveedor.Banco',
                    'Proveedor.Dias_Credito', // Importante para calcular vencimiento

                    // Datos Cotización
                    'Cotizacion.Total'
                ])
                ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'inner')
                ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'inner')
                ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
                ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')

                // FILTRO CLAVE: Solo las que están listas para pagar
                ->where('OrdenCompra.Estado', 'Por Pagar')
                ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON($data);

        } catch (\Throwable $th) {
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    public function getOrdenesCompraPendientesRecepcion()
    {
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $proveedorModel = new ProveedorModel();

        $ordenesPendientes = $ordenCompraModel
            ->whereIn('Estado', [Status::Por_Pagar, Status::En_Proceso_Pago])
            ->findAll();

        $formattedOrdenes = [];
        foreach ($ordenesPendientes as $orden) {
            $cotizacion = $cotizacionModel->find($orden['ID_Cotizacion']);
            if (!$cotizacion) {
                continue;
            }

            $solicitud = $solicitudModel->find($cotizacion['ID_Solicitud']);
            if (!$solicitud) {
                continue;
            }

            $proveedor = $proveedorModel->find($orden['ID_Proveedor']);

            $formattedOrdenes[] = [
                'ID_OrdenCompra' => $orden['ID_OrdenCompra'],
                'ID_Solicitud' => $cotizacion['ID_Solicitud'],
                'No_Folio' => $solicitud['No_Folio'],
                'ProveedorNombre' => $proveedor['RazonSocial'] ?? 'N/A',
                'Total' => $cotizacion['Total'],
                'MetodoPago' => $solicitud['MetodoPago'],
            ];
        }

        return $this->respond($formattedOrdenes, HttpStatus::OK);
    }

    /**
     * Cambia el estado de una orden de compra y opcionalmente sube un archivo de factura y/o un comprobante.
     *
     * @param int $idSolicitud El ID de la solicitud asociada a la orden de compra.
     * @return \CodeIgniter\HTTP\Response
     */
    public function cambiarEstadoOrden($idSolicitud)
    {
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();
        $solicitudModel = new SolicitudModel();
        $proveedorModel = new proveedorModel();

        $nuevoEstado = null;

        if ($this->request->is('json')) {
            $json = $this->request->getJSON();
            $nuevoEstado = $json->nuevoEstado ?? null;
        } else {
            $nuevoEstado = $this->request->getPost('nuevoEstado');
        }

        $facturaFile = $this->request->getFile('factura');
        $comprobanteFile = $this->request->getFile('ficha');

        if (empty($nuevoEstado) && !$facturaFile && !$comprobanteFile) {
            return $this->failValidationAudit(
                'No se especificó un nuevo estado ni se adjuntaron archivos.',
            );
        }

        $cot = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
        if (!$cot) {
            return $this->failNotFound('Cotización no encontrada para la solicitud.');
        }

        $orden = $ordenCompraModel->where('ID_Cotizacion', $cot['ID_Cotizacion'])->first();
        if (!$orden) {
            return $this->failNotFound('Orden no encontrada.');
        }

        $solicitud = $solicitudModel->find($cot['ID_Solicitud']);
        if (!$solicitud) {
            return $this->failNotFound('Solicitud no encontrada.');
        }

        try {
            $db = \Config\Database::connect();
            $db->transException(true)->transStart();
            $idCotizacion = $cot['ID_Cotizacion'];
            $idOrdenCompra = $orden['ID_OrdenCompra'];
            $idProveedor = $cot['ID_Proveedor'];
            $randomString = uniqid();
            $proveedor = $proveedorModel->find($idProveedor); // Ensure $proveedor model is loaded

            if ($facturaFile && $facturaFile->isValid()) {
                $baseFileName = "Factura-{$idSolicitud}-{$idCotizacion}-{$idOrdenCompra}-{$idProveedor}-{$randomString}";
                $savedFile = ImageProcessor::processAndSave(
                    $facturaFile,
                    FPath::FFACTURAS,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, ['File_Factura' => $savedFile]);
                } else {
                    return $this->failServerError('No se pudo guardar el archivo de la factura.');
                }
            }

            if ($comprobanteFile && $comprobanteFile->isValid()) {
                $baseFileName = "Ficha-{$idSolicitud}-{$idCotizacion}-{$idOrdenCompra}-{$idProveedor}-{$randomString}";
                $comprobanteFileName = ImageProcessor::processAndSave(
                    // Changed $savedFile to $comprobanteFileName
                    $comprobanteFile,
                    FPath::FCOMPROBANTES,
                    $baseFileName,
                );
                log_message('info', $comprobanteFileName);
                if ($comprobanteFileName) {
                    // Changed $savedFile to $comprobanteFileName
                    $ordenCompraModel->update($idOrdenCompra, [
                        'File_Comprobante' => $comprobanteFileName,
                    ]);
                    $comprobanteSavedPath = FPath::FCOMPROBANTES . $comprobanteFileName; // Set full path
                } else {
                    return $this->failServerError('No se pudo guardar el archivo del comprobante.');
                }
            }

            if (!empty($nuevoEstado)) {
                if ($nuevoEstado === Status::Por_Pagar) {
                    $orden = $this->api->getOrdenCompra($idSolicitud);
                    $mail = new MBSMail();

                    $proveedorData = null;
                    if (isset($orden['cotizacion']['ID_Proveedor'])) {
                        $proveedorModel = new ProveedorModel();
                        $proveedorData = $proveedorModel->find(
                            $orden['cotizacion']['ID_Proveedor'],
                        );
                    }

                    $to = getenv('EMAIL_TO_TEST');
                    if (empty($to)) {
                        if (!$proveedorData || empty($proveedorData['Correo'])) {
                            throw new \Exception(
                                'No se pudo encontrar un correo electrónico para el proveedor.',
                            );
                        }
                        $to = $proveedorData['Correo'];
                    }
                    $subject =
                        'Comprobante de Pago - Folio ' . // Changed subject
                        ($orden['No_Folio'] ?? $idSolicitud);
                    $message = view('emails/notificacion_pago', [
                        'folio' => $orden['No_Folio'] ?? $idSolicitud,
                        'proveedor' => $proveedorData['RazonSocial'] ?? 'N/A',
                        'total' => number_format($orden['cotizacion']['Total'] ?? 0, 2),
                        'razonSocial' => $orden['Complejo'] ?? 'N/A',
                    ]);

                    $options = [];
                    if (!empty($orden['OrdenCompra']['File_Comprobante'])) {
                        $attachmentPath =
                            FPath::FCOMPROBANTES . $orden['OrdenCompra']['File_Comprobante'];
                        if (file_exists($attachmentPath)) {
                            $options['attachments'] = [$attachmentPath];
                        } else {
                            log_message(
                                'error',
                                "El archivo adjunto para el correo 'Por Pagar' no se encontró: " .
                                    $attachmentPath,
                            );
                        }
                    }
                    log_message(
                        'info',
                        'Opciones de adjunto para correo Por Pagar: ' . print_r($options, true),
                    );

                    $mail->send_email($to, $subject, $message, $options);
                    log_message(
                        'info',
                        "Correo de comprobante de pago enviado a {$to} para solicitud {$idSolicitud}.",
                    );
                }

                if ($nuevoEstado === 'Pagada') {
                    // === ACTUALIZAR PRESUPUESTO (COMPROMETIDO -> EJECUTADO) ===
                    $this->actualizarPresupuestoEjecucion($idSolicitud);

                    $ordenActualizada = $ordenCompraModel->find($idOrdenCompra);

                    $missingFiles = [];
                    if (empty($ordenActualizada['File_Factura'])) {
                        $missingFiles[] = 'Factura';
                    }
                    if (empty($ordenActualizada['File_Comprobante'])) {
                        $missingFiles[] = 'Ficha de pago';
                    }

                    if (!empty($missingFiles)) {
                        return $this->failValidationAudit(
                            'No se puede cerrar la orden de compra. Faltan los siguientes archivos: ' .
                                implode(' y ', $missingFiles) .
                                '.',
                        );
                    }

                    $solicitudModel = new SolicitudModel();
                    $proveedorModel = new ProveedorModel();
                    $razonSocialModel = new RazonSocialModel();

                    $solicitud = $solicitudModel->find($idSolicitud);
                    $proveedor = $proveedorModel->find($ordenActualizada['ID_Proveedor']);
                    $razon = $razonSocialModel->find($solicitud['ID_RazonSocial']);

                    if (!$solicitud || !$proveedor || !$razon) {
                        throw new \Exception('Datos insuficientes para enviar la ficha de pago.');
                    }

                    $attachmentPath = FPath::FCOMPROBANTES . $ordenActualizada['File_Comprobante'];

                    if (!file_exists($attachmentPath)) {
                        throw new \Exception(
                            'El archivo de la ficha de pago no se encontró en la ruta esperada: ' .
                                $attachmentPath,
                        );
                    }

                    $mail = new MBSMail();

                    $subject = "Ficha de Pago - Solicitud Folio {$solicitud['No_Folio']}";

                    $totalAPagar = '$' . number_format($cot['Total'], 2);
                    $proveedorNombre = esc($proveedor['RazonSocial'] ?? 'Proveedor');
                    $folio = esc($solicitud['No_Folio']);
                    $razonNombre = esc($razon['Nombre']);

                    $toProveedor = getenv('EMAIL_TO_TEST') ?: $proveedor['Correo'] ?? null;
                    if ($toProveedor) {
                        $messageProveedor = view('emails/ficha_pago', [
                            'recipientName' => $proveedorNombre,
                            'folio' => $folio,
                            'totalAPagar' => $totalAPagar,
                            'proveedorNombre' => $proveedorNombre,
                            'razonNombre' => $razonNombre,
                        ]);
                        $mail->send_email($toProveedor, $subject, $messageProveedor, [
                            'attachments' => [$attachmentPath],
                            'fromName' => $razonNombre,
                        ]);
                    } else {
                        log_message(
                            'warning',
                            'No se pudo enviar ficha de pago al proveedor (correo no disponible).',
                        );
                    }

                    $ccCompras = getenv('EMAIL_TO_COMPRAS');
                    if ($ccCompras) {
                        $messageCompras = view('emails/ficha_pago', [
                            'recipientName' => 'Departamento de Compras',
                            'folio' => $folio,
                            'totalAPagar' => $totalAPagar,
                            'proveedorNombre' => $proveedorNombre,
                            'razonNombre' => $razonNombre,
                        ]);
                        $mail->send_email($ccCompras, $subject, $messageCompras, [
                            'attachments' => [$attachmentPath],
                            'fromName' => $razonNombre,
                            'isHtml' => true,
                        ]);
                    } else {
                        log_message(
                            'warning',
                            'No se pudo enviar ficha de pago a Compras (correo no configurado).',
                        );
                    }

                    $ccTesoreria = getenv('EMAIL_TO_TESORERIA');
                    if ($ccTesoreria) {
                        $messageTesoreria = view('emails/ficha_pago', [
                            'recipientName' => 'Departamento de Tesorería',
                            'folio' => $folio,
                            'totalAPagar' => $totalAPagar,
                            'proveedorNombre' => $proveedorNombre,
                            'razonNombre' => $razonNombre,
                        ]);
                        $mail->send_email($ccTesoreria, $subject, $messageTesoreria, [
                            'attachments' => [$attachmentPath],
                            'fromName' => $razonNombre,
                            'isHtml' => true,
                        ]);
                    } else {
                        log_message(
                            'warning',
                            'No se pudo enviar ficha de pago a Tesorería (correo no configurado).',
                        );
                    }

                    if ($solicitud['Tipo'] == SolicitudTipo::Servicios) {
                        $pdfGenerator = new \App\Controllers\GenerarPDF();
                        $pdfPath = $pdfGenerator->GenerarFacturaServicioPDF(
                            $solicitud['ID_Solicitud'],
                        );

                        if (empty($pdfPath)) {
                            log_message(
                                'error',
                                'Error al generar factura de servicio para la solicitud ID: ' .
                                    $solicitud['ID_Solicitud'],
                            );
                        } else {
                            $ordenCompraModel->update($idOrdenCompra, [
                                'File_FacturaServicioPDF' => basename($pdfPath),
                            ]);
                        }
                    }
                }

                // PREPARAMOS LOS DATOS A ACTUALIZAR
                $datosActualizar = [
                    'Estado' => $nuevoEstado,
                ];

                // Si el estado que recibimos es el del botón ("Por Pagar"), inyectamos la fecha actual
                if ($nuevoEstado === 'Por Pagar' || (class_exists('Status') && $nuevoEstado === Status::Por_Pagar)) {
                    $datosActualizar['FechaPagoRealizado'] = date('Y-m-d H:i:s');
                    
                    // === ACTUALIZAR PRESUPUESTO (COMPROMETIDO -> EJECUTADO) ===
                    $this->actualizarPresupuestoEjecucion($idSolicitud);
                }

                // EJECUTAMOS EL UPDATE
                $updateResult = $ordenCompraModel->update($idOrdenCompra, $datosActualizar);

                if ($updateResult === false) {
                    $errors = $ordenCompraModel->errors();
                    $errorMessage = $errors
                        ? implode(', ', $errors)
                        : 'La actualización del estado falló.';
                    throw new \Exception($errorMessage);
                }
            }

            $db->transComplete();
            return $this->respondUpdated([
                'success' => true,
                'message' => 'Operación completada exitosamente.',
                'nuevoEstado' => $nuevoEstado ?? $orden['Estado'],
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'FALLO_CAMBIO_ESTADO_ORDEN',
                'modulo'         => 'Compras',
                'solicitud_id'   => $idSolicitud,
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            log_message('error', '[cambiarEstadoOrden - Simple] ' . $e->getMessage());
            return $this->failServerError('Error: ' . $e->getMessage());
        }
    }

    /**
     * Función específica para generar la OC, enviarla por correo y cambiar su estado.
     * Llamada solo desde la vista de 'ordenes_compra'.
     */
    public function enviarOrdenAProveedor($idSolicitud = null, $userid = null)
    {
        if ($idSolicitud === null) {
            return $this->failValidationAudit('Se requiere un ID de solicitud.');
        }
        if ($userid === null) {
            return $this->failValidationAudit('Se requiere un ID.');
        }

        $solicitudModel = new SolicitudModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $proveedorModel = new ProveedorModel();
        $razonSocialModel = new RazonSocialModel();

        $db = \Config\Database::connect();
        $db->transException(true)->transStart();

        try {
            $solicitud = $solicitudModel->find($idSolicitud);
            if (!$solicitud) {
                throw new \Exception('Solicitud no encontrada.');
            }

            if ($solicitud['Estado'] !== Status::Aprobada) {
                throw new \Exception('Solo se puede enviar una orden desde el estado "Aprobada".');
            }

            $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
            if (!$cotizacion) {
                throw new \Exception('No se encontró una cotización asociada a esta solicitud.');
            }

            $proveedor = $proveedorModel->find($cotizacion['ID_Proveedor']);
            $razon = $razonSocialModel->find($solicitud['ID_RazonSocial']);
            $razonNombre = $razon['Nombre'];

            $ordenData = [
                'ID_Cotizacion' => $cotizacion['ID_Cotizacion'],
                'ID_Proveedor' => $cotizacion['ID_Proveedor'],
                'Estado' => Status::Espera_Programacion,
                'Fecha' => date('Y-m-d'),
            ];

            $ordenCompraModel->insert($ordenData);

            $pdf = new GenerarPDF();
            $pdfPath = $pdf->generarYGuardarOrden($idSolicitud, $userid);
            if (empty($pdfPath)) {
                throw new \Exception('No se pudo generar o guardar el PDF de la Orden de Compra.');
            }

            //Solicitud tipo 2 (servicio) no se ejecuta
            if ($solicitud['Tipo'] != 2) {
                $to = getenv('EMAIL_TO_TEST');
                if (empty($to)) {
                    if (!$proveedor || empty($proveedor['Correo'])) {
                        throw new \Exception(
                            "No se pudo encontrar un correo electrónico para el proveedor con ID: {$cotizacion['ID_Proveedor']}.",
                        );
                    }
                    $to = $proveedor['Correo'];
                }
                $proveedorNombre = esc($proveedor['RazonSocial'] ?? 'Proveedor');
                $folio = esc($solicitud['No_Folio']);

                $subject = "Nueva Orden de Compra - {$razonNombre} - Folio {$folio}";

                $message = '';
                $message .=
                    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nueva Orden de Compra</title>';
                $message .= '<style>';
                $message .=
                    'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f8f9fa; }';
                $message .=
                    '.container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #ffffff; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }';
                $message .=
                    '.header { padding: 15px 20px; background-color: #004a99; color: #ffffff; text-align: center; border-radius: 8px 8px 0 0; }';
                $message .= '.header h2 { margin: 0; font-size: 24px; }';
                $message .= '.content { padding: 25px 20px; }';
                $message .= '.content p { margin: 0 0 15px; }';
                $message .=
                    '.content ul { list-style: none; padding: 0; margin: 15px 0; border-left: 3px solid #004a99; padding-left: 15px; }';
                $message .= '.content li { margin-bottom: 8px; }';
                $message .=
                    '.footer { margin-top: 20px; padding: 15px 20px; font-size: 0.85em; color: #6c757d; text-align: center; background-color: #f4f4f4; border-radius: 0 0 8px 8px; }';
                $message .= '</style></head><body>';
                $message .= '<div class="container">';
                $message .= '<div class="header"><h2>Nueva Orden de Compra</h2></div>';
                $message .= '<div class="content">';
                $message .= "<p>Estimado proveedor <strong>{$proveedorNombre}</strong>,</p>";
                $message .= "<p>Por medio de la presente, <strong>{$razonNombre}</strong> se complace en enviarle la Orden de Compra correspondiente a su cotización.</p>";
                $message .= '<p><strong>Detalles de la Orden:</strong></p>';
                $message .=
                    "<ul><li><strong>Folio de Requisición:</strong> {$folio}</li><li><strong>Fecha de Orden:</strong> " .
                    date('d/m/Y') .
                    '</li></ul>';
                $message .=
                    '<p>En el documento PDF adjunto encontrará todos los detalles de los productos/servicios solicitados, así como los términos y condiciones aplicables.</p>';
                $message .=
                    '<p>Agradecemos su colaboración y quedamos a la espera de la confirmación de recibido. Para cualquier consulta, no dude en contactar a nuestro departamento de compras.</p>';
                $message .= '<p>Saludos cordiales,</p>';
                $message .= '</div>';
                $message .= "<div class=\"footer\"><p><strong>Departamento de Compras</strong><br>{$razonNombre}</p></div>";
                $message .= '</div></body></html>';

                $option = [
                    'attachments' => [$pdfPath],
                    'fromName' => $razonNombre,
                ];

                $mail = new MBSMail();
                $mail->send_email($to, $subject, $message, $option);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Orden Creada, estado actualizado y correo enviado.',
                'nuevoEstado' => Status::Espera_Programacion,
            ]);
        } catch (\Exception $e) {
            log_message('error', '[enviarOrdenAProveedor] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado: ' . $e->getMessage());
        }
    }
    public function enviarATesoreria()
    {
        $this->response->setHeader('Content-Type', 'application/json');
        $data = $this->request->getJSON(true);

        if (empty($data['ID_Solicitud'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se proporcionó el ID de la solicitud.',
            ]);
        }

        $solicitudModel = new SolicitudModel();
        $id = $data['ID_Solicitud'];

        $solicitud = $solicitudModel->find($id);

        if (!$solicitud) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Solicitud no encontrada.',
            ]);
        }

        $solicitudModel->update($id, ['Estado' => 'En Proceso de Pago']);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Solicitud enviada a Tesorería con éxito.',
        ]);
    }

    //endregion

    //region Limpiar Almacenamiento

    /**
     * Obtiene el contenido de una carpeta en el almacenamiento.
     * @return \CodeIgniter\HTTP\Response
     */
    public function getStorageList()
    {
        $path = $this->request->getGet('path') ?? '';

        try {
            $results = $this->api->getStorageContent($path);
            return $this->respond($results, HttpStatus::OK);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), HttpStatus::BAD_REQUEST);
        }
    }

    /**
     * Sirve un archivo desde el almacenamiento para previsualización.
     * Versión ultra-robusta: funciona incluso si 'fileinfo' está desactivado en PHP.
     * @return \CodeIgniter\HTTP\Response|\CodeIgniter\HTTP\DownloadResponse
     */
    public function serveFile()
    {
        $relativePath = urldecode($this->request->getGet('path') ?? '');
        $basePath = realpath(WRITEPATH . 'uploads');

        $potentialPath =
            $basePath .
            DIRECTORY_SEPARATOR .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $fullPath = realpath($potentialPath);

        if (!$fullPath || !is_file($fullPath) || strpos($fullPath, $basePath) !== 0) {
            return $this->failNotFound('Archivo no encontrado o acceso denegado.');
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $disallowedExtensions = ['html', 'htm', 'js', 'css'];

        if (in_array($ext, $disallowedExtensions)) {
            return $this->failForbidden('Acceso denegado a este tipo de archivo.');
        }

        $mime = false;

        if (function_exists('mime_content_type')) {
            $mime = @\mime_content_type($fullPath);
        }

        if (!$mime) {
            $mimeMap = [
                'pdf' => 'application/pdf',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'txt' => 'text/plain',
                'xml' => 'application/xml',
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
            ];
            $mime = $mimeMap[$ext] ?? 'application/octet-stream';
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
            ->setHeader('Content-Length', (string) filesize($fullPath))
            ->setBody(file_get_contents($fullPath));
    }
    //endregion

    //Obtener todas las razones sociales
    public function getAllRazonSocial()
    {
        $db = \Config\Database::connect();
        // Usamos los nombres exactos del modelo RazonSocialModel
        $data = $db->table('Razon_Social')
            ->select('ID_RazonSocial, Nombre, RFC, Nombre_Comercial, Direccion') // Incluimos campos nuevos
            ->get()
            ->getResultArray();

        return $this->respond($data);
    }

    /**
     * Actualiza los datos de un usuario utilizando su correo electrónico como identificador.
     * Espera un JSON con "email" y "data".
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function updateUser()
    {
        $json = $this->request->getJSON();

        if (!isset($json->email) || !is_string($json->email)) {
            return $this->failValidationAudit('Se requiere un correo electrónico válido.');
        }

        if (!isset($json->data) || !is_object($json->data)) {
            return $this->failValidationAudit(
                'Se requiere un objeto "data" con los campos a actualizar.',
            );
        }

        $email = $json->email;
        $data = (array) $json->data;

        $userModel = new UsuariosModel();
        $user = $userModel->where('Correo', $email)->first();

        if (!$user) {
            return $this->fail(
                'No se pudo actualizar el usuario. Usuario no encontrado.',
                HttpStatus::BAD_REQUEST,
            );
        }

        $dataToUpdate = [];

        if (isset($data['username'])) {
            if (empty($data['username'])) {
                return $this->failValidationAudit('El nombre de usuario no puede estar vacío.');
            }
            if ($data['username'] !== $user['Nombre']) {
                $dataToUpdate['Nombre'] = $data['username'];
            }
        }

        if (isset($data['password'])) {
            if (empty($data['password'])) {
                return $this->failValidationAudit('La nueva contraseña no puede estar vacía.');
            }
            if (strlen($data['password']) < 8) {
                return $this->failValidationAudit(
                    'La contraseña debe tener al menos 8 caracteres.',
                );
            }
            if (
                !isset($data['old_password']) ||
                !password_verify($data['old_password'], $user['ContrasenaP'])
            ) {
                return $this->fail(
                    'La contraseña anterior es incorrecta.',
                    HttpStatus::BAD_REQUEST,
                );
            }
            $dataToUpdate['ContrasenaP'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['password_g'])) {
            if (empty($data['password_g'])) {
                return $this->failValidationAudit(
                    'La nueva contraseña auxiliar no puede estar vacía.',
                );
            }
            if (strlen($data['password_g']) < 8) {
                return $this->failValidationAudit(
                    'La contraseña auxiliar debe tener al menos 8 caracteres.',
                );
            }
            if (
                !isset($data['user_password']) ||
                !password_verify($data['user_password'], $user['ContrasenaP'])
            ) {
                return $this->fail(
                    'La contraseña de usuario principal es incorrecta.',
                    HttpStatus::BAD_REQUEST,
                );
            }
            $dataToUpdate['ContrasenaG'] = password_hash($data['password_g'], PASSWORD_DEFAULT);
        }

        if (empty($dataToUpdate)) {
            return $this->respond(
                ['success' => false, 'message' => 'No hay cambios para realizar.'],
                HttpStatus::OK,
            );
        }

        $result = $this->api->updateUserByEmail($email, $dataToUpdate);

        if ($result) {
            return $this->respondUpdated([
                'success' => true,
                'message' =>
                    'Usuario actualizado correctamente. Recargar página para ver los cambios.',
            ]);
        } else {
            return $this->fail(
                'No se pudo actualizar el usuario. Verifique los datos proporcionados.',
                HttpStatus::BAD_REQUEST,
            );
        }
    }

    public function upload_signature()
    {
        $userId = session('id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $file = $this->request->getFile('signature');

        if (!$file || !$file->isValid()) {
            return $this->failValidationAudit(
                'No se ha subido ningún archivo o el archivo no es válido.',
            );
        }

        $result = $this->api->save_signature($userId, $file);

        if ($result['success']) {
            return $this->respond(['success' => true, 'message' => $result['message']]);
        } else {
            return $this->fail($result['message'], HttpStatus::BAD_REQUEST);
        }
    }

    public function downloadAttachmentsAsZip($idSolicitud = null)
    {
        if ($idSolicitud === null || !is_numeric($idSolicitud)) {
            return $this->failValidationAudit('Se requiere un ID de solicitud numérico.');
        }

        $solicitudData = $this->api->getOrdenCompra((int) $idSolicitud);

        if (empty($solicitudData)) {
            return $this->failNotFound('No se encontraron datos para la ID: ' . $idSolicitud);
        }

        $filePaths = [];
        $basePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;
        $folio = $solicitudData['No_Folio'] ?? null;

        // NOTA: Agregamos prefijos (01_, 02_, etc.) a las llaves del array.
        // Ese será el nombre que tendrá el archivo DENTRO del ZIP.

        // ---------------------------------------------------------
        // 1. REQUISICIÓN (PDF GENERADO) -> 1_
        // ---------------------------------------------------------
        if (!empty($folio)) {
            $reqName = 'Requisicion-' . $folio . '.pdf';
            $reqPath = 'pdf_solicitudes' . DIRECTORY_SEPARATOR . $reqName;
            if (file_exists($basePath . $reqPath)) {
                $filePaths['1_' . $reqName] = $basePath . $reqPath;
            }
        }

        // ---------------------------------------------------------
        // 1.1 REQUISICIÓN ORIGINAL (ARCHIVOS SUBIDOS) -> 2_ o 2.x_
        // ---------------------------------------------------------
        if (!empty($solicitudData['Archivo']) && !empty($solicitudData['Fecha'])) {
            $fechaCarpeta = date('Y-m-d', strtotime($solicitudData['Fecha']));
            $refFiles = array_filter(explode(',', $solicitudData['Archivo']));
            $totalRef = count($refFiles);

            foreach ($refFiles as $index => $file) {
                $trimmedFile = trim($file);
                if (empty($trimmedFile)) continue;

                $refPath = 'solicitud' . DIRECTORY_SEPARATOR . $fechaCarpeta . DIRECTORY_SEPARATOR . $trimmedFile;

                if (file_exists($basePath . $refPath)) {
                    $prefix = ($totalRef > 1) ? '2.' . ($index + 1) . '_' : '2_';
                    $filePaths[$prefix . $trimmedFile] = $basePath . $refPath;
                }
            }
        }

        // ---------------------------------------------------------
        // 2. COTIZACIÓN -> 3_ o 3.x_
        // ---------------------------------------------------------
        $archivosCotizacionString = null;
        if (!empty($solicitudData['cotizacion']) && is_array($solicitudData['cotizacion']) && !empty($solicitudData['cotizacion']['Cotizacion_Files'])) {
            $archivosCotizacionString = $solicitudData['cotizacion']['Cotizacion_Files'];
        } elseif (!empty($solicitudData['Cotizacion_Files'])) {
            $archivosCotizacionString = $solicitudData['Cotizacion_Files'];
        }

        if (!empty($archivosCotizacionString) && !empty($solicitudData['Fecha'])) {
            $fechaCarpeta = date('Y-m-d', strtotime($solicitudData['Fecha']));
            $cotFiles = array_filter(explode(',', $archivosCotizacionString));
            $totalCot = count($cotFiles);

            foreach ($cotFiles as $index => $file) {
                $trimmedFile = trim($file);
                if (empty($trimmedFile)) continue;

                $cotizacionPath = 'cotizaciones' . DIRECTORY_SEPARATOR . $fechaCarpeta . DIRECTORY_SEPARATOR . $trimmedFile;

                if (file_exists($basePath . $cotizacionPath)) {
                    $prefix = ($totalCot > 1) ? '3.' . ($index + 1) . '_' : '3_';
                    $filePaths[$prefix . $trimmedFile] = $basePath . $cotizacionPath;
                }
            }
        }

        // ---------------------------------------------------------
        // 3. ORDEN DE COMPRA -> 4_
        // ---------------------------------------------------------
        if (!empty($folio) && !empty($solicitudData['OrdenCompra'])) {
            $ocName = 'OrdenCompra-' . $folio . '.pdf';
            $ocPath = 'pdf_ordenes' . DIRECTORY_SEPARATOR . $ocName;
            
            if (!file_exists($basePath . $ocPath)) {
                $generador = new GenerarPDF();
                $generador->generarYGuardarOrden((int)$idSolicitud, session('id'));
            }

            if (file_exists($basePath . $ocPath)) {
                $filePaths['4_' . $ocName] = $basePath . $ocPath;
            }
        }

        // ---------------------------------------------------------
        // 4. REQUISICIÓN DE PAGO -> 5_
        // ---------------------------------------------------------
        if (!empty($folio) && !empty($solicitudData['OrdenCompra'])) {
            $reqPagoName = 'RequisicionPago-' . $folio . '.pdf';
            $reqPagoPath = 'pdf_req_pago' . DIRECTORY_SEPARATOR . $reqPagoName;

            if (!file_exists($basePath . $reqPagoPath)) {
                $generador = new GenerarPDF();
                $generador->generarYGuardarRequisicionPago((int)$idSolicitud);
            }

            if (file_exists($basePath . $reqPagoPath)) {
                $filePaths['5_' . $reqPagoName] = $basePath . $reqPagoPath;
            }
        }

        // ---------------------------------------------------------
        // 5. FICHA DE PAGO -> 6_
        // ---------------------------------------------------------
        $compName = null;
        if (!empty($solicitudData['OrdenCompra']['File_Comprobante'])) {
            $compName = $solicitudData['OrdenCompra']['File_Comprobante'];
        } elseif (!empty($solicitudData['File_Comprobante'])) {
            $compName = $solicitudData['File_Comprobante'];
        }

        if ($compName) {
            $compPath = 'comprobantes' . DIRECTORY_SEPARATOR . $compName;
            if (file_exists($basePath . $compPath)) {
                $filePaths['6_FichaPago-' . $compName] = $basePath . $compPath;
            }
        }

        // ---------------------------------------------------------
        // 6. FACTURA -> 7_
        // ---------------------------------------------------------
        $facName = null;
        if (!empty($solicitudData['OrdenCompra']['File_Factura'])) {
            $facName = $solicitudData['OrdenCompra']['File_Factura'];
        } elseif (!empty($solicitudData['File_Factura'])) {
            $facName = $solicitudData['File_Factura'];
        }

        if ($facName) {
            $facPath = 'facturas' . DIRECTORY_SEPARATOR . $facName;
            if (file_exists($basePath . $facPath)) {
                $filePaths['7_' . $facName] = $basePath . $facPath;
            }
        }

        // ---------------------------------------------------------
        // 6. COMPLEMENTO DE PAGO -> 8_
        // ---------------------------------------------------------
        $complementoName = null;
        if (!empty($solicitudData['OrdenCompra']['File_Complemento'])) {
            $complementoName = $solicitudData['OrdenCompra']['File_Complemento'];
        } elseif (!empty($solicitudData['File_Complemento'])) {
            $complementoName = $solicitudData['File_Complemento'];
        }

        if ($complementoName) {
            $complementoPath = 'complementos' . DIRECTORY_SEPARATOR . $complementoName;
            if (file_exists($basePath . $complementoPath)) {
                $filePaths['8_' . $complementoName] = $basePath . $complementoPath;
            }
        }

        // ---------------------------------------------------------
        // GENERAR ZIP
        // ---------------------------------------------------------
        if (empty($filePaths)) {
            return $this->failNotFound('No se encontraron archivos adjuntos físicos.');
        }

        $zip = new \ZipArchive();
        $zipFileName = ($folio ?? $idSolicitud) . '.zip';
        $tempDir = WRITEPATH . 'temp';
        if (!is_dir($tempDir)) mkdir($tempDir, 0775, true);

        $zipTempPath = $tempDir . DIRECTORY_SEPARATOR . $zipFileName;

        if ($zip->open($zipTempPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return $this->failServerError('Error creando ZIP.');
        }

        foreach ($filePaths as $zipName => $realPath) {
            // $zipName ya trae el prefijo (01_, 02_...)
            $zip->addFile($realPath, $zipName);
        }
        $zip->close();

        if (file_exists($zipTempPath)) {
            $zipContent = file_get_contents($zipTempPath);
            unlink($zipTempPath);

            return $this->response
                ->setBody($zipContent)
                ->setHeader('Content-Type', 'application/zip')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFileName . '"')
                ->setHeader('Content-Length', (string) strlen($zipContent));
        }

        return $this->failServerError('Error al procesar ZIP final.');
    }

    public function exportarRequisiciones()
    {
        $solicitudModel = new SolicitudModel();
        $solicitudes = $solicitudModel
            ->select(
                'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
            )
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->where('Solicitud.Estado', 'En espera')
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'No. Folio');
        $sheet->setCellValue('B1', 'Usuario');
        $sheet->setCellValue('C1', 'Departamento');
        $sheet->setCellValue('D1', 'Fecha');
        $sheet->setCellValue('E1', 'Estado');

        $row = 2;
        foreach ($solicitudes as $solicitud) {
            $sheet->setCellValue('A' . $row, $solicitud['No_Folio']);
            $sheet->setCellValue('B' . $row, $solicitud['UsuarioNombre']);
            $sheet->setCellValue('C' . $row, $solicitud['DepartamentoNombre']);
            $sheet->setCellValue('D' . $row, $solicitud['Fecha']);
            $sheet->setCellValue('E' . $row, $solicitud['Estado']);
            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        $filename = 'requisiciones_pendientes.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function exportarHistorial()
    {
        try {
            // 1. Parámetros de Filtro
            $fecha = $this->request->getGet('fecha');
            $porMes = $this->request->getGet('por_mes');
            $estadoFiltro = $this->request->getGet('estado');
            $dptoRaw = $this->request->getGet('dpto');

            // 2. Seguridad de Sesión
            $sessionDeptoFull = session('departamento_usuario') ?? '';
            $exceptions = ['Compras', 'Administración', 'Direccion', 'Tesoreria', 'Direccion Campus', 'Contaduría'];
            $sessionDeptoClean = trim(explode('(', $sessionDeptoFull)[0]);

            $db = \Config\Database::connect();
            $solicitudModel = new SolicitudModel();

            // 3. Consulta Principal (Incluye el Total de Cotización como MontoOficial)
            $builder = $solicitudModel
                ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as PlaceNombre, Razon_Social.Nombre as EmpresaNombre, Proveedor.RazonSocial as ProveedorNombre, Cotizacion.Total as MontoOficial')
                ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
                ->join('Proveedor', 'Proveedor.ID_Proveedor = Solicitud.ID_Proveedor', 'left')
                ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left');

            // Filtro Multi-Departamento
            if (!in_array($sessionDeptoClean, $exceptions) && !empty($sessionDeptoClean)) {
                $builder->where('Departamentos.Nombre', $sessionDeptoClean);
            } elseif (!empty($dptoRaw)) {
                $seleccionados = explode(',', $dptoRaw);
                $builder->groupStart();
                foreach ($seleccionados as $index => $item) {
                    $parts = explode('|', $item);
                    if ($index === 0) {
                        $builder->where('Departamentos.Nombre', $parts[0]);
                        if(isset($parts[1])) $builder->where('Places.Nombre_Corto', $parts[1]);
                    } else {
                        $builder->orGroupStart()->where('Departamentos.Nombre', $parts[0]);
                        if(isset($parts[1])) $builder->where('Places.Nombre_Corto', $parts[1]);
                        $builder->groupEnd();
                    }
                }
                $builder->groupEnd();
            }

            if ($fecha) {
                if ($porMes) {
                    $builder->where("to_char(Solicitud.Fecha, 'YYYY-MM')", substr($fecha, 0, 7));
                } else {
                    $builder->where('Solicitud.Fecha', $fecha);
                }
            }

            $solicitudes = $builder->orderBy('Solicitud.ID_Solicitud', 'DESC')->findAll();
            if (empty($solicitudes)) exit("No hay datos.");

            $solicitudIds = array_column($solicitudes, 'ID_Solicitud');

            // 4. Carga de Productos y Servicios (Para Respaldo de Monto y Nombres)
            $prodModel = new SolicitudProductModel();
            $servModel = new SolicitudServiciosModel();
            $productosRaw = $prodModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();
            $serviciosRaw = $servModel->whereIn('ID_Solicitud', $solicitudIds)->findAll();

            $conceptosMap = [];
            $respaldoMap = [];
            foreach ($productosRaw as $p) {
                $conceptosMap[$p['ID_Solicitud']][] = $p['Nombre'];
                $respaldoMap[$p['ID_Solicitud']] = ($respaldoMap[$p['ID_Solicitud']] ?? 0) + (float)$p['Importe'];
            }
            foreach ($serviciosRaw as $s) {
                $conceptosMap[$s['ID_Solicitud']][] = $s['Nombre'];
                $respaldoMap[$s['ID_Solicitud']] = ($respaldoMap[$s['ID_Solicitud']] ?? 0) + (float)$s['Importe'];
            }

            // 5. Estados de Orden de Compra
            $estadosOCMap = [];
            $ocQuery = $db->table('OrdenCompra oc')
                ->select('c.ID_Solicitud, oc.Estado as EstadoOC')
                ->join('Cotizacion c', 'c.ID_Cotizacion = oc.ID_Cotizacion', 'inner')
                ->whereIn('c.ID_Solicitud', $solicitudIds)->get()->getResultArray();
            foreach ($ocQuery as $row) { $estadosOCMap[$row['ID_Solicitud']] = $row['EstadoOC']; }

            // 6. Generar Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Estructura de Columnas (Folio a Costo)
            $headers = ['Folio', 'Fecha', 'Razón Social', 'Sede', 'Departamento', 'Estado', 'M. Pago', 'Proveedor', 'Productos / Servicios', 'Costo'];
            $sheet->fromArray($headers, NULL, 'A1');

            // Estilo: Gris Claro (FFD3D3D3)
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1:J1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');
            $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row = 2;
            $montoFinalGlobal = 0;

            foreach ($solicitudes as $sol) {
                $idSol = $sol['ID_Solicitud'];
                $estadoFinal = $estadosOCMap[$idSol] ?? $sol['Estado'];

                if ($estadoFiltro && $estadoFinal !== $estadoFiltro) continue;

                // Mapeo Método Pago
                $txtPago = 'N/A';
                switch ((int)$sol['MetodoPago']) {
                    case MetodoPago::Efectivo: $txtPago = 'Contado'; break;
                    case MetodoPago::Credito:  $txtPago = 'Crédito'; break;
                    case MetodoPago::EnEspera: $txtPago = 'En Espera'; break;
                }

                // Lógica de Monto (Cotización > Suma de Items)
                $montoFila = (float)($sol['MontoOficial'] ?? 0);
                if ($montoFila <= 0) {
                    $montoFila = (float)($respaldoMap[$idSol] ?? 0);
                }

                $txtConceptos = isset($conceptosMap[$idSol]) ? implode(', ', $conceptosMap[$idSol]) : 'N/A';

                $sheet->setCellValue('A' . $row, $sol['No_Folio']);
                $sheet->setCellValue('B' . $row, $sol['Fecha']);
                $sheet->setCellValue('C' . $row, $sol['EmpresaNombre'] ?? 'MB Signature Properties');
                $sheet->setCellValue('D' . $row, $sol['PlaceNombre']);
                $sheet->setCellValue('E' . $row, $sol['DepartamentoNombre']);
                $sheet->setCellValue('F' . $row, $estadoFinal);
                $sheet->setCellValue('G' . $row, $txtPago);
                $sheet->setCellValue('H' . $row, $sol['ProveedorNombre'] ?? 'N/A');
                $sheet->setCellValue('I' . $row, $txtConceptos);
                $sheet->setCellValue('J' . $row, $montoFila);

                $montoFinalGlobal += $montoFila;
                $row++;
            }

            // Fila de Monto Final
            $row++;
            $sheet->setCellValue('I' . $row, 'Monto Final');
            $sheet->setCellValue('J' . $row, $montoFinalGlobal);
            $sheet->getStyle('I' . $row . ':J' . $row)->getFont()->setBold(true);

            foreach (range('A', 'J') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

            // Nombre con fecha actual
            $filename = "historial_requisiciones_" . date('d-m-Y') . ".xlsx";

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            $writer->save('php://output');
            exit();

        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setBody("Error: " . $e->getMessage());
        }
    }

    public function getAllPagos()
    {
        $pagoModel = new PagoModel();
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $solicitudModel = new SolicitudModel();
        $proveedorModel = new ProveedorModel();

        $pagos = $pagoModel->findAll();

        $formattedPagos = [];
        foreach ($pagos as $pago) {
            $ordenCompra = $ordenCompraModel->find($pago['ID_OrdenCompra']);
            if (!$ordenCompra) {
                continue;
            }

            $cotizacion = $cotizacionModel->find($ordenCompra['ID_Cotizacion']);
            if (!$cotizacion) {
                continue;
            }

            $solicitud = $solicitudModel->find($cotizacion['ID_Solicitud']);
            if (!$solicitud) {
                continue;
            }

            $proveedor = $proveedorModel->find($ordenCompra['ID_Proveedor']);

            $formattedPagos[] = [
                'ID_Pago' => $pago['ID_Pago'],
                'Folio' => $solicitud['No_Folio'],
                'Proveedor' => $proveedor['RazonSocial'] ?? 'N/A',
                'Total' => $cotizacion['Total'],
                'Estado' => $ordenCompra['Estado'],
            ];
        }

        return $this->respond($formattedPagos, HttpStatus::OK);
    }

    public function confirmarRecepcion()
    {
        $ordenCompraModel = new OrdenCompraModel();
        $productoModel = new ProductoModel();
        $solicitudProductModel = new SolicitudProductModel();

        $idOrdenCompra = $this->request->getPost('id_orden_compra');
        $productosRecibidosJson = $this->request->getPost('productos_recibidos');
        $remisionFile = $this->request->getFile('remision_file');

        if (!$idOrdenCompra || !$productosRecibidosJson) {
            return $this->failValidationAudit(
                'Faltan datos de la orden de compra o productos recibidos.',
            );
        }

        $productosRecibidos = json_decode($productosRecibidosJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->failValidationAudit('Formato de productos recibidos inválido.');
        }

        $ordenCompra = $ordenCompraModel->find($idOrdenCompra);
        if (!$ordenCompra) {
            return $this->failNotFound('Orden de compra no encontrada.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($remisionFile) {
                $baseFileName = 'remision_' . $ordenCompra['ID_OrdenCompra'] . '_' . uniqid();
                $savedFile = ImageProcessor::processAndSave(
                    $remisionFile,
                    FPath::FREMISIONES,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, ['File_Remision' => $savedFile]);
                } else {
                    throw new \Exception('No se pudo guardar el archivo de remisión.');
                }
            }

            $facturaEntradaFile = $this->request->getFile('factura_entrada_file');
            if ($facturaEntradaFile) {
                $baseFileName =
                    'factura_entrada_' . $ordenCompra['ID_OrdenCompra'] . '_' . uniqid();
                $savedFile = ImageProcessor::processAndSave(
                    $facturaEntradaFile,
                    FPath::FENTRADAS_FACTURAS,
                    $baseFileName,
                );
                if ($savedFile) {
                    $ordenCompraModel->update($idOrdenCompra, [
                        'File_FacturaEntrada' => $savedFile,
                    ]);
                } else {
                    throw new \Exception('No se pudo guardar el archivo de factura de entrada.');
                }
            }

            $totalProductosOrden = 0;
            $totalRecibidoOrden = 0;
            $todosProductosRecibidosCompletamente = true;

            $productosOriginales = $solicitudProductModel
                ->where(
                    'ID_Solicitud',
                    $this->api->getCotizacionById($ordenCompra['ID_Cotizacion'])['ID_Solicitud'],
                )
                ->findAll();
            $productosOriginalesMap = [];
            foreach ($productosOriginales as $po) {
                $productosOriginalesMap[$po['ID_SolicitudProd']] = $po;
            }

            foreach ($productosRecibidos as $prodRecibido) {
                $idSolicitudProd = $prodRecibido['id_solicitud_prod'];
                $cantidadRecibida = (int) $prodRecibido['cantidad_recibida'];
                $idProducto = $prodRecibido['id_producto'];

                $productoOriginal = $productosOriginalesMap[$idSolicitudProd] ?? null;

                if (!$productoOriginal) {
                    throw new \Exception(
                        "Producto solicitado ID {$idSolicitudProd} no encontrado.",
                    );
                }

                $cantidadPedida = (int) $productoOriginal['Cantidad'];
                $totalProductosOrden += $cantidadPedida;

                if ($idProducto) {
                    $producto = $productoModel->find($idProducto);
                    if ($producto) {
                        $productoModel->update($idProducto, [
                            'Existencia' => $producto['Existencia'] + $cantidadRecibida,
                        ]);
                    }
                }

                $solicitudProduct = $solicitudProductModel->find($idSolicitudProd);
                if ($solicitudProduct) {
                    $nuevaCantidadRecibida =
                        ($solicitudProduct['Cantidad_Recibida'] ?? 0) + $cantidadRecibida;
                    $estadoRecepcion = Status::RECEPCION_PARCIAL;
                    if ($nuevaCantidadRecibida >= $cantidadPedida) {
                        $estadoRecepcion = Status::RECEPCION_TOTAL;
                        $nuevaCantidadRecibida = $cantidadPedida;
                    } else {
                        $todosProductosRecibidosCompletamente = false;
                    }
                    $solicitudProductModel->update($idSolicitudProd, [
                        'Cantidad_Recibida' => $nuevaCantidadRecibida,
                        'Estado_Recepcion' => $estadoRecepcion,
                    ]);
                    $totalRecibidoOrden += $nuevaCantidadRecibida;
                } else {
                    $todosProductosRecibidosCompletamente = false;
                }
            }

            $nuevoEstadoOrden = Status::POR_PAGAR;
            if ($totalRecibidoOrden > 0) {
                $nuevoEstadoOrden = Status::RECEPCION_PARCIAL;
                if (
                    $todosProductosRecibidosCompletamente &&
                    $totalRecibidoOrden >= $totalProductosOrden
                ) {
                    $nuevoEstadoOrden = Status::RECIBIDA_TOTALMENTE;
                }
            }
            $ordenCompraModel->update($idOrdenCompra, ['Estado' => $nuevoEstadoOrden]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception(
                    'Falla en la transacción de base de datos al confirmar recepción.',
                );
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Recepción confirmada correctamente.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'FALLO_RECEPCION_PRODUCTOS',
                'modulo'         => 'Almacen',
                'orden_compra_id' => $idOrdenCompra ?? null,
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage()])
            ]);
            log_message('error', '[confirmarRecepcion] ' . $e->getMessage());
            return $this->failServerError('Error al confirmar la recepción: ' . $e->getMessage());
        }
    }

    public function registrarBajaDestruccion()
    {
        $productoModel = new ProductoModel();
        $historialProductosModel = new HistorialProductosModel();

        $idProducto = $this->request->getPost('id_producto');
        $cantidadBaja = (int) $this->request->getPost('cantidad_baja');
        $motivoBaja = $this->request->getPost('motivo_baja');
        $fechaBaja = $this->request->getPost('fecha_baja');
        $idUsuario = session('id');

        if (!$idProducto || $cantidadBaja <= 0 || !$motivoBaja || !$fechaBaja || !$idUsuario) {
            return $this->failValidationAudit('Faltan datos obligatorios o son inválidos.');
        }

        $producto = $productoModel->find($idProducto);
        if (!$producto) {
            return $this->failNotFound('Producto no encontrado.');
        }

        if ($cantidadBaja > $producto['Existencia']) {
            return $this->failValidationAudit(
                'La cantidad a dar de baja excede la existencia actual del producto.',
            );
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $existenciaAnt = $producto['Existencia'];
            $nuevaExistencia = $existenciaAnt - $cantidadBaja;

            $productoModel->update($idProducto, ['Existencia' => $nuevaExistencia]);

            $historialData = [
                'ID_Producto' => $idProducto,
                'ID_Usuario' => $idUsuario,
                'CodigoAnt' => $producto['Codigo'],
                'NombreAnt' => $producto['Nombre'],
                'ExistenciaAnt' => $existenciaAnt,
                'CodigoNew' => $producto['Codigo'],
                'NombreNew' => $producto['Nombre'],
                'ExistenciaNew' => $nuevaExistencia,
                'Razon' => 'Baja por Destrucción: ' . $motivoBaja,
                'created_at' => $fechaBaja . ' ' . date('H:i:s'),
            ];
            $historialProductosModel->insert($historialData);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos al registrar baja.');
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Baja por destrucción registrada correctamente.',
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'FALLO_BAJA_PRODUCTO',
                'modulo'         => 'Almacen',
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage(), 'id_producto' => $idProducto ?? null])
            ]);
            log_message('error', '[registrarBajaDestruccion] ' . $e->getMessage());
            return $this->failServerError(
                'Error al registrar la baja por destrucción: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Genera un archivo XML básico para una factura de servicio.
     *
     * @param array $solicitud Los datos de la solicitud.
     * @param array $ordenCompra Los datos de la orden de compra.
     * @return string|null La ruta al archivo XML generado, o null en caso de error.
     */
    private function GenerarFacturaServicioXML(array $solicitud, array $ordenCompra): ?string
    {
        try {
            if (!is_dir(FPath::FFACTURAS_SERVICIOS)) {
                mkdir(FPath::FFACTURAS_SERVICIOS, 0777, true);
            }

            $folioFactura = $solicitud['No_Folio'] . '-SER';
            $fechaEmision = date('Y-m-d H:i:s');
            $rfcEmisor = $solicitud['ComplejoRFC'];
            $nombreEmisor = $solicitud['Complejo'];
            $rfcReceptor = $solicitud['Proveedor']['RFC'] ?? 'XAXX010101000';
            $nombreReceptor = $solicitud['Proveedor']['RazonSocial'] ?? 'Público en General';

            $importeTotal = 0;
            $serviciosXml = '';
            foreach ($solicitud['servicios'] as $servicio) {
                $importeTotal += (float) $servicio['Importe'];
                $serviciosXml .= "<Concepto>\n";
                $serviciosXml .= '  <Descripcion>' . esc($servicio['Nombre']) . "</Descripcion>\n";
                $serviciosXml .= "  <Cantidad>1</Cantidad>\n";
                $serviciosXml .= "  <Unidad>Servicio</Unidad>\n";
                $serviciosXml .=
                    '  <ValorUnitario>' .
                    number_format($servicio['Importe'], 2, '.', '') .
                    "</ValorUnitario>\n";
                $serviciosXml .=
                    '  <Importe>' .
                    number_format($servicio['Importe'], 2, '.', '') .
                    "</Importe>\n";
                $serviciosXml .= "</Concepto>\n";
            }

            $ivaMonto = $solicitud['IVA'] === 't' ? $importeTotal * 0.16 : 0;
            $granTotal = $importeTotal + $ivaMonto;

            $xmlContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            $xmlContent .= "<FacturaServicio>\n";
            $xmlContent .= " <Encabezado>\n";
            $xmlContent .= ' <FolioFactura>' . esc($folioFactura) . "</FolioFactura>\n";
            $xmlContent .= ' <FechaEmision>' . esc($fechaEmision) . "</FechaEmision>\n";
            $xmlContent .=
                ' <MetodoPago>' .
                esc(MetodoPago::getText($solicitud['MetodoPago'])) .
                "</MetodoPago>\n";
            $xmlContent .= " </Encabezado>\n";
            $xmlContent .= " <Emisor>\n";
            $xmlContent .= ' <RFC>' . esc($rfcEmisor) . "</RFC>\n";
            $xmlContent .= ' <Nombre>' . esc($nombreEmisor) . "</Nombre>\n";
            $xmlContent .= " </Emisor>\n";
            $xmlContent .= " <Receptor>\n";
            $xmlContent .= ' <RFC>' . esc($rfcReceptor) . "</RFC>\n";
            $xmlContent .= ' <Nombre>' . esc($nombreReceptor) . "</Nombre>\n";
            $xmlContent .= " </Receptor>\n";
            $xmlContent .= " <Conceptos>\n";
            $xmlContent .= $serviciosXml;
            $xmlContent .= " </Conceptos>\n";
            $xmlContent .= " <Totales>\n";
            $xmlContent .=
                ' <SubTotal>' . number_format($importeTotal, 2, '.', '') . "</SubTotal>\n";
            if ($solicitud['IVA'] === 't') {
                $xmlContent .= ' <IVA>' . number_format($ivaMonto, 2, '.', '') . "</IVA>\n";
            }
            $xmlContent .= ' <Total>' . number_format($granTotal, 2, '.', '') . "</Total>\n";
            $xmlContent .= " </Totales>\n";
            $xmlContent .= "</FacturaServicio>\n";

            $fileName = 'FacturaServicio-' . $solicitud['No_Folio'] . '.xml';
            $filePath = FPath::FFACTURAS_SERVICIOS . $fileName;

            file_put_contents($filePath, $xmlContent);
            return $filePath;
        } catch (\Exception $e) {
            log_message(
                'error',
                '[GenerarFacturaServicioXML] Error al generar XML de factura de servicio: ' .
                    $e->getMessage(),
            );
            return null;
        }
    }
    //endregion
    public function programarPagos()
    {
        $json = $this->request->getJSON();
        if (!isset($json->ids) || !is_array($json->ids)) {
            return $this->failValidationAudit('Se requiere un array de IDs de solicitud.');
        }

        $ids = $json->ids;
        $ordenCompraModel = new OrdenCompraModel();
        $cotizacionModel = new CotizacionModel();
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($ids as $idSolicitud) {
                $cotizacion = $cotizacionModel->where('ID_Solicitud', $idSolicitud)->first();
                if ($cotizacion) {
                    $orden = $ordenCompraModel->where('ID_Cotizacion', $cotizacion['ID_Cotizacion'])->first();
                    if ($orden) {
                        $ordenCompraModel->update($orden['ID_OrdenCompra'], [
                            'Estado' => Status::Programada
                        ]);
                    }
                }
            }
            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('Error en la transacción de la base de datos.');
            }

            return $this->respondUpdated([
                'success' => true,
                'message' => 'Pagos programados correctamente.',
            ]);
        } catch (\Exception $e) {
            log_message('error', '[programarPagos] ' . $e->getMessage());
            return $this->failServerError('Ocurrió un error inesperado al programar los pagos.');
        }
    }

    public function getPagosProgramados()
    {
        $ordenCompraModel = new OrdenCompraModel();

        $data = $ordenCompraModel
            ->select([
                'Solicitud.ID_Solicitud',
                'Solicitud.No_Folio',
                'Proveedor.RazonSocial as Proveedor',
                'Razon_Social.Nombre as RazonSocial',
                // AGREGAMOS ESTE CAMPO:
                'Departamentos.Nombre as Departamento',
                'Cotizacion.Total',
                'OrdenCompra.Estado',
                'Solicitud.MetodoPago',
                'OrdenCompra.Fecha as FechaOrden'
            ])
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            // AGREGAMOS ESTE JOIN:
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')

            ->where('OrdenCompra.Estado', Status::Programada)
            ->orderBy('Solicitud.Fecha', 'DESC')
            ->findAll();

        return $this->respond($data);
    }

    public function exportarPagosProgramados()
    {
        $metodoPagoFiltro = $this->request->getGet('metodo_pago');
        $ordenCompraModel = new OrdenCompraModel();

        $builder = $ordenCompraModel
            ->select([
                // --- Datos Generales ---
                'Solicitud.No_Folio',
                'Solicitud.Fecha as FechaSolicitud',
                'Solicitud.MetodoPago',
                'OrdenCompra.Estado',
                'Cotizacion.Total',
                'Departamentos.Nombre as Departamento',
                'Razon_Social.Nombre as Proyecto',

                // --- Datos del Proveedor ---
                'Proveedor.RazonSocial as Proveedor',
                'Proveedor.RFC',
                'Proveedor.Banco',
                'Proveedor.Cuenta as CuentaProveedor',
                'Proveedor.Clabe',
                'Proveedor.Dias_Credito',
                'Proveedor.Monto_Credito',
            ])
            // Joins
            ->join('Cotizacion', 'Cotizacion.ID_Cotizacion = OrdenCompra.ID_Cotizacion', 'left')
            ->join('Solicitud', 'Solicitud.ID_Solicitud = Cotizacion.ID_Solicitud', 'left')
            ->join('Proveedor', 'Proveedor.ID_Proveedor = OrdenCompra.ID_Proveedor', 'left')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')

            ->where('OrdenCompra.Estado', 'Programada')
            ->orderBy('Solicitud.Fecha', 'DESC');

        if ($metodoPagoFiltro !== null && $metodoPagoFiltro !== 'todos') {
            $builder->where('Solicitud.MetodoPago', $metodoPagoFiltro);
        }

        $pagos = $builder->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ---  ENCABEZADOS SUPERIORES  ---
        $sheet->setCellValue('A1', 'Datos generales');
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('G1', 'Datos del proveedor');
        $sheet->mergeCells('G1:N1');

        // Estilos Fila 1
        $styleTopHeader = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFD9D9D9'], // Color Gris claro
            ],
        ];
        $sheet->getStyle('A1:N1')->applyFromArray($styleTopHeader);

        // --- F ENCABEZADOS DE COLUMNA ---
        $headers = [
            'A2' => 'Folio',
            'B2' => 'Fecha Solicitud',
            'C2' => 'Departamento',
            'D2' => 'Proyecto',
            'E2' => 'Importe Total',
            'F2' => 'Método Pago',
            'G2' => 'Razón Social',
            'H2' => 'RFC',
            'I2' => 'Banco',
            'J2' => 'Cuenta Proveedor',
            'K2' => 'CLABE',
            'L2' => 'Días Crédito',
            'M2' => 'Monto Máx. Crédito',
            'N2' => 'Estado',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }
        // Negrita para la fila 2
        $sheet->getStyle('A2:N2')->getFont()->setBold(true);

        //LLenar datos
        $row = 3;
        $startRow = 3;

        foreach ($pagos as $pago) {
            $metodoPagoTexto = match ($pago['MetodoPago']) {
                '0' => 'Contado',
                '1' => 'Crédito',
                default => 'Desconocido',
            };

            //Datos Generales
            $sheet->setCellValue('A' . $row, $pago['No_Folio']);
            $sheet->setCellValue('B' . $row, $pago['FechaSolicitud']);
            $sheet->setCellValue('C' . $row, $pago['Departamento'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $pago['Proyecto'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $pago['Total']);
            $sheet
                ->getStyle('E' . $row)
                ->getNumberFormat()
                ->setFormatCode('"$"#,##0.00_-');
            $sheet->setCellValue('F' . $row, $metodoPagoTexto);

            //Datos Proveedor
            $sheet->setCellValue('G' . $row, $pago['Proveedor']);
            $sheet->setCellValue('H' . $row, $pago['RFC'] ?? '');
            $sheet->setCellValue('I' . $row, $pago['Banco'] ?? '');
            $sheet->setCellValueExplicit(
                'J' . $row,
                $pago['CuentaProveedor'] ?? '',
                DataType::TYPE_STRING,
            );
            $sheet->setCellValueExplicit('K' . $row, $pago['Clabe'] ?? '', DataType::TYPE_STRING);
            $sheet->setCellValue('L' . $row, $pago['Dias_Credito'] ?? '0');
            $sheet->setCellValue('M' . $row, $pago['Monto_Credito'] ?? 0);
            $sheet
                ->getStyle('M' . $row)
                ->getNumberFormat()
                ->setFormatCode('"$"#,##0.00_-');

            $sheet->setCellValue('N' . $row, $pago['Estado']);

            $row++;
        }

        //Columna de totales
        $lastDataRow = $row - 1;
        $totalRow = $row + 3; // Bajamos 3 filas para dejar espacio

        // Etiqueta "Total" en la columna D
        $sheet->setCellValue('D' . $totalRow, 'Total');
        $sheet
            ->getStyle('D' . $totalRow)
            ->getFont()
            ->setBold(true);
        $sheet->setCellValue('E' . $totalRow, '=SUM(E' . $startRow . ':E' . $lastDataRow . ')');
        $sheet
            ->getStyle('E' . $totalRow)
            ->getFont()
            ->setBold(true);
        $sheet
            ->getStyle('E' . $totalRow)
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0.00_-');

        // --- AJUSTE FINAL DE COLUMNAS ---
        foreach (range('A', 'N') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'pagos_programados_' . date('Y-m-d_H-i') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    public function getPagosPendientes()
    {
        $data = $this->api->getPagosPendientes();

        if (empty($data)) {
            return $this->respond([
                'success' => false,
                'message' => 'No se encontraron pagos pendientes.',
            ]);
        }

        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function getFichasPago()
    {
        $data = $this->api->getFichasPago();

        if (empty($data)) {
            return $this->respond([
                'success' => false,
                'message' => 'No se encontraron fichas de pago.',
            ]);
        }

        return $this->respond(['success' => true, 'data' => $data]);
    }
    /**
     * Prueba la conexión y el envío de un correo electrónico.
     * Utiliza la configuración de correo de la aplicación.
     *
     * @return \CodeIgniter\HTTP\Response
     */
    public function exportarMovimientosExcel()
    {
        $json = $this->request->getJSON(true);
        $datos = $json['datos'] ?? [];
        $filtros = $json['filtros'] ?? [];

        if (empty($datos)) {
            return $this->fail('No hay datos para exportar.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Movimientos');

        // Estilos para los bloques
        $styleSolicitud = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $styleOrden = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ED7D31']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        $styleProveedor = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];

        // Definición de Columnas por Bloque
        // A-H: Solicitud | I-N: Orden de Compra | O-U: Proveedor
        $sheet->setCellValue('A1', 'BLOQUE 1: INFORMACIÓN DE SOLICITUD');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1:H1')->applyFromArray($styleSolicitud);

        $sheet->setCellValue('I1', 'BLOQUE 2: ORDEN DE COMPRA Y PAGOS');
        $sheet->mergeCells('I1:N1');
        $sheet->getStyle('I1:N1')->applyFromArray($styleOrden);

        $sheet->setCellValue('O1', 'BLOQUE 3: DETALLES DEL PROVEEDOR');
        $sheet->mergeCells('O1:U1');
        $sheet->getStyle('O1:U1')->applyFromArray($styleProveedor);

        $subHeaders = [
            'Folio', 'Fecha', 'Estado', 'Solicitante', 'Departamento', 'Unidad Operativa', 'Complejo', 'Inversión Total',
            'Estado Orden', 'Fecha Orden', 'Ref. Pago', 'F. Pago Realizado', 'Factura', 'Comprobante',
            'Proveedor', 'RFC', 'Banco', 'Cuenta', 'CLABE', 'Contacto', 'Teléfono'
        ];

        $col = 'A';
        foreach ($subHeaders as $sh) {
            $sheet->setCellValue($col . '2', $sh);
            $style = ($col <= 'H') ? $styleSolicitud : (($col <= 'N') ? $styleOrden : $styleProveedor);
            $sheet->getStyle($col . '2')->applyFromArray($style);
            $col++;
        }

        $row = 3;
        foreach ($datos as $d) {
            // BLOQUE 1: Solicitud
            $sheet->setCellValue('A' . $row, $d['No_Folio'] ?? 'N/A');
            $sheet->setCellValue('B' . $row, $d['Fecha'] ?? 'N/A');
            $sheet->setCellValue('C' . $row, $d['Estado'] ?? 'N/A');
            $sheet->setCellValue('D' . $row, $d['UsuarioSolicita'] ?? 'N/A');
            $sheet->setCellValue('E' . $row, $d['DepartamentoNombre'] ?? 'N/A');
            $sheet->setCellValue('F' . $row, $d['UnidadOperativaNombre'] ?? 'N/A');
            $sheet->setCellValue('G' . $row, $d['PlaceNombre'] ?? 'N/A');
            $sheet->setCellValue('H' . $row, (float)($d['MontoTotal'] ?? 0));
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

            // BLOQUE 2: Orden de Compra
            $sheet->setCellValue('I' . $row, $d['OrdenEstado'] ?? 'Pendiente');
            $sheet->setCellValue('J' . $row, $d['OrdenFecha'] ?? 'N/A');
            $sheet->setCellValue('K' . $row, $d['FechaRefPago'] ?? '—');
            $sheet->setCellValue('L' . $row, $d['FechaPagoRealizado'] ?? 'Pendiente');
            $sheet->setCellValue('M' . $row, !empty($d['File_Factura']) ? 'SÍ' : 'NO');
            $sheet->setCellValue('N' . $row, !empty($d['File_Comprobante']) ? 'SÍ' : 'NO');

            // BLOQUE 3: Proveedor
            $sheet->setCellValue('O' . $row, $d['ProveedorNombre'] ?? 'N/A');
            $sheet->setCellValue('P' . $row, $d['ProveedorRFC'] ?? 'N/A');
            $sheet->setCellValue('Q' . $row, $d['CuentaBanco'] ?? 'N/A');
            $sheet->setCellValue('R' . $row, $d['Cuenta'] ?? 'N/A');
            $sheet->setCellValue('S' . $row, $d['Clabe'] ?? 'N/A');
            $sheet->setCellValue('T' . $row, $d['Nombre_Contacto'] ?? 'N/A');
            $sheet->setCellValue('U' . $row, $d['Tel_Contacto'] ?? 'N/A');
            
            $row++;
        }

        // Fila de Totales
        $lastDataRow = $row - 1;
        $sheet->setCellValue('A' . $row, 'TOTAL GENERAL DE INVERSIÓN:');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Sumatoria en la columna H
        $sheet->setCellValue('H' . $row, "=SUM(H3:H$lastDataRow)");
        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00_-');

        foreach (range('A', 'U') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="movimientos_proveedor_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function testEmailConnection()
    {
        $mail = new MBSMail();
        $to = getenv('EMAIL_TO_TEST') ?: 'developer2@mbsignatureproperties.com'; // Usar un correo de prueba o configurar en .env
        $subject = 'Prueba de Conexión de Correo desde CodeIgniter';
        $message = 'Este es un correo de prueba enviado desde tu aplicación CodeIgniter para verificar la conexión SMTP.';

        try {
            $success = $mail->send_email($to, $subject, $message);

            if ($success) {
                return $this->respond([
                    'success' => true,
                    'message' => "Correo de prueba enviado exitosamente a {$to}.",
                ], HttpStatus::OK);
            } else {
                // Si send_email retorna false, buscar errores en el log de CodeIgniter.
                // MBSMail debería loguear los errores detallados.
                return $this->failServerError('Fallo al enviar el correo de prueba. Revisa los logs del servidor para más detalles.');
            }
        } catch (\Exception $e) {
            // Capturar excepciones si el proceso de envío arroja una.
            log_message('critical', '[testEmailConnection] Error al enviar correo de prueba: ' . $e->getMessage());
            return $this->failServerError('Excepción al enviar correo de prueba: ' . $e->getMessage());
        }
    }
    //endregion

    //region Bitacora
    public function bitacora()
    {
        $limit  = (int) ($this->request->getVar('limit') ?? 50);
        $page   = (int) ($this->request->getVar('page') ?? 1);
        $offset = ($page - 1) * $limit;

        $filters = [
            'usuario_id'   => $this->request->getVar('usuario_id'),
            'modulo'       => $this->request->getVar('modulo'),
            'tipo_accion'  => $this->request->getVar('tipo_accion'),
            'fecha_inicio' => $this->request->getVar('fecha_inicio'),
            'fecha_fin'    => $this->request->getVar('fecha_fin'),
        ];

        $result = $this->api->getBitacora($filters, $limit, $offset);
        return $this->respond($result);
    }
    //endregion
}
