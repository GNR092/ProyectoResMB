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
        $db = \Config\Database::connect();
        $schema = $db->getDatabase(); // Get the current database name/schema

        $query = $db->table('information_schema.table_constraints')
                    ->where('constraint_type', 'FOREIGN KEY')
                    ->where('constraint_name', 'presupuestomensual_grupo_fk')
                    ->where('table_schema', $schema) // For PostgreSQL, this is usually 'public' or the schema name
                    ->where('table_name', 'PresupuestoMensual')
                    ->get();

        if ($query->getRow()) {
            $this->forge->dropForeignKey('PresupuestoMensual', 'presupuestomensual_grupo_fk');
        }
        $this->forge->dropColumn('PresupuestoMensual', 'ID_GrupoPresupuestal');
    }
}
