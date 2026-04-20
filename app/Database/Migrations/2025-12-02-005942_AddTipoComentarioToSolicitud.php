<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipoComentarioToSolicitud extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('TipoComentarioAdmin', 'Solicitud')) {
            $this->forge->addColumn('Solicitud', [
                'TipoComentarioAdmin' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'TipoComentarioAdmin');
    }
}
