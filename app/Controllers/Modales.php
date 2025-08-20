<?php

namespace App\Controllers;
use App\Models\DepartamentosModel;
use App\Models\RazonSocialModel;
use App\Models\UsuariosModel;


class Modales extends BaseController
{
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
                return view('modales/ver_historial');
                break;

            case 'solicitar_material':
                return view('modales/solicitar_material', $data);
                break;

            case 'revisar_solicitudes':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre')
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'En espera')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                return view('modales/revisar_solicitudes', $data);
                break;

            case 'proveedores':
                return view('modales/proveedores');
                break;

            case 'ordenes_compra':
                return view('modales/ordenes_compra');
                break;

            case 'enviar_revision':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre')
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->where('Solicitud.Estado', 'Cotizado')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
                    ->findAll();

                return view('modales/enviar_revision', $data);
                break;

            case 'usuarios':
                $departamentosModel = new DepartamentosModel();
                $razonSocialModel = new RazonSocialModel();

                $data = [
                    'departamentos' => $departamentosModel->findAll(),
                    'razones_sociales' => $razonSocialModel->findAll(),
                ];

                return view('modales/usuarios', $data);
                break;

            case 'dictamen_solicitudes':
                return view('modales/dictamen_solicitudes');
                break;

            case 'crud_proveedores':
                return view('modales/crud_proveedores');
                break;

            case 'limpiar_almacenamiento':
                return view('modales/limpiar_almacenamiento');
                break;

            case 'pagos_pendientes':
                return view('modales/pagos_pendientes');
                break;

            case 'registrar_productos':
                $productoModel = new \App\Models\ProductoModel();
                $data['productos'] = $productoModel->findAll();

                return view('modales/registrar_productos', $data);
                break;


            case 'crud_productos':
                return view('modales/crud_productos');
                break;

            case 'entrega_productos':
                return view('modales/entrega_productos');
                break;

            default:
                return 'Opción no válida';
        }
    }


    public function registrarUsuario()
    {
        $usuarioModel = new UsuariosModel();

        $datos = [
            'ID_Dpto'        => $this->request->getPost('departamento'),
            'ID_RazonSocial' => $this->request->getPost('razon_social'),
            'Nombre'         => $this->request->getPost('nombre'),
            'Correo'         => $this->request->getPost('correo'),
            'Contrasena'     => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'Numero'         => $this->request->getPost('telefono'),
        ];

        if ($usuarioModel->insert($datos)) {
            // Si es una solicitud AJAX, respondemos con JSON
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Usuario registrado correctamente.'
                ]);
            }
            return redirect()->to(site_url('modales/usuarios'))
                ->with('success', 'Usuario registrado correctamente.');
        } else {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al registrar usuario.'
                ]);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar usuario.');
        }
    }

    public function registrarMaterial()
    {
        try {
            $codigo = $this->request->getPost('Codigo');
            $nombre = $this->request->getPost('Nombre');
            $existencia = $this->request->getPost('Existencia');

            if (empty($codigo) || empty($nombre) || $existencia === null) {
                throw new \Exception("Datos incompletos: " . json_encode($this->request->getPost()));
            }

            $productoModel = new \App\Models\ProductoModel();
            $productoModel->insert([
                'Codigo'     => $codigo,
                'Nombre'     => $nombre,
                'Existencia' => $existencia
            ]);

            if ($productoModel->db->affectedRows() <= 0) {
                throw new \Exception("No se pudo insertar el producto");
            }

            return $this->response->setJSON(['success' => true]);

        } catch (\Throwable $e) {
            log_message('error', '[Registrar Producto] ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Ocurrió un error al registrar el producto.'
            ]);
        }
    }



}
