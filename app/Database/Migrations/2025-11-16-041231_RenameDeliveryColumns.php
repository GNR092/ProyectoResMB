<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameDeliveryColumns extends Migration
{
    public function up()
    {
        $this->forge->addColumn('Entregas', [
            'NombreEntrega' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
        ]);
        // Rename 'Departamento' to 'DepartamentoRecibe'
        $this->forge->modifyColumn('Entregas', [
            'Departamento' => [
                'name'       => 'DepartamentoRecibe',
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
        ]);

        // Rename 'Receptor' to 'NombreRecibe'
        $this->forge->modifyColumn('Entregas', [
            'Receptor' => [
                'name'       => 'NombreRecibe',
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        // Revert: Rename 'DepartamentoRecibe' back to 'Departamento'
        $this->forge->modifyColumn('Entregas', [
            'DepartamentoRecibe' => [
                'name'       => 'Departamento',
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
        ]);

        // Revert: Rename 'NombreRecibe' back to 'Receptor'
        $this->forge->modifyColumn('Entregas', [
            'NombreRecibe' => [
                'name'       => 'Receptor',
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
        ]);
        $this->forge->dropColumn('Entregas', 'NombreEntrega');
    }
}

