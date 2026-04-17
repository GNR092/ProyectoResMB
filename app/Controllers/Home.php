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
            
            // ********* Rol Compras
            'Compras' => [
                'TituloCompras',
                'solicitar_material',
                'revisar_solicitudes',
                'enviar_revision',
                'ordenes_compra',
                'pagos_pendientes',
                'ver_historial',
                'correcciones',
                'lista_pagos',
                'crud_proveedores',
                'crud_usuarios',
                'crud_cuentas',
                'ReportePresupuesto',
            ],

            // ********** Rol Dirección
            'Direccion' => [
                'TituloDireccion',
                'dictamen_solicitudes',
                'programar_pagos',
                'AjustesPresupuesto',
                'ver_historial',
                //PRESUPUESTOS
                'TituloPresupuestos',
                'UnidadOperativa',
                'GrupoPresupuestal',
                'PresupuestoMensual',
                'ReportePresupuesto',
                'SegmentoNegocio',
                'ver_historial',
                'TituloBancos',
                'BancoDpto',
                'SaldosBancarios',
                'ReportePresupuesto',
            ],

            // ******** Rol Tesorería
            'Tesoreria' => [
                'TituloTesoreria',
                'ficha_pago',
                'pagos_pendientes',
                'crud_cuentas',
                'lista_pagos',
                'ver_historial',
                'solicitar_material',
                'ReportePresupuesto',
            ],

            // ******* Rol Almacén
            'Almacen' => [
                'TituloAlmacen',
                'registrar_productos',
                'crud_productos', // Existencias
                'entrega_productos',
                'recepcion_material',
                'bajas_destruccion',
                'almacen',
            ],

            // ******* Rol Direccion Campus
            'Direccion Campus' => [
                'solicitar_material',
                'enviar_revision',
                'ver_historial',
                'ReportePresupuesto',
            ],

            // ******** Rol Presupuestos
            'Presupuestos' => [
                'TituloPresupuestos',
                'UnidadOperativa',
                'GrupoPresupuestal',
                'PresupuestoMensual',
                'ReportePresupuesto',
                'SegmentoNegocio',
                'ver_historial',
                'TituloBancos',
                'BancoDpto',
                'SaldosBancarios',
                'ReportePresupuesto',
            ],

            // ******** Rol Contaduria
            'Contaduría' => [
                //COMPRAS
                'TituloCompras',
                'solicitar_material',
                'revisar_solicitudes',
                'enviar_revision',
                'ordenes_compra',
                'pagos_pendientes',
                'ver_historial',


                //TESORERIA
                'TituloTesoreria',
                'ficha_pago',
                'pagos_pendientes',
                'crud_cuentas',
                'lista_pagos',

                //ALMACEN
                'TituloAlmacen',
                'registrar_productos',
                'crud_productos', // Existencias
                'entrega_productos',
                'recepcion_material',
                'bajas_destruccion',
                'almacen',

                //CONTABILIDAD
                'reportes',

                //PRESUPUESTOS
                'TituloPresupuestos',
                'UnidadOperativa',
                'GrupoPresupuestal',
                'PresupuestoMensual',
                'ReportePresupuesto',
                'SegmentoNegocio',
                'ver_historial',
                'TituloBancos',
                'BancoDpto',
                'SaldosBancarios',
                'TituloReportes',
                'ReportePresupuesto',
                'GastoManual',
            ],

            // Rol por defecto (Jefes de Departamento)
            'default' => [
                'TituloOperacion',
                'solicitar_material',
                'ver_historial',
                'ReportePresupuesto',
            ],


        ];
        
        $opcionesAjustes = [
            'crud_usuarios',
            'limpiar_almacenamiento',
            'crud_proveedores',
            'reportes',
            'razonsocial',
            'crud_places',
            'crud_departamento'

        ];

        $opcionesCatalogos = [
            'crud_usuarios',
            'crud_proveedores',
            'razonsocial',
            'crud_places',
            'crud_departamento'
        ];

        $permisosAjustesDpto = [
            'Administración' => array_values($opcionesAjustes),
            'Compras' => array_values(array_diff($opcionesAjustes, $opcionesCatalogos)),
            'Direccion' => array_values($opcionesAjustes),
            'Contaduría' => array_values($opcionesAjustes),
            'default' => [
                null
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
        if ($loginType === 'boss' && $nombreDepartamento !== 'Direccion' && $nombreDepartamento !== 'Compras' && $nombreDepartamento !== 'Tesoreria') {
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

            // Creamos un mapa de qué opciones activan qué títulos
            $mapaTitulos = [
                // Operación
                'solicitar_material'   => 'TituloOperacion',
                'aprobar_solicitudes'  => 'TituloOperacion',
                'ver_historial'        => 'TituloOperacion',

                // Compras
                'revisar_solicitudes'  => 'TituloCompras',
                'enviar_revision'      => 'TituloCompras',
                'ordenes_compra'       => 'TituloCompras',
                'pagos_pendientes'     => 'TituloCompras',

                // Dirección
                'dictamen_solicitudes' => 'TituloDireccion',
                'programar_pagos'      => 'TituloDireccion',
                'AjustesPresupuesto'   => 'TituloDireccion',

                // Tesorería
                'lista_pagos'          => 'TituloTesoreria',
                'ficha_pago'           => 'TituloTesoreria',

                // Almacén
                'almacen'              => 'TituloAlmacen',
                'registrar_productos'  => 'TituloAlmacen',
                'crud_productos'       => 'TituloAlmacen',
                'entrega_productos'    => 'TituloAlmacen',
                'recepcion_material'   => 'TituloAlmacen',
                'bajas_destruccion'    => 'TituloAlmacen',

                // Contaduria
                'reportes'    => 'TituloContador',

                // Presupuestos
                'UnidadOperativa'    => 'TituloPresupuestos',
                'GrupoPresupuestal'  => 'TituloPresupuestos',
                'BancoDpto'          => 'TituloPresupuestos',
                'PresupuestoMensual' => 'TituloPresupuestos',
                'ReportePresupuesto' => 'TituloPresupuestos',
                'SaldosBancarios'    => 'TituloPresupuestos',
                'SegmentoNegocio'    => 'TituloPresupuestos',
            ];

            foreach ($mapaTitulos as $opcion => $titulo) {
                if (in_array($opcion, $permisosUsuario)) {
                    if (!in_array($titulo, $permisosUsuario)) {
                        $permisosUsuario[] = $titulo;
                    }
                }
            }

            $opcionesFiltradas = [];
            foreach ($opcionesDisponibles as $key => $info) {
                if (in_array($key, $permisosUsuario)) {
                    $opcionesFiltradas[$key] = $info;
                }
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
            return view('portada');
        }
    }
}
