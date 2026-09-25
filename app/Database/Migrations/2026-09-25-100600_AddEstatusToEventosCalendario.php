<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEstatusToEventosCalendario extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('estatus', 'eventos_calendario')) {
            $this->forge->addColumn('eventos_calendario', [
                'estatus' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'pendiente',
                    'after'      => 'color_evento',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('estatus', 'eventos_calendario')) {
            $this->forge->dropColumn('eventos_calendario', 'estatus');
        }
    }
}