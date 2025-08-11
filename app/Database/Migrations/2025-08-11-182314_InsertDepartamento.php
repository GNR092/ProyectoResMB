<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertDepartamento extends Migration
{
    public function up()
    {
        // Forzar verificación de estructura
        if (!$this->db->tableExists('Departamento')) {
            throw new \RuntimeException('La tabla Departamento no existe');
        }

        // Lista completa de departamentos
        $departamentos = [
            'Sistemas',
            'Direccion',
            'Compras',
            'Tesoreria',
            'Almacen'
        ];

        foreach ($departamentos as $depto) {
            // Verificación con escape de caracteres
            $exists = $this->db->table('Departamento')
                ->where('Nombre', $depto)
                ->countAllResults();

            if (!$exists) {
                $this->db->table('Departamento')->insert(['Nombre' => $depto]);
                log_message('info', "Insertado departamento: {$depto}");
            }
        }
    }

    public function down()
    {
        //
    }
}
