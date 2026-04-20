<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class InsertMissingPlaces extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Datos a insertar sin IDs fijos para permitir autoincremento
        $placesToInsert = [
            ['Nombre_Corto' => 'Transporte', 'Nombre_Completo' => 'MBSP Transporte', 'ID_RazonSocial' => null, 'activo' => true],
            ['Nombre_Corto' => 'Gastos', 'Nombre_Completo' => 'MBSP Gastos Generales', 'ID_RazonSocial' => null, 'activo' => true],
        ];

        $insertedIds = []; // Para guardar los nuevos IDs generados

        foreach ($placesToInsert as $placeData) {
            // Verificar si el lugar ya existe por Nombre_Corto para evitar duplicados
            $existingPlace = $db->table('Places')
                                ->where('Nombre_Corto', $placeData['Nombre_Corto'])
                                ->get()
                                ->getRowArray();

            if (!$existingPlace) {
                // Insertar el nuevo lugar y obtener el ID autoincremental
                $db->table('Places')->insert($placeData);
                $insertedIds[$placeData['Nombre_Corto']] = $db->insertID();
                log_message('info', '[Migración Places] Insertado: ' . $placeData['Nombre_Corto'] . ' con ID: ' . $db->insertID());
            } else {
                // Si ya existe, simplemente guardamos su ID
                $insertedIds[$placeData['Nombre_Corto']] = $existingPlace['ID_Place'];
                log_message('info', '[Migración Places] Ya existe: ' . $placeData['Nombre_Corto'] . ' con ID: ' . $existingPlace['ID_Place']);
            }
        }

        // 2. Actualizar las Unidades Operativas que dependían de los IDs 4 y 5 viejos
        // Ahora usarán los IDs generados para 'Transporte' y 'Gastos'
        
        $transportePlaceId = $insertedIds['Transporte'] ?? null;
        $gastosPlaceId = $insertedIds['Gastos'] ?? null;

        if ($transportePlaceId) {
            // Actualizar UnidadOperativa 'Transporte Campus'
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Transporte Campus')
               ->update(['ID_Place' => $transportePlaceId]);
            log_message('info', '[Migración Places] Actualizada UnidadOperativa "Transporte Campus" con ID_Place: ' . $transportePlaceId);
        }

        if ($gastosPlaceId) {
            // Actualizar UnidadOperativa 'Gastos'
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Gastos')
               ->update(['ID_Place' => $gastosPlaceId]);
            log_message('info', '[Migración Places] Actualizada UnidadOperativa "Gastos" con ID_Place: ' . $gastosPlaceId);
        }

        // Para cualquier otra tabla que dependiera de los IDs fijos 4 y 5 de Places
        // y que ahora deba apuntar a los nuevos IDs de Transporte y Gastos,
        // se debería añadir lógica de actualización aquí.
        // Por ejemplo: si alguna otra tabla X tuviera una FK a Places y apuntara a 4/5 por error.
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $transportePlace = $db->table('Places')->where('Nombre_Corto', 'Transporte')->get()->getRowArray();
        $gastosPlace = $db->table('Places')->where('Nombre_Corto', 'Gastos')->get()->getRowArray();
        $transportePlaceId = $transportePlace['ID_Place'] ?? null;
        $gastosPlaceId = $gastosPlace['ID_Place'] ?? null;

        if ($transportePlaceId) {
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Transporte Campus')
               ->update(['ID_Place' => 1]);
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Presupuesto del área de atención a residentes')
               ->update(['ID_Place' => 1]);
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Presupuesto del área de Mantenimiento')
               ->update(['ID_Place' => 1]);
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Presupuesto del área de Sistemas')
               ->update(['ID_Place' => 1]);
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Presupuesto del área de Seguridad')
               ->update(['ID_Place' => 1]);
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Presupuesto de operadora general')
               ->update(['ID_Place' => 1]);
        }

        if ($gastosPlaceId) {
            $db->table('UnidadOperativa')
               ->where('Nombre', 'Gastos')
               ->update(['ID_Place' => 1]);
        }

        $db->table('Places')
           ->whereIn('Nombre_Corto', ['Transporte', 'Gastos'])
           ->delete();
    }
}
