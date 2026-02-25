<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveIdGrupoPresupuestalFromOrdenCompra extends Migration
{
    public function up()
    {
        // Drop the foreign key first
        $this->forge->dropForeignKey('OrdenCompra', 'ordencompra_grupo_fk');

        // Drop the column 'ID_GrupoPresupuestal' from 'OrdenCompra' table
        $this->forge->dropColumn('OrdenCompra', 'ID_GrupoPresupuestal');
    }

    public function down()
    {
        // Re-add the column 'ID_GrupoPresupuestal' for rollback
        $fields = [
            'ID_GrupoPresupuestal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ];
        $this->forge->addColumn('OrdenCompra', $fields);

        // Re-add the foreign key constraint
        $this->forge->addForeignKey(
            'ID_GrupoPresupuestal',
            'GrupoPresupuestal',
            'ID_GrupoPresupuestal',
            'CASCADE',
            'SET NULL',
            'ordencompra_grupo_fk'
        );

        // Process indexes to apply the foreign key
        $this->forge->processIndexes('OrdenCompra');
    }
}
