<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMontoComprometidoOriginalToSolicitudProducto extends Migration
{
    public function up()
    {
        $field = [
            'type'       => 'DECIMAL',
            'constraint' => '15,2',
            'default'    => 0.00,
            'null'       => false,
        ];
        if ($this->db->DBDriver === 'MySQLi') {
            $field['after'] = 'Importe';
        }

        if (!$this->db->fieldExists('Monto_Comprometido_Original', 'Solicitud_Producto')) {
            $this->forge->addColumn('Solicitud_Producto', [
                'Monto_Comprometido_Original' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'null'       => false,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud_Producto', 'Monto_Comprometido_Original');
    }
}
