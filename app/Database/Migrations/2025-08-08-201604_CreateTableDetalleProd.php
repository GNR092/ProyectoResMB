<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableDetalle_Prodcutos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_DetalleProd' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_SolicitudProd' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'Nombre_Prod' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'Cantidad' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'Costo' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('ID_DetalleProd', primary: true);
        $this->forge->addForeignKey('ID_SolicitudProd', 'Solicitud_Producto', 'ID_SolicitudProd', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Detalle_Producto');
    }

    public function down()
    {
        $this->forge->dropTable('Detalle_Producto');
    }
}
