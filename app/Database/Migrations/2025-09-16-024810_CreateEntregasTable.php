<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEntregasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Entrega' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'Departamento' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'Receptor' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'Fecha' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('ID_Entrega');
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Entregas');
    }

    public function down()
    {
        $this->forge->dropTable('Entregas');
    }
}
