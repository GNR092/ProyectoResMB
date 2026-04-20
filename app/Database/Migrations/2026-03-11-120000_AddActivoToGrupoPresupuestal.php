<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActivoToGrupoPresupuestal extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('activo', 'GrupoPresupuestal')) {
            $this->forge->addColumn('GrupoPresupuestal', [
                'activo' => [
                    'type'       => 'BOOLEAN',
                    'default'    => true,
                    'null'       => false,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('GrupoPresupuestal', 'activo');
    }
}
