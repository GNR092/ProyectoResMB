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
        $api = new Rest();
        if(session('isLoggedIn'))
        {    
        $configMenu = new MenuOptions();
        $opcionesDisponibles = $configMenu->opciones;

        $departamentos = new DepartamentosModel();
        $usuarios = new UsuariosModel();
        $usuario = $usuarios->find(session('id'));
        
        $departamento = $departamentos->find($usuario['ID_Dpto']);
        $nombreDepartamento = $departamento['Nombre'] ?? 'default';

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
                'usuarios',
                'limpiar_almacenamiento' // Almacenamiento
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

        $permisosUsuario = $permisosPorDepto[$nombreDepartamento] ?? $permisosPorDepto['default'];
        $opcionesFiltradas = array_filter($opcionesDisponibles, fn($key) => in_array($key, $permisosUsuario), ARRAY_FILTER_USE_KEY);

        $data = [
            'opcionesDinamicas' => $opcionesFiltradas,
            'nombre_usuario' => session('name') ?? 'Usuario',
            'departamento_usuario' => $departamento['Nombre'] ?? 'Departamento',
            'departamentos' => $departamentos->findall(),
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
