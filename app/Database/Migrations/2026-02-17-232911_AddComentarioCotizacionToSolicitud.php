<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComentarioCotizacionToSolicitud extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('ComentarioCotizacion', 'Solicitud')) {
            $this->forge->addColumn('Solicitud', [
                'ComentarioCotizacion' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        // Eliminamos la columna si revertimos la migración
        $this->forge->dropColumn('Solicitud', 'ComentarioCotizacion');
    }
}
