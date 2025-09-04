<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterSolicitudAddArchivo extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Solicitud', [
            'Archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'No_Folio'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'Archivo');
    }
}
