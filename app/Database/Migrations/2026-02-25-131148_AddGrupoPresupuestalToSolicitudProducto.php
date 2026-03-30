<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGrupoPresupuestalToSolicitudProducto extends Migration
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
            $field['after'] = 'ID_Solicitud';
        }

        $fields = [
            'ID_GrupoPresupuestal' => $field,
        ];
        $this->forge->addColumn('Solicitud_Producto', $fields);

        $this->forge->addForeignKey(
            'ID_GrupoPresupuestal',
            'GrupoPresupuestal',
            'ID_GrupoPresupuestal',
            'CASCADE',
            'SET NULL',
            'solicitudproducto_grupo_fk'
        );
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('Solicitud_Producto', 'solicitudproducto_grupo_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('Solicitud_Producto', 'ID_GrupoPresupuestal');
    }
}
