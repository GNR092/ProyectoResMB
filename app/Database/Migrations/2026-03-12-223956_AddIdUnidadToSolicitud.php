<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdUnidadToSolicitud extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $driver = $db->DBDriver;

        if (! $db->tableExists('UnidadOperativa')) {
            $this->forge->addField([
                'ID_UnidadOperativa' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'Nombre' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'ID_Place' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                ],
                'activo' => [
                    'type'    => 'BOOLEAN',
                    'default' => true,
                ],
            ]);
            $this->forge->addKey('ID_UnidadOperativa', true);
            $this->forge->addForeignKey('ID_Place', 'Places', 'ID_Place', 'CASCADE', 'CASCADE');
            $this->forge->createTable('UnidadOperativa');
        }

        if (! $db->fieldExists('ID_UnidadOperativa', 'Solicitud')) {
            $field = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ];
            if ($driver === 'MySQLi') {
                $field['after'] = 'ID_Dpto';
            }

            $fields = [
                'ID_UnidadOperativa' => $field,
            ];
            $this->forge->addColumn('Solicitud', $fields);
        }

        if ($driver === 'Postgre') {
            $db->query('UPDATE "Solicitud" s SET "ID_UnidadOperativa" = d."ID_UnidadOperativa" FROM "Departamentos" d WHERE s."ID_Dpto" = d."ID_Dpto" AND s."ID_UnidadOperativa" IS NULL');
            $db->query('UPDATE "Solicitud" SET "ID_UnidadOperativa" = NULL WHERE "ID_UnidadOperativa" = 0');
            $db->query('UPDATE "Solicitud" s SET "ID_UnidadOperativa" = NULL WHERE s."ID_UnidadOperativa" IS NOT NULL AND NOT EXISTS (SELECT 1 FROM "UnidadOperativa" u WHERE u."ID_UnidadOperativa" = s."ID_UnidadOperativa")');
        } else {
            $db->query('UPDATE `Solicitud` s JOIN `Departamentos` d ON s.`ID_Dpto` = d.`ID_Dpto` SET s.`ID_UnidadOperativa` = d.`ID_UnidadOperativa` WHERE s.`ID_UnidadOperativa` IS NULL');
            $db->query('UPDATE `Solicitud` SET `ID_UnidadOperativa` = NULL WHERE `ID_UnidadOperativa` = 0');
            $db->query('UPDATE `Solicitud` s LEFT JOIN `UnidadOperativa` u ON u.`ID_UnidadOperativa` = s.`ID_UnidadOperativa` SET s.`ID_UnidadOperativa` = NULL WHERE s.`ID_UnidadOperativa` IS NOT NULL AND u.`ID_UnidadOperativa` IS NULL');
        }

        $fkExists = $this->foreignKeyExists($db, 'Solicitud', 'fk_solicitud_unidad');

        if ($fkExists === 0) {
            $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_solicitud_unidad');
            $this->forge->processIndexes('Solicitud');
        }
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'ID_UnidadOperativa');
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
