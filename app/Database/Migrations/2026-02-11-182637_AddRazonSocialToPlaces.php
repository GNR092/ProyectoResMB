<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRazonSocialToPlaces extends Migration
{
    public function up()
    {
        // Definición de la columna compatible con ambos motores
        if (!$this->db->fieldExists('ID_RazonSocial', 'Places')) {
            $this->forge->addColumn('Places', [
                'ID_RazonSocial' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        $fkExists = $this->foreignKeyExists($this->db, 'Places', 'places_id_razonsocial_fk');

        if ($fkExists === 0) {
            try {
                $this->forge->addForeignKey('ID_RazonSocial', 'Razon_Social', 'ID_RazonSocial', 'CASCADE', 'SET NULL', 'places_id_razonsocial_fk');
                $this->forge->processIndexes('Places');
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('Places', 'places_id_razonsocial_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('Places', 'ID_RazonSocial');
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
