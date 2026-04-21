<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateProveedorConstraints extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        if ($this->db->DBDriver === 'Postgre') {
            // 1. Eliminar restricciones UNIQUE de RFC y Correo
            $db->query('ALTER TABLE "Proveedor" DROP CONSTRAINT IF EXISTS "Proveedor_RFC_key"');
            $db->query('ALTER TABLE "Proveedor" DROP CONSTRAINT IF EXISTS "Proveedor_Correo_key"');
            
            // 2. Agregar UNIQUE a RazonSocial si no existe
            // Usamos un bloque anónimo de DO para verificar existencia antes de crear en Postgres
            $sql = "DO $$ 
                    BEGIN 
                        IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'proveedor_razonsocial_unique') THEN 
                            ALTER TABLE \"Proveedor\" ADD CONSTRAINT \"proveedor_razonsocial_unique\" UNIQUE (\"RazonSocial\"); 
                        END IF; 
                    END $$;";
            $db->query($sql);
        } else {
            // MySQL/MariaDB
            $db->query('ALTER TABLE Proveedor DROP INDEX IF EXISTS RFC');
            $db->query('ALTER TABLE Proveedor DROP INDEX IF EXISTS Correo');
            
            // Verificamos si ya existe el índice único antes de agregarlo
            $exists = $db->query("SHOW INDEX FROM Proveedor WHERE Key_name = 'RazonSocial'")->getResult();
            if (empty($exists)) {
                $db->query('ALTER TABLE Proveedor ADD UNIQUE (RazonSocial)');
            }
        }
    }

    public function down()
    {
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query('ALTER TABLE "Proveedor" DROP CONSTRAINT IF EXISTS "proveedor_razonsocial_unique"');
            $this->db->query('DO $$
            DECLARE
                dup_count INTEGER;
            BEGIN
                SELECT COUNT(*) INTO dup_count FROM (SELECT "RFC" FROM "Proveedor" GROUP BY "RFC" HAVING COUNT(*) > 1) AS dup;
                IF dup_count = 0 THEN
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'Proveedor_RFC_key\') THEN
                        ALTER TABLE "Proveedor" ADD CONSTRAINT "Proveedor_RFC_key" UNIQUE ("RFC");
                    END IF;
                END IF;
            END $$;');
            $this->db->query('DO $$
            DECLARE
                dup_count INTEGER;
            BEGIN
                SELECT COUNT(*) INTO dup_count FROM (SELECT "Correo" FROM "Proveedor" GROUP BY "Correo" HAVING COUNT(*) > 1) AS dup;
                IF dup_count = 0 THEN
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'Proveedor_Correo_key\') THEN
                        ALTER TABLE "Proveedor" ADD CONSTRAINT "Proveedor_Correo_key" UNIQUE ("Correo");
                    END IF;
                END IF;
            END $$;');
        } else {
            $this->db->query('ALTER TABLE Proveedor DROP INDEX IF EXISTS RazonSocial');
            $this->db->query('ALTER TABLE Proveedor ADD UNIQUE (RFC)');
            $this->db->query('ALTER TABLE Proveedor ADD UNIQUE (Correo)');
        }
    }
}
