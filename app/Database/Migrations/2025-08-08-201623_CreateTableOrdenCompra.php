<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableOrdenCompra extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_OrdenCompra' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Cotizacion' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_Proveedor' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('ID_OrdenCompra', true);
        $this->forge->addForeignKey('ID_Cotizacion', 'Cotizacion', 'ID_Cotizacion', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');
        $this->forge->createTable('OrdenCompra');
    }

    public function down()
    {
        $this->forge->dropTable('OrdenCompra');
    }
}