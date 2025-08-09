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
                'constraint' => 5, // Puedes ajustar este valor si esperas IDs muy grandes
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
                'unique' => true, // Para asegurar que el correo sea único
                'null' => false,
            ],
            'Contraseña' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'Complejo' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true, // Es nulo porque no está marcado como NOT NULL en tu SQL
            ],
        ]);

        $this->forge->addKey('ID_Usuario', true); // Define ID_Usuario como clave primaria

        // Definir claves foráneas
        // Nota: Asumimos que la tabla 'Departamentos' en tu SQL es 'Departamento' de tu migración anterior.
        $this->forge->addForeignKey('ID_Dpto', 'Departamento', 'ID_Dpto', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ID_RazonSocial', 'RazonSocial', 'ID_RazonSocial', 'CASCADE', 'CASCADE');

        $this->forge->createTable('Usuarios');

        // --- Lógica para asegurar que existan Departamento y RazonSocial por defecto ---

        // Variables para almacenar los IDs que usaremos
        $departamentoId = null;
        $razonSocialId = null;

        // 1. Verificar y/o crear Departamento por defecto
        // Intenta encontrar un departamento llamado 'Administración'
        $defaultDpto = $this->db->table('Departamento')->where('Nombre', 'Administración')->get()->getRow();
        if ($defaultDpto) {
            $departamentoId = $defaultDpto->ID_Dpto;
        } else {
            // Si no existe, insértalo y obtiene el ID generado
            $this->db->table('Departamento')->insert(['Nombre' => 'Administración']);
            $departamentoId = $this->db->insertID(); // Obtiene el último ID insertado
        }

        // 2. Verificar y/o crear RazonSocial por defecto
        // Intenta encontrar una RazonSocial llamada 'Interna'
        $defaultRS = $this->db->table('RazonSocial')->where('Nombre', 'Interna')->get()->getRow();
        if ($defaultRS) {
            $razonSocialId = $defaultRS->ID_RazonSocial;
        } else {
            // Si no existe, insértala y obtiene el ID generado
            $this->db->table('RazonSocial')->insert(['Nombre' => 'Interna']);
            $razonSocialId = $this->db->insertID(); // Obtiene el último ID insertado
        }

        // --- Insertar el superusuario por defecto ---
        $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);

        $data = [
            'ID_Dpto'        => $departamentoId,
            'ID_RazonSocial' => $razonSocialId,
            'Nombre'         => 'Admin',
            'Correo'         => 'admin@example.com', // Correo del administrador
            'Contraseña'     => $hashedPassword,
            'Complejo'       => 'Principal', // Puedes ajustar el complejo
        ];

        // Insertar los datos del usuario administrador
        $this->db->table('Usuarios')->insert($data);
    }

    public function down()
    {
        $this->forge->dropTable('Usuarios');
    }
}
