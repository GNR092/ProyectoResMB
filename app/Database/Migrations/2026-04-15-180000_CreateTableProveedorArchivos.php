<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableProveedorArchivos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Archivo' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Proveedor' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
            ],
            'Nombre_Archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'Fecha_Subida' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('ID_Archivo', true);
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Proveedor_Archivos');
    }

    public function down()
    {
        $this->forge->dropTable('Proveedor_Archivos', true);
    }
}
