<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserToCotizacion extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('ID_Usuario_Cotiza', 'Cotizacion')) {
            $this->forge->addColumn('Cotizacion', [
                'ID_Usuario_Cotiza' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'Cotizacion', 'cotizacion_id_usuario_cotiza_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey('ID_Usuario_Cotiza', 'Usuarios', 'ID_Usuario', 'CASCADE', 'SET NULL', 'cotizacion_id_usuario_cotiza_fk');
                $this->forge->processIndexes('Cotizacion');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('Cotizacion', 'cotizacion_id_usuario_cotiza_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('Cotizacion', 'ID_Usuario_Cotiza');
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
