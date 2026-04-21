<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableProveedorArchivos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_archivo' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_proveedor' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
            ],
            'nombre_archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'fecha_subida' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id_archivo', true);
        // Usamos el nombre exacto de la tabla de proveedores según la migración original
        $this->forge->addForeignKey('id_proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');
        $this->forge->createTable('proveedor_archivos', true);
    }

    public function down()
    {
        $this->forge->dropTable('proveedor_archivos', true);
    }
}
