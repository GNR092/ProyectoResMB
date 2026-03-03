<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="reporte-presupuesto-main-div"
     class="p-6 bg-white rounded-xl shadow-md"
     x-data="reportePresupuestoComponent"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <!-- Título y Filtros -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Reporte de Presupuesto vs Ejecutado</h2>
        
        <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div class="flex flex-col gap-1">
                <label for="rp-razon-social" class="text-sm font-medium text-gray-700">Razón Social</label>
                <select id="rp-razon-social"
                        x-model="idRazonSocial"
                        @change="idPlace = ''; departamentos = []"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300 min-w-[200px]">
                    <option value="">Seleccione Razón Social</option>
                    <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial">
                        <option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option>
                    </template>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label for="rp-place" class="text-sm font-medium text-gray-700">Place</label>
                <select id="rp-place"
                        x-model="idPlace"
                        @change="cargarComparativo()"
                        :disabled="!idRazonSocial"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300 min-w-[200px] disabled:bg-gray-100">
                    <option value="">Seleccione Place</option>
                    <template x-for="place in placesFiltrados" :key="place.ID_Place">
                        <option :value="place.ID_Place" x-text="place.Nombre_Corto"></option>
                    </template>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label for="rp-mes-anio" class="text-sm font-medium text-gray-700">Mes y año</label>
                <input type="month"
                       id="rp-mes-anio"
                       x-model="mesAnio"
                       @change="cargarComparativo()"
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
            </div>
        </div>
    </div>

    <!-- Tabla de Reporte -->
    <div class="border border-gray-300 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-800 text-white text-xs uppercase">
            <tr>
                <th class="px-6 py-3 text-left">Departamento / Grupo</th>
                <th class="px-4 py-3 text-right">Presp. Asignado</th>
                <th class="px-4 py-3 text-right">Comprometido</th>
                <th class="px-4 py-3 text-right">Ejecutado</th>
                <th class="px-4 py-3 text-right">Disponible</th>
                <th class="px-4 py-3 text-center">% Ejecución</th>
            </tr>
            </thead>

            <tbody x-show="cargando || departamentos.length === 0">
            <tr x-show="cargando">
                <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                    <span class="inline-block animate-pulse">Generando reporte comparativo...</span>
                </td>
            </tr>
            <tr x-show="!cargando && departamentos.length === 0">
                <td colspan="6" class="px-4 py-12 text-center text-gray-400 font-medium">
                    Seleccione filtros para visualizar el análisis de presupuesto.
                </td>
            </tr>
            </tbody>

            <template x-for="dpto in departamentos" :key="dpto.ID_Dpto">
                <tbody class="border-b border-gray-300">
                    <!-- Cabecera de Departamento -->
                    <tr class="bg-blue-50">
                        <td colspan="6" class="px-6 py-2 font-bold text-blue-900 border-b border-blue-100">
                            🏢 <span x-text="dpto.Nombre"></span>
                        </td>
                    </tr>

                    <!-- Filas de Análisis por Grupo -->
                    <template x-for="item in dpto.analisis" :key="item.grupo">
                        <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100">
                            <td class="px-6 py-3 pl-10 text-gray-700 font-medium" x-text="item.grupo"></td>
                            <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(item.asignado)"></td>
                            <td class="px-4 py-3 text-right text-orange-600 italic" x-text="formatearMoneda(item.comprometido)"></td>
                            <td class="px-4 py-3 text-right text-blue-700 font-semibold" x-text="formatearMoneda(item.ejecutado)"></td>
                            <td class="px-4 py-3 text-right font-bold" 
                                :class="item.disponible < 0 ? 'text-red-600' : 'text-green-700'"
                                x-text="formatearMoneda(item.disponible)"></td>
                            <td class="px-4 py-3 text-center">
                                <span :class="getClaseSemaforo(item.porcentaje)" x-text="item.porcentaje + '%'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </template>
        </table>
    </div>

    <!-- Leyenda -->
    <div class="mt-4 flex gap-6 text-xs text-gray-500">
        <div class="flex items-center gap-1"><span class="w-3 h-3 bg-green-600 rounded-full"></span> Saludable (< 80%)</div>
        <div class="flex items-center gap-1"><span class="w-3 h-3 bg-orange-600 rounded-full"></span> Por agotar (80% - 99%)</div>
        <div class="flex items-center gap-1"><span class="w-3 h-3 bg-red-600 rounded-full"></span> Excedido (>= 100%)</div>
    </div>

</div>
