<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActivoToGrupoPresupuestal extends Migration
{
    public function up()
    {
        $fields = [
            'activo' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
                'null'       => false,
                'after'      => 'ID_Dpto',
            ],
        ];
        $this->forge->addColumn('GrupoPresupuestal', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('GrupoPresupuestal', 'activo');
    }
}
