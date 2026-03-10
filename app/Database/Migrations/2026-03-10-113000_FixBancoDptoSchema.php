<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixBancoDptoSchema extends Migration
{
    public function up()
    {
        // 1. Intentar borrar la FK vieja hacia Departamentos
        try {
            $this->forge->dropForeignKey('BancoDpto', 'bancodpto_dpto_fk');
        } catch (\Throwable $e) {
            // Ignorar si no existe
        }

        // 2. Renombrar ID_Dpto a ID_RazonSocial
        $fields = [
            'ID_Dpto' => [
                'name'       => 'ID_RazonSocial',
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
            ],
        ];
        $this->forge->modifyColumn('BancoDpto', $fields);

        // 3. Crear la nueva FK hacia Razon_Social (ID_RazonSocial)
        $this->forge->addForeignKey(
            'ID_RazonSocial', 
            'Razon_Social', 
            'ID_RazonSocial', 
            'CASCADE', 
            'CASCADE', 
            'bancodpto_rs_fk'
        );

        $this->forge->processIndexes('BancoDpto');
    }

    public function down()
    {
        try {
            $this->forge->dropForeignKey('BancoDpto', 'bancodpto_rs_fk');
        } catch (\Throwable $e) {}

        $fields = [
            'ID_RazonSocial' => [
                'name'       => 'ID_Dpto',
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
            ],
        ];
        $this->forge->modifyColumn('BancoDpto', $fields);

        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'CASCADE', 'bancodpto_dpto_fk');
    }
}
