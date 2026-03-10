<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipoComentarioToSolicitud extends Migration
{
    public function up()
    {
        $fields = [
            'TipoComentarioAdmin' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
        ];
        $this->forge->addColumn('Solicitud', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'TipoComentarioAdmin');
    }
}
