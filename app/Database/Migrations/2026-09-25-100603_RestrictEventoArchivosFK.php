<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestrictEventoArchivosFK extends Migration
{
    public function up()
    {
        $fkExists = $this->foreignKeyExists($this->db, 'evento_archivos', 'fk_evento_archivo');

        if ($fkExists === 1) {
            try {
                $this->forge->dropForeignKey('evento_archivos', 'fk_evento_archivo');
            } catch (\Throwable $e) {}
        }

        $fkExists = $this->foreignKeyExists($this->db, 'evento_archivos', 'fk_evento_archivo');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey(
                    'id_evento',
                    'eventos_calendario',
                    'id',
                    'CASCADE',
                    'RESTRICT',
                    'fk_evento_archivo'
                );
                $this->forge->processIndexes('evento_archivos');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        $fkExists = $this->foreignKeyExists($this->db, 'evento_archivos', 'fk_evento_archivo');

        if ($fkExists === 1) {
            try {
                $this->forge->dropForeignKey('evento_archivos', 'fk_evento_archivo');
            } catch (\Throwable $e) {}
        }

        $fkExists = $this->foreignKeyExists($this->db, 'evento_archivos', 'fk_evento_archivo');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey(
                    'id_evento',
                    'eventos_calendario',
                    'id',
                    'CASCADE',
                    'CASCADE',
                    'fk_evento_archivo'
                );
                $this->forge->processIndexes('evento_archivos');
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