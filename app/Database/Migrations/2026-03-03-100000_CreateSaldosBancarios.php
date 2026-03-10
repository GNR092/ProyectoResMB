<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSaldosBancarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_bancodpto' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'mes' => [
                'type'           => 'INT',
                'constraint'     => 2,
            ],
            'anio' => [
                'type'           => 'INT',
                'constraint'     => 4,
            ],
            'saldo_inicial' => [
                'type'           => 'DECIMAL',
                'constraint'     => '15,2',
                'default'        => 0.00,
            ],
            'saldo_final' => [
                'type'           => 'DECIMAL',
                'constraint'     => '15,2',
                'default'        => 0.00,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        
        // Llave foránea vinculada a BancoDpto
        $this->forge->addForeignKey(
            'id_bancodpto', 
            'BancoDpto', 
            'ID_BancoDpto', 
            'CASCADE', 
            'CASCADE',
            'saldosbancarios_bancodpto_fk'
        );

        $this->forge->createTable('SaldosBancarios');
    }

    public function down()
    {
        $this->forge->dropTable('SaldosBancarios', true);
    }
}

