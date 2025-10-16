<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumsOrden extends Migration
{
    /**
     * Agrega las columnas 'Estado' y 'Fecha' a la tabla 'OrdenCompra'.
     *
     * La columna 'Estado' almacena el estado actual de la orden de compra (ej. Pendiente, Aprobada, Rechazada).
     * La columna 'Fecha' registra la fecha de creación o última actualización de la orden.
     */
    public function up()
    {
        $fields = [
            'Estado' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
                'default'    => 'Pendiente',
                'comment'    => 'Estado de la orden de compra (Pendiente, Aprobada, etc.)',
            ],
            'Fecha' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Fecha de creación o actualización de la orden',
            ],
        ];

        $this->forge->addColumn('OrdenCompra', $fields);
    }

    /**
     * Revierte la adición de las columnas 'Estado' y 'Fecha'.
     */
    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', ['Estado', 'Fecha']);
    }
}