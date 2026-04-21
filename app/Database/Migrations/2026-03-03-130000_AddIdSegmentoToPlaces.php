<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdSegmentoToPlaces extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $driver = $db->DBDriver;

        if (! $db->fieldExists('id_segmento', 'Places')) {
            $field = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ];
            if ($driver === 'MySQLi') {
                $field['after'] = 'ID_RazonSocial';
            }

            $this->forge->addColumn('Places', [
                'id_segmento' => $field,
            ]);
        }

        if ($driver === 'Postgre') {
            $fkExists = (int) ($db->query(
                "SELECT COUNT(*) AS total FROM information_schema.table_constraints WHERE table_catalog = current_database() AND table_schema = current_schema() AND LOWER(table_name) = LOWER('Places') AND LOWER(constraint_name) = LOWER('places_segmento_fk') AND constraint_type = 'FOREIGN KEY'"
            )->getRow('total') ?? 0);
        } else {
            $fkExists = (int) ($db->query(
                "SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER('Places') AND LOWER(CONSTRAINT_NAME) = LOWER('places_segmento_fk') AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
            )->getRow('total') ?? 0);
        }

        if ($fkExists === 0) {
            $this->forge->addForeignKey(
                'id_segmento',
                'segmento_negocio',
                'id',
                'CASCADE',
                'SET NULL',
                'places_segmento_fk'
            );

            $this->forge->processIndexes('Places');
        }

        // --- MIGRACIÓN DE DATOS (Asociar segmentos existentes) ---
        $mapeo = [
            'Campus'            => 1, // Arrendamiento
            'Transporte Campus' => 2, // Transporte
            'BSHotel'           => 3, // Hotel
        ];

        foreach ($mapeo as $nombreCorto => $idSegmento) {
            $exists = $db->table('segmento_negocio')->where('id', $idSegmento)->countAllResults() > 0;
            if ($exists) {
                $db->table('Places')
                   ->where('Nombre_Corto', $nombreCorto)
                   ->update(['id_segmento' => $idSegmento]);
            }
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('Places', 'places_segmento_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('Places', 'id_segmento');
    }
}
