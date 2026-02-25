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
                /**
                 * CORRECCIÓN DE COMPATIBILIDAD POSTGRES/MYSQL:
                 * - MySQL: Requiere 'unsigned' => true para coincidir con la tabla padre.
                 * - Postgres: Ignorará esta instrucción o la manejará sin romper nada.
                 * - Es vital dejarlo en TRUE para que funcione en tu servidor.
                 */
                'unsigned'   => true,
                'null'       => true,
            ],
            'Anio' => [
                'type'       => 'INT',
                'constraint' => 4,
            ],
            'Mes' => [
                'type'       => 'INT',
                'constraint' => 2,
            ],
            'Monto_Asignado' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
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
            'ID_Dpto',               // Columna en esta tabla
            'Departamentos',         // Tabla padre (Confirmado que en MySQL es con mayúscula)
            'ID_Dpto',               // Columna padre
            'CASCADE',               // ON UPDATE
            'RESTRICT',              // ON DELETE
            'fk_presupuesto_dpto_v3' // <--- Usamos un nombre único para evitar basura anterior en MySQL
        );

        $this->forge->createTable('PresupuestoMensual');
    }

    public function down()
    {
        $this->forge->dropTable('PresupuestoMensual');
    }
}