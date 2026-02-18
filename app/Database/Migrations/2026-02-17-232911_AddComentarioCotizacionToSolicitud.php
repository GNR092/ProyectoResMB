<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComentarioCotizacionToSolicitud extends Migration
{
    public function up()
    {
        $fields = [
            'ComentarioCotizacion' => [
                'type'       => 'TEXT', // O 'VARCHAR' con 'constraint' => 255 si es corto
                'null'       => true,
                'after'      => 'ID_Usuario_Autoriza', // Se agregará al final de tus columnas actuales
            ],
        ];

        // Agregamos la columna a la tabla 'Solicitud'
        $this->forge->addColumn('Solicitud', $fields);
    }

    public function down()
    {
        // Eliminamos la columna si revertimos la migración
        $this->forge->dropColumn('Solicitud', 'ComentarioCotizacion');
    }
}