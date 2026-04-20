<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixBancoDptoSchema extends Migration
{
    public function up()
    {
        // 1. Intentar borrar la FK vieja hacia Departamentos
        try {
            $this->forge->dropForeignKey('BancoDpto', 'bancodpto_dpto_fk');
        } catch (\Throwable $e) {}

        if ($this->db->fieldExists('ID_Dpto', 'BancoDpto') && !$this->db->fieldExists('ID_RazonSocial', 'BancoDpto')) {
            $fields = [
                'ID_Dpto' => [
                    'name'       => 'ID_RazonSocial',
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => false,
                ],
            ];
            $this->forge->modifyColumn('BancoDpto', $fields);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'BancoDpto', 'bancodpto_rs_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey(
                    'ID_RazonSocial',
                    'Razon_Social',
                    'ID_RazonSocial',
                    'CASCADE',
                    'CASCADE',
                    'bancodpto_rs_fk'
                );
                $this->forge->processIndexes('BancoDpto');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        try {
            $this->forge->dropForeignKey('BancoDpto', 'bancodpto_rs_fk');
        } catch (\Throwable $e) {}

        if ($this->db->fieldExists('ID_RazonSocial', 'BancoDpto') && !$this->db->fieldExists('ID_Dpto', 'BancoDpto')) {
            $fields = [
                'ID_RazonSocial' => [
                    'name'       => 'ID_Dpto',
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => false,
                ],
            ];
            $this->forge->modifyColumn('BancoDpto', $fields);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'BancoDpto', 'bancodpto_dpto_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE', 'bancodpto_dpto_fk');
                $this->forge->processIndexes('BancoDpto');
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
