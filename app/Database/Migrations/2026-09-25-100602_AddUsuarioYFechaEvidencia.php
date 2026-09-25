<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddUsuarioYFechaEvidencia extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('nombre_usuario', 'evento_archivos')) {
            $this->forge->addColumn('evento_archivos', [
                'nombre_usuario' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
            ]);
        }

        if ($this->db->fieldExists('fecha_subida', 'evento_archivos')) {
            $this->forge->modifyColumn('evento_archivos', [
                'fecha_subida' => [
                    'type'    => 'DATETIME',
                    'null'    => false,
                    'default' => new RawSql('CURRENT_TIMESTAMP'),
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('nombre_usuario', 'evento_archivos')) {
            $this->forge->dropColumn('evento_archivos', 'nombre_usuario');
        }

        if ($this->db->fieldExists('fecha_subida', 'evento_archivos')) {
            $this->forge->modifyColumn('evento_archivos', [
                'fecha_subida' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
        }
    }
}