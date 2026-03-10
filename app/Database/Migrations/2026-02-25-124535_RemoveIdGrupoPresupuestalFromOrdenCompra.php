<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveIdGrupoPresupuestalFromOrdenCompra extends Migration
{
    public function up()
    {
        // 1. Intentamos borrar la llave foránea
        // Usamos try-catch para ignorar el error si la llave no existe en la BD
        try {
            $this->forge->dropForeignKey('OrdenCompra', 'ordencompra_grupo_fk');
        } catch (\Throwable $e) {
            // Si falla (porque no existe), no hacemos nada y continuamos.
        }

        // 2. Ahora borramos la columna (si existe)
        // Verificamos si la columna existe antes de intentar borrarla para evitar otro error
        if ($this->db->fieldExists('ID_GrupoPresupuestal', 'OrdenCompra')) {
            $this->forge->dropColumn('OrdenCompra', 'ID_GrupoPresupuestal');
        }
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
        // Nota: Esto solo funcionará si 'OrdenCompra' es InnoDB.
        // Si sigue siendo MyISAM, fallará silenciosamente (lo cual está bien para un rollback).
        $this->forge->addForeignKey(
            'ID_GrupoPresupuestal',
            'GrupoPresupuestal',
            'ID_GrupoPresupuestal',
            'CASCADE',
            'SET NULL',
            'ordencompra_grupo_fk'
        );
    }
}
