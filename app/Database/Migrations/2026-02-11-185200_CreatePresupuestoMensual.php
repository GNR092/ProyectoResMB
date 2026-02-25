<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresupuestoMensual extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'ID_PresupuestoMensual' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ID_Dpto' => [
                'type'       => 'INT',
                'constraint' => 11,
                // 'unsigned'   => true, // Debe coincidir con la PK de Departamentos
                'null'       => true, // Permitimos NULL si un presupuesto puede quedar huérfano temporalmente
            ],
            'Anio' => [
                'type'       => 'INT',
                'constraint' => 4, // Año de 4 dígitos (ej. 2025)
            ],
            'Mes' => [
                'type'       => 'INT',
                'constraint' => 2, // Mes numérico (1-12)
            ],
            'Monto_Asignado' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2', // 15 dígitos en total, 2 decimales
                'default'    => 0.00,
            ],
            'Monto_Comprometido' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
            'Monto_Ejecutado' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0.00,
            ],
        ]);

        // Llave Primaria
        $this->forge->addKey('ID_PresupuestoMensual', true);

        // Llave Foránea
        $this->forge->addForeignKey(
            'ID_Dpto',           // Columna actual
            'Departamentos',     // Tabla padre
            'ID_Dpto',           // Columna padre
            'CASCADE',           // On Update
            'RESTRICT',          // On Delete (No permite borrar un depto si tiene presupuesto asignado)
            'presupuesto_dpto_fk' // Nombre único para Postgres
        );

        $this->forge->createTable('PresupuestoMensual');
    }

    public function down()
    {
        $this->forge->dropTable('PresupuestoMensual');
    }
}