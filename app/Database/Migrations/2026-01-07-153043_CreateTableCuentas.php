<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableCuentas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Cuenta' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Proveedor' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'Cuenta' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => false,
            ]
        ]);

        // Definir clave primaria
        $this->forge->addPrimaryKey('ID_Cuenta');

        // Definir clave foránea
        $this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');

        // Crear tabla
        $this->forge->createTable('Cuentas', true);
    }

    public function down()
    {
        // Eliminar tabla
        $this->forge->dropTable('Cuentas', true);
    }
}
