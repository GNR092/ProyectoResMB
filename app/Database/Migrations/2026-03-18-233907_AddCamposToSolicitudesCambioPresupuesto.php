<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCamposToSolicitudesCambioPresupuesto extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('Comentarios_Solicitante', 'SolicitudesCambioPresupuesto')) {
            $this->forge->addColumn('SolicitudesCambioPresupuesto', [
                'Comentarios_Solicitante' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('Datos_Antiguos', 'SolicitudesCambioPresupuesto')) {
            $this->forge->addColumn('SolicitudesCambioPresupuesto', [
                'Datos_Antiguos' => [
                    'type' => 'JSON',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('SolicitudesCambioPresupuesto', ['Comentarios_Solicitante', 'Datos_Antiguos']);
    }
}
