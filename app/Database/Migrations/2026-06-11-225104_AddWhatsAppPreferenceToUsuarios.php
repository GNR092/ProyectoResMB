<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWhatsAppPreferenceToUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Usuarios', [
            'notificaciones_whatsapp' => [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('Usuarios', 'notificaciones_whatsapp');
    }
}
