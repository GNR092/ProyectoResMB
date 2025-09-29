<?php

namespace App\Controllers;

use Config\MenuOptions;
use App\Models\DepartamentosModel;
use App\Models\UsuariosModel;
use App\Libraries\Rest;


class Home extends BaseController
{
    
    public function index()
    {
        if(session('isLoggedIn'))
        {    
        $configMenu = new MenuOptions();
        $opcionesDisponibles = $configMenu->opciones;

        // Definir permisos por rol/departamento
        $permisosPorDepto = [
            // Rol SuperAdmin: ve todo
            'Administración' => array_keys($opcionesDisponibles),
            
            // Rol Compras
            'Compras' => [
                'revisar_solicitudes',
                'enviar_revision',
                'crud_proveedores',
                'ver_historial',
                'crud_usuarios',
                'limpiar_almacenamiento', // Almacenamiento
                'ficha_pago'
            ],

            // Rol Dirección
            'Direccion' => [
                'dictamen_solicitudes',
                'crud_proveedores',
                'usuarios'
            ],

            // Rol Tesorería
            'Tesoreria' => ['ordenes_compra'],

            // Rol Almacén
            'Almacen' => [
                'registrar_productos',
                'crud_productos', // Existencias
                'entrega_productos'
            ],

            // Rol por defecto (Jefes de Departamento)
            'default' => [
                'solicitar_material',
                'ver_historial'
            ]
        ];

        $departamentos = new DepartamentosModel();
        $usuarios = new UsuariosModel();
        $usuario = $usuarios->find(session('id'));
        $departamento = $departamentos->find($usuario['ID_Dpto']);
        $nombreDepartamento = $departamento['Nombre'] ?? 'default';

        $loginType = session('login_type');
        $permisosUsuario = $permisosPorDepto[$nombreDepartamento] ?? $permisosPorDepto['default'];

        // --- Lógica de permisos por tipo de login (Jefe vs Empleado) ---
        if ($loginType === 'boss' && $nombreDepartamento !== 'Administración' && $nombreDepartamento !== 'Compras') {
            // Si es Jefe, añadimos el permiso para aprobar solicitudes.
            $permisosUsuario[] = 'aprobar_solicitudes';
        }
        // Los empleados ('employee') se quedan con los permisos por defecto de su depto.

        $opcionesFiltradas = array_filter($opcionesDisponibles, fn($key) => in_array($key, $permisosUsuario), ARRAY_FILTER_USE_KEY);

        // Determinar el texto del modo de inicio de sesión
        $loginType = session('login_type');
        $loginModeText = '';
        if ($loginType === 'employee') {
            $loginModeText = 'Empleado';
        } elseif ($loginType === 'boss') {
            $loginModeText = 'Jefe de Depto.';
        }

        $data = [
            'opcionesDinamicas' => $opcionesFiltradas,
            'nombre_usuario' => session('nombre_usuario') ?? 'Usuario',
            'departamento_usuario' => $departamento['Nombre'] ?? 'Departamento',
            'id_departamento_usuario' => $usuario['ID_Dpto'] ?? null,
            'departamentos' => $departamentos->findall(),
            'modo_login' => $loginModeText,
            'login_type' => $loginType, // Pasamos el tipo de login a la vista
        ];
        $session = session();
        $session->set($data);

        return view('inicio', $data);
        }
        else
        {
            return redirect()->to('/auth');
        }
    }
}
