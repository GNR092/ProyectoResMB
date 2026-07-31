<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaComprobanteToOrdenCompra extends Migration
{
    public function up()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        // PASO 1: Agregar la columna (idempotente para producción).
        if ($driver === 'Postgre') {
            // PostgreSQL soporta ADD COLUMN IF NOT EXISTS
            $db->query('ALTER TABLE "OrdenCompra" ADD COLUMN IF NOT EXISTS "Fecha_Comprobante" DATE NULL');
        } else {
            // MySQL: verificar existencia primero
            $exists = $db->query(
                "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'OrdenCompra' AND COLUMN_NAME = 'Fecha_Comprobante'"
            )->getRow('total');
            if (!$exists) {
                $db->query('ALTER TABLE `OrdenCompra` ADD COLUMN `Fecha_Comprobante` DATE NULL AFTER `FechaPagoRealizado`');
            }
        }

        // PASO 2: Copiar los valores de FechaPagoRealizado existentes para no dejar fechas vacías (requisito de producción).
        // No modifica ni borra datos existentes: solo rellena la nueva columna donde ya hay FechaPagoRealizado.
        if ($driver === 'Postgre') {
            $db->query('UPDATE "OrdenCompra" SET "Fecha_Comprobante" = "FechaPagoRealizado"::date WHERE "Fecha_Comprobante" IS NULL AND "FechaPagoRealizado" IS NOT NULL');
        } else {
            $db->query('UPDATE `OrdenCompra` SET `Fecha_Comprobante` = DATE(`FechaPagoRealizado`) WHERE `Fecha_Comprobante` IS NULL AND `FechaPagoRealizado` IS NOT NULL');
        }

        // PASO 3: Índice para búsquedas por fecha de comprobante.
        if ($driver === 'Postgre') {
            $db->query('CREATE INDEX IF NOT EXISTS idx_ordcompra_fecha_comprobante ON "OrdenCompra" ("Fecha_Comprobante")');
        } else {
            $db->query('CREATE INDEX idx_ordcompra_fecha_comprobante ON `OrdenCompra` (`Fecha_Comprobante`)');
        }
    }

    public function down()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        if ($driver === 'Postgre') {
            $db->query('ALTER TABLE "OrdenCompra" DROP COLUMN IF EXISTS "Fecha_Comprobante"');
        } else {
            $db->query('ALTER TABLE `OrdenCompra` DROP COLUMN `Fecha_Comprobante`');
        }
    }
}
