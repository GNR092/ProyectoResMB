<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableProveedor extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Proveedor' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'Nombre_Comercial' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'RFC' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
                'unique'     => true,
            ],
            'Banco' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'Cuenta' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'null'       => true,
            ],
            'Clabe' => [
                'type'       => 'VARCHAR',
                'constraint' => '25',
                'null'       => true,
            ],
            'Tel_Contacto' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'Nombre_Contacto' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => true,
            ],
            'Servicio' => [
                'type'       => 'TEXT',
                'null'       => true,
            ]
        ]);

        $this->forge->addKey('ID_Proveedor', true);
        $this->forge->createTable('Proveedor');

        // Agregar validaciones específicas para PostgreSQL
        $this->db->query('ALTER TABLE "Proveedor" ADD CONSTRAINT chk_cuenta_numerica CHECK ("Cuenta" IS NULL OR "Cuenta" ~ \'^[0-9]*$\')');
        $this->db->query('ALTER TABLE "Proveedor" ADD CONSTRAINT chk_clabe_numerica CHECK ("Clabe" IS NULL OR "Clabe" ~ \'^[0-9]*$\')');
        $this->db->query('ALTER TABLE "Proveedor" ADD CONSTRAINT chk_telefono_numerico CHECK ("Tel_Contacto" IS NULL OR "Tel_Contacto" ~ \'^[0-9]*$\')');
    }

    public function down()
    {
        $this->forge->dropTable('Proveedor');
    }
}