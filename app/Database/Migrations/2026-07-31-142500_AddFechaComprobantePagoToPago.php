<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaComprobantePagoToPago extends Migration
{
    public function up()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        // PASO 1: Agregar la columna (idempotente para producción).
        if ($driver === 'Postgre') {
            // PostgreSQL soporta ADD COLUMN IF NOT EXISTS
            $db->query('ALTER TABLE "Pago" ADD COLUMN IF NOT EXISTS "Fecha_Comprobante" DATE NULL');
        } else {
            // MySQL: verificar existencia primero
            $exists = $db->query(
                "SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Pago' AND COLUMN_NAME = 'Fecha_Comprobante'"
            )->getRow('total');
            if (!$exists) {
                $db->query('ALTER TABLE `Pago` ADD COLUMN `Fecha_Comprobante` DATE NULL AFTER `Fecha_Pago`');
            }
        }

        // PASO 2: Copiar los valores de Fecha_Pago existentes para no dejar fechas vacías (requisito de producción).
        // No modifica ni borra datos existentes: solo rellena la nueva columna donde ya hay Fecha_Pago.
        if ($driver === 'Postgre') {
            $db->query('UPDATE "Pago" SET "Fecha_Comprobante" = "Fecha_Pago" WHERE "Fecha_Comprobante" IS NULL AND "Fecha_Pago" IS NOT NULL');
        } else {
            $db->query('UPDATE `Pago` SET `Fecha_Comprobante` = `Fecha_Pago` WHERE `Fecha_Comprobante` IS NULL AND `Fecha_Pago` IS NOT NULL');
        }

        // PASO 3: Índice para búsquedas por fecha de comprobante.
        if ($driver === 'Postgre') {
            $db->query('CREATE INDEX IF NOT EXISTS idx_pago_fecha_comprobante ON "Pago" ("Fecha_Comprobante")');
        } else {
            $db->query('CREATE INDEX idx_pago_fecha_comprobante ON `Pago` (`Fecha_Comprobante`)');
        }
    }

    public function down()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        if ($driver === 'Postgre') {
            $db->query('ALTER TABLE "Pago" DROP COLUMN IF EXISTS "Fecha_Comprobante"');
        } else {
            $db->query('ALTER TABLE `Pago` DROP COLUMN `Fecha_Comprobante`');
        }
    }
}
