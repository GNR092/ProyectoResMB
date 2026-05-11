<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsuariosProductosFavoritos extends Migration
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
            'id_usuario' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'id_catalogoprod' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'alias_personal' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'frecuencia' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
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

        $this->forge->addPrimaryKey('id');
        
        // Foreign Keys
        $this->forge->addForeignKey('id_usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_catalogoprod', 'Catalogo_Productos', 'ID_CatalogoProd', 'CASCADE', 'CASCADE');

        $this->forge->createTable('usuarios_productos_favoritos', true);
    }

    public function down()
    {
        $this->forge->dropTable('usuarios_productos_favoritos', true);
    }
}
