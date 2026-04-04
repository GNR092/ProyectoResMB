<?php

namespace App\Controllers;
use App\Models\DepartamentosModel;
use App\Models\RazonSocialModel;
use App\Models\UsuariosModel;
use App\Libraries\Rest;
use App\Models\ProveedorModel;
use App\Models\ProductoModel;
use App\Models\SolicitudModel;
use App\Models\HistorialProductosModel;
use App\Models\CuentasModel;
use App\Models\PlacesModel;
use App\Models\GrupoPresupuestalModel;
use App\Models\BancoDptoModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use App\Models\PresupuestoMensualModel;
use App\Models\SegmentoNegocioModel;
use App\Models\UnidadOperativaModel;

class Modales extends BaseController
{
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }
    public function mostrar($opcion)
    {
        $session = session();
        $data = [
            'departamentos' => $session->get('departamentos'),
            'nombre_usuario' => $session->get('nombre_usuario'),
            'departamento_usuario' => $session->get('departamento_usuario'),
        ];

        switch ($opcion) {
            case 'ver_historial':
                $departamentoModel = new DepartamentosModel();
                $data['departamentos'] = $departamentoModel
                    ->select('Departamentos.*, Places.Nombre_Corto as PlaceNombre')
                    ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Departamentos.ID_UnidadOperativa', 'left')
                    ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();
                return view('modales/ver_historial', $data);

            case 'solicitar_material':
                $proveedorModel = new ProveedorModel();
                $razonSocialModel = new RazonSocialModel();
                $grupoModel = new GrupoPresupuestalModel();
                $departamentoModel = new DepartamentosModel();
                $placeModel = new PlacesModel();

                // 1. Obtener el departamento del usuario actual
                $idDeptoUsuario = session('id_departamento_usuario');
                $idUsuario = session('id');
                $deptoActual = $idDeptoUsuario ? $departamentoModel->find($idDeptoUsuario) : null;

                if (!$deptoActual) {
                    log_message('error', 'No se encontró departamento para el usuario ID: ' . $idUsuario . ' con DeptoID: ' . $idDeptoUsuario);
                    return "<p class='text-red-500 p-4'>No se pudo identificar su departamento. Por favor, cierre sesión e ingrese de nuevo.</p>";
                }

                $nombreDepto = $deptoActual['Nombre'] ?? '';

                // 2. Obtener proveedores
                $data['proveedores'] = $proveedorModel
                    ->select('ID_Proveedor, RazonSocial, Tel_Contacto, RFC, Servicio')
                    ->orderBy('RazonSocial', 'ASC')
                    ->findAll();

                // Detección del departamento especial (Operacion o variantes)
                $deptoLower = mb_strtolower(trim($nombreDepto));
                $data['is_depto_especial'] = (strpos($deptoLower, 'operacion') !== false || strpos($deptoLower, 'operación') !== false);

                $db = \Config\Database::connect();

                // 3. Obtener Razones Sociales
                if ($data['is_depto_especial']) {
                    // Si es el departamento especial, ve TODAS las razones sociales activas
                    $data['razones_sociales'] = $razonSocialModel
                        ->select('ID_RazonSocial, Nombre')
                        ->orderBy('Nombre', 'ASC')
                        ->findAll();
                    
                    // También enviamos todos los places para que JS los filtre
                    // Vinculación directa únicamente: Places -> ID_RazonSocial
                    $data['all_places'] = $db->table('Places')
                        ->select('ID_Place, Nombre_Corto, ID_RazonSocial')
                        ->where('ID_RazonSocial IS NOT NULL')
                        ->orderBy('Nombre_Corto', 'ASC')
                        ->get()
                        ->getResultArray();
                } else {
                    /**
                     * Requisito:
                     * 1. La razón social a la cual pertenece su departamento.
                     * 2. Razones sociales que tengan entre sus complejos un departamento igual al del solicitante.
                     */
                    
                    // Usamos Query Builder para construir la consulta de forma compatible con cualquier DB
                    $builder = $db->table('Razon_Social rs');
                    $builder->select('rs.ID_RazonSocial, rs.Nombre')->distinct();
                    
                    // Sub-consulta para encontrar IDs de Razones Sociales vinculadas al departamento del usuario
                    // Ruta 1: RS -> Place -> Departamentos
                    $builder->join('Places p', 'p.ID_RazonSocial = rs.ID_RazonSocial', 'left');
                    $builder->join('segmento_negocio sn', 'sn.id_razon_social = rs.ID_RazonSocial', 'left');
                    $builder->join('Places p2', 'p2.id_segmento = sn.id', 'left');
                    
                    // Unir con Departamentos por ID_Place o ID_UnidadOperativa
                    $builder->join('Departamentos d', 'd.ID_Place = p.ID_Place OR d.ID_Place = p2.ID_Place', 'left');
                    $builder->join('UnidadOperativa uo', 'uo.ID_Place = p.ID_Place OR uo.ID_Place = p2.ID_Place', 'left');
                    $builder->join('Departamentos d2', 'd2.ID_UnidadOperativa = uo.ID_UnidadOperativa', 'left');

                    $builder->groupStart()
                        ->where('d.ID_Dpto', $idDeptoUsuario)
                        ->orWhere('d.Nombre', $nombreDepto)
                        ->orWhere('d2.ID_Dpto', $idDeptoUsuario)
                        ->orWhere('d2.Nombre', $nombreDepto)
                    ->groupEnd();

                    $data['razones_sociales'] = $builder->orderBy('rs.Nombre', 'ASC')->get()->getResultArray();

                    // Fallback: si por alguna razón estructural sigue vacío, cargamos todas
                    if (empty($data['razones_sociales'])) {
                        log_message('notice', 'Consulta Query Builder vacía para usuario ' . $idUsuario . '. Cargando todas.');
                        $data['razones_sociales'] = $razonSocialModel
                            ->select('ID_RazonSocial, Nombre')
                            ->orderBy('Nombre', 'ASC')
                            ->findAll();
                    }
                }

                // 4. Obtener grupos presupuestales filtrados por la Unidad Operativa del departamento del usuario
                $idUnidad = $deptoActual['ID_UnidadOperativa'] ?? 0;

                $data['grupos_presupuestales'] = $grupoModel
                    ->where('ID_UnidadOperativa', $idUnidad)
                    ->where('activo', true)
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();

                return view('modales/solicitar_material', $data);

            case 'revisar_solicitudes':
                $solicitudModel = new SolicitudModel();
                $proveedorModel = new ProveedorModel();

                // --- Solicitudes Pendientes ---
                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En espera')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                // --- Proveedores ---
                $data['proveedores'] = $proveedorModel
                    ->select('ID_Proveedor, RazonSocial, Tel_Contacto, RFC, Servicio')
                    ->orderBy('RazonSocial', 'ASC')
                    ->findAll();

                return view('modales/revisar_solicitudes', $data);

            case 'ordenes_compra':
                $data['solicitudes'] = $this->api->getSolicitudesSinOrdenPago();
                return view('modales/ordenes_compra', $data);

            case 'enviar_revision':
                $departamentoModel = new DepartamentosModel();

                $data['departamentos'] = $departamentoModel
                    ->select('Departamentos.*, Places.Nombre_Corto as PlaceNombre')
                    ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Departamentos.ID_UnidadOperativa', 'left')
                    ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();

                return view('modales/enviar_revision', $data);

            case 'usuarios':
                $departamentosModel = new DepartamentosModel();
                $razonSocialModel = new RazonSocialModel();

                $data = [
                    'departamentos' => $departamentosModel->findAll(),
                    'razones_sociales' => $razonSocialModel->findAll(),
                ];

                return view('modales/usuarios', $data);

            case 'crud_usuarios':
                $razonSocialModel = new RazonSocialModel();
                $data['usuarios'] = $this->api->getAllUsers();
                $data['razones_sociales'] = $razonSocialModel->findAll();
                $data['departamentos'] = $this->api->getAllDepartments();
                return view('modales/crud_usuarios', $data);

            case 'dictamen_solicitudes':
                $solicitudModel = new SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En revision')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                return view('modales/dictamen_solicitudes', $data);

            case 'crud_proveedores':
                $proveedorModel = new ProveedorModel();

                // Traer todos los registros de proveedores
                $data['proveedores'] = $proveedorModel->orderBy('ID_Proveedor', 'ASC')->findAll();

                return view('modales/crud_proveedores', $data);

            case 'limpiar_almacenamiento':
                return view('modales/limpiar_almacenamiento');

            case 'pagos_pendientes':
                return view('modales/pagos_pendientes');

            case 'registrar_productos':
                $productoModel = new ProductoModel();
                $data['productos'] = $productoModel->findAll();

                return view('modales/registrar_productos', $data);

            case 'crud_productos':
                $productoModel = new ProductoModel();
                $session = session(); // <-- inicializamos sesión

                // Obtener todos los productos ordenados
                $data['productos'] = $productoModel
                    ->select('Producto.*')
                    ->orderBy('CAST("Producto"."Codigo" AS INTEGER)', 'ASC', false)
                    ->findAll();

                // Agregar datos de sesión
                $data['nombre_usuario'] = $session->get('nombre_usuario');
                $data['departamento_usuario'] = $session->get('departamento_usuario');

                return view('modales/crud_productos', $data);

            case 'entrega_productos':
                $productoModel = new ProductoModel();
                $departamentosModel = new DepartamentosModel();
                $session = session();

                $data = [
                    'productos' => $productoModel
                        ->select('Producto.*')
                        ->orderBy('CAST("Producto"."Codigo" AS INTEGER)', 'ASC', false)
                        ->findAll(),
                    'nombre_usuario' => $session->get('nombre_usuario'),
                    'departamento_usuario' => $session->get('departamento_usuario'),
                    'departamentos' => $departamentosModel->findAll(), // <- se agregan
                ];

                return view('modales/entrega_productos', $data);

            case 'ficha_pago':
                $solicitudModel = new SolicitudModel();

                // Solicitudes de contado (MetodoPago = 0)
                $data['solicitudes_contado'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En Proceso de Pago')
                    ->where('Solicitud.MetodoPago', 0)
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                // Solicitudes a crédito (MetodoPago = 1)
                $data['solicitudes_credito'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En Proceso de Pago')
                    ->where('Solicitud.MetodoPago', 1)
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                return view('modales/ficha_pago', $data);

            case 'aprobar_solicitudes':
                $idDepartamentoJefe = $this->api->getUserById(session('id'))['ID_Dpto'];
                $data['solicitudes_pendientes'] = $this->api->getSolicitudesUsersByDepartment(
                    $idDepartamentoJefe,
                );

                return view('modales/aprobar_solicitudes', $data);

            case 'ajustes':
                return view('modales/ajustes');

            case 'almacen':
                return view('modales/almacen');

            case 'reportes':
                $razonSocialModel = new RazonSocialModel();
                $data['razones_sociales'] = $razonSocialModel
                    ->select('ID_RazonSocial, Nombre')
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();
                $departamentoModel = new DepartamentosModel();
                $data['departamentos'] = $departamentoModel
                    ->select('ID_Dpto, Nombre')
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();
                $provedores = $this->api->getProveedorIdAndRazonSocial();
                $data['proveedores'] = $provedores;

                $id_solicitud = $this->request->getGet('id_solicitud');

                if ($id_solicitud) {
                    $data['reporte_detalles'] = $this->api->getOrdenCompra($id_solicitud);
                } else {
                    $ocs = $this->api->getAllOrdenCompraData();
                    $tabledata = [];

                    foreach ($ocs as $oc) {
                        $o = $this->api->getOrdenCompra($oc['ID_Solicitud']);

                        if ($o && !empty($o['OrdenCompra'])) {
                            $estado = $o['OrdenCompra']['Estado'] ?? '';

                            if (in_array($estado, ['Por Pagar', 'Pagada'])) {

                                // AGREGAMOS ESTA LÍNEA:
                                // Volvemos a colocar el estado en la raíz solo para esta vista
                                $o['EstadoOrden'] = $estado;

                                $tabledata[] = $o;
                            }
                        }
                    }
                    $data['tabledata'] = $tabledata;
                }

                // 4. Pasar los datos a la vista
                return view('modales/reportes', $data);

            case 'razonsocial':
                $razonModel = new RazonSocialModel();
                $data['razones'] = $razonModel->orderBy('ID_RazonSocial', 'ASC')->findAll();
                return view('modales/razonsocial', $data);

            case 'reporte_almacen':
                $historialModel = new HistorialProductosModel();
                $data['historial'] = $historialModel->orderBy('created_at', 'DESC')->findAll();

                return view('modales/reporte_almacen', $data);

            case 'micuenta':
                $id = session('id');
                $sign = $this->api->getSignB64ByUserID($id);
                $data['firmaUrl'] = $sign;
                return view('modales/micuenta', $data);

            case 'programar_pagos':
                return view('modales/programar_pagos');

            case 'lista_pagos':
                return view('modales/lista_pagos', $data);

            case 'recepcion_material':
                return view('modales/recepcion_material', $data);

            case 'bajas_destruccion':
                $data['productos'] = $this->api->getAllProducts();
                return view('modales/bajas_destruccion', $data);

            case 'crud_places':
                $placesModel = new PlacesModel();
                $razonSocialModel = new RazonSocialModel();
                $segmentoModel = new \App\Models\SegmentoNegocioModel();

                // 2. Modificamos la consulta para traer el NOMBRE de la Razón Social y el Segmento
                $data['places'] = $placesModel
                    ->select('Places.*, Razon_Social.Nombre as razonsocial_nombre, segmento_negocio.nombre as segmento_nombre')
                    ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial', 'left')
                    ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left')
                    ->orderBy('Places.Nombre_Corto', 'ASC')
                    ->findAll();

                // 3. Enviamos las listas para llenar los <select>
                $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();
                $data['segmentos'] = $segmentoModel->orderBy('nombre', 'ASC')->findAll();

                return view('modales/crud_places', $data);

            case 'crud_departamento':
                $deptosModel = new DepartamentosModel();
                $unidadesModel = new UnidadOperativaModel();
                $placesModel = new PlacesModel();

                // Obtenemos los departamentos junto con el nombre de la unidad operativa y el lugar
                $data['departamentos'] = $deptosModel
                    ->select('Departamentos.*, UnidadOperativa.Nombre as UnidadNombre, Places.Nombre_Corto as PlaceNombre')
                    ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Departamentos.ID_UnidadOperativa', 'left')
                    ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();

                // Obtenemos las unidades operativas y los lugares para llenar los selects
                $data['unidades_operativas'] = $unidadesModel
                    ->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre')
                    ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left')
                    ->orderBy('UnidadOperativa.Nombre', 'ASC')
                    ->findAll();

                $data['places'] = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();

                return view('modales/crud_departamento', $data);

            case 'crud_cuentas':
                // Llenamos la tabla principal con los datos del proveedor
                $proveedorModel = new ProveedorModel();
                $data['cuentas'] = $proveedorModel->orderBy('RazonSocial', 'ASC')->findAll();

                return view('modales/crud_cuentas', $data);

            case 'correcciones':
                return view('modales/correcciones');

            case 'GrupoPresupuestal':
                $grupoModel = new GrupoPresupuestalModel();
                $unidadesModel = new UnidadOperativaModel();
                $solicitudesCambioModel = new \App\Models\SolicitudesCambioPresupuestoModel();

                $data['grupos'] = $grupoModel
                    ->select('GrupoPresupuestal.*, UnidadOperativa.Nombre as UnidadNombre, Places.Nombre_Corto as PlaceNombre')
                    ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = GrupoPresupuestal.ID_UnidadOperativa', 'left')
                    ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left')
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();

                $data['unidades_operativas'] = $unidadesModel
                    ->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre')
                    ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left')
                    ->orderBy('UnidadOperativa.Nombre', 'ASC')
                    ->findAll();

                $data['registros_bloqueados'] = $solicitudesCambioModel->where('Estado', 'Pendiente')->where('Modulo', 'GrupoPresupuestal')->findColumn('ID_Afectado') ?? [];

                return view('modales/CrudGrupos', $data);

            case 'UnidadOperativa':
                $unidadesModel = new UnidadOperativaModel();
                $placesModel = new PlacesModel();
                $solicitudesCambioModel = new \App\Models\SolicitudesCambioPresupuestoModel();

                $data['unidades'] = $unidadesModel
                    ->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre')
                    ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place', 'left')
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();

                $data['places'] = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();
                $data['registros_bloqueados'] = $solicitudesCambioModel->where('Estado', 'Pendiente')->where('Modulo', 'UnidadOperativa')->findColumn('ID_Afectado') ?? [];

                return view('modales/CrudUnidades', $data);

            case 'BancoDpto':
                $bancoModel = new BancoDptoModel();
                $rsModel    = new RazonSocialModel();
                $solicitudesCambioModel = new \App\Models\SolicitudesCambioPresupuestoModel();
                
                $data['bancos_dpto'] = $bancoModel->withRazonSocial()->findAll();
                $data['razones_sociales'] = $rsModel
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();
                $data['registros_bloqueados'] = $solicitudesCambioModel->where('Estado', 'Pendiente')->where('Modulo', 'BancoDpto')->findColumn('ID_Afectado') ?? [];

                return view('modales/BancoDpto', $data);

            case 'PresupuestoMensual':
                $razonSocialModel = new \App\Models\RazonSocialModel();
                $placesModel      = new \App\Models\PlacesModel();

                // Mandamos catálogos base para los primeros dos selectores
                $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();
                $data['places']           = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();

                return view('modales/control/PresupuestoMensual', $data);

            case 'SaldosBancarios':
                $razonSocialModel = new \App\Models\RazonSocialModel();
                $placesModel      = new \App\Models\PlacesModel();

                $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();
                $data['places']           = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();

                return view('modales/control/SaldosBancarios', $data);

            case 'SegmentoNegocio':
                $segmentoModel = new \App\Models\SegmentoNegocioModel();
                $razonModel = new \App\Models\RazonSocialModel();
                $solicitudesCambioModel = new \App\Models\SolicitudesCambioPresupuestoModel();

                $data['segmentos'] = $segmentoModel->withRazonSocial()->findAll();
                $data['razones_sociales'] = $razonModel->orderBy('Nombre', 'ASC')->findAll();
                $data['registros_bloqueados'] = $solicitudesCambioModel->where('Estado', 'Pendiente')->where('Modulo', 'SegmentoNegocio')->findColumn('ID_Afectado') ?? [];

                return view('modales/control/SegmentoNegocio', $data);

            case 'AjustesPresupuesto':
                $razonSocialModel = new \App\Models\RazonSocialModel();
                $placesModel      = new \App\Models\PlacesModel();

                $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();
                $data['places']           = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();

                return view('modales/control/AjustesPresupuesto', $data);

            default:
                return 'Opción no válida';
        }
    }

    // --- CRUD SEGMENTOS DE NEGOCIO ---
    public function insertarSegmento()
    {
        $data = $this->request->getPost(['nombre', 'descripcion', 'id_razon_social']);
        $comentarios = $this->request->getPost('comentarios');
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'SegmentoNegocio',
            'Accion'        => 'Insertar',
            'ID_Afectado'   => null,
            'Datos_Payload' => json_encode($data),
            'Datos_Antiguos'=> null,
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al enviar a revisión', 'errors' => $solicitudesModel->errors()]);
        }
    }

    public function editarSegmento($id)
    {
        $data = $this->request->getPost(['nombre', 'descripcion', 'id_razon_social']);
        $comentarios = $this->request->getPost('comentarios');
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        
        $modeloReal = new \App\Models\SegmentoNegocioModel();
        $datosAntiguos = $modeloReal->find($id);

        try {
            $solicitudesModel->insert([
                'ID_Usuario'    => session('id'),
                'Modulo'        => 'SegmentoNegocio',
                'Accion'        => 'Editar',
                'ID_Afectado'   => $id,
                'Datos_Payload' => json_encode($data),
                'Datos_Antiguos'=> json_encode($datosAntiguos),
                'Estado'        => 'Pendiente',
                'Comentarios_Solicitante' => $comentarios
            ]);
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function eliminarSegmento($id)
    {
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $comentarios = $this->request->getPost('comentarios');
        
        $modeloReal = new \App\Models\SegmentoNegocioModel();
        $datosAntiguos = $modeloReal->find($id);

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'SegmentoNegocio',
            'Accion'        => 'Eliminar',
            'ID_Afectado'   => $id,
            'Datos_Payload' => json_encode(['id' => $id]),
            'Datos_Antiguos'=> json_encode($datosAntiguos),
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Solicitud de eliminación enviada a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo enviar la solicitud de eliminación a revisión',
            ]);
        }
    }

    //Funciones para tablas
    public function getProductTableRow()
    {
        $grupoModel = new GrupoPresupuestalModel();
        $deptoModel = new DepartamentosModel();
        
        $idDepto = session('id_departamento_usuario');
        $deptoObj = $idDepto ? $deptoModel->find($idDepto) : null;
        $idUnidad = ($deptoObj && is_array($deptoObj)) ? ($deptoObj['ID_UnidadOperativa'] ?? 0) : 0;

        $data['grupos_presupuestales'] = $grupoModel
            ->where('ID_UnidadOperativa', $idUnidad)
            ->where('activo', true)
            ->orderBy('Nombre', 'ASC')
            ->findAll();

        return view('layout/productTable', $data);
    }
    public function getServiceTableRow()
    {
        return view('layout/serviceTable');
    }


    //Funciones para usuarios
    public function registrarUsuario()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405); // Method Not Allowed
        }

        $data = $this->request->getJSON(true);

        // Validación de los datos
        $rules = [
            'Nombre' => 'required|string|max_length[255]',
            'Correo' => 'required|valid_email|is_unique[Usuarios.Correo]',
            'ID_Dpto' => 'required|is_natural_no_zero',
            'ID_RazonSocial' => 'required|is_natural_no_zero',
            'ContrasenaP' => 'required|min_length[8]',
            'Numero' => 'permit_empty|string|max_length[20]',
            'ContrasenaG' => 'permit_empty|min_length[8]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Datos de entrada inválidos.',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        // Hashear la contraseña
        $data['ContrasenaP'] = password_hash($data['ContrasenaP'], PASSWORD_DEFAULT);
        if (!empty($data['ContrasenaG'])) {
            $data['ContrasenaG'] = password_hash($data['ContrasenaG'], PASSWORD_DEFAULT);
        } else {
            $data['ContrasenaG'] = null; // Opcional: asegúrate de que se guarde como nulo si está vacío
        }
        $usuarioModel = new UsuariosModel();
        $newUserId = $usuarioModel->insert($data, true);

        if ($newUserId) {
            $newUser = $this->api->getUserById($newUserId);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario registrado correctamente.',
                'user' => $newUser,
            ]);
        }

        return $this->response
            ->setStatusCode(500)
            ->setJSON(['success' => false, 'message' => 'No se pudo registrar el usuario.']);
    }
    public function actualizarUsuario($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405); // Method Not Allowed
        }

        $data = $this->request->getJSON(true);

        // Validación básica
        $rules = [
            'Nombre' => 'required|string|max_length[255]',
            'Correo' => 'required|valid_email',
            'ID_Dpto' => 'required|is_natural_no_zero',
            'ID_RazonSocial' => 'required|is_natural_no_zero',
            'Numero' => 'permit_empty|string|max_length[20]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Datos de entrada inválidos.',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        // Si se proporciona una nueva contraseña, la hasheamos.
        if (!empty($data['ContrasenaP'])) {
            $data['ContrasenaP'] = password_hash($data['ContrasenaP'], PASSWORD_DEFAULT);
        } else {
            // Si no se envía, la eliminamos para no sobreescribir la existente con un valor vacío.
            unset($data['ContrasenaP']);
        }
        if (!empty($data['ContrasenaG'])) {
            $data['ContrasenaG'] = password_hash($data['ContrasenaG'], PASSWORD_DEFAULT);
        } else {
            // Si no se envía, la eliminamos para no sobreescribir la existente con un valor vacío.
            unset($data['ContrasenaG']);
        }

        if ($this->api->updateUser((int) $id, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
            ]);
        }

        return $this->response
            ->setStatusCode(500)
            ->setJSON(['success' => false, 'message' => 'No se pudo actualizar el usuario.']);
    }
    public function eliminarUsuario($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(405);
        }

        // --- Medida de seguridad: Evitar eliminar administradores ---
        $userModel = new UsuariosModel();
        $userToDelete = $userModel
            ->select('Departamentos.Nombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Usuarios.ID_Dpto', 'left')
            ->where('Usuarios.ID_Usuario', $id)
            ->first();

        if ($userToDelete && $userToDelete['Nombre'] === 'Administración') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'No se puede eliminar a un usuario administrador.',
            ]);
        }

        if ($this->api->deleteUser((int) $id)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario eliminado correctamente.',
            ]);
        }

        return $this->response
            ->setStatusCode(500)
            ->setJSON(['success' => false, 'message' => 'No se pudo eliminar el usuario.']);
    }


    //Funciones para almacen
    public function registrarMaterial()
    {
        // 1. Usar la validación de CodeIgniter
        $rules = [
            'Codigo' => 'required|is_unique[Producto.Codigo]',
            'Nombre' => 'required',
            'Existencia' => 'required|numeric',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $data = [
                'Codigo' => $this->request->getPost('Codigo'),
                'Nombre' => $this->request->getPost('Nombre'),
                'Existencia' => $this->request->getPost('Existencia'),
            ];

            $newId = $this->api->registrarProductoArray($data);

            if ($newId === false) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'No se pudo registrar el producto en la base de datos.',
                ]);
            }

            return $this->response->setStatusCode(201)->setJSON([
                'success' => true,
                'message' => 'Producto registrado correctamente.',
                'id' => $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Registrar Producto] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al registrar el producto.',
            ]);
        }
    }
    public function eliminarProducto($id = null)
    {
        try {
            $productoModel = new ProductoModel();

            if (!$id || !$this->api->getProductById($id)) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Producto no encontrado o ID no válido.',
                ]);
            }

            if ($this->api->eliminarProductoById($id)) {
                return $this->response->setStatusCode(200)->setJSON([
                    'success' => true,
                    'message' => 'Producto eliminado correctamente.',
                ]);
            } else {
                log_message(
                    'error',
                    '[Eliminar Producto] Error de la base de datos al eliminar el producto ID: ' .
                        $id .
                        ' Errores: ' .
                        json_encode($productoModel->errors()),
                );
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'No se pudo eliminar el producto.',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Eliminar Producto] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al eliminar el producto.',
            ]);
        }
    }
    public function editarProducto($id)
    {
        // 1. Reglas de validación para los datos de entrada
        $rules = [
            'Nombre' => 'required|string|max_length[255]',
            'Existencia' => 'required|numeric|greater_than_equal_to[0]',
        ];

        $data = $this->request->getJSON(true);

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Datos de entrada inválidos.',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $productoActual = $this->api->getProductById($id);
            if (!$productoActual) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Producto no encontrado.']);
            }

            if ($data['Existencia'] < $productoActual['Existencia']) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'No se puede reducir la existencia. Solo se puede aumentar.',
                ]);
            }

            if ($this->api->actualizarProducto($id, $data)) {
                return $this->response->setStatusCode(200)->setJSON([
                    'success' => true,
                    'message' => 'Producto actualizado correctamente.',
                ]);
            }

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'No se pudo actualizar el producto.']);
        } catch (\Throwable $e) {
            log_message('error', '[Editar Producto] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Ocurrió un error inesperado al editar el producto.',
            ]);
        }
    }
    public function descontarStockEntrega()
    {
        $productoModel = new ProductoModel();
        // Recibimos el JSON desde el JS
        $data = $this->request->getJSON(true);

        if (empty($data['materiales'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'No se recibieron materiales.']);
        }

        try {
            // Iteramos sobre los materiales para restarlos
            foreach ($data['materiales'] as $item) {
                $id = $item['id'];
                $cantidad = $item['cantidad'];

                // Obtenemos el producto actual para asegurar la existencia real
                $productoActual = $productoModel->find($id);

                if ($productoActual) {
                    $nuevaExistencia = (float)$productoActual['Existencia'] - (float)$cantidad;

                    // Evitar negativos
                    if ($nuevaExistencia < 0) $nuevaExistencia = 0;

                    // Actualizamos solo la existencia
                    $productoModel->update($id, ['Existencia' => $nuevaExistencia]);

                    // Opcional: Aquí podrías agregar un registro en HistorialProductosModel si lo deseas
                }
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Inventario actualizado.']);

        } catch (\Exception $e) {
            log_message('error', '[Descontar Stock] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error al actualizar inventario: ' . $e->getMessage()
            ]);
        }
    }
    public function insertarHistorialProducto()
    {
        $historialModel = new HistorialProductosModel();
        $session = session();

        $data = $this->request->getJSON(true);

        // Agregar ID_Usuario desde la sesión
        $data['ID_Usuario'] = $session->get('id'); // id del usuario logeado

        try {
            $historialModel->insert($data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function actualizarProducto($id)
    {
        $productoModel = new ProductoModel();

        $data = $this->request->getJSON(true);

        $rules = [
            'Nombre' => 'required|string|max_length[255]',
            'Existencia' => 'required|numeric|greater_than_equal_to[0]',
        ];

        if (!$this->validateData($data, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $this->validator->getErrors(),
            ]);
        }

        try {
            $productoModel->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    //Funciones para proveedores
    public function insertarProveedor()
    {
        $proveedorModel = new ProveedorModel();
        $request = $this->request;

        $data = [
            'RazonSocial' => $request->getPost('RazonSocial'),
            'RFC' => $request->getPost('RFC'),
            'Banco' => $request->getPost('Banco'),
            'Cuenta' => $request->getPost('Cuenta'),
            'Clabe' => $request->getPost('Clabe'),
            'Tel_Contacto' => $request->getPost('Tel_Contacto'),
            'Nombre_Contacto' => $request->getPost('Nombre_Contacto'),
            'Servicio' => $request->getPost('Servicio'),
            'Correo' => $request->getPost('correo'),
        ];

        $tiene_credito = $request->getPost('tiene_credito');

        if (isset($tiene_credito)) {
            $data['Dias_Credito'] = $request->getPost('dias_credito');
            $data['Monto_Credito'] = $request->getPost('monto_credito');
        } else {
            $data['Dias_Credito'] = null;
            $data['Monto_Credito'] = null;
        }

        try {
            if ($proveedorModel->insert($data)) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se pudo insertar el proveedor. Verifique los datos.',
                    'errors' => $proveedorModel->errors(), // Opcional: enviar errores de validación
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function eliminarProveedor($id)
    {
        $proveedorModel = new ProveedorModel();

        if ($proveedorModel->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar el proveedor',
            ]);
        }
    }
    public function editarProveedor($id)
    {
        $model = new ProveedorModel();
        $request = $this->request;

        // Obtener datos del formulario
        $data = [
            'RazonSocial' => $request->getPost('RazonSocial'),
            'RFC' => $request->getPost('RFC'),
            'Correo' => $request->getPost('correo'),
            'Banco' => $request->getPost('Banco'),
            'Cuenta' => $request->getPost('Cuenta'),
            'Clabe' => $request->getPost('Clabe'),
            'Tel_Contacto' => $request->getPost('Tel_Contacto'),
            'Nombre_Contacto' => $request->getPost('Nombre_Contacto'),
            'Servicio' => $request->getPost('Servicio'),
        ];
        $tiene_credito = $request->getPost('tiene_credito');

        if (isset($tiene_credito)) {
            $data['Dias_Credito'] = $request->getPost('dias_credito');
            $data['Monto_Credito'] = $request->getPost('monto_credito');
        } else {
            $data['Dias_Credito'] = null;
            $data['Monto_Credito'] = null;
        }

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }


    //Funcion crud para razon social
    public function insertarRazonSocial()
    {
        $model = new RazonSocialModel();
        $data = $this->request->getPost(['Nombre', 'RFC']);

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo insertar la razón social',
            ]);
        }
    }
    public function editarRazonSocial($id)
    {
        $model = new RazonSocialModel();
        $data = $this->request->getPost(['Nombre', 'RFC']);

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function eliminarRazonSocial($id)
    {
        $model = new RazonSocialModel();

        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar la razón social',
            ]);
        }
    }


    // --- CRUD PLACES ---
    public function insertarPlace()
    {
        $model = new PlacesModel();
        // AGREGAR 'ID_RazonSocial' e 'id_segmento' al array
        $postData = $this->request->getPost();
        
        $data = [
            'Nombre_Corto'    => $postData['Nombre_Corto'] ?? '',
            'Nombre_Completo' => $postData['Nombre_Completo'] ?? '',
            'ID_RazonSocial'  => $postData['ID_RazonSocial'] ?? '',
            'id_segmento'     => !empty($postData['id_segmento']) ? (int)$postData['id_segmento'] : null,
            'activo'          => true
        ];

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            // ... resto del código igual
            return $this->response->setJSON(['success' => false, 'message' => 'Error', 'errors' => $model->errors()]);
        }
    }
    public function editarPlace($id)
    {
        $model = new PlacesModel();
        $postData = $this->request->getPost();
        
        $placeActual = $model->find($id);
        if (!$placeActual) return $this->response->setJSON(['success' => false, 'message' => 'Lugar no encontrado']);

        $valPost = $this->request->getPost('activo');
        $esActivoPost = ($valPost === 'on' || $valPost === '1' || $valPost === 1 || $valPost === true);

        $nuevoSegmento = !empty($postData['id_segmento']) ? (int)$postData['id_segmento'] : null;
        $segmentoAnterior = (int)($placeActual['id_segmento'] ?? 0);

        if ($nuevoSegmento !== $segmentoAnterior && $segmentoAnterior !== 0) {
            // CAMBIO DE SEGMENTO: Clonación en cascada
            $db = \Config\Database::connect();
            $db->transStart();

            // 1. Desactivar lugar viejo
            $model->update($id, ['activo' => false]);

            // 2. Crear nuevo lugar
            $idNuevoPlace = $model->insert([
                'Nombre_Corto'    => $postData['Nombre_Corto'] ?? $placeActual['Nombre_Corto'],
                'Nombre_Completo' => $postData['Nombre_Completo'] ?? $placeActual['Nombre_Completo'],
                'ID_RazonSocial'  => $postData['ID_RazonSocial'] ?? $placeActual['ID_RazonSocial'],
                'id_segmento'     => $nuevoSegmento,
                'activo'          => true
            ], true);

            // 3. Clonar Unidades Operativas y sus Grupos
            $unidadModel = new \App\Models\UnidadOperativaModel();
            $grupoModel = new \App\Models\GrupoPresupuestalModel();
            $deptoModel = new \App\Models\DepartamentosModel();

            $unidadesViejas = $unidadModel->where('ID_Place', $id)->findAll();
            foreach ($unidadesViejas as $uv) {
                $idNuevaUni = $unidadModel->insert([
                    'Nombre'   => $uv['Nombre'],
                    'ID_Place' => $idNuevoPlace,
                    'activo'   => filter_var($uv['activo'], FILTER_VALIDATE_BOOLEAN)
                ], true);

                // Clonar Grupos de esta unidad
                $gruposViejos = $grupoModel->where('ID_UnidadOperativa', $uv['ID_UnidadOperativa'])->findAll();
                foreach ($gruposViejos as $gv) {
                    $grupoModel->insert([
                        'Nombre'             => $gv['Nombre'],
                        'Descripcion'        => $gv['Descripcion'],
                        'ID_UnidadOperativa' => $idNuevaUni,
                        'activo'             => filter_var($gv['activo'], FILTER_VALIDATE_BOOLEAN)
                    ]);
                }

                // Mover Departamentos a la nueva unidad y lugar
                $deptoModel->where('ID_UnidadOperativa', $uv['ID_UnidadOperativa'])
                           ->update(null, ['ID_UnidadOperativa' => $idNuevaUni, 'ID_Place' => $idNuevoPlace]);
            }

            $db->transComplete();
            if ($db->transStatus() === false) return $this->response->setJSON(['success' => false, 'message' => 'Error al migrar complejo']);

            return $this->response->setJSON(['success' => true, 'message' => 'Complejo migrado al nuevo segmento. El historial permanece en el segmento anterior.']);
        } else {
            // Actualización normal
            $data = [
                'Nombre_Corto'    => $postData['Nombre_Corto'] ?? '',
                'Nombre_Completo' => $postData['Nombre_Completo'] ?? '',
                'ID_RazonSocial'  => $postData['ID_RazonSocial'] ?? '',
                'id_segmento'     => $nuevoSegmento,
                'activo'          => $esActivoPost
            ];
            if ($model->update($id, $data)) return $this->response->setJSON(['success' => true]);
            return $this->response->setJSON(['success' => false, 'message' => 'Error al actualizar']);
        }
    }
    public function eliminarPlace($id)
    {
        $model = new PlacesModel();

        try {
            if ($model->delete($id)) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se pudo eliminar el lugar',
                ]);
            }
        } catch (DatabaseException $e) {
            if (strpos($e->getMessage(), '1451') !== false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar este complejo porque tiene departamentos, presupuestos o solicitudes asociados. 🚫',
                ]);
            }
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error de base de datos al intentar eliminar.',
            ]);
        }
    }


    // --- CRUD DEPARTAMENTOS ---
    public function insertarDepartamento()
    {
        $model = new DepartamentosModel();
        
        $nombre = $this->request->getPost('Nombre');
        $unidadId = $this->request->getPost('ID_UnidadOperativa');
        $placeId = $this->request->getPost('ID_Place'); // Capturamos el lugar elegido

        if (empty($nombre) || empty($placeId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'El nombre y el complejo son obligatorios',
            ]);
        }

        $data = [
            'Nombre'             => $nombre,
            'ID_UnidadOperativa' => !empty($unidadId) ? $unidadId : null,
            'ID_Place'           => $placeId,
        ];

        if ($model->insert($data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => "Departamento creado correctamente.",
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo insertar el departamento',
            ]);
        }
    }

    public function editarDepartamento($id)
    {
        $model = new DepartamentosModel();
        
        $unidadId = $this->request->getPost('ID_UnidadOperativa');

        $data = [
            'Nombre'             => $this->request->getPost('Nombre'),
            'ID_UnidadOperativa' => !empty($unidadId) ? $unidadId : null,
            'ID_Place'           => $this->request->getPost('ID_Place')
        ];

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
    public function eliminarDepartamento($id)
    {
        $model = new DepartamentosModel();

        try {
            if ($model->delete($id)) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se pudo eliminar el departamento',
                ]);
            }
        } catch (DatabaseException $e) {
            if (strpos($e->getMessage(), '1451') !== false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se puede eliminar este departamento porque tiene presupuestos o solicitudes asociados. 🚫',
                ]);
            }
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error de base de datos al intentar eliminar.',
            ]);
        }
    }


    // --- CRUD CUENTAS ---
    public function getCuentasByProveedor($idProveedor)
    {
        // Instanciamos el modelo directamente como en tus otros ejemplos
        $cuentasModel = new CuentasModel();

        // Buscamos todas las cuentas asociadas a este proveedor
        $cuentas = $cuentasModel->where('ID_Proveedor', $idProveedor)->findAll();

        // Retornamos en formato JSON
        return $this->response->setJSON($cuentas);
    }
    public function insertarCuenta()
    {
        $model = new CuentasModel();

        // Obtenemos los datos enviados por FormData
        $data = [
            'ID_Proveedor' => $this->request->getPost('ID_Proveedor'),
            'Cuenta'       => $this->request->getPost('Cuenta')
        ];

        // Validar que tengamos el ID del proveedor
        if (empty($data['ID_Proveedor'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'No se identificó al proveedor.']);
        }

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo guardar la cuenta.',
                'errors' => $model->errors()
            ]);
        }
    }
    public function actualizarCuenta($id)
    {
        $model = new CuentasModel();

        // Recibimos solo el campo 'Cuenta' que es el editable
        $data = [
            'Cuenta' => $this->request->getPost('Cuenta')
        ];

        // Validamos que no esté vacío
        if (empty($data['Cuenta'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'El número de cuenta es obligatorio.']);
        }

        try {
            // CodeIgniter update: update($primaryKey, $data)
            if ($model->update($id, $data)) {
                return $this->response->setJSON(['success' => true]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No se pudo actualizar la cuenta.',
                    'errors' => $model->errors()
                ]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error del servidor: ' . $e->getMessage()
            ]);
        }
    }
    public function eliminarCuenta($id)
    {
        $model = new CuentasModel();

        // CodeIgniter delete
        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar la cuenta.'
            ]);
        }
    }

    // ----------- Grupos Presupuestales ------------
    public function insertarGrupo()
    {
        $postData = $this->request->getPost();
        $comentarios = $this->request->getPost('comentarios');
        
        $data = [
            'Nombre'             => $postData['Nombre'] ?? '',
            'Descripcion'        => $postData['Descripcion'] ?? '',
            'ID_UnidadOperativa' => !empty($postData['ID_UnidadOperativa']) ? $postData['ID_UnidadOperativa'] : null,
            'activo'             => true // Booleano nativo para compatibilidad universal
        ];

        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'GrupoPresupuestal',
            'Accion'        => 'Insertar',
            'ID_Afectado'   => null,
            'Datos_Payload' => json_encode($data),
            'Datos_Antiguos'=> null,
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al enviar a revisión', 'errors' => $solicitudesModel->errors()]);
        }
    }
    
    public function editarGrupo($id)
    {
        $postData = $this->request->getPost();
        $comentarios = $this->request->getPost('comentarios');
        
        // 1. Captura booleana robusta
        $valPost = $this->request->getPost('activo');
        $esActivoPost = ($valPost === 'on' || $valPost === '1' || $valPost === 1 || $valPost === true);
        
        $nuevaUnidad = !empty($postData['ID_UnidadOperativa']) ? (int)$postData['ID_UnidadOperativa'] : null;

        $data = [
            'Nombre'             => $postData['Nombre'] ?? '',
            'Descripcion'        => $postData['Descripcion'] ?? '',
            'ID_UnidadOperativa' => $nuevaUnidad,
            'activo'             => $esActivoPost
        ];

        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        
        $modeloReal = new \App\Models\GrupoPresupuestalModel();
        $datosAntiguos = $modeloReal->find($id);

        try {
            $solicitudesModel->insert([
                'ID_Usuario'    => session('id'),
                'Modulo'        => 'GrupoPresupuestal',
                'Accion'        => 'Editar',
                'ID_Afectado'   => $id,
                'Datos_Payload' => json_encode($data),
                'Datos_Antiguos'=> json_encode($datosAntiguos),
                'Estado'        => 'Pendiente',
                'Comentarios_Solicitante' => $comentarios
            ]);
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function eliminarGrupo($id)
    {
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $comentarios = $this->request->getPost('comentarios');
        
        $modeloReal = new \App\Models\GrupoPresupuestalModel();
        $datosAntiguos = $modeloReal->find($id);

        // En lugar de eliminar, solicitamos desactivar
        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'GrupoPresupuestal',
            'Accion'        => 'Eliminar', // Se manejará como desactivación en el dictamen
            'ID_Afectado'   => $id,
            'Datos_Payload' => json_encode(['activo' => false]),
            'Datos_Antiguos'=> json_encode($datosAntiguos),
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Solicitud de desactivación enviada a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo enviar la solicitud de desactivación',
            ]);
        }
    }

    //-----Bancos de Dpto -------------
    public function insertarBancoDpto()
    {
        // Recibimos ID_RazonSocial, Banco, Clabe
        $data = $this->request->getPost(['ID_RazonSocial', 'Banco', 'Clabe']);
        $comentarios = $this->request->getPost('comentarios');
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'BancoDpto',
            'Accion'        => 'Insertar',
            'ID_Afectado'   => null,
            'Datos_Payload' => json_encode($data),
            'Datos_Antiguos'=> null,
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al enviar a revisión',
                'errors' => $solicitudesModel->errors()
            ]);
        }
    }
    public function editarBancoDpto($id)
    {
        $data = $this->request->getPost(['ID_RazonSocial', 'Banco', 'Clabe']);
        $comentarios = $this->request->getPost('comentarios');
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        
        $modeloReal = new \App\Models\BancoDptoModel();
        $datosAntiguos = $modeloReal->find($id);

        try {
            $solicitudesModel->insert([
                'ID_Usuario'    => session('id'),
                'Modulo'        => 'BancoDpto',
                'Accion'        => 'Editar',
                'ID_Afectado'   => $id,
                'Datos_Payload' => json_encode($data),
                'Datos_Antiguos'=> json_encode($datosAntiguos),
                'Estado'        => 'Pendiente',
                'Comentarios_Solicitante' => $comentarios
            ]);
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function eliminarBancoDpto($id)
    {
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $comentarios = $this->request->getPost('comentarios');
        
        $modeloReal = new \App\Models\BancoDptoModel();
        $datosAntiguos = $modeloReal->find($id);

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'BancoDpto',
            'Accion'        => 'Eliminar',
            'ID_Afectado'   => $id,
            'Datos_Payload' => json_encode(['id' => $id]),
            'Datos_Antiguos'=> json_encode($datosAntiguos),
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Solicitud de eliminación enviada a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo enviar la solicitud de eliminación a revisión',
            ]);
        }
    }

    // ----------- Unidades Operativas ------------
    public function insertarUnidadOperativa()
    {
        $data = $this->request->getPost(['Nombre', 'ID_Place']);
        $data['activo'] = true;
        $comentarios = $this->request->getPost('comentarios');
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'UnidadOperativa',
            'Accion'        => 'Insertar',
            'ID_Afectado'   => null,
            'Datos_Payload' => json_encode($data),
            'Datos_Antiguos'=> null,
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al enviar a revisión', 'errors' => $solicitudesModel->errors()]);
        }
    }

    public function editarUnidadOperativa($id)
    {
        $postData = $this->request->getPost();
        $comentarios = $this->request->getPost('comentarios');
        
        // Captura booleana robusta
        $valPost = $this->request->getPost('activo');
        $esActivo = ($valPost === 'on' || $valPost === '1' || $valPost === 1 || $valPost === true);

        $data = [
            'Nombre'   => $postData['Nombre'] ?? '',
            'ID_Place' => !empty($postData['ID_Place']) ? $postData['ID_Place'] : null,
            'activo'   => $esActivo
        ];
        
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        
        $modeloReal = new \App\Models\UnidadOperativaModel();
        $datosAntiguos = $modeloReal->find($id);

        try {
            $solicitudesModel->insert([
                'ID_Usuario'    => session('id'),
                'Modulo'        => 'UnidadOperativa',
                'Accion'        => 'Editar',
                'ID_Afectado'   => $id,
                'Datos_Payload' => json_encode($data),
                'Datos_Antiguos'=> json_encode($datosAntiguos),
                'Estado'        => 'Pendiente',
                'Comentarios_Solicitante' => $comentarios
            ]);
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Cambio enviado a Dirección para su autorización.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function eliminarUnidadOperativa($id)
    {
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $comentarios = $this->request->getPost('comentarios');
        
        $modeloReal = new \App\Models\UnidadOperativaModel();
        $datosAntiguos = $modeloReal->find($id);
        
        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'UnidadOperativa',
            'Accion'        => 'Eliminar',
            'ID_Afectado'   => $id,
            'Datos_Payload' => json_encode(['activo' => false]),
            'Datos_Antiguos'=> json_encode($datosAntiguos),
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->response->setJSON(['success' => true, 'pending_review' => true, 'message' => 'Solicitud de desactivación enviada a Dirección para su autorización.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al enviar a revisión']);
        }
    }
}
