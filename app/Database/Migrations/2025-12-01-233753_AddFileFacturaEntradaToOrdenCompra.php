<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFileFacturaEntradaToOrdenCompra extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('File_FacturaEntrada', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'File_FacturaEntrada' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'File_FacturaEntrada');
    }
}
