<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropTableDetalleProducto extends Migration
{
    public function up()
    {
        $this->forge->dropTable('Detalle_Producto', true);
    }

    public function down()
    {
        // En una migración de borrado de tabla obsoleta, el down suele quedar vacío 
        // a menos que se desee reconstruir la estructura exacta antigua.
    }
}
