<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudProductoTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_SolicitudProducto' => [
                'type'           => 'INT', 
                'constraint'     => 11,
                'unsigned'       => true,  
                'auto_increment' => true,  
            ],
            'ID_Solicitud' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'Codigo' => [
                'type'       => 'VARCHAR',
                'constraint' => '50', 
                'null'       => false,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'Cantidad' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 1,
            ],
            'Importe' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2', 
                'null'       => false,
                'default'    => '0.00',
            ]
        ]);

        $this->forge->addPrimaryKey('ID_SolicitudProducto');
        $this->forge->addForeignKey('ID_Solicitud', 'Solicitud', 'ID_Solicitud', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Solicitud_Producto');
    }

    public function down()
    {
        $this->forge->dropTable('Solicitud_Producto');
    }
}
