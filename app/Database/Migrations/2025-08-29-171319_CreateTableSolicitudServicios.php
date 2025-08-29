<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudServiciosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_SolicitudServ' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'ID_Usuario' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false
            ],
            'ID_Dpto' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false
            ],
            'Fecha' => [
                'type' => 'TIMESTAMP',
                'default' => 'CURRENT_TIMESTAMP'
            ],
            'Estado' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'Folio' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true
            ]
        ]);
        $this->forge->addPrimaryKey('ID_SolicitudServ');
        $this->forge->addForeignKey('ID_Usuario', 'D_Utilizarlo', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Solicitud_Servicios');
    }

    public function down()
    {
        $this->forge->dropTable('Solicitud_Servicios');
    }
}