<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdSegmentoToPlaces extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Places', [
            'id_segmento' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Permitimos nulo para no romper registros existentes
                'after'      => 'ID_RazonSocial' // Posicionamos después de la RS
            ],
        ]);

        // Añadimos la Llave Foránea
        $this->forge->addForeignKey(
            'id_segmento', 
            'segmento_negocio', 
            'id', 
            'CASCADE', 
            'SET NULL',
            'places_segmento_fk'
        );

        // Procesar cambios pendientes (algunos drivers requieren esto para llaves foráneas en addColumn)
        $this->forge->processIndexes('Places');

        // --- MIGRACIÓN DE DATOS (Asociar segmentos existentes) ---
        $db = \Config\Database::connect();
        
        $mapeo = [
            'Campus'            => 1, // Arrendamiento
            'Transporte Campus' => 2, // Transporte
            'BSHotel'           => 3, // Hotel
        ];

        foreach ($mapeo as $nombreCorto => $idSegmento) {
            $db->table('Places')
               ->where('Nombre_Corto', $nombreCorto)
               ->update(['id_segmento' => $idSegmento]);
        }
    }

    public function down()
    {
        try { $this->forge->dropForeignKey('Places', 'places_segmento_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('Places', 'id_segmento');
    }
}

