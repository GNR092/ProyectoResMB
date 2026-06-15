<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWhatsAppNotificationsToSolicitud extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Solicitud', [
            'notificaciones_whatsapp' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'notificaciones_whatsapp');
    }
}
