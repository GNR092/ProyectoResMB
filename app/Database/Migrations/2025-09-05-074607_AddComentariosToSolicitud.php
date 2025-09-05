<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComentariosToSolicitud extends Migration
{
    public function up()
    {
        $fields = [
            'Comentarios' => [
                'type' => 'VARCHAR',
                'constraint' => '500',
                'null' => true,
                'after' => 'Archivo',
            ],
        ];
        $this->forge->addColumn('Solicitud', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'Comentarios');
    }
}
