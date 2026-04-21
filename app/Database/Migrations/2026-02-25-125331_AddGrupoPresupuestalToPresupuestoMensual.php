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

        if (!$this->db->fieldExists('ID_GrupoPresupuestal', 'PresupuestoMensual')) {
            $this->forge->addColumn('PresupuestoMensual', [
                'ID_GrupoPresupuestal' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'PresupuestoMensual', 'presupuestomensual_grupo_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey(
                    'ID_GrupoPresupuestal',
                    'GrupoPresupuestal',
                    'ID_GrupoPresupuestal',
                    'CASCADE',
                    'SET NULL',
                    'presupuestomensual_grupo_fk'
                );
                $this->forge->processIndexes('PresupuestoMensual');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('PresupuestoMensual', 'presupuestomensual_grupo_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('PresupuestoMensual', 'ID_GrupoPresupuestal');
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
