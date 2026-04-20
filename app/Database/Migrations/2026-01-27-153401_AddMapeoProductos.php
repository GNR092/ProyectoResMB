<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMapeoProductos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Mapeo' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Proveedor' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'IdentificadorXML' => [
                'type'       => 'TEXT',
                'null'       => false,
            ],
            'ID_Producto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Ajustar si tu tabla Producto no es unsigned
            ],
            'FactorConversion' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 1.00,
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

        $this->forge->addKey('ID_Mapeo', true);

        // FKs a tus tablas existentes
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('ID_Producto', 'Producto', 'ID_Producto', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('MapeoProductos', true);
    }

    public function down()
    {
        $this->forge->dropTable('MapeoProductos', true);
    }
}
