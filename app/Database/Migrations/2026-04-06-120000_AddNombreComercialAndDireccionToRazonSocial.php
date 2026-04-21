<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNombreComercialAndDireccionToRazonSocial extends Migration
{
    public function up()
    {
        $fields = [
            'Nombre_Comercial' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'Nombre'
            ],
            'Direccion' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'RFC'
            ],
        ];
        if (!$this->db->fieldExists('Nombre_Comercial', 'Razon_Social')) {
            $this->forge->addColumn('Razon_Social', [
                'Nombre_Comercial' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('Direccion', 'Razon_Social')) {
            $this->forge->addColumn('Razon_Social', [
                'Direccion' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('Razon_Social', ['Nombre_Comercial', 'Direccion']);
    }
}
