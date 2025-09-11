<?php
// app/Database/Migrations/2024_01_01_000002_create_departamentos_table.php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartamentosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Dpto' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'ID_Place' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true, 
                'after'      => 'ID_Dpto',
            ],
            'Nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            
        ]);
        $this->forge->addPrimaryKey('ID_Dpto');
        $this->forge->addForeignKey('ID_Place', 'Places', 'ID_Place', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Departamentos');
    }

    public function down()
    {
        $this->forge->dropTable('Departamentos');
    }
}