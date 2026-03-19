<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertUnidadOperativaData extends Migration
{
    public function up()
    {
        $data = [
            ['ID_UnidadOperativa' => 3, 'Nombre' => 'Transporte Campus', 'ID_Place' => 4, 'activo' => true],
            ['ID_UnidadOperativa' => 2, 'Nombre' => 'Gastos', 'ID_Place' => 5, 'activo' => true],
            ['ID_UnidadOperativa' => 4, 'Nombre' => 'Presupuesto del área de atención a residentes', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 5, 'Nombre' => 'Presupuesto del área de Mantenimiento', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 6, 'Nombre' => 'Presupuesto del área de Sistemas', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 7, 'Nombre' => 'Presupuesto del área de Seguridad', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 8, 'Nombre' => 'Presupuesto de operadora general', 'ID_Place' => 2, 'activo' => true],
            ['ID_UnidadOperativa' => 1, 'Nombre' => 'Operación Central (Migración)1', 'ID_Place' => 1, 'activo' => false],
            ['ID_UnidadOperativa' => 9, 'Nombre' => 'Gastos generales', 'ID_Place' => 1, 'activo' => true],
        ];

        $this->db->table('UnidadOperativa')->insertBatch($data);
    }

    public function down()
    {
        // Al forzar los IDs, podemos revertir la migración de forma muy precisa usando esos mismos IDs
        $insertedIds = [1, 2, 3, 4, 5, 6, 7, 8, 9];

        $this->db->table('UnidadOperativa')->whereIn('ID_UnidadOperativa', $insertedIds)->delete();
    }
}