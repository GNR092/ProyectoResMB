<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFileComplementoToOrdenCompra extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('File_Complemento', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'File_Complemento' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', 'File_Complemento');
    }
}
