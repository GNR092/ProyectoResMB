<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGrupoPresupuestalToPresupuestoMensual extends Migration
{
    public function up()
    {
        $field = [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
        ];
        if ($this->db->DBDriver === 'MySQLi') {
            $field['after'] = 'ID_Dpto';
        }

        $fields = [
            'ID_GrupoPresupuestal' => $field,
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
        try { $this->forge->dropForeignKey('PresupuestoMensual', 'presupuestomensual_grupo_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('PresupuestoMensual', 'ID_GrupoPresupuestal');
    }
}
