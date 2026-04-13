<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFileComplementoToOrdenCompra extends Migration
{
    public function up()
    {
        $this->forge->addColumn('OrdenCompra', [
            'File_Complemento' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'File_FacturaServicioXML'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'File_Complemento');
    }
}
