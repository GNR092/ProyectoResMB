<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHistorialProductosTable extends Migration
{
     public function up()
    {
        $this->forge->addField([
            'ID_HistorialP' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'ID_Producto' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'CodigoAnt' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'NombreAnt' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'ExistenciaAnt' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'CodigoNew' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'NombreNew' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'ExistenciaNew' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
            ],
            'Razon' => [
                'type'       => 'TEXT',
                'null'       => true,
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

        $this->forge->addPrimaryKey('ID_HistorialP');
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Producto', 'Producto', 'ID_Producto', 'CASCADE', 'CASCADE');
        $this->forge->createTable('HistorialProductos');
    }

    public function down()
    {
        $this->forge->dropTable('HistorialProductos');
    }
}
