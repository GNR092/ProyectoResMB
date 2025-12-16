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
                'solicitar_material',
                'revisar_solicitudes',
                'enviar_revision',
                'crud_proveedores',
                'ver_historial',
                'crud_usuarios',
                'limpiar_almacenamiento',
                'ficha_pago',
                'ordenes_compra',
                'pagos_pendientes',
                'almacen'
            ],

            // Rol Dirección
            'Direccion' => [
                //Estos no se si deberia tenerlos dirección, no tendria logica
//                'revisar_solicitudes',
//                'enviar_revision',
                'dictamen_solicitudes',
                'crud_proveedores',
                'ver_historial',
                'crud_usuarios',
                'limpiar_almacenamiento',
                'ficha_pago',
                'ordenes_compra',
                'pagos_pendientes',
                'almacen'
            ],

            // Rol Tesorería
            'Tesoreria' => [
                'solicitar_material',
                'ordenes_compra',
                'ficha_pago',
                'pagos_pendientes',
                'lista_pagos',
            ],

            // Rol Almacén
            'Almacen' => [
                'registrar_productos',
                'crud_productos', // Existencias
                'entrega_productos',
                'recepcion_material',
                'bajas_destruccion',
                'almacen'
            ],

            // Rol por defecto (Jefes de Departamento)
            'default' => [
                'solicitar_material',
                'ver_historial'
            ]
        ];
        
        $opcionesAjustes = [
            'crud_usuarios',
            'limpiar_almacenamiento',
            'crud_proveedores',
            'reportes',
            'razonsocial'

        ];

        $permisosAjustesDpto = [
            'Administración' => array_values($opcionesAjustes),
            'Compras' => array_values($opcionesAjustes),
            'Direccion' => array_values($opcionesAjustes),   
            'Tesoreria' => array_values($opcionesAjustes),
            // Rol por defecto (Jefes de Departamento)
            'default' => [
                'limpiar_almacenamiento'
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
            //Aqui Cambie Administración por Dirección, creo que asi deberia ir
        if ($loginType === 'boss' && $nombreDepartamento !== 'Direccion' && $nombreDepartamento !== 'Compras') {
            // Si es Jefe, añadimos el permiso para aprobar solicitudes.
            $permisosUsuario[] = 'aprobar_solicitudes';
        }
        // Los empleados ('employee') se quedan con los permisos por defecto de su depto.

        $opcionesFiltradas = array_filter($opcionesDisponibles, fn($key) => in_array($key, $permisosUsuario), ARRAY_FILTER_USE_KEY);

        $ajustesFiltrados = array_intersect($opcionesAjustes, $permisosAjustesDpto[$nombreDepartamento] ?? $permisosAjustesDpto['default']);
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
            'ajustes' => $ajustesFiltrados,
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
