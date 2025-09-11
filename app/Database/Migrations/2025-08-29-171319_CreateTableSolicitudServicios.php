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
            'ID_Solicitud' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false
            ],
            'Fecha' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'Estado' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true
            ],
            'Costo' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
            
        ]);
        $this->forge->addPrimaryKey('ID_SolicitudServ');
        $this->forge->addForeignKey('ID_Solicitud', 'Solicitud', 'ID_Solicitud', 'CASCADE', 'CASCADE');
        //$this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        //$this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Solicitud_Servicios');
    }

    public function down()
    {
        $this->forge->dropTable('Solicitud_Servicios');
    }
}