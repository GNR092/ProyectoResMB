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
                'label' => 'Dictamen de requisiciones',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#dictamen_solicitudes"></use></svg>',
            ],

            'ordenes_compra' => [
                'label' => 'Ordenes de compra',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ordenes_compra"></use></svg>',
            ],

            'crud_proveedores' => [
                'label' => 'Proveedores',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#crud_proveedores"></use></svg>',
            ],

            'ver_historial' => [
                'label' => 'Ver historial',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ver_historial"></use></svg>',
            ],

            'registrar_productos' => [
                'label' => 'Registrar Productos',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#registrar_productos"></use></svg>',
            ],

            'crud_productos' => [
                'label' => 'Existencias',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#crud_productos"></use></svg>',
            ],

            'entrega_productos' => [
                'label' => 'Entrega de Material',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#entrega_productos"></use></svg>',
            ],

            'pagos_pendientes' => [
                'label' => 'Pagos Pendientes',
                'icon' =>
                    '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#pagos_pendientes"></use></svg>',
            ],

            //Otros
            // 'usuarios' => [
            //     'label' => 'Usuarios',
            //     'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#usuarios"></use></svg>',
            // ],
            'crud_usuarios' => [
                'label' => 'Usuarios',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#usuarios"></use></svg>',
            ],            

            'proveedores' => [
                'label' => 'Proveedores',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#proveedores"></use></svg>',
            ],

            'limpiar_almacenamiento' => [
                'label' => 'Limpiar Almacenamiento',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#limpiar_almacenamiento"></use></svg>',
            ],

            'ficha_pago' => [
                'label' => 'Fichas de pago',
                'icon' => '<svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="' . $iconUrl . '#ficha_pago"></use></svg>',
            ],
        ];
    }
}