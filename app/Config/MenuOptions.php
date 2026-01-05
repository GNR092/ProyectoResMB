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
            //Flujo del programa
            'solicitar_material' => [
                'label' => 'Requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#solicitar_material"></use></svg>',
            ],

            'aprobar_solicitudes' => [
                'label' => 'Aprobar Requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#aprobar"></use></svg>',
            ],

            'revisar_solicitudes' => [
                'label' => 'Revisar requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#revisar_solicitudes"></use></svg>',
            ],

            'enviar_revision' => [
                'label' => 'Enviar a revisión',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#enviar_revision"></use></svg>',
            ],

            'dictamen_solicitudes' => [
                'label' => 'Aprobar requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#dictamen_solicitudes"></use></svg>',
            ],

            'ordenes_compra' => [
                'label' => 'Ordenes de compra',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ordenes_compra"></use></svg>',
            ],

            'programar_pagos' => [
                'label' => 'Programación de pagos',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#aprobar_pagos"></use></svg>',
            ],

            'lista_pagos' => [
                'label' => 'Lista de Pagos',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#list_alt"></use></svg>', // Placeholder icon
            ],

            'pagos_pendientes' => [
                'label' => 'Facturas Pendientes',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#pagos_pendientes"></use></svg>',
            ],

            'ver_historial' => [
                'label' => 'Ver historial',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ver_historial"></use></svg>',
            ],



            // 'ficha_pago' => [
            //     'label' => 'Fichas de pago',
            //     'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ficha_pago"></use></svg>',
            // ],

//            'crud_productos' => [
//                'label' => 'Inventario',
//                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#crud_productos"></use></svg>',
//            ],

//            'recepcion_material' => [
//                'label' => 'Recepción de Material',
//                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#registrar_productos"></use></svg>', // Placeholder icon
//            ],

//            'entrega_productos' => [
//                'label' => 'Salidas de Almacén',
//                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#entrega_productos"></use></svg>', // Placeholder icon
//            ],
//
//            'bajas_destruccion' => [
//                'label' => 'Bajas por Destrucción',
//                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#bajas"></use></svg>', // Placeholder icon
//            ],


            'almacen' => [
                'label' => 'Almacén',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#almacen"></use></svg>',
            ],

            // 'crud_places' => [
            //     'label' => 'Complejos',
            //     'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#razonsocial"></use></svg>',
            // ],

            // 'crud_departamento' => [
            //     'label' => 'Departamentos',
            //     'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#departamentos"></use></svg>',
            // ],
        ];
    }
}