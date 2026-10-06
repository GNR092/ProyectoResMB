<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeUsuarioFKToSetNull extends Migration
{
    // Tablas y columnas a modificar: [tabla => columna]
    private $targetTables = [
        'Solicitud'             => 'ID_Usuario',
        'ApiToken'              => 'ID_Usuario',
        'HistorialProductos'    => 'ID_Usuario',
        'SolicitudesCambioPresupuesto' => 'ID_Usuario',
        'UsuariosProductosFavoritos'   => 'id_usuario',
        'EventosCalendario'     => 'ID_Usuario',
        'Entregas'              => 'ID_Usuario',
    ];

    public function up()
    {
        $driver = $this->db->DBDriver; // 'Postgre' o 'MySQLi'

        foreach ($this->targetTables as $table => $column) {
            if (! $this->db->tableExists($table)) {
                continue; // Tabla no existe en esta instalación
            }

            if (! $this->db->fieldExists($column, $table)) {
                continue; // Columna no existe
            }

            // 1. Hacer columna NULLABLE (compatible ambos drivers)
            $this->makeColumnNullable($table, $column, $driver);

            // 2. Obtener nombre real de FK existente (compatibilidad MySQL/PostgreSQL)
            $fkName = $this->findForeignKeyName($table, $column, 'Usuarios', 'ID_Usuario');
            
            if ($fkName) {
                // 3. Dropear FK antigua
                $this->dropForeignKey($table, $fkName, $driver);

                // 4. Crear FK nueva con SET NULL
                $this->createForeignKey($table, $column, $fkName, $driver);
            }
        }
    }

    public function down()
    {
        $driver = $this->db->DBDriver;

        foreach ($this->targetTables as $table => $column) {
            if (! $this->db->tableExists($table)) continue;

            $fkName = $this->findForeignKeyName($table, $column, 'Usuarios', 'ID_Usuario');
            
            if ($fkName) {
                $this->dropForeignKey($table, $fkName, $driver);
                $this->createForeignKey($table, $column, $fkName, $driver, 'CASCADE');
            }
        }
    }

    /**
     * Hace una columna nullable - compatible PostgreSQL y MySQL
     */
    private function makeColumnNullable(string $table, string $column, string $driver): void
    {
        $fields = $this->db->getFieldData($table);
        foreach ($fields as $field) {
            if ($field->name === $column && ! $field->nullable) {
                if ($driver === 'Postgre') {
                    $this->db->query("ALTER TABLE \"{$table}\" ALTER COLUMN \"{$column}\" DROP NOT NULL");
                } else {
                    // MySQL: usar Forge para mantener tipo exacto
                    $this->forge->modifyColumn($table, [
                        $column => [
                            'type'       => $field->type,
                            'constraint' => $field->constraint,
                            'unsigned'   => $field->unsigned,
                            'null'       => true,
                        ]
                    ]);
                }
                break;
            }
        }
    }

    /**
     * Busca el nombre real de la FK en information_schema
     */
    private function findForeignKeyName(string $table, string $column, string $refTable, string $refColumn): ?string
    {
        $driver = $this->db->DBDriver;

        if ($driver === 'Postgre') {
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
            $result = $this->db->query($sql, [$table, $column, $refTable, $refColumn])->getRow();
            return $result ? ($result->constraint_name ?? $result->CONSTRAINT_NAME ?? null) : null;
        } else {
            // MySQL
            $sql = "
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                  AND REFERENCED_TABLE_NAME = ?
                  AND REFERENCED_COLUMN_NAME = ?
            ";
            $result = $this->db->query($sql, [$table, $column, $refTable, $refColumn])->getRow();
            return $result ? ($result->CONSTRAINT_NAME ?? $result->constraint_name ?? null) : null;
        }
    }

    /**
     * Elimina una FK - compatible ambos drivers
     */
    private function dropForeignKey(string $table, string $fkName, string $driver): void
    {
        if ($driver === 'Postgre') {
            $this->db->query("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$fkName}\"");
        } else {
            $this->forge->dropForeignKey($table, $fkName);
        }
    }

    /**
     * Crea una FK con ON DELETE SET NULL (o CASCADE en down())
     */
    private function createForeignKey(
        string $table,
        string $column,
        string $fkName,
        string $driver,
        string $onDelete = 'SET NULL'
    ): void {
        if ($driver === 'Postgre') {
            $this->db->query(
                "ALTER TABLE \"{$table}\" ADD CONSTRAINT \"{$fkName}\" " .
                "FOREIGN KEY (\"{$column}\") REFERENCES \"Usuarios\" (\"ID_Usuario\") " .
                "ON DELETE {$onDelete} ON UPDATE CASCADE"
            );
        } else {
            // MySQL: Forge maneja comillas backticks automáticamente
            $this->forge->addForeignKey($column, 'Usuarios', 'ID_Usuario', 'CASCADE', $onDelete, $fkName);
            $this->forge->processIndexes($table);
        }
    }
}