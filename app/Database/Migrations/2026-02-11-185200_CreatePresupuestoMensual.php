<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresupuestoMensual extends Migration
{
    public function up()
    {
        // 1. Modernizamos la tabla padre a InnoDB (Seguro y necesario)
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query("ALTER TABLE Departamentos ENGINE = InnoDB");
        }

        // 2. Definimos la nueva tabla
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
                'unsigned'   => true, // Coincide con Departamentos
                'null'       => true,
            ],
            'Anio' => ['type' => 'INT', 'constraint' => 4],
            'Mes'  => ['type' => 'INT', 'constraint' => 2],
            'Monto_Asignado'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'Monto_Comprometido' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
            'Monto_Ejecutado'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0.00],
        ]);

        $this->forge->addKey('ID_PresupuestoMensual', true);

        // 3. Creamos la llave foránea (Ahora sí funcionará)
        $this->forge->addForeignKey(
            'ID_Dpto',
            'Departamentos',
            'ID_Dpto',
            'CASCADE',
            'RESTRICT',
            'fk_presupuesto_dpto_innodb'
        );

        $this->forge->createTable('PresupuestoMensual');
    }

    public function down()
    {
        $this->forge->dropTable('PresupuestoMensual', true);
    }
}
