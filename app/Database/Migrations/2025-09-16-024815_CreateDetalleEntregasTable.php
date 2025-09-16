<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDetalleEntregasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_DetalleEntrega' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Entrega' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'ID_Producto' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
            ],
            'Cantidad' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('ID_DetalleEntrega');
        $this->forge->addForeignKey('ID_Entrega', 'Entregas', 'ID_Entrega', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Producto', 'Producto', 'ID_Producto', 'CASCADE', 'CASCADE');
        $this->forge->createTable('DetalleEntrega');
    }

    public function down()
    {
        $this->forge->dropTable('DetalleEntrega');
    }
}