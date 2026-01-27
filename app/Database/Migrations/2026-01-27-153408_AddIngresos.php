<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIngresos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Ingreso' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Proveedor' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ID_Usuario' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'UUID' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'unique'     => true,
            ],
            'RFC_Receptor' => [
                'type'       => 'VARCHAR',
                'constraint' => 13,
            ],
            'FechaEmision' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'NombreArchivoXML' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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

        $this->forge->addKey('ID_Ingreso', true);

        // FKs a tus tablas existentes
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'RESTRICT');

        $this->forge->createTable('Ingresos');
    }

    public function down()
    {
        $this->forge->dropTable('Ingresos');
    }
}