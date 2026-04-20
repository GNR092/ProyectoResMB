<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertPresupuestoMensualData extends Migration
{
    public function up()
    {
        $data = [
            ['ID_PresupuestoMensual' => 7, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 114, 'ID_UnidadOperativa' => 13, ],
            ['ID_PresupuestoMensual' => 8, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 141, 'ID_UnidadOperativa' => 9, ],
            ['ID_PresupuestoMensual' => 9, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 152, 'ID_UnidadOperativa' => 12, ],
            ['ID_PresupuestoMensual' => 10, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 143, 'ID_UnidadOperativa' => 10, ],
            ['ID_PresupuestoMensual' => 11, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 137, 'ID_UnidadOperativa' => 1, ],
            ['ID_PresupuestoMensual' => 12, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 145, 'ID_UnidadOperativa' => 11, ],
            ['ID_PresupuestoMensual' => 22, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1760.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 62, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 94, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 172, 'ID_UnidadOperativa' => 18, ],
            ['ID_PresupuestoMensual' => 2, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 97, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 32, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2610.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 103, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 33, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '400000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 95, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 34, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 85, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 35, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '30000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 96, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 36, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '27564.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 93, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 37, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '23855.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 87, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 38, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '32701.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 94, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 39, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '126637.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 90, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 40, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '39605.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 92, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 41, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '3500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 106, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 42, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 86, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 13, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 52, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 61, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '40000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 18, 'ID_UnidadOperativa' => 5, ],
            ['ID_PresupuestoMensual' => 65, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '3000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 24, 'ID_UnidadOperativa' => 5, ],
            ['ID_PresupuestoMensual' => 66, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '25000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 25, 'ID_UnidadOperativa' => 5, ],
            ['ID_PresupuestoMensual' => 67, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '5000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 26, 'ID_UnidadOperativa' => 5, ],
            ['ID_PresupuestoMensual' => 6, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 44, 'ID_UnidadOperativa' => 7, ],
            ['ID_PresupuestoMensual' => 14, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '15400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 61, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 43, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '30000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 78, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 44, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 83, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 45, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '15672.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 98, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 46, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '160000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 105, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 47, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1254.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 82, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 48, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '4000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 100, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 49, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 104, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 50, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '64887.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 91, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 51, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 102, 'ID_UnidadOperativa' => 8, ],
            ['ID_PresupuestoMensual' => 52, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '23200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 153, 'ID_UnidadOperativa' => 13, ],
            ['ID_PresupuestoMensual' => 53, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 172, 'ID_UnidadOperativa' => 13, ],
            ['ID_PresupuestoMensual' => 54, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '150.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 168, 'ID_UnidadOperativa' => 13, ],
            ['ID_PresupuestoMensual' => 55, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 162, 'ID_UnidadOperativa' => 13, ],
            ['ID_PresupuestoMensual' => 56, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 140, 'ID_UnidadOperativa' => 9, ],
            ['ID_PresupuestoMensual' => 57, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 139, 'ID_UnidadOperativa' => 9, ],
            ['ID_PresupuestoMensual' => 58, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '3000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 138, 'ID_UnidadOperativa' => 9, ],
            ['ID_PresupuestoMensual' => 59, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '34800.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 157, 'ID_UnidadOperativa' => 12, ],
            ['ID_PresupuestoMensual' => 60, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 170, 'ID_UnidadOperativa' => 12, ],
            ['ID_PresupuestoMensual' => 1, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '43000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 76, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 62, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 158, 'ID_UnidadOperativa' => 12, ],
            ['ID_PresupuestoMensual' => 63, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1902.40', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 164, 'ID_UnidadOperativa' => 12, ],
            ['ID_PresupuestoMensual' => 64, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 175, 'ID_UnidadOperativa' => 12, ],
            ['ID_PresupuestoMensual' => 68, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 208, 'ID_UnidadOperativa' => 40, ],
            ['ID_PresupuestoMensual' => 69, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '600.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 200, 'ID_UnidadOperativa' => 40, ],
            ['ID_PresupuestoMensual' => 70, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '4100.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 195, 'ID_UnidadOperativa' => 40, ],
            ['ID_PresupuestoMensual' => 71, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '15000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 187, 'ID_UnidadOperativa' => 43, ],
            ['ID_PresupuestoMensual' => 72, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '19000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 191, 'ID_UnidadOperativa' => 43, ],
            ['ID_PresupuestoMensual' => 73, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '57328.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 206, 'ID_UnidadOperativa' => 39, ],
            ['ID_PresupuestoMensual' => 74, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '27599.90', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 216, 'ID_UnidadOperativa' => 39, ],
            ['ID_PresupuestoMensual' => 75, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '600.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 219, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 76, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '700.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 284, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 77, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '800.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 234, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 79, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 249, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 80, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 224, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 81, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '850.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 239, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 82, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 244, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 83, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '6042.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 53, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 84, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '17000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 51, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 85, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 48, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 86, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 49, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 21, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 60, 'ID_UnidadOperativa' => 2, ],
            ['ID_PresupuestoMensual' => 23, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 64, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 24, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '560.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 65, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 25, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '25000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 77, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 87, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '100000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 71, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 26, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '8922.80', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 72, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 88, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '61024.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 75, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 27, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 70, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 89, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '15000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 69, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 28, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 68, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 90, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '3000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 67, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 29, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 66, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 30, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '0.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 73, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 31, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 74, 'ID_UnidadOperativa' => 3, ],
            ['ID_PresupuestoMensual' => 91, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 177, 'ID_UnidadOperativa' => 18, ],
            ['ID_PresupuestoMensual' => 92, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '150.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 168, 'ID_UnidadOperativa' => 18, ],
            ['ID_PresupuestoMensual' => 93, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '19000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 153, 'ID_UnidadOperativa' => 18, ],
            ['ID_PresupuestoMensual' => 95, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 162, 'ID_UnidadOperativa' => 18, ],
            ['ID_PresupuestoMensual' => 96, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '556.80', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 167, 'ID_UnidadOperativa' => 18, ],
            ['ID_PresupuestoMensual' => 97, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 210, 'ID_UnidadOperativa' => 16, ],
            ['ID_PresupuestoMensual' => 98, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '100.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 202, 'ID_UnidadOperativa' => 16, ],
            ['ID_PresupuestoMensual' => 99, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 193, 'ID_UnidadOperativa' => 16, ],
            ['ID_PresupuestoMensual' => 100, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '6500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 186, 'ID_UnidadOperativa' => 17, ],
            ['ID_PresupuestoMensual' => 101, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '6000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 190, 'ID_UnidadOperativa' => 17, ],
            ['ID_PresupuestoMensual' => 102, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '14332.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 203, 'ID_UnidadOperativa' => 15, ],
            ['ID_PresupuestoMensual' => 103, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '12933.30', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 213, 'ID_UnidadOperativa' => 15, ],
            ['ID_PresupuestoMensual' => 104, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '300.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 220, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 105, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 237, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 106, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 250, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 107, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '450.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 225, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 108, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 240, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 109, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 245, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 110, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '700.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 285, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 111, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 290, 'ID_UnidadOperativa' => 19, ],
            ['ID_PresupuestoMensual' => 112, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 282, 'ID_UnidadOperativa' => 14, ],
            ['ID_PresupuestoMensual' => 113, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 272, 'ID_UnidadOperativa' => 14, ],
            ['ID_PresupuestoMensual' => 114, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 267, 'ID_UnidadOperativa' => 14, ],
            ['ID_PresupuestoMensual' => 115, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 178, 'ID_UnidadOperativa' => 20, ],
            ['ID_PresupuestoMensual' => 116, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '150.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 182, 'ID_UnidadOperativa' => 20, ],
            ['ID_PresupuestoMensual' => 117, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '27400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 154, 'ID_UnidadOperativa' => 20, ],
            ['ID_PresupuestoMensual' => 118, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 173, 'ID_UnidadOperativa' => 20, ],
            ['ID_PresupuestoMensual' => 119, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 161, 'ID_UnidadOperativa' => 20, ],
            ['ID_PresupuestoMensual' => 120, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1322.40', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 166, 'ID_UnidadOperativa' => 20, ],
            ['ID_PresupuestoMensual' => 121, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 212, 'ID_UnidadOperativa' => 22, ],
            ['ID_PresupuestoMensual' => 122, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '100.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 198, 'ID_UnidadOperativa' => 22, ],
            ['ID_PresupuestoMensual' => 123, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2850.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 197, 'ID_UnidadOperativa' => 22, ],
            ['ID_PresupuestoMensual' => 124, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '7000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 183, 'ID_UnidadOperativa' => 21, ],
            ['ID_PresupuestoMensual' => 125, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '13300.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 192, 'ID_UnidadOperativa' => 21, ],
            ['ID_PresupuestoMensual' => 126, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '28664.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 207, 'ID_UnidadOperativa' => 23, ],
            ['ID_PresupuestoMensual' => 127, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '18933.30', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 217, 'ID_UnidadOperativa' => 23, ],
            ['ID_PresupuestoMensual' => 128, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '300.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 222, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 129, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '700.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 287, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 130, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 236, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 131, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 252, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 132, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '450.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 227, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 133, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 242, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 134, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 247, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 135, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 292, 'ID_UnidadOperativa' => 24, ],
            ['ID_PresupuestoMensual' => 136, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 278, 'ID_UnidadOperativa' => 25, ],
            ['ID_PresupuestoMensual' => 137, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 268, 'ID_UnidadOperativa' => 25, ],
            ['ID_PresupuestoMensual' => 138, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 263, 'ID_UnidadOperativa' => 25, ],
            ['ID_PresupuestoMensual' => 139, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 174, 'ID_UnidadOperativa' => 26, ],
            ['ID_PresupuestoMensual' => 140, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 181, 'ID_UnidadOperativa' => 26, ],
            ['ID_PresupuestoMensual' => 141, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '23200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 155, 'ID_UnidadOperativa' => 26, ],
            ['ID_PresupuestoMensual' => 142, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 169, 'ID_UnidadOperativa' => 26, ],
            ['ID_PresupuestoMensual' => 143, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 160, 'ID_UnidadOperativa' => 26, ],
            ['ID_PresupuestoMensual' => 144, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '649.60', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 165, 'ID_UnidadOperativa' => 26, ],
            ['ID_PresupuestoMensual' => 145, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '3500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 211, 'ID_UnidadOperativa' => 28, ],
            ['ID_PresupuestoMensual' => 146, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '100.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 199, 'ID_UnidadOperativa' => 28, ],
            ['ID_PresupuestoMensual' => 147, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 196, 'ID_UnidadOperativa' => 28, ],
            ['ID_PresupuestoMensual' => 148, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '7000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 185, 'ID_UnidadOperativa' => 27, ],
            ['ID_PresupuestoMensual' => 149, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '6500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 189, 'ID_UnidadOperativa' => 27, ],
            ['ID_PresupuestoMensual' => 150, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '14332.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 205, 'ID_UnidadOperativa' => 29, ],
            ['ID_PresupuestoMensual' => 151, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '12933.30', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 215, 'ID_UnidadOperativa' => 29, ],
            ['ID_PresupuestoMensual' => 152, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '300.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 218, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 153, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '700.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 283, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 154, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 233, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 155, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 248, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 156, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '450.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 223, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 157, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 238, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 158, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 243, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 159, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 288, 'ID_UnidadOperativa' => 30, ],
            ['ID_PresupuestoMensual' => 160, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 281, 'ID_UnidadOperativa' => 31, ],
            ['ID_PresupuestoMensual' => 161, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 271, 'ID_UnidadOperativa' => 31, ],
            ['ID_PresupuestoMensual' => 162, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 266, 'ID_UnidadOperativa' => 31, ],
            ['ID_PresupuestoMensual' => 163, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 175, 'ID_UnidadOperativa' => 38, ],
            ['ID_PresupuestoMensual' => 164, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 179, 'ID_UnidadOperativa' => 38, ],
            ['ID_PresupuestoMensual' => 165, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '34800.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 157, 'ID_UnidadOperativa' => 38, ],
            ['ID_PresupuestoMensual' => 166, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 170, 'ID_UnidadOperativa' => 38, ],
            ['ID_PresupuestoMensual' => 167, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 158, 'ID_UnidadOperativa' => 38, ],
            ['ID_PresupuestoMensual' => 168, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1902.40', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 164, 'ID_UnidadOperativa' => 38, ],
            ['ID_PresupuestoMensual' => 169, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 208, 'ID_UnidadOperativa' => 40, ],
            ['ID_PresupuestoMensual' => 170, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '600.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 200, 'ID_UnidadOperativa' => 40, ],
            ['ID_PresupuestoMensual' => 171, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '4100.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 195, 'ID_UnidadOperativa' => 40, ],
            ['ID_PresupuestoMensual' => 172, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '15000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 187, 'ID_UnidadOperativa' => 43, ],
            ['ID_PresupuestoMensual' => 173, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '19000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 191, 'ID_UnidadOperativa' => 43, ],
            ['ID_PresupuestoMensual' => 174, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '57328.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 206, 'ID_UnidadOperativa' => 39, ],
            ['ID_PresupuestoMensual' => 175, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '27599.90', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 216, 'ID_UnidadOperativa' => 39, ],
            ['ID_PresupuestoMensual' => 176, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '600.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 219, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 177, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '700.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 284, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 178, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '800.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 234, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 179, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 249, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 180, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 224, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 181, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '850.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 239, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 182, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 244, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 183, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '900.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 258, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 184, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1350.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 289, 'ID_UnidadOperativa' => 41, ],
            ['ID_PresupuestoMensual' => 185, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 279, 'ID_UnidadOperativa' => 42, ],
            ['ID_PresupuestoMensual' => 186, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 269, 'ID_UnidadOperativa' => 42, ],
            ['ID_PresupuestoMensual' => 187, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '4000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 264, 'ID_UnidadOperativa' => 42, ],
            ['ID_PresupuestoMensual' => 188, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '4995.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 273, 'ID_UnidadOperativa' => 42, ],
            ['ID_PresupuestoMensual' => 189, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 176, 'ID_UnidadOperativa' => 32, ],
            ['ID_PresupuestoMensual' => 190, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '150.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 180, 'ID_UnidadOperativa' => 32, ],
            ['ID_PresupuestoMensual' => 191, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '23200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 156, 'ID_UnidadOperativa' => 32, ],
            ['ID_PresupuestoMensual' => 192, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 171, 'ID_UnidadOperativa' => 32, ],
            ['ID_PresupuestoMensual' => 193, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 159, 'ID_UnidadOperativa' => 32, ],
            ['ID_PresupuestoMensual' => 194, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '835.20', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 163, 'ID_UnidadOperativa' => 32, ],
            ['ID_PresupuestoMensual' => 195, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 209, 'ID_UnidadOperativa' => 34, ],
            ['ID_PresupuestoMensual' => 196, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '100.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 201, 'ID_UnidadOperativa' => 34, ],
            ['ID_PresupuestoMensual' => 197, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1800.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 194, 'ID_UnidadOperativa' => 34, ],
            ['ID_PresupuestoMensual' => 198, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '7500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 184, 'ID_UnidadOperativa' => 33, ],
            ['ID_PresupuestoMensual' => 199, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '7500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 188, 'ID_UnidadOperativa' => 33, ],
            ['ID_PresupuestoMensual' => 200, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '28664.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 204, 'ID_UnidadOperativa' => 35, ],
            ['ID_PresupuestoMensual' => 201, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '27600.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 214, 'ID_UnidadOperativa' => 35, ],
            ['ID_PresupuestoMensual' => 202, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '300.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 221, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 203, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '700.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 286, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 204, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '200.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 235, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 205, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '350.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 251, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 206, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '450.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 226, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 207, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 241, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 208, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '300.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 246, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 209, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 291, 'ID_UnidadOperativa' => 36, ],
            ['ID_PresupuestoMensual' => 210, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '2400.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 280, 'ID_UnidadOperativa' => 37, ],
            ['ID_PresupuestoMensual' => 211, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1500.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 270, 'ID_UnidadOperativa' => 37, ],
            ['ID_PresupuestoMensual' => 212, 'Anio' => 2026, 'Mes' => 3, 'Monto_Asignado' => '1000.00', 'Monto_Comprometido' => '0.00', 'Monto_Ejecutado' => '0.00', 'ID_GrupoPresupuestal' => 265, 'ID_UnidadOperativa' => 37, ],
        ];

        foreach ($data as $row) {
            $idUnidad = $row['ID_UnidadOperativa'] ?? null;
            if ($idUnidad !== null) {
                $unidadExists = $this->db->table('UnidadOperativa')
                    ->where('ID_UnidadOperativa', $idUnidad)
                    ->countAllResults() > 0;
                if (!$unidadExists) continue;
            }

            $idGrupo = $row['ID_GrupoPresupuestal'] ?? null;
            if ($idGrupo !== null) {
                $grupoExists = $this->db->table('GrupoPresupuestal')
                    ->where('ID_GrupoPresupuestal', $idGrupo)
                    ->countAllResults() > 0;
                if (!$grupoExists) continue;
            }

            $exists = $this->db->table('PresupuestoMensual')
                ->where('ID_PresupuestoMensual', $row['ID_PresupuestoMensual'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('PresupuestoMensual')->insert($row);
            } else {
                $this->db->table('PresupuestoMensual')
                    ->where('ID_PresupuestoMensual', $row['ID_PresupuestoMensual'])
                    ->update($row);
            }
        }
    }

    public function down()
    {
        $idsToDelete = [7, 8, 9, 10, 11, 12, 22, 94, 2, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 13, 61, 65, 66, 67, 6, 14, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 1, 62, 63, 64, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 79, 80, 81, 82, 83, 84, 85, 86, 21, 23, 24, 25, 26, 87, 88, 89, 90, 91, 92, 93, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200, 201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212];
        $this->db->table('PresupuestoMensual')->whereIn('ID_PresupuestoMensual', $idsToDelete)->delete();
    }
}
