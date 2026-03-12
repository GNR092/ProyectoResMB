<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUnidadOperativaAndRestructure extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
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
                'type'       => 'INT',
                'constraint' => 11,
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

        // 2. Agregar columnas ID_UnidadOperativa a las tablas existentes
        $this->forge->addColumn('Departamentos', [
            'ID_UnidadOperativa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Place'
            ]
        ]);

        $this->forge->addColumn('GrupoPresupuestal', [
            'ID_UnidadOperativa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Dpto'
            ]
        ]);

        $this->forge->addColumn('PresupuestoMensual', [
            'ID_UnidadOperativa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Dpto'
            ]
        ]);

        // 3. MIGRACIÓN DE DATOS (Preservar historial)
        // Obtener todos los Places que tienen departamentos actualmente
        $placesConDeptos = $db->table('Departamentos')
                              ->select('ID_Place')
                              ->distinct()
                              ->where('ID_Place IS NOT NULL')
                              ->get()->getResultArray();

        foreach ($placesConDeptos as $row) {
            $idPlace = $row['ID_Place'] ?? $row['id_place'];
            if (!$idPlace) continue;

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
        $deptos = $db->table('Departamentos')->get()->getResultArray();
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
        $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_depto_unidad');
        $this->forge->processIndexes('Departamentos');

        $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_grupo_unidad');
        $this->forge->processIndexes('GrupoPresupuestal');

        $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_presupuesto_unidad');
        $this->forge->processIndexes('PresupuestoMensual');

        // 5. Eliminar las columnas viejas que ya no se usan
        $this->forge->dropColumn('Departamentos', 'ID_Place');
        $this->forge->dropColumn('GrupoPresupuestal', 'ID_Dpto');
        $this->forge->dropColumn('PresupuestoMensual', 'ID_Dpto');

        $db->transComplete();
    }

    public function down()
    {
        // En caso de rollback, es complejo revertir la normalización sin perder datos.
        // Haremos un esquema básico de bajada.
        $this->forge->addColumn('Departamentos', ['ID_Place' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
        $this->forge->addColumn('GrupoPresupuestal', ['ID_Dpto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);
        $this->forge->addColumn('PresupuestoMensual', ['ID_Dpto' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true]]);

        $this->forge->dropColumn('Departamentos', 'ID_UnidadOperativa');
        $this->forge->dropColumn('GrupoPresupuestal', 'ID_UnidadOperativa');
        $this->forge->dropColumn('PresupuestoMensual', 'ID_UnidadOperativa');

        $this->forge->dropTable('UnidadOperativa');
    }
}
