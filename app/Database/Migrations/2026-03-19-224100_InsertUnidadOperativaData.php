<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertUnidadOperativaData extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Obtener ID_Place de 'Gastos' y 'Transporte' dinámicamente
        $gastosPlace = $db->table('Places')->where('Nombre_Corto', 'Gastos')->get()->getRowArray();
        $transportePlace = $db->table('Places')->where('Nombre_Corto', 'Transporte')->get()->getRowArray();

        $gastosPlaceId = $gastosPlace['ID_Place'] ?? null;
        $transportePlaceId = $transportePlace['ID_Place'] ?? null;

        $data = [
            ['ID_UnidadOperativa' => 2, 'Nombre' => 'Gastos', 'ID_Place' => $gastosPlaceId, 'activo' => true],
            ['ID_UnidadOperativa' => 3, 'Nombre' => 'Transporte Campus', 'ID_Place' => $transportePlaceId, 'activo' => true],
            ['ID_UnidadOperativa' => 4, 'Nombre' => 'Presupuesto del área de atención a residentes', 'ID_Place' => 2, 'activo' => true], // ID 2 (Campus) es fijo, ya existe en el servidor
            ['ID_UnidadOperativa' => 5, 'Nombre' => 'Presupuesto del área de Mantenimiento', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 6, 'Nombre' => 'Presupuesto del área de Sistemas', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 7, 'Nombre' => 'Presupuesto del área de Seguridad', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 8, 'Nombre' => 'Presupuesto de operadora general', 'ID_Place' => 2, 'activo' => true],
        ];

        foreach ($data as $row) {
            $exists = $this->db->table('UnidadOperativa')
                ->where('ID_UnidadOperativa', $row['ID_UnidadOperativa'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('UnidadOperativa')->insert($row);
            } else {
                // Si el ID ya existe (creado por la migración de reestructuración), actualizamos sus datos
                $this->db->table('UnidadOperativa')
                    ->where('ID_UnidadOperativa', $row['ID_UnidadOperativa'])
                    ->update($row);
            }
        }
    }

    public function down()
    {
        // Al forzar los IDs, podemos revertir la migración de forma muy precisa usando esos mismos IDs
        $insertedIds = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $this->db->table('UnidadOperativa')->whereIn('ID_UnidadOperativa', $insertedIds)->delete();
    }
}