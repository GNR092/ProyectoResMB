<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMontoComprometidoOriginalToSolicitudProducto extends Migration
{
    public function up()
    {
        $fields = [
            'Monto_Comprometido_Original' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'Importe'
            ],
        ];
        $this->forge->addColumn('Solicitud_Producto', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud_Producto', 'Monto_Comprometido_Original');
    }
}
