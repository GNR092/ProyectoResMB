<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUniqueFromNombreCorto extends Migration
{
    public function up()
    {
        // En PostgreSQL, el constraint unique puede llamarse "Places_Nombre_Corto_key" o "places_nombre_corto_key"
        $this->db->query('ALTER TABLE "Places" DROP CONSTRAINT IF EXISTS "Places_Nombre_Corto_key"');
        $this->db->query('ALTER TABLE "Places" DROP CONSTRAINT IF EXISTS "places_nombre_corto_key"');
    }

    public function down()
    {
        try {
            $this->db->query('ALTER TABLE "Places" ADD CONSTRAINT "Places_Nombre_Corto_key" UNIQUE ("Nombre_Corto")');
        } catch (\Throwable $e) {
            // Silencioso en caso de que ya existan duplicados y no se pueda revertir
        }
    }
}
