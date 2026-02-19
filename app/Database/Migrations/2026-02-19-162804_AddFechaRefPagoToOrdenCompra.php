<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaRefPagoToOrdenCompra extends Migration
{
    public function up()
    {
        $fields = [
            'FechaRefPago' => [
                'type' => 'DATETIME', // Puedes cambiar a 'DATE' si no necesitas registrar la hora
                'null' => true,
            ],
        ];

        $this->forge->addColumn('OrdenCompra', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'FechaRefPago');
    }
}