<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDepartamento extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Dpto' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
        ]);
        $this->forge->addKey('ID_Dpto', true);
        $this->forge->createTable('Departamento');
    }

    public function down()
    {
        $this->forge->dropTable('Departamento');
    }
}
