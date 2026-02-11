<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRazonSocialToPlaces extends Migration
{
    public function up()
    {
        // Definición de la columna compatible con ambos motores
        $fields = [
            'ID_RazonSocial' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Permitir NULL inicialmente
            ],
        ];

        $this->forge->addColumn('Places', $fields);

        $this->forge->addForeignKey('ID_RazonSocial', 'Razon_Social', 'ID_RazonSocial', 'CASCADE', 'SET NULL', 'places_id_razonsocial_fk');

        $this->forge->processIndexes('Places');
    }

    public function down()
    {
        $this->forge->dropForeignKey('Places', 'places_id_razonsocial_fk');

        $this->forge->dropColumn('Places', 'ID_RazonSocial');
    }
}