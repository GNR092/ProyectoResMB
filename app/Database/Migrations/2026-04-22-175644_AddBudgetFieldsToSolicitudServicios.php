<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBudgetFieldsToSolicitudServicios extends Migration
{
    public function up()
    {
        $fields = [
            'ID_GrupoPresupuestal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'ID_Solicitud'
            ],
            'Monto_Comprometido_Original' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'default'    => '0.00',
                'after'      => 'Importe'
            ],
        ];
        $this->forge->addColumn('Solicitud_Servicios', $fields);

        // Add foreign key using raw query for better compatibility/control if needed, 
        // but forge is preferred if possible.
        $this->db->query('ALTER TABLE Solicitud_Servicios ADD CONSTRAINT fk_solicitud_servicios_grupo FOREIGN KEY (ID_GrupoPresupuestal) REFERENCES GrupoPresupuestal(ID_GrupoPresupuestal) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // Drop foreign key first
        // Note: constraint name might vary by DB engine if not specified, but we specified it.
        $this->db->query('ALTER TABLE Solicitud_Servicios DROP CONSTRAINT fk_solicitud_servicios_grupo');
        $this->forge->dropColumn('Solicitud_Servicios', ['ID_GrupoPresupuestal', 'Monto_Comprometido_Original']);
    }
}
