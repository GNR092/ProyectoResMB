<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameDeliveryColumns extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('NombreEntrega', 'Entregas')) {
            $this->forge->addColumn('Entregas', [
                'NombreEntrega' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => false,
                ],
            ]);
        }

        if ($this->db->fieldExists('Departamento', 'Entregas') && !$this->db->fieldExists('DepartamentoRecibe', 'Entregas')) {
            $this->forge->modifyColumn('Entregas', [
                'Departamento' => [
                    'name'       => 'DepartamentoRecibe',
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => false,
                ],
            ]);
        }

        if ($this->db->fieldExists('Receptor', 'Entregas') && !$this->db->fieldExists('NombreRecibe', 'Entregas')) {
            $this->forge->modifyColumn('Entregas', [
                'Receptor' => [
                    'name'       => 'NombreRecibe',
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                    'null'       => false,
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('DepartamentoRecibe', 'Entregas') && !$this->db->fieldExists('Departamento', 'Entregas')) {
            $this->forge->modifyColumn('Entregas', [
                'DepartamentoRecibe' => [
                    'name'       => 'Departamento',
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => false,
                ],
            ]);
        }

        if ($this->db->fieldExists('NombreRecibe', 'Entregas') && !$this->db->fieldExists('Receptor', 'Entregas')) {
            $this->forge->modifyColumn('Entregas', [
                'NombreRecibe' => [
                    'name'       => 'Receptor',
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                    'null'       => false,
                ],
            ]);
        }
        $this->forge->dropColumn('Entregas', 'NombreEntrega');
    }
}

