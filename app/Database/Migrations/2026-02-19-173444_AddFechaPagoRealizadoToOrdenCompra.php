<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaPagoRealizadoToOrdenCompra extends Migration
{
    public function up()
    {
        $fields = [
            'FechaPagoRealizado' => [
                'type' => 'DATETIME', // Puedes usar 'DATE' si no requieres la hora exacta
                'null' => true,
            ],
        ];

        $this->forge->addColumn('OrdenCompra', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'FechaPagoRealizado');
    }
}
