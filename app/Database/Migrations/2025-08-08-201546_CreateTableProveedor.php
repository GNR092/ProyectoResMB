<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableProveedor extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Proveedor' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'Nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'Nombre_Comercial' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'RFC' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'unique' => true,
                'null' => true,
            ],
            'Banco' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'Cuenta' => [
                'type' => 'VARCHAR',
                'constraint' => '20',
                'null' => true,
            ],
            'Clabe' => [
                'type' => 'CHAR',
                'constraint' => '18',
                'null' => true,
            ],
            'Tel_Contacto' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'Nombre_Contacto' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'Servicio' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('ID_Proveedor', true);
        $this->forge->createTable('Proveedor');
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query(
                'ALTER TABLE "Proveedor" ADD CONSTRAINT "chk_cuenta_format" CHECK (LENGTH("Cuenta") > 0 AND "Cuenta" ~ \'^\d+$\')',
            );
            $this->db->query(
                'ALTER TABLE "Proveedor" ADD CONSTRAINT "chk_clabe_format" CHECK (LENGTH("Clabe") = 18 AND "Clabe" ~ \'^\d+$\')',
            );
        }
    }

    public function down()
    {
        $this->forge->dropTable('Proveedor');
    }
}