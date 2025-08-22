<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAndInsertPlace extends Migration
{
    public function up()
    {
        // Definir la estructura de la tabla "Place"
        $this->forge->addField([
            'ID_Place' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'Nombre_Corto' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'unique'     => true,
                'null'       => false,
            ],
            'Nombre_Completo' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => false,
            ],
        ]);

        $this->forge->addKey('ID_Place', true);
        $this->forge->createTable('Places');

        // Insertar los datos iniciales
        $data = [
            ['Nombre_Corto' => 'City32', 'Nombre_Completo' => 'MB Properties Signature City 32'],
            ['Nombre_Corto' => 'Campus', 'Nombre_Completo' => 'MB Properties Signature Campus'],
        ];

        $this->db->table('Places')->insertBatch($data);
    }

    public function down()
    {
        $this->forge->dropTable('Places');
    }
}
