<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableUsuarios extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_Usuario' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ID_Dpto' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'ID_RazonSocial' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => false,
            ],
            'Nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'Correo' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'unique' => true,
                'null' => false,
            ],
            'ContrasenaP' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'ContrasenaG' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'Numero' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('ID_Usuario', true);
        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_RazonSocial', 'Razon_Social', 'ID_RazonSocial', 'CASCADE', 'CASCADE');

        $this->forge->createTable('Usuarios');

        $departamentoId = null;
        $razonSocialId = null;

        $defaultDpto = $this->db->table('Departamentos')->where('Nombre', 'Administración')->get()->getRow();
        if ($defaultDpto) {
            $departamentoId = $defaultDpto->ID_Dpto;
        } else {
            $this->db->table('Departamentos')->insert(['Nombre' => 'Administración']);
            $departamentoId = $this->db->insertID();
        }

        $defaultRS = $this->db->table('Razon_Social')->where('Nombre', 'Interna')->get()->getRow();
        if ($defaultRS) {
            $razonSocialId = $defaultRS->ID_RazonSocial;
        } else {
            $this->db->table('Razon_Social')->insert(['Nombre' => 'Interna', 'RFC' => 'INTERNAL123']);
            $razonSocialId = $this->db->insertID();
        }

        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);

        $data = [
            'ID_Dpto'        => $departamentoId,
            'ID_RazonSocial' => $razonSocialId,
            'Nombre'         => 'Admin',
            'Correo'         => 'admin@example.com',
            'ContrasenaP'    => $hashedPassword,
            'Numero'       => '+019999999999',
        ];

        $this->db->table('Usuarios')->insert($data);
    }

    public function down()
    {
        $this->forge->dropTable('Usuarios');
    }
}
