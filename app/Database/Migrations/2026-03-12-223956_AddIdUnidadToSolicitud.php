<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdUnidadToSolicitud extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Agregar columna ID_UnidadOperativa a Solicitud
        $fields = [
            'ID_UnidadOperativa' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Dpto'
            ],
        ];
        $this->forge->addColumn('Solicitud', $fields);

        // 2. Migración de datos: Respaldar la unidad operativa actual de cada solicitud
        // Unimos Solicitud con Departamentos para saber a qué unidad pertenecía
        $db->query('
            UPDATE "Solicitud" s
            SET "ID_UnidadOperativa" = d."ID_UnidadOperativa"
            FROM "Departamentos" d
            WHERE s."ID_Dpto" = d."ID_Dpto"
        ');

        // 3. Agregar Foreign Key para integridad
        $this->forge->addForeignKey('ID_UnidadOperativa', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL', 'fk_solicitud_unidad');
        $this->forge->processIndexes('Solicitud');
    }

    public function down()
    {
        $this->forge->dropColumn('Solicitud', 'ID_UnidadOperativa');
    }
}
