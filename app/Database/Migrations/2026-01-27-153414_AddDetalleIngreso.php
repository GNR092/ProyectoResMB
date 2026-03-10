<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDetalleIngreso extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_DetalleIngreso' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Ingreso' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ID_Producto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'CantidadOriginal' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'CantidadIngresada' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);

        $this->forge->addKey('ID_DetalleIngreso', true);

        // FKs: Una a la tabla nueva (Ingresos) y otra a la existente (Producto)
        $this->forge->addForeignKey('ID_Ingreso', 'Ingresos', 'ID_Ingreso', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Producto', 'Producto', 'ID_Producto', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('DetalleIngreso');
    }

    public function down()
    {
        $this->forge->dropTable('DetalleIngreso', true);
    }
}
