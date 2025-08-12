<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertDepartamento extends Migration
{
    public function up()
    {
        // Forzar verificación de estructura
        if (!$this->db->tableExists('Departamentos')) {
            throw new \RuntimeException('La tabla Departamentos no existe');
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
            $exists = $this->db->table('Departamentos')
                ->where('Nombre', $depto)
                ->countAllResults();

            if (!$exists) {
                $this->db->table('Departamentos')->insert(['Nombre' => $depto]);
                log_message('info', "Insertado departamentos: {$depto}");
            }
        }
    }

    public function down()
    {
        //
    }
}
