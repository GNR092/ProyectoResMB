<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExtraFilesToOrdenCompra extends Migration
{
    public function up()
    {
        $this->forge->addColumn('OrdenCompra', [
            'File_Factura' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'File_Comprobante' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'File_ReqPag' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', ['File_Factura', 'File_Comprobante', 'File_ReqPag']);
    }
}