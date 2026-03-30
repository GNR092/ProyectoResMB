<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepairUnidadOperativaIntegrity extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        $tablasUnidad = [
            'Solicitud',
            'Departamentos',
            'GrupoPresupuestal',
            'PresupuestoMensual',
        ];

        // 1) Normalizar 0 -> NULL en columnas de unidad operativa
        foreach ($tablasUnidad as $tabla) {
            if ($db->tableExists($tabla) && $db->fieldExists('ID_UnidadOperativa', $tabla)) {
                $this->setZeroAsNull($tabla, 'ID_UnidadOperativa');
            }
        }

        // 2) Backfill de Solicitud por ID_Dpto para bases existentes
        if (
            $db->tableExists('Solicitud')
            && $db->tableExists('Departamentos')
            && $db->fieldExists('ID_UnidadOperativa', 'Solicitud')
            && $db->fieldExists('ID_Dpto', 'Solicitud')
            && $db->fieldExists('ID_UnidadOperativa', 'Departamentos')
            && $db->fieldExists('ID_Dpto', 'Departamentos')
        ) {
            $tSolicitud = $this->qi('Solicitud');
            $tDptos = $this->qi('Departamentos');
            $cUnidad = $this->qi('ID_UnidadOperativa');
            $cDpto = $this->qi('ID_Dpto');

            $db->query(
                "UPDATE {$tSolicitud} s SET {$cUnidad} = (SELECT d.{$cUnidad} FROM {$tDptos} d WHERE d.{$cDpto} = s.{$cDpto} LIMIT 1) WHERE s.{$cUnidad} IS NULL"
            );

            // Si el backfill trajo 0 desde datos legacy, lo normalizamos de nuevo.
            $this->setZeroAsNull('Solicitud', 'ID_UnidadOperativa');
        }

        // 3) Limpiar huerfanos contra UnidadOperativa
        if ($db->tableExists('UnidadOperativa') && $db->fieldExists('ID_UnidadOperativa', 'UnidadOperativa')) {
            foreach ($tablasUnidad as $tabla) {
                if ($db->tableExists($tabla) && $db->fieldExists('ID_UnidadOperativa', $tabla)) {
                    $this->setOrphansAsNull($tabla, 'ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa');
                }
            }
        }

        // 4) Asegurar FK principal de Solicitud -> UnidadOperativa
        if (
            $db->tableExists('Solicitud')
            && $db->tableExists('UnidadOperativa')
            && $db->fieldExists('ID_UnidadOperativa', 'Solicitud')
            && $db->fieldExists('ID_UnidadOperativa', 'UnidadOperativa')
            && $this->foreignKeyExists($db, 'Solicitud', 'fk_solicitud_unidad') === 0
        ) {
            $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_solicitud_unidad');
            $this->forge->processIndexes('Solicitud');
        }

        // 5) En PostgreSQL, ajustar secuencia tras inserts con ID explicito.
        if ($db->DBDriver === 'Postgre' && $db->tableExists('UnidadOperativa')) {
            $db->query(
                'SELECT setval(pg_get_serial_sequence(\'"UnidadOperativa"\', \'ID_UnidadOperativa\'), COALESCE((SELECT MAX("ID_UnidadOperativa") FROM "UnidadOperativa"), 1), true)'
            );
        }
    }

    public function down()
    {
        // Migracion de reparacion de datos existente: no reversible automaticamente.
    }

    private function setZeroAsNull(string $table, string $column): void
    {
        $t = $this->qi($table);
        $c = $this->qi($column);
        $this->db->query("UPDATE {$t} SET {$c} = NULL WHERE {$c} = 0");
    }

    private function setOrphansAsNull(string $table, string $column, string $parentTable, string $parentColumn): void
    {
        $t = $this->qi($table);
        $c = $this->qi($column);
        $pt = $this->qi($parentTable);
        $pc = $this->qi($parentColumn);
        $this->db->query(
            "UPDATE {$t} x SET {$c} = NULL WHERE x.{$c} IS NOT NULL AND NOT EXISTS (SELECT 1 FROM {$pt} p WHERE p.{$pc} = x.{$c})"
        );
    }

    private function qi(string $identifier): string
    {
        return $this->db->DBDriver === 'Postgre'
            ? '"' . $identifier . '"'
            : '`' . $identifier . '`';
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
