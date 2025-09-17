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
            'Operacion',
            'Proyectos', 
            'Recepcion',
            'Recursos humanos',
            'Sistemas',
            'Tesoreria',
            'Ventas',
        ];

        foreach ($departamentos as $depto) {
            // Verificación con escape de caracteres
            $exists = $this->db->table('Departamentos')
                ->where('Nombre', $depto)
                ->where('ID_Place', 1)
                ->countAllResults();

            if (!$exists) {
                $this->db->table('Departamentos')->insert(['Nombre' => $depto, 'ID_Place' => 1]);
                log_message('info', "Insertado departamento: {$depto} para ID_Place 1");
            }
        }
    }

    public function down()
    {
        //
    }
}
