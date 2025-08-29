<?php
// app/Database/Migrations/2024_01_01_000001_create_razon_social_table.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRazonSocialTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_RazonSocial' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'Nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'RFC' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
        ]);
        $this->forge->addPrimaryKey('ID_RazonSocial');
        $this->forge->createTable('Razon_Social');
    }

    public function down()
    {
        $this->forge->dropTable('Razon_Social');
    }
}