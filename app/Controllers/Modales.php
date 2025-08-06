<?php

namespace App\Controllers;

class Modales extends BaseController
{
    public function mostrar($opcion)
    {
        switch ($opcion) {
            case 'solicitar_material':
                return view('modales/solicitar_material');
            case 'ver_historial':
                return view('modales/ver_historial');
            case 'revisar_solicitudes':
                return view('modales/revisar_solicitudes');
            case 'proveedores':
                return view('modales/proveedores');
            case 'ordenes_compra':
                return view('modales/ordenes_compra');
            case 'enviar_revision':
                return view('modales/enviar_revision');
            case 'usuarios':
                return view('modales/usuarios');
            case 'dictamen_solicitudes':
                return view('modales/dictamen_solicitudes');
            case 'crud_proveedores':
                return view('modales/crud_proveedores');
            case 'limpiar_almacenamiento':
                return view('modales/limpiar_almacenamiento');
            case 'pagos_pendientes':
                return view('modales/pagos_pendientes');
            default:
                return 'Opción no válida';
        }
    }
}
