<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFileRemisionToOrdenCompra extends Migration
{
    public function up()
    {
        $this->forge->addColumn('OrdenCompra', [
            'File_Remision' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'File_Remision');
    }
}
