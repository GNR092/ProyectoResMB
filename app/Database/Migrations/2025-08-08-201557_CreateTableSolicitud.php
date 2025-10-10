<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableSolicitudProd extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Solicitud' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Usuario' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_Dpto' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_Proveedor' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'ID_RazonSocial' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'IVA' => [
                'type' => 'BOOLEAN',
                'null' => false,
                'default' => false,
            ],
            'Fecha' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'Estado' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => false,
            ],
            'No_Folio' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'unique' => true,
                'null' => true,
            ],
            'Archivo' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'No_Folio',
            ],
            'ComentariosAdmin' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'Archivo',
            ],
            'ComentariosUser' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'ComentariosAdmin',
            ],
            'Tipo' => [
                'type' => 'INT',
                'constraint' => 1,
                'unsigned' => true,
                'null' => false,
            ],
            'MetodoPago' => [
                'type' => 'INT',
                'constraint' => 1,
                'unsigned' => true,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('ID_Solicitud', true);
        $this->forge->addForeignKey('ID_Usuario', 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_RazonSocial', 'Razon_Social', 'ID_RazonSocial', 'CASCADE', 'SET NULL');
        //$this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE');
        //$this->forge->addForeignKey('ID_Proveedor', 'Proveedor', 'ID_Proveedor', 'CASCADE', 'CASCADE');
        $this->forge->createTable('Solicitud');
    }

    public function down()
    {
        $this->forge->dropTable('Solicitud');
    }
}