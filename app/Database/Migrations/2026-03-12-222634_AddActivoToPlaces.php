<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActivoToPlaces extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('activo', 'Places')) {
            $this->forge->addColumn('Places', [
                'activo' => [
                    'type'    => 'BOOLEAN',
                    'default' => true,
                    'null'    => false,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('Places', 'activo');
    }
}
