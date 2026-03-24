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
            ['ID_UnidadOperativa' => 1, 'Nombre' => 'Prestamos', 'ID_Place' => 1, 'activo' => true],
            ['ID_UnidadOperativa' => 2, 'Nombre' => 'Gastos', 'ID_Place' => 3, 'activo' => true],
            ['ID_UnidadOperativa' => 3, 'Nombre' => 'Transporte Campus', 'ID_Place' => 9, 'activo' => true],
            ['ID_UnidadOperativa' => 4, 'Nombre' => 'Presupuesto del área de atención a residentes', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 5, 'Nombre' => 'Presupuesto del área de Mantenimiento', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 6, 'Nombre' => 'Presupuesto del área de Sistemas', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 7, 'Nombre' => 'Presupuesto del área de Seguridad', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 8, 'Nombre' => 'Presupuesto de operadora general', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 9, 'Nombre' => 'Equipo de transporte', 'ID_Place' => 1, 'activo' => true],
            ['ID_UnidadOperativa' => 10, 'Nombre' => 'Mobiliario y Equipo', 'ID_Place' => 1, 'activo' => true],
            ['ID_UnidadOperativa' => 11, 'Nombre' => 'Renta Oficinas Empresas asociadas', 'ID_Place' => 1, 'activo' => true],
            ['ID_UnidadOperativa' => 12, 'Nombre' => 'Gastos de avion', 'ID_Place' => 1, 'activo' => true],
            ['ID_UnidadOperativa' => 13, 'Nombre' => 'Gastos de Administración/Operación', 'ID_Place' => 1, 'activo' => true],
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
        $insertedIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];

        $this->db->table('UnidadOperativa')->whereIn('ID_UnidadOperativa', $insertedIds)->delete();
    }
}