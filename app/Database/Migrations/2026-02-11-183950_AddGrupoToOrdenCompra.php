<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGrupoToOrdenCompra extends Migration
{
    public function up()
    {
        // 1. Definir la columna nueva
        $fields = [
            'ID_GrupoPresupuestal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true, // Debe ser igual al ID de la tabla nueva
                'null'       => true, // Permitir NULL inicialmente
            ],
        ];

        // 2. Agregar la columna (Sin 'after' para compatibilidad Postgres)
        $this->forge->addColumn('OrdenCompra', $fields);

        // 3. Agregar la FK con nombre específico para evitar colisiones
        $this->forge->addForeignKey(
            'ID_GrupoPresupuestal',  // Columna en OrdenCompra
            'GrupoPresupuestal',     // Tabla destino
            'ID_GrupoPresupuestal',  // ID destino
            'CASCADE',               // Al actualizar
            'SET NULL',              // Al borrar
            'ordencompra_grupo_fk'   // Nombre único del constraint
        );

        // 4. Procesar índices
        $this->forge->processIndexes('OrdenCompra');
    }

    public function down()
    {
        // Borrar FK primero usando el nombre específico (con try-catch por si no existe)
        try {
            $this->forge->dropForeignKey('OrdenCompra', 'ordencompra_grupo_fk');
        } catch (\Throwable $e) {
            // Ignoramos si no existe
        }

        // Borrar columna después
        $this->forge->dropColumn('OrdenCompra', 'ID_GrupoPresupuestal');
    }
}
