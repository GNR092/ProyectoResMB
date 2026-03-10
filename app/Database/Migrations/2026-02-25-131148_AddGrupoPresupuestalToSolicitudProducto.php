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
        $db = \Config\Database::connect();
        $schema = $db->getDatabase(); // Get the current database name/schema

        $query = $db->table('information_schema.table_constraints')
                    ->where('constraint_type', 'FOREIGN KEY')
                    ->where('constraint_name', 'solicitudproducto_grupo_fk')
                    ->where('table_schema', $schema) // For PostgreSQL, this is usually 'public' or the schema name
                    ->where('table_name', 'Solicitud_Producto')
                    ->get();

        if ($query->getRow()) {
            try { $this->forge->dropForeignKey('Solicitud_Producto', 'solicitudproducto_grupo_fk'); } catch (\Throwable $e) {}
        }
        $this->forge->dropColumn('Solicitud_Producto', 'ID_GrupoPresupuestal');
    }
}

