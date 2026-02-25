<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGrupoPresupuestalToSolicitudProducto extends Migration
{
    public function up()
    {
        $fields = [
            'ID_GrupoPresupuestal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Solicitud',
            ],
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
        $this->forge->dropForeignKey('Solicitud_Producto', 'solicitudproducto_grupo_fk');
        $this->forge->dropColumn('Solicitud_Producto', 'ID_GrupoPresupuestal');
    }
}
