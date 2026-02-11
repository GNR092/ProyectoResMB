<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresupuestoAnual extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_PresupuestoAnual' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_RazonSocial' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Debe coincidir con la PK de Razon_Social
            ],
            'Anio' => [
                'type'       => 'INT',
                'constraint' => 4, // Año de 4 dígitos (ej. 2026)
            ],
            'Monto' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2', // 15 dígitos totales, 2 decimales
                'default'    => 0.00,
            ],
        ]);

        // Llave Primaria
        $this->forge->addKey('ID_PresupuestoAnual', true);

        // Llave Foránea
        $this->forge->addForeignKey(
            'ID_RazonSocial',      // Columna actual
            'Razon_Social',        // Tabla padre
            'ID_RazonSocial',      // Columna padre
            'CASCADE',             // On Update
            'RESTRICT',            // On Delete (Evita borrar una Razón Social si tiene historial de presupuestos)
            'presupuesto_rs_fk'    // Nombre único para evitar conflicto en Postgres
        );

        $this->forge->createTable('PresupuestoAnual');
    }

    public function down()
    {
        $this->forge->dropTable('PresupuestoAnual');
    }
}