<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableProducto extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Producto' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Codigo' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
                'null'       => false,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'Existencia' => [
                'type'       => 'INT',
                'unsigned'   => true, // Equivale a CHECK (Existencia >= 0)
                'default'    => 0,
            ],
        ]);
        $this->forge->addKey('ID_Producto', true);
        $this->forge->createTable('Producto');
    }

    public function down()
    {
        $this->forge->dropTable('Producto');
    }
}
