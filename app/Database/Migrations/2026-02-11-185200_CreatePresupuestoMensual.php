<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePresupuestoMensual extends Migration
{
    public function up()
    {
        // 1. Conectamos a la BD
        $db = \Config\Database::connect();

        // 2. Preguntamos CÓMO está creada la tabla padre
        // (Esto es solo lectura, no modifica nada)
        $query = $db->query("SHOW CREATE TABLE Departamentos");
        $result = $query->getRowArray();

        // 3. Mostramos el resultado en la consola
        echo "\n\n================ EL SECRETO DE LA TABLA PADRE ================\n";
        print_r($result);
        echo "\n==============================================================\n\n";

        // 4. IMPORTANTE: Detenemos todo aquí para no hacer cambios
        die("Diagnóstico terminado. Copia lo que salió arriba y pásamelo.");
    }

    public function down()
    {
        // Nada
    }
}