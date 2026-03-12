<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdPlaceBackToDepartamentos extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Agregar columna ID_Place
        $fields = [
            'ID_Place' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Dpto'
            ],
        ];
        $this->forge->addColumn('Departamentos', $fields);

        // 2. Restaurar datos basados en la Unidad Operativa actual
        $query = $db->query('
            UPDATE "Departamentos" d
            SET "ID_Place" = u."ID_Place"
            FROM "UnidadOperativa" u
            WHERE d."ID_UnidadOperativa" = u."ID_UnidadOperativa"
        ');

        // 3. Agregar Foreign Key
        $this->forge->addForeignKey('ID_Place', 'Places', 'ID_Place', 'CASCADE', 'SET NULL', 'fk_depto_place_restored');
        $this->forge->processIndexes('Departamentos');
    }

    public function down()
    {
        $this->forge->dropColumn('Departamentos', 'ID_Place');
    }
}
