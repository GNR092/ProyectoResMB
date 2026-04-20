<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdDptoToGrupoPresupuestal extends Migration
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
            $field['after'] = 'Descripcion';
        }

        if (!$this->db->fieldExists('ID_Dpto', 'GrupoPresupuestal')) {
            $this->forge->addColumn('GrupoPresupuestal', [
                'ID_Dpto' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'GrupoPresupuestal', 'grupopresupuestal_id_dpto_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey(
                    'ID_Dpto',
                    'Departamentos',
                    'ID_Dpto',
                    'CASCADE',
                    'SET NULL',
                    'grupopresupuestal_id_dpto_fk'
                );
                $this->forge->processIndexes('GrupoPresupuestal');
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

    public function down()
    {
        try { $this->forge->dropForeignKey('GrupoPresupuestal', 'grupopresupuestal_id_dpto_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('GrupoPresupuestal', 'ID_Dpto');
    }
}
