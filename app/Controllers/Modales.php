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

class Modales extends BaseController
{
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }
    public function mostrar($opcion)
    {
        //agregar el lugar de los de partamentos
        $session = session();
        $data = [
            'departamentos' => $session->get('departamentos'),
            'nombre_usuario' => $session->get('nombre_usuario'),
            'departamento_usuario' => $session->get('departamento_usuario'),
        ];

        switch ($opcion) {
            case 'ver_historial':
                return view('modales/ver_historial');

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
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre'
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
                $solicitudModel = new SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre'
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'Aprobada')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                return view('modales/ordenes_compra', $data);

            case 'enviar_revision':
                return view('modales/enviar_revision');
            
            case 'usuarios':
                $departamentosModel = new DepartamentosModel();
                $razonSocialModel = new RazonSocialModel();

                $data = [
                    'departamentos' => $departamentosModel->findAll(),
                    'razones_sociales' => $razonSocialModel->findAll(),
                ];

                return view('modales/usuarios', $data);

            case 'crud_usuarios':
                $usuariosModel = new UsuariosModel();
                $data['usuarios'] = $this->api->getAllUsers();
                $data['razones_sociales'] = (new RazonSocialModel())->findAll();
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
                $data['proveedores'] = $proveedorModel
                    ->orderBy('ID_Proveedor', 'ASC')
                    ->findAll();

                return view('modales/crud_proveedores', $data);

            case 'limpiar_almacenamiento':
                return view('modales/limpiar_almacenamiento');

            case 'pagos_pendientes':
                $solicitudModel = new SolicitudModel();

                // Solicitudes con estado "Por Pagar"
                $data['solicitudes_contado'] = $solicitudModel
                    ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre')
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'Por Pagar')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                $data['solicitudes_credito'] = $solicitudModel
                    ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre')
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'Por Pagar')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                return view('modales/pagos_pendientes', $data);
                
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
                $departamentosModel = new DepartamentosModel(); // <- para cargar los departamentos
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
                return view('modales/ficha_pago');

            case 'aprobar_solicitudes':
                $idDepartamentoJefe = $this->api->getUserById(session('id'))['ID_Dpto'];
                $data['solicitudes_pendientes'] = $this->api->getSolicitudesUsersByDepartment($idDepartamentoJefe);

                return view('modales/aprobar_solicitudes', $data);

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

        $newUserId = (new UsuariosModel())->insert($data, true);

        if ($newUserId) {
            $newUser = $this->api->getUserById($newUserId);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Usuario registrado correctamente.',
                'user' => $newUser,
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'No se pudo registrar el usuario.']);
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

        if ($this->api->updateUser((int)$id, $data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'No se pudo actualizar el usuario.']);
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
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No se puede eliminar a un usuario administrador.']);
        }

        if ($this->api->deleteUser((int)$id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Usuario eliminado correctamente.']);
        }

        return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'No se pudo eliminar el usuario.']);
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
                'errors' => $this->validator->getErrors()
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

        $data = $this->request->getPost([
            'RazonSocial',
            'RFC',
            'Banco',
            'Cuenta',
            'Clabe',
            'Tel_Contacto',
            'Nombre_Contacto',
            'Servicio',
        ]);

        if ($proveedorModel->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se pudo insertar el proveedor'
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
                'message' => 'No se pudo eliminar el proveedor'
            ]);
        }
    }
    public function editarProveedor($id)
    {
        $model = new ProveedorModel();

        // Obtener datos del formulario
        $data = [
            'RazonSocial'       => $this->request->getPost('RazonSocial'),
            'RFC'               => $this->request->getPost('RFC'),
            'Banco'             => $this->request->getPost('Banco'),
            'Cuenta'            => $this->request->getPost('Cuenta'),
            'Clabe'             => $this->request->getPost('Clabe'),
            'Tel_Contacto'      => $this->request->getPost('Tel_Contacto'),
            'Nombre_Contacto'   => $this->request->getPost('Nombre_Contacto'),
            'Servicio'          => $this->request->getPost('Servicio'),
        ];

        try {
            $model->update($id, $data);
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }




}