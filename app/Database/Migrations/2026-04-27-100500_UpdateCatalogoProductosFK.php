<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateCatalogoProductosFK extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Usar SQL directo para asegurar el cambio en PostgreSQL
        // 1. Eliminar la FK vieja si existe
        $db->query('ALTER TABLE "Catalogo_Productos" DROP CONSTRAINT IF EXISTS "Catalogo_Productos_ID_Dpto_foreign"');
        
        // 2. Crear la nueva FK apuntando a UnidadOperativa
        $db->query('ALTER TABLE "Catalogo_Productos" ADD CONSTRAINT "Catalogo_Productos_ID_Dpto_foreign" FOREIGN KEY ("ID_Dpto") REFERENCES "UnidadOperativa" ("ID_UnidadOperativa") ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Revertir a Departamentos
        $db->query('ALTER TABLE "Catalogo_Productos" DROP CONSTRAINT IF EXISTS "Catalogo_Productos_ID_Dpto_foreign"');
        $db->query('ALTER TABLE "Catalogo_Productos" ADD CONSTRAINT "Catalogo_Productos_ID_Dpto_foreign" FOREIGN KEY ("ID_Dpto") REFERENCES "Departamentos" ("ID_Dpto") ON DELETE SET NULL ON UPDATE CASCADE');
    }
}
