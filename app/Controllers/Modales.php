<?php

namespace App\Controllers;
use App\Models\DepartamentosModel;
use App\Models\RazonSocialModel;
use App\Models\UsuarioModel;


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

            case 'solicitar_material':
                return view('modales/solicitar_material', $data);

            case 'revisar_solicitudes':
                return view('modales/revisar_solicitudes');

            case 'proveedores':
                return view('modales/proveedores');

            case 'ordenes_compra':
                return view('modales/ordenes_compra');

            case 'enviar_revision':
                $solicitudModel = new \App\Models\SolicitudModel();

                $data['solicitudes'] = $solicitudModel
                    ->select('Solicitud.*, Usuarios.Nombre AS UsuarioNombre, Departamentos.Nombre AS DepartamentoNombre')
                    ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
                    ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
                    ->orderBy('Solicitud.ID_Solicitud', 'DESC')
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
                return view('modales/dictamen_solicitudes');

            case 'crud_proveedores':
                return view('modales/crud_proveedores');

            case 'limpiar_almacenamiento':
                return view('modales/limpiar_almacenamiento');

            case 'pagos_pendientes':
                return view('modales/pagos_pendientes');

            case 'registrar_productos':
                return view('modales/registrar_productos');

            case 'crud_productos':
                return view('modales/crud_productos');

            case 'entrega_productos':
                return view('modales/entrega_productos');

            default:
                return 'Opción no válida';
        }
    }


    public function registrarUsuario()
    {
        $usuarioModel = new UsuarioModel();

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


}
