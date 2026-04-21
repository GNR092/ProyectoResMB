<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFirmaToUsuarios extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('Firma_digital', 'Usuarios')) {
            $this->forge->addColumn('Usuarios', [
                'Firma_digital' => [
                    'type' => 'VARCHAR',
                    'constraint' => '255',
                    'null' => true
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('Usuarios', 'Firma_digital');
    }
}
