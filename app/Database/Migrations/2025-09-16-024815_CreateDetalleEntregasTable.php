<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

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
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addPrimaryKey('ID_DetalleEntrega');
        $this->forge->addForeignKey('ID_Entrega', 'Entregas', 'ID_Entrega', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Producto', 'Producto', 'ID_Producto', 'CASCADE', 'CASCADE');
        $this->forge->createTable('DetalleEntrega', true);
    }

    public function down()
    {
        $this->forge->dropTable('DetalleEntrega', true);
    }
}
