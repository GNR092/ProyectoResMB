<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEsManualToGrupoPresupuestal extends Migration
{
    public function up()
    {
        $fields = [
            'es_manual' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
                'null'       => false,
            ],
        ];

        // En MySQL podemos especificar la posición después de 'activo'
        if ($this->db->DBDriver === 'MySQLi') {
            $fields['es_manual']['after'] = 'activo';
        }

        if (!$this->db->fieldExists('es_manual', 'GrupoPresupuestal')) {
            $this->forge->addColumn('GrupoPresupuestal', $fields);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('GrupoPresupuestal', 'es_manual');
    }
}
