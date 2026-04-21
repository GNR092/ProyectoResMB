<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserToSolicitud extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('ID_Usuario_Autoriza', 'Solicitud')) {
            $this->forge->addColumn('Solicitud', [
                'ID_Usuario_Autoriza' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'Solicitud', 'solicitud_id_usuario_autoriza_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey('ID_Usuario_Autoriza', 'Usuarios', 'ID_Usuario', 'CASCADE', 'SET NULL', 'solicitud_id_usuario_autoriza_fk');
                $this->forge->processIndexes('Solicitud');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('Solicitud', 'solicitud_id_usuario_autoriza_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('Solicitud', 'ID_Usuario_Autoriza');
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
