<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertGrupoPresupuestalData extends Migration
{
    public function up()
    {
        $data = [
            ['ID_GrupoPresupuestal' => 2, 'Nombre' => 'Papeleria', 'Descripcion' => 'Papeleria', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 3, 'Nombre' => 'Insumos de palomitas', 'Descripcion' => 'Palomitas', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 4, 'Nombre' => 'Materiales áreas comunes', 'Descripcion' => 'Materiales áreas comunes', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 5, 'Nombre' => 'Bikini Buttom Party', 'Descripcion' => 'Bikini Buttom Party', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 6, 'Nombre' => 'Pausa Bonita (Febrero)', 'Descripcion' => 'Pausa Bonita (Febrero)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 7, 'Nombre' => 'Kore And Rive (Febrero)', 'Descripcion' => 'Kore And Rive (Febrero)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 8, 'Nombre' => 'Olimpiadas (Febrero)', 'Descripcion' => 'Olimpiadas (Febrero)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 9, 'Nombre' => 'Movie Night (abril)', 'Descripcion' => 'Movie Night (abril)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 10, 'Nombre' => 'Last Bloom (abril)', 'Descripcion' => 'Last Bloom (abril)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 11, 'Nombre' => 'THRIFTSHOP (Abril)', 'Descripcion' => 'THRIFTSHOP (Abril)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 12, 'Nombre' => 'Breakfast Movie (Mayo)', 'Descripcion' => 'Breakfast Movie (Mayo)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 13, 'Nombre' => 'Drink And Draw (mayo)', 'Descripcion' => 'Drink And Draw (mayo)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 14, 'Nombre' => 'Game Over (Mayo)', 'Descripcion' => 'Game Over (Mayo)', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 15, 'Nombre' => 'Pool Party', 'Descripcion' => 'Pool Partu', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 16, 'Nombre' => 'Halloween party', 'Descripcion' => 'Halloween party', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 17, 'Nombre' => 'THRIFTSHOP ', 'Descripcion' => 'THRIFTSHOP ', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 18, 'Nombre' => 'Consumibles del depto', 'Descripcion' => 'Consumibles del depto', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 19, 'Nombre' => 'Consumibles para el area Grill, games R. y salon de estudios', 'Descripcion' => 'Consumibles para el area Grill, games R. y salon de estudios', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 20, 'Nombre' => 'Consomubles Gimancio', 'Descripcion' => 'Gimnacio', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 21, 'Nombre' => 'Consumibles Piscina', 'Descripcion' => 'Consumibles Piscina', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 22, 'Nombre' => 'Consumibles áreas con pasillos', 'Descripcion' => 'Consumibles áreas con pasillos', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 23, 'Nombre' => 'Consumibles Jardineria', 'Descripcion' => 'Consumibles Jardineria', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 24, 'Nombre' => 'Fumigación', 'Descripcion' => 'Fumigación', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 25, 'Nombre' => 'Gas LP Edf. 7,8 y 9', 'Descripcion' => 'Gas LP Edf. 7,8 y 9', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 26, 'Nombre' => 'Herramientas y Mantto general de motores Complejo', 'Descripcion' => 'Herramientas y Mantto general de motores Complejo', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 27, 'Nombre' => 'Laboratorio de Petar', 'Descripcion' => 'Lasb', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 28, 'Nombre' => 'Papeleria', 'Descripcion' => 'Papeleria', 'activo' => true, 'ID_UnidadOperativa' => 4],
            ['ID_GrupoPresupuestal' => 29, 'Nombre' => 'Quimicos', 'Descripcion' => 'Quimicos ', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 30, 'Nombre' => 'Insumos', 'Descripcion' => 'Insumos', 'activo' => true, 'ID_UnidadOperativa' => 5],
            ['ID_GrupoPresupuestal' => 31, 'Nombre' => 'Telmex Servicios (6)', 'Descripcion' => 'Telmex Servicios (6)', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 32, 'Nombre' => 'Hosting y Dominios', 'Descripcion' => 'Hosting y dom', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 33, 'Nombre' => 'Router / AP', 'Descripcion' => 'Router / AP', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 34, 'Nombre' => 'Cerraduras electricas', 'Descripcion' => 'Cerraduras', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 35, 'Nombre' => 'Tel ip/ analog', 'Descripcion' => 'Tel ip/ analog', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 36, 'Nombre' => 'Bobina UTP', 'Descripcion' => 'Bobina UTP', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 37, 'Nombre' => 'Herramienta/Accesorios', 'Descripcion' => 'Herramienta/Accesorios', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 38, 'Nombre' => 'Baterias AA (Cerraduras)', 'Descripcion' => 'Baterias AA (Cerraduras)', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 39, 'Nombre' => 'Tarjetas RFID (Cerraduras)', 'Descripcion' => 'Tarjetas RFID (Cerraduras)', 'activo' => true, 'ID_UnidadOperativa' => 6],
            ['ID_GrupoPresupuestal' => 40, 'Nombre' => 'Papeleria', 'Descripcion' => 'Papeleria', 'activo' => true, 'ID_UnidadOperativa' => 7],
            ['ID_GrupoPresupuestal' => 41, 'Nombre' => 'Radiocomunicador', 'Descripcion' => 'Radio', 'activo' => true, 'ID_UnidadOperativa' => 7],
            ['ID_GrupoPresupuestal' => 42, 'Nombre' => 'Pila para radio comunicador', 'Descripcion' => 'Pila para radio comunicador', 'activo' => true, 'ID_UnidadOperativa' => 7],
            ['ID_GrupoPresupuestal' => 43, 'Nombre' => 'Manos Libres Para Radio', 'Descripcion' => 'Manos Libres Para Radio', 'activo' => true, 'ID_UnidadOperativa' => 7],
            ['ID_GrupoPresupuestal' => 44, 'Nombre' => 'Lamparas Recargables', 'Descripcion' => 'Lamparas', 'activo' => true, 'ID_UnidadOperativa' => 7],
            ['ID_GrupoPresupuestal' => 45, 'Nombre' => 'OTAS 21%', 'Descripcion' => 'OTAS 21%', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 46, 'Nombre' => 'Insumos de limpieza', 'Descripcion' => 'Insumos de limpieza', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 47, 'Nombre' => 'Insumos de mantenimiento', 'Descripcion' => 'Insumos de mantenimiento', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 48, 'Nombre' => 'Papeleria', 'Descripcion' => 'Papeleria', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 49, 'Nombre' => 'Renta de inmobiliario y equipo (OTV)', 'Descripcion' => 'Renta de inmobiliario y equipo (OTV)', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 50, 'Nombre' => 'CFE', 'Descripcion' => 'CFE', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 51, 'Nombre' => 'Lavanderia', 'Descripcion' => 'Lavanderia', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 52, 'Nombre' => 'Amenidades ', 'Descripcion' => 'Agua, detalles', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 53, 'Nombre' => 'Kone', 'Descripcion' => 'Kone', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 54, 'Nombre' => 'Fumigacion', 'Descripcion' => 'Fumigacion', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 55, 'Nombre' => 'Lavado de Alfombras/muebles', 'Descripcion' => 'Lavado de Alfombras/muebles', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 56, 'Nombre' => 'Pintura de pasillos, cuartos y lobby', 'Descripcion' => 'Pintura', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 57, 'Nombre' => 'Impermeabilizante', 'Descripcion' => 'Impermeabilizante', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 58, 'Nombre' => 'Trabajo en Boilers', 'Descripcion' => 'Trabajo en Boilers', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 59, 'Nombre' => 'Dispensador de amenidades', 'Descripcion' => 'dispensador', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 60, 'Nombre' => 'Trabajos de tablaroca', 'Descripcion' => 'tablaroca', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 61, 'Nombre' => 'Cambio de mobiliario', 'Descripcion' => 'Cambio de mobiliario', 'activo' => true, 'ID_UnidadOperativa' => 2],
            ['ID_GrupoPresupuestal' => 62, 'Nombre' => 'Aditivo adblue VW', 'Descripcion' => 'Aditivo adblue VW ', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 63, 'Nombre' => 'Articulos de limpieza', 'Descripcion' => 'Articulos de limpieza', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 64, 'Nombre' => 'Consumibles de limpieza', 'Descripcion' => 'Consumibles de limpieza', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 65, 'Nombre' => 'Consumibles de motor', 'Descripcion' => 'Consumibles de motor', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 66, 'Nombre' => 'Reparaciones menores', 'Descripcion' => 'Reparaciones menores', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 67, 'Nombre' => 'Mantenimiento de unidades Vans MEC', 'Descripcion' => 'Mantenimiento de unidades Vans MEC', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 68, 'Nombre' => 'Mantenimiento de unidades Vans AA', 'Descripcion' => 'Mantenimiento de unidades Vans AA', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 69, 'Nombre' => 'Mantenimiento de unidades Bus MEC', 'Descripcion' => 'Mantenimiento de unidades Bus MEC', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 70, 'Nombre' => 'Mantenimiento de unidades Bus AA', 'Descripcion' => 'Mantenimiento de unidades Bus AA', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 71, 'Nombre' => 'gasolina', 'Descripcion' => 'gas ', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 72, 'Nombre' => 'IMSS', 'Descripcion' => 'IMSS', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 73, 'Nombre' => 'Seguros', 'Descripcion' => 'Seguritos', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 74, 'Nombre' => 'Telefonia', 'Descripcion' => 'Telefonia', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 75, 'Nombre' => 'Leasing bus', 'Descripcion' => 'Leasing bus', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 76, 'Nombre' => 'Leasing Van', 'Descripcion' => 'Leasing Van', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 77, 'Nombre' => 'Contingencias', 'Descripcion' => 'Contingencias', 'activo' => true, 'ID_UnidadOperativa' => 3],
            ['ID_GrupoPresupuestal' => 78, 'Nombre' => 'Mercadotecnia y publicidad', 'Descripcion' => 'Mercadotecnia y publicidad', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 79, 'Nombre' => 'Viaticos', 'Descripcion' => 'Viaticos', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 80, 'Nombre' => 'Servicio celular', 'Descripcion' => 'Servicio general', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 81, 'Nombre' => 'Convivios y eventos Vtas', 'Descripcion' => 'Convivios y eventos Vtas', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 82, 'Nombre' => 'Papeleria', 'Descripcion' => 'Papeleria', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 83, 'Nombre' => 'obsequios', 'Descripcion' => 'obsequios', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 84, 'Nombre' => 'Dominios y hosting', 'Descripcion' => 'dominios y hosting', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 85, 'Nombre' => 'Combustible', 'Descripcion' => 'Combustible', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 86, 'Nombre' => 'Mantto Equipo de transporte', 'Descripcion' => 'Mantto Equipo de transporte', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 87, 'Nombre' => 'CRM y aplicaciones', 'Descripcion' => 'CRM y aplicaciones', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 88, 'Nombre' => 'Eventos ANAHUAC', 'Descripcion' => 'Eventos ANAHUAC', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 89, 'Nombre' => 'Souvenir', 'Descripcion' => 'Souvenir', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 90, 'Nombre' => 'IMSS mensual', 'Descripcion' => 'IMSS', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 91, 'Nombre' => 'Retiro Cesantia y vejez', 'Descripcion' => 'Retiro Cesantia y vejez', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 92, 'Nombre' => 'Infonavit', 'Descripcion' => 'infonavit', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 93, 'Nombre' => 'Creditos infonavit', 'Descripcion' => 'Creditos infonavit', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 94, 'Nombre' => 'Impuestos sobre nomina', 'Descripcion' => 'Impuestos sobre nomina', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 95, 'Nombre' => 'CFE', 'Descripcion' => 'CFE', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 96, 'Nombre' => 'CONAGUA', 'Descripcion' => 'CONAGUA', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 97, 'Nombre' => 'Agua empleados', 'Descripcion' => 'agua', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 98, 'Nombre' => 'Pagos comisiones HSBC', 'Descripcion' => 'comision hsbc', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 99, 'Nombre' => 'Software campus icarus y contpaq', 'Descripcion' => 'Software campus icarus y contpaq', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 100, 'Nombre' => 'Plataforma HIGO', 'Descripcion' => 'HIGO', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 101, 'Nombre' => 'Uniformes', 'Descripcion' => 'uniformes', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 102, 'Nombre' => 'Timbres icarus/otros', 'Descripcion' => 'Timbres icarus/otros', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 103, 'Nombre' => 'Asesoramiento Cumplimiento PLD', 'Descripcion' => 'Asesoramiento PLD', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 104, 'Nombre' => 'Renta inmobiliaria (copiadora)', 'Descripcion' => 'Timbres icarus/otros', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 105, 'Nombre' => 'Paneles solares', 'Descripcion' => 'Paneles solares', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 106, 'Nombre' => 'Lavado de colchones', 'Descripcion' => 'Lavado de colchones', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 107, 'Nombre' => 'seguro propiedad', 'Descripcion' => 'seguro propiedad', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 108, 'Nombre' => 'Capex', 'Descripcion' => 'Capex', 'activo' => true, 'ID_UnidadOperativa' => 8],
            ['ID_GrupoPresupuestal' => 109, 'Nombre' => 'Gastos sistemas', 'Descripcion' => 'gastos', 'activo' => true, 'ID_UnidadOperativa' => null],
            ['ID_GrupoPresupuestal' => 110, 'Nombre' => 'Papeleria ', 'Descripcion' => 'Papeleria', 'activo' => true, 'ID_UnidadOperativa' => 6],
        ];

        foreach ($data as $row) {
            $exists = $this->db->table('GrupoPresupuestal')
                ->where('ID_GrupoPresupuestal', $row['ID_GrupoPresupuestal'])
                ->countAllResults();

            if ($exists === 0) {
                $this->db->table('GrupoPresupuestal')->insert($row);
            } else {
                $this->db->table('GrupoPresupuestal')
                    ->where('ID_GrupoPresupuestal', $row['ID_GrupoPresupuestal'])
                    ->update($row);
            }
        }
    }

    public function down()
    {
        $this->db->table('GrupoPresupuestal')->where('ID_GrupoPresupuestal >', 0)->delete();
    }
}
