<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaPagoRealizadoToOrdenCompra extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('FechaPagoRealizado', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'FechaPagoRealizado' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'FechaPagoRealizado');
    }
}
