<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class MenuOptions extends BaseConfig
{
    public array $opciones = [
        //Flujo del programa
        'solicitar_material' => [
            'label' => 'Solicitar material',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#solicitar_material"></use></svg>',
        ],

        'revisar_solicitudes' => [
            'label' => 'Revisar solicitudes',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#revisar_solicitudes"></use></svg>',
        ],

        'enviar_revision' => [
            'label' => 'Enviar a revisión',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#enviar_revision"></use></svg>',
        ],

        'dictamen_solicitudes' => [
            'label' => 'Dictamen de solicitudes',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#dictamen_solicitudes"></use></svg>',
        ],

        'ordenes_compra' => [
            'label' => 'Ordenes de compra',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#ordenes_compra"></use></svg>',
        ],

        'crud_proveedores' => [
            'label' => 'Proveedores',
            'icon' =>
                '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#crud_proveedores"></use></svg>',
        ],

        'ver_historial' => [
            'label' => 'Ver historial',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#ver_historial"></use></svg>
',
        ],

        'registrar_productos' => [
            'label' => 'Registrar Productos',
            'icon' =>
                '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#registrar_productos"></use></svg>',
        ],

        'crud_productos' => [
            'label' => 'Existencias',
            'icon' =>
                '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#crud_productos"></use></svg>',
        ],

        'entrega_productos' => [
            'label' => 'Entrega de Material',
            'icon' =>
                '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#entrega_productos"></use></svg>',
        ],

        'pagos_pendientes' => [
            'label' => 'Pagos Pendientes',
            'icon' =>
                '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#pagos_pendientes"></use></svg>',
        ],

        //Otros
        'usuarios' => [
            'label' => 'Usuarios',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#usuarios"></use></svg>
',
        ],

        'proveedores' => [
            'label' => 'Proveedores',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#proveedores"></use></svg>
',
        ],

        'limpiar_almacenamiento' => [
            'label' => 'Limpiar Almacenamiento',
            'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#limpiar_almacenamiento"></use></svg>
',
        ],
    ];
}