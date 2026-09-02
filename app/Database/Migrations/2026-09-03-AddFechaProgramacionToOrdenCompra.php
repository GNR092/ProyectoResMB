<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaProgramacionToOrdenCompra extends Migration
{
    public function up()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        // PASO 1: Agregar columna FechaProgramacion (DATETIME NULL) idempotente.
        if ($driver === 'Postgre') {
            $db->query('ALTER TABLE "OrdenCompra" ADD COLUMN IF NOT EXISTS "FechaProgramacion" TIMESTAMP NULL');
        } else {
            $exists = $db->query(
                "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'OrdenCompra' AND COLUMN_NAME = 'FechaProgramacion'"
            )->getRow('total');
            if (!$exists) {
                $db->query('ALTER TABLE `OrdenCompra` ADD COLUMN `FechaProgramacion` DATETIME NULL AFTER `Fecha`');
            }
        }

        // PASO 2: No se copia Fecha automáticamente - son conceptos distintos (creación vs programación).
        // Se deja NULL para filas antiguas; mostrará N/A en frontend.

        // PASO 3: Índice para búsquedas por fecha de programación.
        if ($driver === 'Postgre') {
            $db->query('CREATE INDEX IF NOT EXISTS idx_ordcompra_fecha_programacion ON "OrdenCompra" ("FechaProgramacion")');
        } else {
            // MySQL no soporta IF NOT EXISTS en CREATE INDEX, verificar manualmente
            $indexExists = $db->query(
                "SELECT COUNT(*) AS total FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'OrdenCompra' AND INDEX_NAME = 'idx_ordcompra_fecha_programacion'"
            )->getRow('total');
            if (!$indexExists) {
                $db->query('CREATE INDEX idx_ordcompra_fecha_programacion ON `OrdenCompra` (`FechaProgramacion`)');
            }
        }
    }

    public function down()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        if ($driver === 'Postgre') {
            $db->query('ALTER TABLE "OrdenCompra" DROP COLUMN IF EXISTS "FechaProgramacion"');
        } else {
            $db->query('ALTER TABLE `OrdenCompra` DROP COLUMN `FechaProgramacion`');
        }
    }
}
