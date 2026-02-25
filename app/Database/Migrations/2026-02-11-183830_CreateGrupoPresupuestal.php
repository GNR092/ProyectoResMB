<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGrupoPresupuestal extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_GrupoPresupuestal' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'Descripcion' => [
                'type' => 'TEXT', // Compatible con ambos motores
                'null' => true,
            ],
        ]);

        $this->forge->addKey('ID_GrupoPresupuestal', true);
        $this->forge->createTable('GrupoPresupuestal', true);
    }

    public function down()
    {
        $this->forge->dropTable('GrupoPresupuestal');
    }
}