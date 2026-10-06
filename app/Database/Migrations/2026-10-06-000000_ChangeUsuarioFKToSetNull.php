<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeUsuarioFKToSetNull extends Migration
{
    /**
     * Migración SEGURA: cambia FK de Usuarios de CASCADE a SET NULL
     * - NO toca datos existentes (solo metadatos)
     * - Hace nullable las columnas necesarias
     * - Descubre nombres reales de FK en runtime
     * - Compatible MySQL y PostgreSQL
     */
    public function up()
    {
        $db = $this->db;
        $driver = $db->DBDriver; // 'MySQLi' o 'Postgre'

        // Tablas objetivo: [tabla => columna_fk]
        $targetTables = [
            'Solicitud'             => 'ID_Usuario',
            'ApiToken'              => 'ID_Usuario',
            'HistorialProductos'    => 'ID_Usuario',
            'SolicitudesCambioPresupuesto' => 'ID_Usuario',
            'UsuariosProductosFavoritos'   => 'id_usuario',
            'EventosCalendario'     => 'ID_Usuario',
            'Entregas'              => 'ID_Usuario',
        ];

        foreach ($targetTables as $table => $column) {
            if (! $db->tableExists($table)) {
                log_message('info', "[ChangeUsuarioFKToSetNull] Tabla '$table' no existe, saltando");
                continue;
            }

            // 1. Hacer columna nullable (seguro: no modifica valores existentes)
            if ($db->fieldExists($column, $table)) {
                $fields = $db->getFieldData($table);
                foreach ($fields as $field) {
                    if ($field->name === $column && ! $field->nullable) {
                        log_message('info', "[ChangeUsuarioFKToSetNull] Haciendo nullable '$table.$column'");
                        $this->forge->modifyColumn($table, [
                            $column => [
                                'type'       => $field->type,
                                'constraint' => $field->constraint,
                                'unsigned'   => $field->unsigned,
                                'null'       => true,
                            ]
                        ]);
                        break;
                    }
                }
            }

            // 2. Encontrar FK real que apunta a Usuarios.ID_Usuario
            $fkName = $this->findForeignKeyName($db, $driver, $table, $column, 'Usuarios', 'ID_Usuario');
            
            if ($fkName) {
                log_message('info', "[ChangeUsuarioFKToSetNull] Cambiando FK '$fkName' en '$table' a SET NULL");
                
                // Eliminar FK antigua
                $this->forge->dropForeignKey($table, $fkName);
                
                // Recrear con SET NULL
                $this->forge->addForeignKey($column, 'Usuarios', 'ID_Usuario', 'CASCADE', 'SET NULL', $fkName);
                $this->forge->processIndexes($table);
            } else {
                log_message('warning', "[ChangeUsuarioFKToSetNull] No se encontró FK en '$table.$column' -> 'Usuarios.ID_Usuario'");
            }
        }
    }

    public function down()
    {
        $db = $this->db;
        $driver = $db->DBDriver;

        $targetTables = [
            'Solicitud'             => 'ID_Usuario',
            'ApiToken'              => 'ID_Usuario',
            'HistorialProductos'    => 'ID_Usuario',
            'SolicitudesCambioPresupuesto' => 'ID_Usuario',
            'UsuariosProductosFavoritos'   => 'id_usuario',
            'EventosCalendario'     => 'ID_Usuario',
            'Entregas'              => 'ID_Usuario',
        ];

        foreach ($targetTables as $table => $column) {
            if (! $db->tableExists($table)) continue;

            // Buscar FK actual (que ahora debería ser SET NULL)
            $fkName = $this->findForeignKeyName($db, $driver, $table, $column, 'Usuarios', 'ID_Usuario');
            
            if ($fkName) {
                log_message('info', "[ChangeUsuarioFKToSetNull:down] Restaurando FK '$fkName' en '$table' a CASCADE");
                $this->forge->dropForeignKey($table, $fkName);
                $this->forge->addForeignKey($column, 'Usuarios', 'ID_Usuario', 'CASCADE', 'CASCADE', $fkName);
                $this->forge->processIndexes($table);
            }
        }
    }

    /**
     * Busca el nombre real de la FK en information_schema
     * Compatible MySQL y PostgreSQL
     */
    private function findForeignKeyName($db, string $driver, string $table, string $column, string $refTable, string $refColumn): ?string
    {
        if ($driver === 'Postgre') {
            // PostgreSQL: information_schema.referential_constraints + key_column_usage
            $sql = "
                SELECT rc.constraint_name
                FROM information_schema.referential_constraints rc
                JOIN information_schema.key_column_usage kcu
                  ON kcu.constraint_name = rc.constraint_name
                 AND kcu.table_schema = rc.constraint_schema
                JOIN information_schema.key_column_usage kcu_ref
                  ON kcu_ref.constraint_name = rc.unique_constraint_name
                 AND kcu_ref.table_schema = rc.unique_constraint_schema
                WHERE kcu.table_name = ?
                  AND kcu.column_name = ?
                  AND kcu_ref.table_name = ?
                  AND kcu_ref.column_name = ?
            ";
            $result = $db->query($sql, [$table, $column, $refTable, $refColumn])->getRow();
            return $result ? $result->constraint_name : null;
        } else {
            // MySQL: information_schema.KEY_COLUMN_USAGE
            $sql = "
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME = ?
                  AND REFERENCED_COLUMN_NAME = ?
            ";
            $result = $db->query($sql, [$table, $column, $refTable, $refColumn])->getRow();
            return $result ? $result->CONSTRAINT_NAME : null;
        }
    }
}