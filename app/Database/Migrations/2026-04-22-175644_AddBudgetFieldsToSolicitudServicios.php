<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBudgetFieldsToSolicitudServicios extends Migration
{
    public function up()
    {
        $fieldsToAdd = [];
        $fieldsToModify = [];
        
        if (!$this->db->fieldExists('ID_GrupoPresupuestal', 'Solicitud_Servicios')) {
            $fieldsToAdd['ID_GrupoPresupuestal'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'ID_Solicitud'
            ];
        } else {
            // Ensure it is unsigned even if it exists
            $fieldsToModify['ID_GrupoPresupuestal'] = [
                'name'       => 'ID_GrupoPresupuestal',
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ];
        }

        if (!$this->db->fieldExists('Monto_Comprometido_Original', 'Solicitud_Servicios')) {
            $fieldsToAdd['Monto_Comprometido_Original'] = [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
                'default'    => '0.00',
                'after'      => 'Importe'
            ];
        }

        if (!empty($fieldsToAdd)) {
            $this->forge->addColumn('Solicitud_Servicios', $fieldsToAdd);
        }

        if (!empty($fieldsToModify)) {
            $this->forge->modifyColumn('Solicitud_Servicios', $fieldsToModify);
        }

        // Check if constraint exists before adding
        $checkConstraint = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'Solicitud_Servicios' AND TABLE_SCHEMA = '{$this->db->database}' AND CONSTRAINT_NAME = 'fk_solicitud_servicios_grupo'")->getResult();

        if (empty($checkConstraint)) {
            $this->db->query('ALTER TABLE Solicitud_Servicios ADD CONSTRAINT fk_solicitud_servicios_grupo FOREIGN KEY (ID_GrupoPresupuestal) REFERENCES GrupoPresupuestal(ID_GrupoPresupuestal) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }

    public function down()
    {
        // Drop foreign key first if it exists
        $checkConstraint = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'Solicitud_Servicios' AND TABLE_SCHEMA = '{$this->db->database}' AND CONSTRAINT_NAME = 'fk_solicitud_servicios_grupo'")->getResult();

        if (!empty($checkConstraint)) {
            $this->db->query('ALTER TABLE Solicitud_Servicios DROP CONSTRAINT fk_solicitud_servicios_grupo');
        }

        $fieldsToDrop = [];
        if ($this->db->fieldExists('ID_GrupoPresupuestal', 'Solicitud_Servicios')) {
            $fieldsToDrop[] = 'ID_GrupoPresupuestal';
        }
        if ($this->db->fieldExists('Monto_Comprometido_Original', 'Solicitud_Servicios')) {
            $fieldsToDrop[] = 'Monto_Comprometido_Original';
        }

        if (!empty($fieldsToDrop)) {
            $this->forge->dropColumn('Solicitud_Servicios', $fieldsToDrop);
        }
    }
}
