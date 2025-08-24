<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableRazonSocial extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_RazonSocial' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
            'RFC' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
                'null'       => false,
            ],
        ]);
        $this->forge->addKey('ID_RazonSocial', true);
        $this->forge->createTable('RazonSocial');
    }

    public function down()
    {
        $this->forge->dropTable('RazonSocial');
    }
}
