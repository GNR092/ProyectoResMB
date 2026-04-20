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
        } catch (\Throwable $e) {}

        if ($this->db->fieldExists('ID_GrupoPresupuestal', 'OrdenCompra')) {
            $this->forge->dropColumn('OrdenCompra', 'ID_GrupoPresupuestal');
        }
    }

    public function down()
    {
        if (!$this->db->fieldExists('ID_GrupoPresupuestal', 'OrdenCompra')) {
            $this->forge->addColumn('OrdenCompra', [
                'ID_GrupoPresupuestal' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'OrdenCompra', 'ordencompra_grupo_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey(
                    'ID_GrupoPresupuestal',
                    'GrupoPresupuestal',
                    'ID_GrupoPresupuestal',
                    'CASCADE',
                    'SET NULL',
                    'ordencompra_grupo_fk'
                );
                $this->forge->processIndexes('OrdenCompra');
            } catch (\Throwable $e) {}
        }
    }

    private function foreignKeyExists($db, string $tableName, string $constraintName): int
    {
        if ($db->DBDriver === 'Postgre') {
            return (int) ($db->query(
                "SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE table_catalog = current_database() AND table_schema = current_schema() AND LOWER(table_name) = LOWER(?) AND LOWER(constraint_name) = LOWER(?) AND constraint_type = 'FOREIGN KEY'",
                [$tableName, $constraintName],
            )->getRow('total') ?? 0);
        }

        return (int) ($db->query(
            "SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(?) AND LOWER(CONSTRAINT_NAME) = LOWER(?) AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$tableName, $constraintName],
        )->getRow('total') ?? 0);
    }
}
