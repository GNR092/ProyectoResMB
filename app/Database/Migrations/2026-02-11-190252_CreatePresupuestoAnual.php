<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresupuestoAnual extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------
        // PASO 1: ARREGLAR LA TABLA PADRE (Razon_Social)
        // ---------------------------------------------------------
        // Convertimos 'Razon_Social' a InnoDB para permitir llaves foráneas.
        // Verificamos si es MySQL para no afectar tu entorno local si usas otro driver.
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query("ALTER TABLE Razon_Social ENGINE = InnoDB");
        }

        // ---------------------------------------------------------
        // PASO 2: DEFINIR LA NUEVA TABLA
        // ---------------------------------------------------------
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
                'constraint' => 4,
            ],
            'Monto' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
        ]);

        // Llave Primaria
        $this->forge->addKey('ID_PresupuestoAnual', true);

        // Llave Foránea
        $this->forge->addForeignKey(
            'ID_RazonSocial',          // Columna actual
            'Razon_Social',            // Tabla padre
            'ID_RazonSocial',          // Columna padre
            'CASCADE',                 // On Update
            'RESTRICT',                // On Delete
            'fk_presupuesto_rs_innodb' // Nombre único nuevo para evitar basura de intentos previos
        );

        $this->forge->createTable('PresupuestoAnual');
    }

    public function down()
    {
        $this->forge->dropTable('PresupuestoAnual');
    }
}