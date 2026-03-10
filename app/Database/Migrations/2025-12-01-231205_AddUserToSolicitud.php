<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserToSolicitud extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Solicitud', [
            'ID_Usuario_Autoriza' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);
        
        $this->forge->addForeignKey('ID_Usuario_Autoriza', 'Usuarios', 'ID_Usuario', 'CASCADE', 'SET NULL');
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'ID_Usuario_Autoriza');
    }
}
