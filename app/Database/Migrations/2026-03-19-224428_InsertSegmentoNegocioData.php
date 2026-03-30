<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertSegmentoNegocioData extends Migration
{
    public function up()
    {
        $data = [
            [
                'id'              => 1,
                'id_razon_social' => 2,
                'nombre'          => 'Arrendamiento',
                'descripcion'     => "Segmento de negocio arrendamiento para la razón social de MBSP Rentas\r\n ",
                'created_at'      => '2026-03-10 16:49:15',
                'updated_at'      => '2026-03-20 12:24:02',
            ],
            [
                'id'              => 2,
                'id_razon_social' => 2,
                'nombre'          => 'Transporte',
                'descripcion'     => 'Segmento de transporte',
                'created_at'      => '2026-03-10 16:56:08',
                'updated_at'      => '2026-03-10 16:56:08',
            ],
            [
                'id'              => 3,
                'id_razon_social' => 8,
                'nombre'          => 'Hotel',
                'descripcion'     => 'Segmento de Hotel de la Razon social MBSP Rentas',
                'created_at'      => '2026-03-10 17:03:56',
                'updated_at'      => '2026-03-10 23:57:53',
            ],
            [
                'id'              => 4,
                'id_razon_social' => 2,
                'nombre'          => 'Presupuesto Para Residen',
                'descripcion'     => 'Presupuesto Para Residen',
                'created_at'      => '2026-03-26 12:05:27',
                'updated_at'      => '2026-03-26 12:05:27',
            ],
            [
                'id'              => 5,
                'id_razon_social' => 2,
                'nombre'          => 'Presupuesto Para Temozon 122',
                'descripcion'     => 'Presupuesto Para Temozon 122',
                'created_at'      => '2026-03-26 12:05:31',
                'updated_at'      => '2026-03-26 12:05:31',
            ],
            [
                'id'              => 6,
                'id_razon_social' => 2,
                'nombre'          => 'Presupuesto Aldea Borboleta 3',
                'descripcion'     => 'Presupuesto Aldea Borboleta 2',
                'created_at'      => '2026-03-26 12:05:37',
                'updated_at'      => '2026-03-26 12:05:37',
            ],
            [
                'id'              => 7,
                'id_razon_social' => 2,
                'nombre'          => 'Presupuesto Aldea Borboleta 2',
                'descripcion'     => 'Presupuesto Aldea Borboleta 2',
                'created_at'      => '2026-03-26 12:05:41',
                'updated_at'      => '2026-03-26 12:05:41',
            ],
            [
                'id'              => 8,
                'id_razon_social' => 2,
                'nombre'          => 'Presupuesto Aldea Borboleta 1',
                'descripcion'     => 'Presupuesto del condominio aldea borboleta 1',
                'created_at'      => '2026-03-26 12:05:44',
                'updated_at'      => '2026-03-26 12:05:44',
            ],
        ];

        foreach ($data as $row) {
            $exists = $this->db->table('segmento_negocio')
                ->where('id', $row['id'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('segmento_negocio')->insert($row);
            } else {
                $this->db->table('segmento_negocio')
                    ->where('id', $row['id'])
                    ->update($row);
            }
        }
    }

    public function down()
    {
        $insertedIds = [1, 2, 3, 4, 5, 6, 7, 8];

        $this->db->table('segmento_negocio')->whereIn('id', $insertedIds)->delete();
    }
}
