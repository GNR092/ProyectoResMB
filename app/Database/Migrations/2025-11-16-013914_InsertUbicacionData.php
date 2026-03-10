<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertUbicacionData extends Migration
{
    public function up()
    {
        $this->db->table('Razon_Social')
            ->update(['Ubicacion' => 'ANDRES GARCIA LAVIN NUMERO INTERIOR PA-13 NUMERO EXTERIOR 298 COLONIA MONTEBELLO MERIDA YUCATAN']);
    }

    public function down()
    {
        $this->db->table('Razon_Social')
            ->update(['Ubicacion' => null]);
    }
}

