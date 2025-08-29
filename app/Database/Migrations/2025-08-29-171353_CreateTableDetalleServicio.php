<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDetalleServicioTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_DetalleServ' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'ID_SolicitudServ' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false
            ],
            'Nombre_Servicio' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false
            ],
            'Costo' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true
            ]
        ]);
        $this->forge->addPrimaryKey('ID_DetalleServ');
        $this->forge->addForeignKey('ID_SolicitudServ', 'Solicitud_Servicios', 'ID_SolicitudServ', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Detalle_Servicio');
    }

    public function down()
    {
        $this->forge->dropTable('Detalle_Servicio');
    }
}