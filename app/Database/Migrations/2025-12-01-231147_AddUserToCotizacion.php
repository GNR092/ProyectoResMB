<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserToCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Cotizacion', [
            'ID_Usuario_Cotiza' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);

        $this->forge->addForeignKey('ID_Usuario_Cotiza', 'Usuarios', 'ID_Usuario', 'CASCADE', 'SET NULL');
    }

    public function down()
    {
        $this->forge->dropColumn('Cotizacion', 'ID_Usuario_Cotiza');
    }
}
