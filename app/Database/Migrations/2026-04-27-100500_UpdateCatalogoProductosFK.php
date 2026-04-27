<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateCatalogoProductosFK extends Migration
{
    public function up()
    {
        // El nombre estándar que genera CI4 es Tabla_Columna_foreign
        $foreignKeyName = 'Catalogo_Productos_ID_Dpto_foreign';

        // 1. Intentar eliminar la FK antigua (que apuntaba a Departamentos)
        // Usamos un bloque try-catch por si el nombre de la FK varía o ya no existe
        try {
            $this->db->query("ALTER TABLE " . $this->db->protectIdentifiers('Catalogo_Productos') . " DROP FOREIGN KEY " . $this->db->protectIdentifiers($foreignKeyName));
        } catch (\Exception $e) {
            // Si falla como FOREIGN KEY (MySQL), intentamos como CONSTRAINT (PostgreSQL)
            try {
                $this->db->query("ALTER TABLE " . $this->db->protectIdentifiers('Catalogo_Productos') . " DROP CONSTRAINT IF EXISTS " . $this->db->protectIdentifiers($foreignKeyName));
            } catch (\Exception $e2) {
                // Ignorar si no se puede eliminar, procedemos a intentar crear la nueva
            }
        }

        // 2. Crear la nueva FK apuntando a UnidadOperativa
        // Forge se encarga de usar backticks (MySQL) o comillas (PostgreSQL) automáticamente
        $this->forge->addForeignKey('ID_Dpto', 'UnidadOperativa', 'ID_UnidadOperativa', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('Catalogo_Productos');
    }

    public function down()
    {
        $foreignKeyName = 'Catalogo_Productos_ID_Dpto_foreign';

        try {
            $this->db->query("ALTER TABLE " . $this->db->protectIdentifiers('Catalogo_Productos') . " DROP FOREIGN KEY " . $this->db->protectIdentifiers($foreignKeyName));
        } catch (\Exception $e) {
            try {
                $this->db->query("ALTER TABLE " . $this->db->protectIdentifiers('Catalogo_Productos') . " DROP CONSTRAINT IF EXISTS " . $this->db->protectIdentifiers($foreignKeyName));
            } catch (\Exception $e2) {}
        }

        // Revertir a Departamentos
        $this->forge->addForeignKey('ID_Dpto', 'Departamentos', 'ID_Dpto', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('Catalogo_Productos');
    }
}
