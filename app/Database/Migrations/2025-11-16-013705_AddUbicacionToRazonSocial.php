<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUbicacionToRazonSocial extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Razon_Social', [
            'Ubicacion' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('Razon_Social', 'Ubicacion');
    }
}
