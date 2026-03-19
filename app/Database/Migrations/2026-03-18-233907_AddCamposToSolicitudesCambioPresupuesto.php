<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCamposToSolicitudesCambioPresupuesto extends Migration
{
    public function up()
    {
        $fields = [
            'Comentarios_Solicitante' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'Datos_Antiguos' => [
                'type' => 'JSON', // o TEXT dependiendo del soporte
                'null' => true,
            ],
        ];
        $this->forge->addColumn('SolicitudesCambioPresupuesto', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('SolicitudesCambioPresupuesto', ['Comentarios_Solicitante', 'Datos_Antiguos']);
    }
}
