<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSolicitudToEventosCalendario extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('ID_Solicitud', 'eventos_calendario')) {
            $this->forge->addColumn('eventos_calendario', [
                'ID_Solicitud' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'eventos_calendario', 'fk_evento_solicitud');
        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey('ID_Solicitud', 'Solicitud', 'ID_Solicitud', 'CASCADE', 'SET NULL', 'fk_evento_solicitud');
                $this->forge->processIndexes('eventos_calendario');
            } catch (\Throwable $e) {
                log_message('error', 'AddSolicitudToEventosCalendario FK: ' . $e->getMessage());
            }
        }

        // Índice para búsquedas
        try {
            $this->db->query('CREATE INDEX IF NOT EXISTS idx_eventos_calendario_id_solicitud ON eventos_calendario ("ID_Solicitud")');
        } catch (\Throwable $e) {
            // MySQL no soporta IF NOT EXISTS en índice, verificar manualmente
            try {
                $indexes = $this->db->query("SHOW INDEX FROM eventos_calendario WHERE Key_name = 'ID_Solicitud'")->getResultArray();
                if (empty($indexes)) {
                    $this->forge->addKey('ID_Solicitud');
                    $this->forge->processIndexes('eventos_calendario');
                }
            } catch (\Throwable $e2) {}
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('eventos_calendario', 'fk_evento_solicitud'); } catch (\Throwable $e) {}
        try { $this->db->query('DROP INDEX IF EXISTS idx_eventos_calendario_id_solicitud ON eventos_calendario'); } catch (\Throwable $e) {}
        if ($this->db->fieldExists('ID_Solicitud', 'eventos_calendario')) {
            $this->forge->dropColumn('eventos_calendario', 'ID_Solicitud');
        }
    }

    private function foreignKeyExists($db, string $tableName, string $constraintName): int
    {
        if ($db->DBDriver === 'Postgre') {
            return (int) ($db->query(
                "SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE table_catalog = current_database() AND table_schema = current_schema() AND LOWER(table_name) = LOWER(?) AND LOWER(constraint_name) = LOWER(?) AND constraint_type = 'FOREIGN KEY'",
                [$tableName, $constraintName]
            )->getRow('total') ?? 0);
        }
        return (int) ($db->query(
            "SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(?) AND LOWER(CONSTRAINT_NAME) = LOWER(?) AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$tableName, $constraintName]
        )->getRow('total') ?? 0);
    }
}
