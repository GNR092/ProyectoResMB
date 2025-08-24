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
            ['Nombre' => 'MB SIGNATURE PROPERTIES',
              'RFC' => 'MSP220504I99'
            ],
            ['Nombre' => 'MBSP RENTAS',
              'RFC' => 'MRE230623IH5'
            ],
            ['Nombre' => 'MBSP SERVICIOS ACCESORIOS',
              'RFC' => 'MSA230623HP8'
            ], 
            ['Nombre' => 'MBSP INVESTMENTS',
              'RFC' => 'MIN230623P88'
            ],
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
