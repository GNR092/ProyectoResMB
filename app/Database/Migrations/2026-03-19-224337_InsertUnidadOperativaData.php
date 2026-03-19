<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertUnidadOperativaData extends Migration
{
    public function up()
    {
        $data = [
            ['Nombre' => 'Transporte Campus', 'ID_Place' => 4, 'activo' => true],
            ['Nombre' => 'Gastos', 'ID_Place' => 5, 'activo' => true],
            ['Nombre' => 'Presupuesto del área de atención a residentes', 'ID_Place' => 2, 'activo' => true],
            ['Nombre' => 'Presupuesto del área de Mantenimiento', 'ID_Place' => 2, 'activo' => true],
            ['Nombre' => 'Presupuesto del área de Sistemas', 'ID_Place' => 2, 'activo' => true],
            ['Nombre' => 'Presupuesto del área de Seguridad', 'ID_Place' => 2, 'activo' => true],
            ['Nombre' => 'Presupuesto de operadora general', 'ID_Place' => 2, 'activo' => true],
            ['Nombre' => 'Operación Central (Migración)1', 'ID_Place' => 1, 'activo' => false],
            ['Nombre' => 'Gastos generales', 'ID_Place' => 1, 'activo' => true],
        ];

        // Insertamos los datos usando Query Builder
        $this->db->table('UnidadOperativa')->insertBatch($data);
    }

    public function down()
    {
        // Revertimos la migración eliminando por el campo 'Nombre'
        $nombresInsertados = [
            'Transporte Campus',
            'Gastos',
            'Presupuesto del área de atención a residentes',
            'Presupuesto del área de Mantenimiento',
            'Presupuesto del área de Sistemas',
            'Presupuesto del área de Seguridad',
            'Presupuesto de operadora general',
            'Operación Central (Migración)1',
            'Gastos generales'
        ];

        $this->db->table('UnidadOperativa')->whereIn('Nombre', $nombresInsertados)->delete();
    }
}