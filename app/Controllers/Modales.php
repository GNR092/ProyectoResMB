<?php

namespace App\Controllers;
use App\Models\DepartamentosModel;
use App\Models\RazonSocialModel;
use App\Models\UsuariosModel;
use App\Libraries\Rest;

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
                return view('modales/solicitar_material', $data);

            case 'revisar_solicitudes':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En espera')
                    ->orderBy('Solicitud.ID_SolicitudProd', 'DESC')
                    ->findAll();

                return view('modales/revisar_solicitudes', $data);

            case 'proveedores':
                return view('modales/proveedores');

            case 'ordenes_compra':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre'
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'Aprobado')
                    ->orderBy('Solicitud.ID_SolicitudProd', 'DESC')
                    ->findAll();

                return view('modales/ordenes_compra', $data);


            case 'enviar_revision':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'Cotizado')
                    ->orderBy('Solicitud.ID_SolicitudProd', 'DESC')
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

            case 'dictamen_solicitudes':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select(
                        'Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre',
                    )
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En revision')
                    ->orderBy('Solicitud.ID_SolicitudProd', 'DESC')
                    ->findAll();

                return view('modales/dictamen_solicitudes', $data);

            case 'crud_proveedores':
                return view('modales/crud_proveedores');

            case 'limpiar_almacenamiento':
                return view('modales/limpiar_almacenamiento');

            case 'pagos_pendientes':
                return view('modales/pagos_pendientes');

            case 'registrar_productos':
                $productoModel = new \App\Models\ProductoModel();
                $data['productos'] = $productoModel->findAll();

                return view('modales/registrar_productos', $data);

            case 'crud_productos':
                $productoModel = new \App\Models\ProductoModel();

                // Orden numérico ascendente por el campo texto "Codigo"
                $data['productos'] = $productoModel
                    ->select('Producto.*')
                    ->orderBy('CAST("Producto"."Codigo" AS INTEGER)', 'ASC', false)
                    ->findAll();

                return view('modales/crud_productos', $data);

            case 'entrega_productos':
                return view('modales/entrega_productos');

            default:
                return 'Opción no válida';
        }
    }

    public function getProductTableRow()
    {
        return view('layout/productTable');
    }

    //Funciones para usuarios
    public function registrarUsuario()
    {
        $usuarioModel = new UsuariosModel();

        $datos = [
            'ID_Dpto' => $this->request->getPost('departamento'),
            'ID_RazonSocial' => $this->request->getPost('razon_social'),
            'Nombre' => $this->request->getPost('nombre'),
            'Correo' => $this->request->getPost('correo'),
            'Contrasena' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'Numero' => $this->request->getPost('telefono'),
        ];

        if ($this->api->addUser($datos)) {
            // Si es una solicitud AJAX, respondemos con JSON
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Usuario registrado correctamente.',
                ]);
            }
            return redirect()
                ->to(site_url('modales/usuarios'))
                ->with('success', 'Usuario registrado correctamente.');
        } else {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al registrar usuario.',
                ]);
            }
            return redirect()->back()->withInput()->with('error', 'Error al registrar usuario.');
        }
    }

    //Funciones para materiales
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

    //Funciones para almacen
    public function eliminarProducto($id = null)
    {
        try {
            $productoModel = new \App\Models\ProductoModel();

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
}