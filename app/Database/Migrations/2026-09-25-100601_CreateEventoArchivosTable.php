<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventoArchivosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_archivo' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_evento' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'nombre_archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'fecha_subida' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_archivo', true);
        $this->forge->addForeignKey('id_evento', 'eventos_calendario', 'id', 'CASCADE', 'CASCADE', 'fk_evento_archivo');
        $this->forge->addKey('id_evento');
        
        $attributes = [];
        if ($this->db->getPlatform() === 'MySQLi') {
            $attributes = ['ENGINE' => 'InnoDB'];
        }
        $this->forge->createTable('evento_archivos', true, $attributes);
    }

    public function down()
    {
        $this->forge->dropTable('evento_archivos', true);
    }
}