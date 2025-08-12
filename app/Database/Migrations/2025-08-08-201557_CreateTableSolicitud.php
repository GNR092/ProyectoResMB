<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSolicitud extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Solicitud' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_Dpto' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'Fecha' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'Estado' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ],
            'No_Folio' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'unique' => true,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('ID_Solicitud', true);
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Solicitud');
    }

    public function down()
    {
        $this->forge->dropTable('Solicitud');
    }
}
