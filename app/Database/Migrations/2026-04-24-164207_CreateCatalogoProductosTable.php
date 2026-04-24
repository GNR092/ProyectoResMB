<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCatalogoProductosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_CatalogoProd' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_RazonSocial' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'id_segmento' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ID_Place' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ID_Dpto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ID_GrupoPresupuestal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('ID_CatalogoProd');
        
        // Foreign Keys
        $this->forge->addForeignKey('ID_RazonSocial', 'Razon_Social', 'ID_RazonSocial', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('id_segmento', 'segmento_negocio', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('ID_Place', 'Places', 'ID_Place', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'SET NULL');
        $this->forge->addForeignKey('ID_GrupoPresupuestal', 'GrupoPresupuestal', 'ID_GrupoPresupuestal', 'CASCADE', 'SET NULL');

        $this->forge->createTable('Catalogo_Productos', true);
    }

    public function down()
    {
        $this->forge->dropTable('Catalogo_Productos', true);
    }
}
