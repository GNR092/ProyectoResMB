<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdDptoToGrupoPresupuestal extends Migration
{
    public function up()
    {
        $fields = [
            'ID_Dpto' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // Permitimos NULL por si hay registros existentes
                'after'      => 'Descripcion', // Opcional: Para ordenar la columna visualmente
            ],
        ];

        // 1. Agregamos la columna a la tabla 'GrupoPresupuestal'
        $this->forge->addColumn('GrupoPresupuestal', $fields);

        // 2. Definimos la llave foránea
        // Estructura: addForeignKey(columna_local, tabla_referencia, columna_referencia, onUpdate, onDelete, nombre_fk)
        $this->forge->addForeignKey(
            'ID_Dpto',           // Columna en GrupoPresupuestal
            'Departamentos',     // Tabla externa
            'ID_Dpto',           // Columna en Departamentos
            'CASCADE',           // Si cambia el ID del dpto, se actualiza aquí
            'SET NULL',          // Si se borra el dpto, aquí se pone NULL (más seguro que borrar el grupo)
            'grupopresupuestal_id_dpto_fk' // Nombre clave para la restricción
        );

        // 3. Procesamos los índices
        $this->forge->processIndexes('GrupoPresupuestal');
    }

    public function down()
    {
        // IMPORTANTE: Primero borrar la FK, luego la columna
        try { $this->forge->dropForeignKey('GrupoPresupuestal', 'grupopresupuestal_id_dpto_fk'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('GrupoPresupuestal', 'ID_Dpto');
    }
}
