<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCuentaToSolicitud extends Migration
{
    public function up()
    {
        $field = [
            'type'       => 'BIGINT',
            'constraint' => 20,
            'unsigned'   => true,
            'null'       => true,
        ];
        if ($this->db->DBDriver === 'MySQLi') {
            $field['after'] = 'ID_Proveedor';
        }

        $this->forge->addColumn('Solicitud', [
            'ID_Cuenta' => $field,
        ]);

        // It's possible the FK isn't being created reliably, 
        // but we'll attempt it anyway as it's best practice.
        $this->forge->addForeignKey('ID_Cuenta', 'Cuentas', 'ID_Cuenta', 'CASCADE', 'SET NULL');
    }

    public function down()
    {
        // To avoid issues with unreliable FK names or creation,
        // we will only drop the column. This matches the pattern in
        // other migrations like 2025-12-01-231205_AddUserToSolicitud.php
        $this->forge->dropColumn('Solicitud', 'ID_Cuenta');
    }
}
