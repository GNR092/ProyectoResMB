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
        } /* else {
            // Ensure it is unsigned even if it exists
            $fieldsToModify['ID_GrupoPresupuestal'] = [
                'name'       => 'ID_GrupoPresupuestal',
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ];
        } */

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

        // Check if constraint exists before adding (using a more direct PG check)
        $checkConstraint = $this->db->query("SELECT 1 FROM pg_constraint WHERE conname = 'fk_solicitud_servicios_grupo'")->getRow();

        if (!$checkConstraint) {
            try {
                $this->db->query('ALTER TABLE "Solicitud_Servicios" ADD CONSTRAINT fk_solicitud_servicios_grupo FOREIGN KEY ("ID_GrupoPresupuestal") REFERENCES "GrupoPresupuestal"("ID_GrupoPresupuestal") ON DELETE SET NULL ON UPDATE CASCADE');
            } catch (\Exception $e) {
                // Ignore if it fails (likely already exists despite the check)
            }
        }
    }

    public function down()
    {
        // Drop foreign key first if it exists
        $checkConstraint = $this->db->query("SELECT 1 FROM pg_constraint WHERE conname = 'fk_solicitud_servicios_grupo'")->getRow();

        if ($checkConstraint) {
            try {
                $this->db->query('ALTER TABLE "Solicitud_Servicios" DROP CONSTRAINT fk_solicitud_servicios_grupo');
            } catch (\Exception $e) {}
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
