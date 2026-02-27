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
use App\Models\PresupuestoMensualModel;

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
                    ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();
                return view('modales/ver_historial', $data);

            case 'solicitar_material':
                $proveedorModel = new ProveedorModel();
                $razonSocialModel = new RazonSocialModel();

                // Obtener proveedores
                $data['proveedores'] = $proveedorModel
                    ->select('ID_Proveedor, RazonSocial, Tel_Contacto, RFC, Servicio')
                    ->orderBy('RazonSocial', 'ASC')
                    ->findAll();

                // Obtener razones sociales
                $data['razones_sociales'] = $razonSocialModel
                    ->select('ID_RazonSocial, Nombre')
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();

                // Cargar la vista única que contiene las tres pantallas
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
                    ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
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
                // 1. Necesitamos el modelo de Razon Social
                $razonSocialModel = new RazonSocialModel();

                // 2. Modificamos la consulta para traer el NOMBRE de la Razón Social
                $data['places'] = $placesModel
                    ->select('Places.*, Razon_Social.Nombre as RazonSocial_Nombre') // Seleccionamos campos
                    ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial', 'left') // Unimos tablas
                    ->orderBy('Places.Nombre_Corto', 'ASC')
                    ->findAll();

                // 3. Enviamos la lista de razones sociales para llenar los <select>
                $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();

                return view('modales/crud_places', $data);

            case 'crud_departamento':
                $deptosModel = new DepartamentosModel();
                $placesModel = new PlacesModel();

                // Obtenemos los departamentos junto con el nombre del lugar
                $data['departamentos'] = $deptosModel
                    ->select('Departamentos.*, Places.Nombre_Completo as PlaceNombre')
                    ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();

                // Obtenemos los lugares para llenar el select
                $data['places'] = $placesModel->orderBy('Nombre_Completo', 'ASC')->findAll();

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
                $departamentosModel = new DepartamentosModel();
                $placesModel = new PlacesModel();

                $data['grupos'] = $grupoModel
                    ->orderBy('Nombre', 'ASC')
                    ->findAll();

                // Fetch departments with their associated place names
                $data['departamentos'] = $departamentosModel
                    ->select('Departamentos.ID_Dpto, Departamentos.Nombre, Places.Nombre_Corto as PlaceNombre')
                    ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();

                return view('modales/CrudGrupos', $data);

            case 'BancoDpto':
                $bancoModel = new BancoDptoModel();
                $dptoModel  = new DepartamentosModel();
                $data['bancos_dpto'] = $bancoModel->withDepartamento()->findAll();
                $data['departamentos'] = $dptoModel
                    ->select('Departamentos.*, Places.Nombre_Completo as PlaceNombre')
                    ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
                    ->orderBy('Departamentos.Nombre', 'ASC')
                    ->findAll();

                return view('modales/BancoDpto', $data);

            case 'PresupuestoMensual':
                $razonSocialModel = new \App\Models\RazonSocialModel();
                $placesModel      = new \App\Models\PlacesModel();

                // Mandamos catálogos base para los primeros dos selectores
                $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();
                $data['places']           = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();

                return view('modales/control/PresupuestoMensual', $data);

            default:
                return 'Opción no válida';
        }
    }

    //Funciones para tablas
    public function getProductTableRow()
    {
        return view('layout/productTable');
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
        //return Rest::ShowDebug($data);
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
        // AGREGAR 'ID_RazonSocial' al array
        $data = $this->request->getPost(['Nombre_Corto', 'Nombre_Completo', 'ID_RazonSocial']);

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
        // AGREGAR 'ID_RazonSocial' al array
        $data = $this->request->getPost(['Nombre_Corto', 'Nombre_Completo', 'ID_RazonSocial']);

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            // ... resto del código igual
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function eliminarPlace($id)
    {
        $model = new PlacesModel();

        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar el lugar',
            ]);
        }
    }


    // --- CRUD DEPARTAMENTOS ---
    public function insertarDepartamento()
    {
        $model = new DepartamentosModel();
        $data = $this->request->getPost(['Nombre', 'ID_Place']);

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo insertar el departamento',
                'errors' => $model->errors()
            ]);
        }
    }
    public function editarDepartamento($id)
    {
        $model = new DepartamentosModel();
        $data = $this->request->getPost(['Nombre', 'ID_Place']);

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

        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar el departamento',
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
        $model = new GrupoPresupuestalModel();
        // Recibimos Nombre, Descripcion y ID_Dpto
        $data = $this->request->getPost(['Nombre', 'Descripcion', 'ID_Dpto']);

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Error al guardar', 'errors' => $model->errors()]);
        }
    }
    public function editarGrupo($id)
    {
        $model = new GrupoPresupuestalModel();
        $data = $this->request->getPost(['Nombre', 'Descripcion', 'ID_Dpto']);

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function eliminarGrupo($id)
    {
        $model = new GrupoPresupuestalModel();

        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar el grupo presupuestal',
            ]);
        }
    }

    //-----Bancos de Dpto -------------
    public function insertarBancoDpto()
    {
        $model = new BancoDptoModel();
        // Recibimos ID_Dpto, Banco, Clabe
        $data = $this->request->getPost(['ID_Dpto', 'Banco', 'Clabe']);

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error al guardar',
                'errors' => $model->errors()
            ]);
        }
    }
    public function editarBancoDpto($id)
    {
        $model = new BancoDptoModel();
        $data = $this->request->getPost(['ID_Dpto', 'Banco', 'Clabe']);

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    public function eliminarBancoDpto($id)
    {
        $model = new BancoDptoModel();

        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo eliminar el registro',
            ]);
        }
    }
}

