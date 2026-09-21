<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventosCalendario extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
            ],
            'evento' => [
                'type' => 'VARCHAR',
                'constraint' => 250,
            ],
            'color_evento' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'fecha_inicio' => [
                'type' => 'DATETIME',
            ],
            'fecha_fin' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->addKey('ID_Usuario');
        $this->forge->addKey(['fecha_inicio', 'fecha_fin']);
        $attributes = [];
        if ($this->db->getPlatform() === 'MySQLi') {
            $attributes = ['ENGINE' => 'InnoDB'];
        }
        $this->forge->createTable('eventos_calendario', true, $attributes);
    }

    public function down()
    {
        $this->forge->dropTable('eventos_calendario');
    }
}