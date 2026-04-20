<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceInvoiceFilesToOrdenCompra extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('File_FacturaServicioPDF', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'File_FacturaServicioPDF' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('File_FacturaServicioXML', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'File_FacturaServicioXML' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('OrdenCompra', ['File_FacturaServicioPDF', 'File_FacturaServicioXML']);
    }
}
