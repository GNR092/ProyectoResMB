<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Usuario' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Dpto' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_RazonSocial' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'Nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'Correo' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'unique' => true,
                'null' => false,
            ],
            'ContrasenaP' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'ContrasenaG' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'Numero' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('ID_Usuario', true);
        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_RazonSocial', 'Razon_Social', 'ID_RazonSocial', 'CASCADE', 'CASCADE');

        $this->forge->createTable('Usuarios');
    }

    public function down()
    {
        $this->forge->dropTable('Usuarios');
    }
}
