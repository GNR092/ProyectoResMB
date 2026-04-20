<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaRefPagoToOrdenCompra extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('FechaRefPago', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'FechaRefPago' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'FechaRefPago');
    }
}
