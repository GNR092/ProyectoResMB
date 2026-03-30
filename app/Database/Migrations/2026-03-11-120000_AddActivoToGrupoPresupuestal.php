<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActivoToGrupoPresupuestal extends Migration
{
    public function up()
    {
        $field = [
            'type'       => 'BOOLEAN',
            'default'    => true,
            'null'       => false,
        ];
        if ($this->db->DBDriver === 'MySQLi') {
            $field['after'] = 'ID_Dpto';
        }

        $fields = [
            'activo' => $field,
        ];
        $this->forge->addColumn('GrupoPresupuestal', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('GrupoPresupuestal', 'activo');
    }
}
