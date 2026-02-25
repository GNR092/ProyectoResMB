<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGrupoPresupuestalToPresupuestoMensual extends Migration
{
    public function up()
    {
        $fields = [
            'ID_GrupoPresupuestal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Dpto',
            ],
        ];
        $this->forge->addColumn('PresupuestoMensual', $fields);

        $this->forge->addForeignKey(
            'ID_GrupoPresupuestal',
            'GrupoPresupuestal',
            'ID_GrupoPresupuestal',
            'CASCADE',
            'SET NULL',
            'presupuestomensual_grupo_fk'
        );
    }

    public function down()
    {
        $this->forge->dropForeignKey('PresupuestoMensual', 'presupuestomensual_grupo_fk');
        $this->forge->dropColumn('PresupuestoMensual', 'ID_GrupoPresupuestal');
    }
}
