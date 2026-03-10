<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropTableDetalleProducto extends Migration
{
    public function up()
    {
        // Eliminamos la tabla. Forge se encarga de las restricciones de FK en la mayoría de los casos,
        // pero usamos IF EXISTS por seguridad.
        $this->db->query('DROP TABLE IF EXISTS "Detalle_Producto" CASCADE');
    }

    public function down()
    {
        // En una migración de borrado de tabla obsoleta, el down suele quedar vacío 
        // a menos que se desee reconstruir la estructura exacta antigua.
    }
}

