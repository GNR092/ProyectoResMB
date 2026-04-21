<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSegmentoNegocio extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_razon_social' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'nombre' => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
            ],
            'descripcion' => [
                'type'           => 'TEXT',
                'null'           => true,
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

        $this->forge->addKey('id', true);
        
        // Llave foránea vinculada a la tabla Razon_Social (ID_RazonSocial)
        $this->forge->addForeignKey(
            'id_razon_social', 
            'Razon_Social', 
            'ID_RazonSocial', 
            'CASCADE', 
            'CASCADE',
            'segmento_negocio_rs_fk'
        );

        $this->forge->createTable('segmento_negocio', true);
    }

    public function down()
    {
        $this->forge->dropTable('segmento_negocio', true);
    }
}

