<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddActivoToPlaces extends Migration
{
    public function up()
    {
        $fields = [
            'activo' => [
                'type'    => 'BOOLEAN',
                'default' => true,
                'null'    => false,
            ],
        ];
        $this->forge->addColumn('Places', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('Places', 'activo');
    }
}
