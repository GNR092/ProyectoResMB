<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertRazonSocial extends Migration
{
    public function up()
    {
        // Verificar existencia de la tabla
        if (!$this->db->tableExists('RazonSocial')) {
            throw new \RuntimeException('La tabla RazonSocial no existe');
        }

        // Datos a insertar
        $razones = [
            ['Nombre' => 'MBSignature'],
            ['Nombre' => 'Otros']
        ];

        // Insertar con validación individual
        foreach ($razones as $razon) {
            $exists = $this->db->table('RazonSocial')
                ->where('Nombre', $razon['Nombre'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('RazonSocial')->insert($razon);
                log_message('info', '[Migración] Insertada razón social: ' . $razon['Nombre']);
            }
        }
    }

    public function down()
    {
        //
    }
}
