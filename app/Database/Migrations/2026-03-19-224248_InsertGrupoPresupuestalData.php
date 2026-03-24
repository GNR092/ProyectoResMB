<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InsertGrupoPresupuestalData extends Migration
{
    public function up()
    {
        $data = [
  0 => 
  array (
    'ID_GrupoPresupuestal' => 2,
    'Nombre' => 'Papeleria',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  1 => 
  array (
    'ID_GrupoPresupuestal' => 3,
    'Nombre' => 'Insumos de palomitas',
    'Descripcion' => 'Palomitas',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  2 => 
  array (
    'ID_GrupoPresupuestal' => 4,
    'Nombre' => 'Materiales áreas comunes',
    'Descripcion' => 'Materiales áreas comunes',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  3 => 
  array (
    'ID_GrupoPresupuestal' => 5,
    'Nombre' => 'Bikini Buttom Party',
    'Descripcion' => 'Bikini Buttom Party',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  4 => 
  array (
    'ID_GrupoPresupuestal' => 6,
    'Nombre' => 'Pausa Bonita (Febrero)',
    'Descripcion' => 'Pausa Bonita (Febrero)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  5 => 
  array (
    'ID_GrupoPresupuestal' => 7,
    'Nombre' => 'Kore And Rive (Febrero)',
    'Descripcion' => 'Kore And Rive (Febrero)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  6 => 
  array (
    'ID_GrupoPresupuestal' => 8,
    'Nombre' => 'Olimpiadas (Febrero)',
    'Descripcion' => 'Olimpiadas (Febrero)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  7 => 
  array (
    'ID_GrupoPresupuestal' => 9,
    'Nombre' => 'Movie Night (abril)',
    'Descripcion' => 'Movie Night (abril)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  8 => 
  array (
    'ID_GrupoPresupuestal' => 10,
    'Nombre' => 'Last Bloom (abril)',
    'Descripcion' => 'Last Bloom (abril)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  9 => 
  array (
    'ID_GrupoPresupuestal' => 11,
    'Nombre' => 'THRIFTSHOP (Abril)',
    'Descripcion' => 'THRIFTSHOP (Abril)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  10 => 
  array (
    'ID_GrupoPresupuestal' => 12,
    'Nombre' => 'Breakfast Movie (Mayo)',
    'Descripcion' => 'Breakfast Movie (Mayo)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  11 => 
  array (
    'ID_GrupoPresupuestal' => 13,
    'Nombre' => 'Drink And Draw (mayo)',
    'Descripcion' => 'Drink And Draw (mayo)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  12 => 
  array (
    'ID_GrupoPresupuestal' => 14,
    'Nombre' => 'Game Over (Mayo)',
    'Descripcion' => 'Game Over (Mayo)',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  13 => 
  array (
    'ID_GrupoPresupuestal' => 15,
    'Nombre' => 'Pool Party',
    'Descripcion' => 'Pool Partu',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  14 => 
  array (
    'ID_GrupoPresupuestal' => 16,
    'Nombre' => 'Halloween party',
    'Descripcion' => 'Halloween party',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  15 => 
  array (
    'ID_GrupoPresupuestal' => 17,
    'Nombre' => 'THRIFTSHOP ',
    'Descripcion' => 'THRIFTSHOP ',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  16 => 
  array (
    'ID_GrupoPresupuestal' => 18,
    'Nombre' => 'Consumibles del depto',
    'Descripcion' => 'Consumibles del depto',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  17 => 
  array (
    'ID_GrupoPresupuestal' => 19,
    'Nombre' => 'Consumibles para el area Grill, games R. y salon de estudios',
    'Descripcion' => 'Consumibles para el area Grill, games R. y salon de estudios',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  18 => 
  array (
    'ID_GrupoPresupuestal' => 20,
    'Nombre' => 'Consomubles Gimancio',
    'Descripcion' => 'Gimnacio',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  19 => 
  array (
    'ID_GrupoPresupuestal' => 21,
    'Nombre' => 'Consumibles Piscina',
    'Descripcion' => 'Consumibles Piscina',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  20 => 
  array (
    'ID_GrupoPresupuestal' => 22,
    'Nombre' => 'Consumibles áreas con pasillos',
    'Descripcion' => 'Consumibles áreas con pasillos',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  21 => 
  array (
    'ID_GrupoPresupuestal' => 23,
    'Nombre' => 'Consumibles Jardineria',
    'Descripcion' => 'Consumibles Jardineria',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  22 => 
  array (
    'ID_GrupoPresupuestal' => 24,
    'Nombre' => 'Fumigación',
    'Descripcion' => 'Fumigación',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  23 => 
  array (
    'ID_GrupoPresupuestal' => 25,
    'Nombre' => 'Gas LP Edf. 7,8 y 9',
    'Descripcion' => 'Gas LP Edf. 7,8 y 9',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  24 => 
  array (
    'ID_GrupoPresupuestal' => 26,
    'Nombre' => 'Herramientas y Mantto general de motores Complejo',
    'Descripcion' => 'Herramientas y Mantto general de motores Complejo',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  25 => 
  array (
    'ID_GrupoPresupuestal' => 27,
    'Nombre' => 'Laboratorio de Petar',
    'Descripcion' => 'Lasb',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  26 => 
  array (
    'ID_GrupoPresupuestal' => 28,
    'Nombre' => 'Papeleria',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 4,
  ),
  27 => 
  array (
    'ID_GrupoPresupuestal' => 29,
    'Nombre' => 'Quimicos',
    'Descripcion' => 'Quimicos ',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  28 => 
  array (
    'ID_GrupoPresupuestal' => 30,
    'Nombre' => 'Insumos',
    'Descripcion' => 'Insumos',
    'activo' => true,
    'ID_UnidadOperativa' => 5,
  ),
  29 => 
  array (
    'ID_GrupoPresupuestal' => 31,
    'Nombre' => 'Telmex Servicios (6)',
    'Descripcion' => 'Telmex Servicios (6)',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  30 => 
  array (
    'ID_GrupoPresupuestal' => 32,
    'Nombre' => 'Hosting y Dominios',
    'Descripcion' => 'Hosting y dom',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  31 => 
  array (
    'ID_GrupoPresupuestal' => 33,
    'Nombre' => 'Router / AP',
    'Descripcion' => 'Router / AP',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  32 => 
  array (
    'ID_GrupoPresupuestal' => 34,
    'Nombre' => 'Cerraduras electricas',
    'Descripcion' => 'Cerraduras',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  33 => 
  array (
    'ID_GrupoPresupuestal' => 35,
    'Nombre' => 'Tel ip/ analog',
    'Descripcion' => 'Tel ip/ analog',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  34 => 
  array (
    'ID_GrupoPresupuestal' => 36,
    'Nombre' => 'Bobina UTP',
    'Descripcion' => 'Bobina UTP',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  35 => 
  array (
    'ID_GrupoPresupuestal' => 37,
    'Nombre' => 'Herramienta/Accesorios',
    'Descripcion' => 'Herramienta/Accesorios',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  36 => 
  array (
    'ID_GrupoPresupuestal' => 38,
    'Nombre' => 'Baterias AA (Cerraduras)',
    'Descripcion' => 'Baterias AA (Cerraduras)',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  37 => 
  array (
    'ID_GrupoPresupuestal' => 39,
    'Nombre' => 'Tarjetas RFID (Cerraduras)',
    'Descripcion' => 'Tarjetas RFID (Cerraduras)',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  38 => 
  array (
    'ID_GrupoPresupuestal' => 40,
    'Nombre' => 'Papeleria',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 7,
  ),
  39 => 
  array (
    'ID_GrupoPresupuestal' => 41,
    'Nombre' => 'Radiocomunicador',
    'Descripcion' => 'Radio',
    'activo' => true,
    'ID_UnidadOperativa' => 7,
  ),
  40 => 
  array (
    'ID_GrupoPresupuestal' => 42,
    'Nombre' => 'Pila para radio comunicador',
    'Descripcion' => 'Pila para radio comunicador',
    'activo' => true,
    'ID_UnidadOperativa' => 7,
  ),
  41 => 
  array (
    'ID_GrupoPresupuestal' => 43,
    'Nombre' => 'Manos Libres Para Radio',
    'Descripcion' => 'Manos Libres Para Radio',
    'activo' => true,
    'ID_UnidadOperativa' => 7,
  ),
  42 => 
  array (
    'ID_GrupoPresupuestal' => 44,
    'Nombre' => 'Lamparas Recargables',
    'Descripcion' => 'Lamparas',
    'activo' => true,
    'ID_UnidadOperativa' => 7,
  ),
  43 => 
  array (
    'ID_GrupoPresupuestal' => 45,
    'Nombre' => 'OTAS 21%',
    'Descripcion' => 'OTAS 21%',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  44 => 
  array (
    'ID_GrupoPresupuestal' => 46,
    'Nombre' => 'Insumos de limpieza',
    'Descripcion' => 'Insumos de limpieza',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  45 => 
  array (
    'ID_GrupoPresupuestal' => 47,
    'Nombre' => 'Insumos de mantenimiento',
    'Descripcion' => 'Insumos de mantenimiento',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  46 => 
  array (
    'ID_GrupoPresupuestal' => 48,
    'Nombre' => 'Papeleria',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  47 => 
  array (
    'ID_GrupoPresupuestal' => 49,
    'Nombre' => 'Renta de inmobiliario y equipo (OTV)',
    'Descripcion' => 'Renta de inmobiliario y equipo (OTV)',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  48 => 
  array (
    'ID_GrupoPresupuestal' => 50,
    'Nombre' => 'CFE',
    'Descripcion' => 'CFE',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  49 => 
  array (
    'ID_GrupoPresupuestal' => 51,
    'Nombre' => 'Lavanderia',
    'Descripcion' => 'Lavanderia',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  50 => 
  array (
    'ID_GrupoPresupuestal' => 52,
    'Nombre' => 'Amenidades ',
    'Descripcion' => 'Agua, detalles',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  51 => 
  array (
    'ID_GrupoPresupuestal' => 53,
    'Nombre' => 'Kone',
    'Descripcion' => 'Kone',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  52 => 
  array (
    'ID_GrupoPresupuestal' => 54,
    'Nombre' => 'Fumigacion',
    'Descripcion' => 'Fumigacion',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  53 => 
  array (
    'ID_GrupoPresupuestal' => 55,
    'Nombre' => 'Lavado de Alfombras/muebles',
    'Descripcion' => 'Lavado de Alfombras/muebles',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  54 => 
  array (
    'ID_GrupoPresupuestal' => 56,
    'Nombre' => 'Pintura de pasillos, cuartos y lobby',
    'Descripcion' => 'Pintura',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  55 => 
  array (
    'ID_GrupoPresupuestal' => 57,
    'Nombre' => 'Impermeabilizante',
    'Descripcion' => 'Impermeabilizante',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  56 => 
  array (
    'ID_GrupoPresupuestal' => 58,
    'Nombre' => 'Trabajo en Boilers',
    'Descripcion' => 'Trabajo en Boilers',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  57 => 
  array (
    'ID_GrupoPresupuestal' => 59,
    'Nombre' => 'Dispensador de amenidades',
    'Descripcion' => 'dispensador',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  58 => 
  array (
    'ID_GrupoPresupuestal' => 60,
    'Nombre' => 'Trabajos de tablaroca',
    'Descripcion' => 'tablaroca',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  59 => 
  array (
    'ID_GrupoPresupuestal' => 61,
    'Nombre' => 'Cambio de mobiliario',
    'Descripcion' => 'Cambio de mobiliario',
    'activo' => true,
    'ID_UnidadOperativa' => 2,
  ),
  60 => 
  array (
    'ID_GrupoPresupuestal' => 62,
    'Nombre' => 'Aditivo adblue VW',
    'Descripcion' => 'Aditivo adblue VW ',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  61 => 
  array (
    'ID_GrupoPresupuestal' => 63,
    'Nombre' => 'Articulos de limpieza',
    'Descripcion' => 'Articulos de limpieza',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  62 => 
  array (
    'ID_GrupoPresupuestal' => 64,
    'Nombre' => 'Consumibles de limpieza',
    'Descripcion' => 'Consumibles de limpieza',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  63 => 
  array (
    'ID_GrupoPresupuestal' => 65,
    'Nombre' => 'Consumibles de motor',
    'Descripcion' => 'Consumibles de motor',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  64 => 
  array (
    'ID_GrupoPresupuestal' => 66,
    'Nombre' => 'Reparaciones menores',
    'Descripcion' => 'Reparaciones menores',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  65 => 
  array (
    'ID_GrupoPresupuestal' => 67,
    'Nombre' => 'Mantenimiento de unidades Vans MEC',
    'Descripcion' => 'Mantenimiento de unidades Vans MEC',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  66 => 
  array (
    'ID_GrupoPresupuestal' => 68,
    'Nombre' => 'Mantenimiento de unidades Vans AA',
    'Descripcion' => 'Mantenimiento de unidades Vans AA',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  67 => 
  array (
    'ID_GrupoPresupuestal' => 69,
    'Nombre' => 'Mantenimiento de unidades Bus MEC',
    'Descripcion' => 'Mantenimiento de unidades Bus MEC',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  68 => 
  array (
    'ID_GrupoPresupuestal' => 70,
    'Nombre' => 'Mantenimiento de unidades Bus AA',
    'Descripcion' => 'Mantenimiento de unidades Bus AA',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  69 => 
  array (
    'ID_GrupoPresupuestal' => 71,
    'Nombre' => 'gasolina',
    'Descripcion' => 'gas ',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  70 => 
  array (
    'ID_GrupoPresupuestal' => 72,
    'Nombre' => 'IMSS',
    'Descripcion' => 'IMSS',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  71 => 
  array (
    'ID_GrupoPresupuestal' => 73,
    'Nombre' => 'Seguros',
    'Descripcion' => 'Seguritos',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  72 => 
  array (
    'ID_GrupoPresupuestal' => 74,
    'Nombre' => 'Telefonia',
    'Descripcion' => 'Telefonia',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  73 => 
  array (
    'ID_GrupoPresupuestal' => 75,
    'Nombre' => 'Leasing bus',
    'Descripcion' => 'Leasing bus',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  74 => 
  array (
    'ID_GrupoPresupuestal' => 76,
    'Nombre' => 'Leasing Van',
    'Descripcion' => 'Leasing Van',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  75 => 
  array (
    'ID_GrupoPresupuestal' => 77,
    'Nombre' => 'Contingencias',
    'Descripcion' => 'Contingencias',
    'activo' => true,
    'ID_UnidadOperativa' => 3,
  ),
  76 => 
  array (
    'ID_GrupoPresupuestal' => 78,
    'Nombre' => 'Mercadotecnia y publicidad',
    'Descripcion' => 'Mercadotecnia y publicidad',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  77 => 
  array (
    'ID_GrupoPresupuestal' => 79,
    'Nombre' => 'Viaticos',
    'Descripcion' => 'Viaticos',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  78 => 
  array (
    'ID_GrupoPresupuestal' => 80,
    'Nombre' => 'Servicio celular',
    'Descripcion' => 'Servicio general',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  79 => 
  array (
    'ID_GrupoPresupuestal' => 81,
    'Nombre' => 'Convivios y eventos Vtas',
    'Descripcion' => 'Convivios y eventos Vtas',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  80 => 
  array (
    'ID_GrupoPresupuestal' => 82,
    'Nombre' => 'Papeleria',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  81 => 
  array (
    'ID_GrupoPresupuestal' => 83,
    'Nombre' => 'obsequios',
    'Descripcion' => 'obsequios',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  82 => 
  array (
    'ID_GrupoPresupuestal' => 84,
    'Nombre' => 'Dominios y hosting',
    'Descripcion' => 'dominios y hosting',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  83 => 
  array (
    'ID_GrupoPresupuestal' => 85,
    'Nombre' => 'Combustible',
    'Descripcion' => 'Combustible',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  84 => 
  array (
    'ID_GrupoPresupuestal' => 86,
    'Nombre' => 'Mantto Equipo de transporte',
    'Descripcion' => 'Mantto Equipo de transporte',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  85 => 
  array (
    'ID_GrupoPresupuestal' => 87,
    'Nombre' => 'CRM y aplicaciones',
    'Descripcion' => 'CRM y aplicaciones',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  86 => 
  array (
    'ID_GrupoPresupuestal' => 88,
    'Nombre' => 'Eventos ANAHUAC',
    'Descripcion' => 'Eventos ANAHUAC',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  87 => 
  array (
    'ID_GrupoPresupuestal' => 89,
    'Nombre' => 'Souvenir',
    'Descripcion' => 'Souvenir',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  88 => 
  array (
    'ID_GrupoPresupuestal' => 90,
    'Nombre' => 'IMSS mensual',
    'Descripcion' => 'IMSS',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  89 => 
  array (
    'ID_GrupoPresupuestal' => 91,
    'Nombre' => 'Retiro Cesantia y vejez',
    'Descripcion' => 'Retiro Cesantia y vejez',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  90 => 
  array (
    'ID_GrupoPresupuestal' => 92,
    'Nombre' => 'Infonavit',
    'Descripcion' => 'infonavit',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  91 => 
  array (
    'ID_GrupoPresupuestal' => 93,
    'Nombre' => 'Creditos infonavit',
    'Descripcion' => 'Creditos infonavit',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  92 => 
  array (
    'ID_GrupoPresupuestal' => 94,
    'Nombre' => 'Impuestos sobre nomina',
    'Descripcion' => 'Impuestos sobre nomina',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  93 => 
  array (
    'ID_GrupoPresupuestal' => 95,
    'Nombre' => 'CFE',
    'Descripcion' => 'CFE',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  94 => 
  array (
    'ID_GrupoPresupuestal' => 96,
    'Nombre' => 'CONAGUA',
    'Descripcion' => 'CONAGUA',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  95 => 
  array (
    'ID_GrupoPresupuestal' => 97,
    'Nombre' => 'Agua empleados',
    'Descripcion' => 'agua',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  96 => 
  array (
    'ID_GrupoPresupuestal' => 98,
    'Nombre' => 'Pagos comisiones HSBC',
    'Descripcion' => 'comision hsbc',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  97 => 
  array (
    'ID_GrupoPresupuestal' => 99,
    'Nombre' => 'Software campus icarus y contpaq',
    'Descripcion' => 'Software campus icarus y contpaq',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  98 => 
  array (
    'ID_GrupoPresupuestal' => 100,
    'Nombre' => 'Plataforma HIGO',
    'Descripcion' => 'HIGO',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  99 => 
  array (
    'ID_GrupoPresupuestal' => 101,
    'Nombre' => 'Uniformes',
    'Descripcion' => 'uniformes',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  100 => 
  array (
    'ID_GrupoPresupuestal' => 102,
    'Nombre' => 'Timbres icarus/otros',
    'Descripcion' => 'Timbres icarus/otros',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  101 => 
  array (
    'ID_GrupoPresupuestal' => 103,
    'Nombre' => 'Asesoramiento Cumplimiento PLD',
    'Descripcion' => 'Asesoramiento PLD',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  102 => 
  array (
    'ID_GrupoPresupuestal' => 104,
    'Nombre' => 'Renta inmobiliaria (copiadora)',
    'Descripcion' => 'Timbres icarus/otros',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  103 => 
  array (
    'ID_GrupoPresupuestal' => 105,
    'Nombre' => 'Paneles solares',
    'Descripcion' => 'Paneles solares',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  104 => 
  array (
    'ID_GrupoPresupuestal' => 106,
    'Nombre' => 'Lavado de colchones',
    'Descripcion' => 'Lavado de colchones',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  105 => 
  array (
    'ID_GrupoPresupuestal' => 107,
    'Nombre' => 'seguro propiedad',
    'Descripcion' => 'seguro propiedad',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  106 => 
  array (
    'ID_GrupoPresupuestal' => 108,
    'Nombre' => 'Capex',
    'Descripcion' => 'Capex',
    'activo' => true,
    'ID_UnidadOperativa' => 8,
  ),
  107 => 
  array (
    'ID_GrupoPresupuestal' => 109,
    'Nombre' => 'Gastos sistemas',
    'Descripcion' => 'gastos',
    'activo' => false,
    'ID_UnidadOperativa' => NULL,
  ),
  108 => 
  array (
    'ID_GrupoPresupuestal' => 110,
    'Nombre' => 'Papeleria ',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 6,
  ),
  109 => 
  array (
    'ID_GrupoPresupuestal' => 111,
    'Nombre' => 'Insumos de limpieza',
    'Descripcion' => 'insumos',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  110 => 
  array (
    'ID_GrupoPresupuestal' => 112,
    'Nombre' => 'Amenidades oficina',
    'Descripcion' => 'amenidades',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  111 => 
  array (
    'ID_GrupoPresupuestal' => 113,
    'Nombre' => 'Comisiones bancarias',
    'Descripcion' => 'Comisiones',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  112 => 
  array (
    'ID_GrupoPresupuestal' => 114,
    'Nombre' => 'Adara',
    'Descripcion' => 'Adara',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  113 => 
  array (
    'ID_GrupoPresupuestal' => 115,
    'Nombre' => 'Impuestos federales',
    'Descripcion' => 'Impuestos federales',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  114 => 
  array (
    'ID_GrupoPresupuestal' => 116,
    'Nombre' => 'Servicios oficina city32 L 14',
    'Descripcion' => 'servicios oficina',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  115 => 
  array (
    'ID_GrupoPresupuestal' => 117,
    'Nombre' => 'Cuota Mantto Oficina City32 L14',
    'Descripcion' => 'Mantenimiento',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  116 => 
  array (
    'ID_GrupoPresupuestal' => 118,
    'Nombre' => 'Estacionamiento oficina city 32 L14',
    'Descripcion' => 'estacionamiento',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  117 => 
  array (
    'ID_GrupoPresupuestal' => 119,
    'Nombre' => 'Rentas de oficina city32 L 14',
    'Descripcion' => 'rentas oficina',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  118 => 
  array (
    'ID_GrupoPresupuestal' => 120,
    'Nombre' => 'Software de construccion',
    'Descripcion' => 'construccion',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  119 => 
  array (
    'ID_GrupoPresupuestal' => 121,
    'Nombre' => 'Servicios de PLD',
    'Descripcion' => 'PLD',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  120 => 
  array (
    'ID_GrupoPresupuestal' => 122,
    'Nombre' => 'Celulares',
    'Descripcion' => 'celulare',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  121 => 
  array (
    'ID_GrupoPresupuestal' => 123,
    'Nombre' => 'Telefono e internet',
    'Descripcion' => 'Telefono e internet',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  122 => 
  array (
    'ID_GrupoPresupuestal' => 124,
    'Nombre' => 'ISN',
    'Descripcion' => 'ISN',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  123 => 
  array (
    'ID_GrupoPresupuestal' => 125,
    'Nombre' => 'Credito infonavit',
    'Descripcion' => 'credito infonavit',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  124 => 
  array (
    'ID_GrupoPresupuestal' => 126,
    'Nombre' => 'Infonavit',
    'Descripcion' => 'Infonavit',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  125 => 
  array (
    'ID_GrupoPresupuestal' => 127,
    'Nombre' => 'RCV',
    'Descripcion' => 'RCV',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  126 => 
  array (
    'ID_GrupoPresupuestal' => 128,
    'Nombre' => 'IMSS',
    'Descripcion' => 'IMSS',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  127 => 
  array (
    'ID_GrupoPresupuestal' => 129,
    'Nombre' => 'Saldos y salarios USA',
    'Descripcion' => 'Salarios USA',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  128 => 
  array (
    'ID_GrupoPresupuestal' => 130,
    'Nombre' => 'Sueldos y salasarios',
    'Descripcion' => 'sueldos',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  129 => 
  array (
    'ID_GrupoPresupuestal' => 131,
    'Nombre' => 'Renta equipo de City 32',
    'Descripcion' => 'E',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  130 => 
  array (
    'ID_GrupoPresupuestal' => 132,
    'Nombre' => 'Publicidad aeropuerto',
    'Descripcion' => 'publicidad',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  131 => 
  array (
    'ID_GrupoPresupuestal' => 133,
    'Nombre' => 'Gastos de plotter',
    'Descripcion' => 'Gastos de plotter',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  132 => 
  array (
    'ID_GrupoPresupuestal' => 134,
    'Nombre' => 'Papeleria',
    'Descripcion' => 'Papeleria',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  133 => 
  array (
    'ID_GrupoPresupuestal' => 135,
    'Nombre' => 'Capacitaciones fiscales',
    'Descripcion' => 'Capacitaciones fiscales',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  134 => 
  array (
    'ID_GrupoPresupuestal' => 136,
    'Nombre' => 'Capacitaciones de construccion',
    'Descripcion' => 'Capacitaciones',
    'activo' => true,
    'ID_UnidadOperativa' => 13,
  ),
  135 => 
  array (
    'ID_GrupoPresupuestal' => 137,
    'Nombre' => 'Intereses Jubi jubi',
    'Descripcion' => 'interes',
    'activo' => true,
    'ID_UnidadOperativa' => 1,
  ),
  136 => 
  array (
    'ID_GrupoPresupuestal' => 138,
    'Nombre' => 'Seguro Equipo de transporte',
    'Descripcion' => 'Seguros',
    'activo' => true,
    'ID_UnidadOperativa' => 9,
  ),
  137 => 
  array (
    'ID_GrupoPresupuestal' => 139,
    'Nombre' => 'Mantto Equipo de transporte',
    'Descripcion' => 'Mantenimiento',
    'activo' => true,
    'ID_UnidadOperativa' => 9,
  ),
  138 => 
  array (
    'ID_GrupoPresupuestal' => 140,
    'Nombre' => 'Combustible equipo de transporte Avion',
    'Descripcion' => 'combustible transporte avion',
    'activo' => true,
    'ID_UnidadOperativa' => 9,
  ),
  139 => 
  array (
    'ID_GrupoPresupuestal' => 141,
    'Nombre' => 'Combustible equipo de transporte',
    'Descripcion' => 'combustible transporte city32',
    'activo' => true,
    'ID_UnidadOperativa' => 9,
  ),
  140 => 
  array (
    'ID_GrupoPresupuestal' => 142,
    'Nombre' => 'Mobiliario y equipo',
    'Descripcion' => 'mobiliario',
    'activo' => true,
    'ID_UnidadOperativa' => 10,
  ),
  141 => 
  array (
    'ID_GrupoPresupuestal' => 143,
    'Nombre' => 'Equipo de computo',
    'Descripcion' => 'equipo',
    'activo' => true,
    'ID_UnidadOperativa' => 10,
  ),
  142 => 
  array (
    'ID_GrupoPresupuestal' => 144,
    'Nombre' => 'Servicios oficina city32 MBSP INVESTMENTS L13',
    'Descripcion' => 'Servicios oficina city32 MBSP INVESTMENTS L13',
    'activo' => true,
    'ID_UnidadOperativa' => 11,
  ),
  143 => 
  array (
    'ID_GrupoPresupuestal' => 145,
    'Nombre' => 'Cuota Mantto oficina city32 MBSP INVESTMENTS L13',
    'Descripcion' => 'Cuota Mantto oficina city32 MBSP INVESTMENTS L13',
    'activo' => true,
    'ID_UnidadOperativa' => 11,
  ),
  144 => 
  array (
    'ID_GrupoPresupuestal' => 146,
    'Nombre' => 'Renta de oficina city31 MBSP INVESTMENTS L13',
    'Descripcion' => 'Oficita L13 investments',
    'activo' => true,
    'ID_UnidadOperativa' => 11,
  ),
  145 => 
  array (
    'ID_GrupoPresupuestal' => 147,
    'Nombre' => 'Suscripcion de navegacion',
    'Descripcion' => 'Suscripcion de navegación',
    'activo' => true,
    'ID_UnidadOperativa' => 12,
  ),
  146 => 
  array (
    'ID_GrupoPresupuestal' => 148,
    'Nombre' => 'Entrenamiento pilotos',
    'Descripcion' => 'capacitacion',
    'activo' => true,
    'ID_UnidadOperativa' => 12,
  ),
  147 => 
  array (
    'ID_GrupoPresupuestal' => 149,
    'Nombre' => 'Mantenimiento del avion anual',
    'Descripcion' => 'mantenimiento avion',
    'activo' => true,
    'ID_UnidadOperativa' => 12,
  ),
  148 => 
  array (
    'ID_GrupoPresupuestal' => 150,
    'Nombre' => 'Seguro avion',
    'Descripcion' => 'Seguro avion',
    'activo' => true,
    'ID_UnidadOperativa' => 12,
  ),
  149 => 
  array (
    'ID_GrupoPresupuestal' => 151,
    'Nombre' => 'Renta Hangar',
    'Descripcion' => 'Hangar avion',
    'activo' => true,
    'ID_UnidadOperativa' => 12,
  ),
  150 => 
  array (
    'ID_GrupoPresupuestal' => 152,
    'Nombre' => 'Administracion',
    'Descripcion' => 'Administracion avion',
    'activo' => true,
    'ID_UnidadOperativa' => 12,
  ),
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
