<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertDepartamento extends Migration
{
    public function up()
    {
        // Verificar si el departamento "Sistemas" ya existe
        $existingDepto = $this->db->table('Departamento')
            ->where('Nombre', 'Sistemas')
            ->get()
            ->getRow();

        // Insertar solo si no existe
        if (!$existingDepto) {
            $this->db->table('Departamento')->insert([
                'Nombre' => 'Sistemas'
            ]);
        }
    }

    public function down()
    {
        //
    }
}
