<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddComentarioCotizacionToSolicitud extends Migration
{
    public function up()
    {
        $field = [
            'type'       => 'TEXT',
            'null'       => true,
        ];
        if ($this->db->DBDriver === 'MySQLi') {
            $field['after'] = 'ID_Usuario_Autoriza';
        }

        $fields = [
            'ComentarioCotizacion' => $field,
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
