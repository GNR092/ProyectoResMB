<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAliasCuentaSucursalToBancoDpto extends Migration
{
    public function up()
    {
        $fields = [
            'Alias' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'ID_BancoDpto'
            ],
            'Cuenta' => [
                'type'       => 'VARCHAR',
                'constraint' => '16',
                'null'       => true,
                'after'      => 'Banco'
            ],
            'Sucursal' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'Cuenta'
            ],
        ];
        if (!$this->db->fieldExists('Alias', 'BancoDpto')) {
            $this->forge->addColumn('BancoDpto', [
                'Alias' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('Cuenta', 'BancoDpto')) {
            $this->forge->addColumn('BancoDpto', [
                'Cuenta' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '16',
                    'null'       => true,
                ],
            ]);
        }

        if (!$this->db->fieldExists('Sucursal', 'BancoDpto')) {
            $this->forge->addColumn('BancoDpto', [
                'Sucursal' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('BancoDpto', ['Alias', 'Cuenta', 'Sucursal']);
    }
}
