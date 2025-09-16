<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablePago extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Pago' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_OrdenCompra' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_Proveedor' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'Tipo' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ],
            'Fecha_Solicitud' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'Fecha_Pago' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'Folio' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'unique' => true,
                'null' => true,
            ],
            'Concepto' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'Forma' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('ID_Pago', true);
        $this->forge->addForeignKey('ID_OrdenCompra', 'OrdenCompra', 'ID_OrdenCompra', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Pago');
    }

    public function down()
    {
        $this->forge->dropTable('Pago');
    }
}
