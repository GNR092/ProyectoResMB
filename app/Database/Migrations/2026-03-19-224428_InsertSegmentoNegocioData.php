<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertSegmentoNegocioData extends Migration
{
    public function up()
    {
        $data = [
            [
                'id_razon_social' => 2,
                'nombre'          => 'Arrendamiento',
                'descripcion'     => "Segmento de negocio arrendamiento para la razón social de MBSP Rentas\r\n ",
                'created_at'      => '2026-03-10 16:49:15',
                'updated_at'      => '2026-03-10 16:49:15',
            ],
            [
                'id_razon_social' => 2,
                'nombre'          => 'Transporte',
                'descripcion'     => 'Segmento de transporte',
                'created_at'      => '2026-03-10 16:56:08',
                'updated_at'      => '2026-03-10 16:56:08',
            ],
            [
                'id_razon_social' => 5,
                'nombre'          => 'Hotel',
                'descripcion'     => 'Segmento de Hotel de la Razon social MBSP Rentas',
                'created_at'      => '2026-03-10 17:03:56',
                'updated_at'      => '2026-03-10 23:57:53',
            ],
            [
                'id_razon_social' => 1,
                'nombre'          => 'Gastos generale',
                'descripcion'     => 'Gastos',
                'created_at'      => '2026-03-17 16:52:31',
                'updated_at'      => '2026-03-17 16:52:31',
            ]
        ];

        // Insertamos los datos usando Query Builder
        $this->db->table('segmento_negocio')->insertBatch($data);
    }

    public function down()
    {
        // Revertimos la migración eliminando por el campo 'nombre'
        $nombresInsertados = [
            'Arrendamiento',
            'Transporte',
            'Hotel',
            'Gastos generale'
        ];

        $this->db->table('segmento_negocio')->whereIn('nombre', $nombresInsertados)->delete();
    }
}