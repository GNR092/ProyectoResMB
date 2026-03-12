<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class MenuOptions extends BaseConfig
{
    public array $opciones;

    public function __construct()
    {
        parent::__construct();

        $iconPath = FCPATH . 'icons/icons.svg';
        $version = file_exists($iconPath) ? filemtime($iconPath) : time();
        $iconUrl = "/icons/icons.svg?v=$version";

        $this->opciones = [

            //******* OPERACION *********//
            'TituloOperacion' => [
                'label' => 'Operaciones',
                'icon' => '',
                'is_title' => true,
            ],

            'solicitar_material' => [
                'label' => 'Crear Requisición',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#solicitar_material"></use></svg>',
            ],

            'aprobar_solicitudes' => [
                'label' => 'Aprobar Requisiciones Auxiliares',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#aprobar"></use></svg>',
            ],

            'ver_historial' => [
                'label' => 'Estado De Requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ver_historial"></use></svg>',
            ],

            // ********* COMNPRAS **********//
            'TituloCompras' => [
                'label' => 'Compras',
                'icon' => '',
                'is_title' => true,
            ],

            'revisar_solicitudes' => [
                'label' => 'Cotizar Solicitudes',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#revisar_solicitudes"></use></svg>',
            ],

            'enviar_revision' => [
                'label' => 'Enviar a revisión',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#enviar_revision"></use></svg>',
            ],

            'ordenes_compra' => [
                'label' => 'Ordenes de Compras',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ordenes_compra"></use></svg>',
            ],

            'pagos_pendientes' => [
                'label' => 'Facturas Pendientes',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#pagos_pendientes"></use></svg>',
            ],

            'correcciones' => [
                'label' => 'Corregir solicitudes',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#Fix"></use></svg>',
            ],

            // ******** DIRECCION *******//
            'TituloDireccion' => [
                'label' => 'Dirección',
                'icon' => '',
                'is_title' => true,
            ],

            'dictamen_solicitudes' => [
                'label' => 'Aprobar requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#dictamen_solicitudes"></use></svg>',
            ],

            'programar_pagos' => [
                'label' => 'Programación de pagos',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#aprobar_pagos"></use></svg>',
            ],

            // ********** COMPRAS Y TESORERIA ************ //
            'crud_cuentas' => [
                'label' => 'Cuentas',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#cuentas"></use></svg>',
            ],

            // ****** TESORERIA ******//
            'TituloTesoreria' => [
                'label' => 'Tesorería',
                'icon' => '',
                'is_title' => true,
            ],

            'lista_pagos' => [
                'label' => 'Lista de Pagos',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#list_alt"></use></svg>', // Placeholder icon
            ],


            // ********* INVENTARIO *************//
            'TituloAlmacen' => [
                'label' => 'Inventario',
                'icon' => '',
                'is_title' => true,
            ],

            'almacen' => [
                'label' => 'Almacén',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#almacen"></use></svg>',
            ],


            //**********CONTADOR**********//
            'TituloContador' => [
                'label' => 'Contaduría',
                'icon' => '',
                'is_title' => true,
            ],

            'reportes' => [
                'label' => 'Reportes/Auditoria',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#reportes"></use></svg>',
            ],

            'UnidadOperativa' => [
                'label' => 'Unidades Operativas',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#UnidadOperativa"></use></svg>',
            ],

            'GrupoPresupuestal' => [
                'label' => 'Partidas Presupuestales',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#GrupoPresupuestal"></use></svg>',
            ],

            'BancoDpto' => [
                'label' => 'Bancos de Departamento',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#BancoDpto"></use></svg>',
            ],

            'PresupuestoMensual' => [
                'label' => 'Asignar Presupuestos',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#PresupuestoMensual"></use></svg>',
            ],

            'ReportePresupuesto' => [
                'label' => 'Reportes de Presupuestos',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ReportePresupuestal"></use></svg>',
            ],

            'SaldosBancarios' => [
                'label' => 'Saldos Bancarios',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#SaldoBancario"></use></svg>',
            ],

            'SegmentoNegocio' => [
                'label' => 'Segmentos de Negocio',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#SegmentoNegocio"></use></svg>',
            ],

        ];
    }
}