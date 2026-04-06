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
        $this->forge->addColumn('BancoDpto', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('BancoDpto', ['Alias', 'Cuenta', 'Sucursal']);
    }
}
