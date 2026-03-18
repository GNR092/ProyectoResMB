<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudesCambioPresupuesto extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_SolicitudCambio' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'Modulo' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'Accion' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'ID_Afectado' => [
                'type'       => 'VARCHAR',
                'constraint' => '255', // Puede ser un ID o múltiples IDs concatenados (ej. en masivo)
                'null'       => true,
            ],
            'Datos_Payload' => [
                'type' => 'JSON', // o TEXT si JSON no es soportado, pero MySQL/Postgres modernos lo soportan
                'null' => true,
            ],
            'Estado' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'Pendiente', // Pendiente, Aprobado, Rechazado
            ],
            'Comentarios_Revisor' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('ID_SolicitudCambio', true);
        
        // Asumiendo que la tabla Usuarios existe. Ajustar si el nombre difiere (ej. 'usuarios' vs 'Usuarios')
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        
        $this->forge->createTable('SolicitudesCambioPresupuesto');
    }

    public function down()
    {
        $this->forge->dropTable('SolicitudesCambioPresupuesto');
    }
}
