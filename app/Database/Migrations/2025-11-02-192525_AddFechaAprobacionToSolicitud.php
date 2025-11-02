<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaAprobacionToSolicitud extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Solicitud', [
            'Fecha_Aprobacion' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'Status',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'Fecha_Aprobacion');
    }
}