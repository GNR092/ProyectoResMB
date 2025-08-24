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
            'Administración',
            'Almacen',
            'Cobranza proyectos',
            'Construccion',
            'Compras',
            'Direccion',
            'Marketing',
            'Juridico',
            'Operacion orlando',
            'Proyectos', 
            'Recepcion city',
            'Recursos humanos',
            'Sistemas',
            'Tesoreria',
            'Ventas',
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
