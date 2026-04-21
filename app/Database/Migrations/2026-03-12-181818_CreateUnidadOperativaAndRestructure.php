<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUnidadOperativaAndRestructure extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $driver = $db->DBDriver;
        $db->transStart();

        // 1. Crear tabla UnidadOperativa
        $this->forge->addField([
            'ID_UnidadOperativa' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
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
        $this->forge->createTable('UnidadOperativa', true);

        // 2. Agregar columnas ID_UnidadOperativa a las tablas existentes
        $campoDepto = [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
        ];
        if ($driver === 'MySQLi') {
            $campoDepto['after'] = 'ID_Place';
        }
        $this->forge->addColumn('Departamentos', ['ID_UnidadOperativa' => $campoDepto]);

        $campoGrupo = [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
        ];
        if ($driver === 'MySQLi') {
            $campoGrupo['after'] = 'ID_Dpto';
        }
        $this->forge->addColumn('GrupoPresupuestal', ['ID_UnidadOperativa' => $campoGrupo]);

        $campoPresupuesto = [
            'type'       => 'INT',
            'constraint' => 11,
            'unsigned'   => true,
            'null'       => true,
        ];
        if ($driver === 'MySQLi') {
            $campoPresupuesto['after'] = 'ID_Dpto';
        }
        $this->forge->addColumn('PresupuestoMensual', ['ID_UnidadOperativa' => $campoPresupuesto]);

        // 3. MIGRACIÓN DE DATOS (Preservar historial)
        // Obtener todos los Places que tienen departamentos actualmente
        $placesConDeptos = $db->table('Departamentos')
                              ->select('ID_Place')
                              ->distinct()
                              ->where('ID_Place IS NOT NULL')
                              ->get();

        if ($placesConDeptos === false) {
            log_message('error', 'CreateUnidadOperativaAndRestructure: Error al obtener places con deptos');
            log_message('error', 'Error: ' . print_r($db->error(), true));
            $placesConDeptos = [];
        } else {
            $placesConDeptos = $placesConDeptos->getResultArray();
        }

        foreach ($placesConDeptos as $row) {
            $idPlace = $row['ID_Place'] ?? $row['id_place'];
            if (!$idPlace) continue;

            $placeExists = $db->table('Places')->where('ID_Place', $idPlace)->countAllResults() > 0;
            if (!$placeExists) continue;

            // Crear Unidad Operativa Genérica para este Place
            $db->table('UnidadOperativa')->insert([
                'Nombre'   => 'Operación Central (Migración)',
                'ID_Place' => $idPlace,
                'activo'   => true
            ]);
            $idUnidad = $db->insertID();

            // Enlazar Departamentos de este Place a la nueva Unidad Operativa
            $db->table('Departamentos')
               ->where('ID_Place', $idPlace)
               ->update(['ID_UnidadOperativa' => $idUnidad]);
        }

        // Enlazar GrupoPresupuestal y PresupuestoMensual basándose en su ID_Dpto actual
        // Necesitamos mapear ID_Dpto -> ID_UnidadOperativa
        $deptosResult = $db->table('Departamentos')->get();
        $deptos = $deptosResult === false ? [] : $deptosResult->getResultArray();
        foreach ($deptos as $d) {
            $idDpto = $d['ID_Dpto'] ?? $d['id_dpto'];
            $idUnidad = $d['ID_UnidadOperativa'] ?? $d['id_unidadoperativa'];
            if (!$idUnidad) continue;

            $db->table('GrupoPresupuestal')
               ->where('ID_Dpto', $idDpto)
               ->update(['ID_UnidadOperativa' => $idUnidad]);

            $db->table('PresupuestoMensual')
               ->where('ID_Dpto', $idDpto)
               ->update(['ID_UnidadOperativa' => $idUnidad]);
        }

        // 4. Establecer Foreign Keys para las nuevas columnas
        // Nota: En algunos motores de BD (como MySQL) agregar FK a una columna que ya existe requiere un ALTER TABLE
        try {
            $fkExists = $this->foreignKeyExists($db, 'Departamentos', 'fk_depto_unidad');
            if ($fkExists === 0 && $this->columnExists('Departamentos', 'ID_UnidadOperativa')) {
                $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_depto_unidad');
            }
        } catch (\Throwable $e) { log_message('error', 'FK Deptos: ' . $e->getMessage()); }
        try { $this->forge->processIndexes('Departamentos'); } catch (\Throwable $e) {}

        try {
            $fkExists = $this->foreignKeyExists($db, 'GrupoPresupuestal', 'fk_grupo_unidad');
            if ($fkExists === 0 && $this->columnExists('GrupoPresupuestal', 'ID_UnidadOperativa')) {
                $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_grupo_unidad');
            }
        } catch (\Throwable $e) { log_message('error', 'FK Grupo: ' . $e->getMessage()); }
        try { $this->forge->processIndexes('GrupoPresupuestal'); } catch (\Throwable $e) {}

        try {
            $fkExists = $this->foreignKeyExists($db, 'PresupuestoMensual', 'fk_presupuesto_unidad');
            if ($fkExists === 0 && $this->columnExists('PresupuestoMensual', 'ID_UnidadOperativa')) {
                $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_presupuesto_unidad');
            }
        } catch (\Throwable $e) { log_message('error', 'FK Presupuesto: ' . $e->getMessage()); }
        try { $this->forge->processIndexes('PresupuestoMensual'); } catch (\Throwable $e) {}

        // 5. Eliminar las columnas viejas que ya no se usan
        // Se comenta la eliminación de ID_Place de Departamentos para preservar datos del servidor.
        // $this->forge->dropColumn('Departamentos', 'ID_Place');
        if ($this->columnExists('GrupoPresupuestal', 'ID_Dpto')) {
            $this->forge->dropColumn('GrupoPresupuestal', 'ID_Dpto');
        }
        if ($this->columnExists('PresupuestoMensual', 'ID_Dpto')) {
            $this->forge->dropColumn('PresupuestoMensual', 'ID_Dpto');
        }

        $db->transComplete();
    }

    public function down()
    {
        if (!$this->db->tableExists('UnidadOperativa')) {
            return;
        }

        if (!$this->columnExists('Departamentos', 'ID_Place')) {
            $this->forge->addColumn('Departamentos', ['ID_Place' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
        }

        if (!$this->columnExists('GrupoPresupuestal', 'ID_Dpto')) {
            $this->forge->addColumn('GrupoPresupuestal', ['ID_Dpto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
        }

        if (!$this->columnExists('PresupuestoMensual', 'ID_Dpto')) {
            $this->forge->addColumn('PresupuestoMensual', ['ID_Dpto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
        }

        if ($this->columnExists('Departamentos', 'ID_UnidadOperativa')) {
            $this->forge->dropColumn('Departamentos', 'ID_UnidadOperativa');
        }
        if ($this->columnExists('GrupoPresupuestal', 'ID_UnidadOperativa')) {
            $this->forge->dropColumn('GrupoPresupuestal', 'ID_UnidadOperativa');
        }
        if ($this->columnExists('PresupuestoMensual', 'ID_UnidadOperativa')) {
            $this->forge->dropColumn('PresupuestoMensual', 'ID_UnidadOperativa');
        }

        if ($this->db->tableExists('UnidadOperativa')) {
            $this->forge->dropTable('UnidadOperativa');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $result = $this->db->query(
                "SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_catalog = current_database() AND table_schema = current_schema() AND LOWER(table_name) = LOWER(?) AND LOWER(column_name) = LOWER(?)",
                [$table, $column]
            );
            if ($result === false) return false;
            $row = $result->getRow();
            return $row && $row->cnt > 0;
        } catch (\Throwable $e) {
            return false;
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
