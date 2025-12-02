<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceInvoiceFilesToOrdenCompra extends Migration
{
    public function up()
    {
        $this->forge->addColumn('OrdenCompra', [
            'File_FacturaServicioPDF' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'File_FacturaServicioXML' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', ['File_FacturaServicioPDF', 'File_FacturaServicioXML']);
    }
}