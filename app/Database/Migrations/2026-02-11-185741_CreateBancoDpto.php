<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBancoDpto extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_BancoDpto' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Dpto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Debe coincidir con la PK de Departamentos
            ],
            'Clabe' => [
                'type'       => 'VARCHAR',
                'constraint' => '18', // Límite exacto de 18 caracteres
            ],
            'Banco' => [
                'type'       => 'VARCHAR',
                'constraint' => '100', // Longitud suficiente para nombres de bancos
            ],
        ]);

        // Llave Primaria
        $this->forge->addKey('ID_BancoDpto', true);

        // Llave Foránea
        $this->forge->addForeignKey(
            'ID_Dpto',           // Columna actual
            'Departamentos',     // Tabla padre
            'ID_Dpto',           // Columna padre
            'CASCADE',           // On Update
            'CASCADE',           // On Delete (Si borras el depto, se borran sus datos bancarios)
            'bancodpto_dpto_fk'  // Nombre único para evitar conflicto en Postgres
        );

        $this->forge->createTable('BancoDpto');
    }

    public function down()
    {
        $this->forge->dropTable('BancoDpto');
    }
}