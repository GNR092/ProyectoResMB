<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUniqueFromNombreCorto extends Migration
{
    public function up()
    {
        $driver = strtolower($this->db->DBDriver);

        if (strpos($driver, 'mysql') !== false || strpos($driver, 'mysqli') !== false || strpos($driver, 'postgre') === false) {
            // Para MySQL y MariaDB
            try {
                $this->db->query("ALTER TABLE `Places` DROP INDEX `Nombre_Corto`");
            } catch (\Throwable $e) {
                // Silencioso si no existe
            }
            try {
                $this->db->query("ALTER TABLE `Places` DROP INDEX `Places_Nombre_Corto_key`");
            } catch (\Throwable $e) {
                // Silencioso si no existe
            }
        } else {
            // Para PostgreSQL
            try {
                $this->db->query('ALTER TABLE "Places" DROP CONSTRAINT IF EXISTS "Places_Nombre_Corto_key"');
            } catch (\Throwable $e) {
                // Silencioso si no existe
            }
            try {
                $this->db->query('ALTER TABLE "Places" DROP CONSTRAINT IF EXISTS "places_nombre_corto_key"');
            } catch (\Throwable $e) {
                // Silencioso si no existe
            }
        }
    }

    public function down()
    {
        $driver = strtolower($this->db->DBDriver);

        if (strpos($driver, 'mysql') !== false || strpos($driver, 'mysqli') !== false || strpos($driver, 'postgre') === false) {
            try {
                $this->db->query("ALTER TABLE `Places` ADD UNIQUE KEY `Nombre_Corto` (`Nombre_Corto`)");
            } catch (\Throwable $e) {
                // Silencioso
            }
        } else {
            try {
                $this->db->query('ALTER TABLE "Places" ADD CONSTRAINT "Places_Nombre_Corto_key" UNIQUE ("Nombre_Corto")');
            } catch (\Throwable $e) {
                // Silencioso
            }
        }
    }
}
