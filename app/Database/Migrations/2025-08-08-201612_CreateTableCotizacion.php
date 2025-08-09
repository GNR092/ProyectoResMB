<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCotizacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Cotizacion' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Solicitud' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_Proveedor' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'Total' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('ID_Cotizacion', true);
        $this->forge->addForeignKey('ID_Solicitud', 'Solicitud', 'ID_Solicitud', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Cotizacion');
    }

    public function down()
    {
        $this->forge->dropTable('Cotizacion');
    }
}
