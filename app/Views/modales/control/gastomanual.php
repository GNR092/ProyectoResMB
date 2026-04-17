<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="gasto-manual-main-div"
     class="p-6 bg-white rounded-xl shadow-md"
     x-data="gastoManualComponent"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <div class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div class="flex flex-col gap-1">
            <label for="gm-razon-social" class="text-sm font-medium text-gray-700">Razón Social</label>
            <select id="gm-razon-social"
                    x-model="idRazonSocial"
                    @change="actualizarChoicesPlace()"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-amber-300 min-w-[200px]">
                <option value="">Seleccione Razón Social</option>
                <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial">
                    <option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option>
                </template>
            </select>
        </div>

        <div class="flex flex-col gap-1 min-w-[300px]">
            <label for="gm-place" class="text-sm font-medium text-gray-700">Complejos</label>
            <select id="gm-place"
                    x-ref="filtroPlace"
                    multiple
                    class="disabled:bg-gray-100 disabled:cursor-not-allowed">
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label for="gm-mes-anio" class="text-sm font-medium text-gray-700">Mes y año</label>
            <input type="month"
                   id="gm-mes-anio"
                   x-model="mesAnio"
                   @change="cargarEstructura()"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-amber-300">
        </div>

        <div class="flex flex-col gap-1 self-end mb-0.5" x-show="departamentos.length > 0" x-cloak>
            <div class="flex items-center gap-2">
                <button @click="guardarGastos()"
                        :disabled="guardando"
                        class="px-5 py-2 bg-amber-600 text-white font-bold rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm flex items-center gap-2 text-xs">
                    <svg x-show="guardando" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="guardando ? 'Guardando...' : 'Registrar Gastos Manuales'"></span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="!cargando && departamentosOriginales.length > 0" class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-white p-4 rounded-lg border border-gray-200 shadow-sm" x-cloak>
        <div class="flex flex-col gap-1 w-full md:w-[45%]">
            <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Departamentos</label>
            <select x-ref="filtroUnidad" multiple>
                <template x-for="uni in departamentosOriginales" :key="uni.ID_UnidadOperativa">
                    <option :value="uni.ID_UnidadOperativa" x-text="uni.Nombre"></option>
                </template>
            </select>
        </div>
        <div class="flex flex-col gap-1 self-end mb-1">
            <button @click="limpiarFiltros()" 
                    class="px-4 py-2 text-xs font-bold text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors uppercase">
                Limpiar Filtros
            </button>
        </div>
    </div>

    <div class="flex justify-end mb-4" x-show="!cargando && departamentos.length > 0" x-cloak>
        <h3 class="text-lg font-semibold text-gray-800 bg-amber-50 px-4 py-2 rounded-lg border border-amber-200 shadow-sm flex items-center gap-2">
            Total Gastos Manuales del Mes:
            <span class="text-amber-700 font-bold text-xl" x-text="formatearMoneda(sumaTotal)">$0.00</span>
        </h3>
    </div>

    <div class="border border-gray-300 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-amber-50">
            <tr>
                <th class="w-1/2 px-6 py-3 border-b border-gray-300 text-left font-semibold text-gray-700 uppercase tracking-tighter">Departamento / Partida de Gasto Manual</th>
                <th class="w-1/4 px-6 py-3 border-b border-gray-300 text-right font-semibold text-gray-700 border-l border-l-gray-300 uppercase tracking-tighter">Importe Ejecutado (Actual)</th>
                <th class="w-1/4 px-6 py-3 border-b border-gray-300 text-right font-semibold text-gray-700 border-l border-l-gray-300 uppercase tracking-tighter bg-amber-100/50">Nuevos Gastos (+)</th>
            </tr>
            </thead>

            <tbody x-show="cargando || departamentos.length === 0">
            <tr x-show="cargando">
                <td colspan="2" class="px-4 py-12 text-center text-gray-500">
                    <span class="inline-block animate-pulse">Cargando estructura de gastos...</span>
                </td>
            </tr>
            <tr x-show="!cargando && departamentos.length === 0">
                <td colspan="2" class="px-4 py-12 text-center text-gray-400">
                    Seleccione una Razón Social, un Place y una Fecha para visualizar los departamentos.
                </td>
            </tr>
            </tbody>

            <template x-for="uni in departamentos" :key="uni.ID_UnidadOperativa">
                <tbody x-show="!cargando && departamentos.length > 0 && uni.grupos.some(g => (g.es_manual == 1 || g.es_manual === true || g.es_manual === 't'))">

                <tr class="bg-gray-100 border-y border-gray-300">
                    <td class="px-6 py-3 font-bold text-gray-800">
                        <span class="text-amber-600 mr-2">⚙️</span>
                        <span x-text="uni.Nombre"></span>
                    </td>
                    <td class="px-6 py-3 border-l border-l-gray-300 text-right font-bold text-amber-700">
                        <span x-text="'Subtotal: ' + formatearMoneda(getDptoTotal(uni))"></span>
                    </td>
                </tr>

                <template x-for="grupo in uni.grupos" :key="grupo.ID_GrupoPresupuestal">
                    <template x-if="grupo.es_manual == 1 || grupo.es_manual === true || grupo.es_manual === 't'">
                        <tr class="bg-white hover:bg-amber-50/30 transition-colors duration-150">
                            <td class="px-6 py-2 border-b border-gray-200 text-gray-600 pl-12">
                                <span x-text="grupo.Nombre"></span>
                                <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    MANUAL
                                </span>
                            </td>
                            <td class="px-6 py-2 border-b border-gray-200 border-l border-l-gray-200 text-right font-medium text-gray-500 bg-gray-50/50">
                                <span x-text="formatearMoneda(grupo.Monto_Ejecutado)"></span>
                            </td>
                            <td class="px-6 py-2 border-b border-gray-200 border-l border-l-gray-200 bg-amber-50/20">
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-amber-600 font-bold">+ $</span>
                                    <input type="number"
                                           min="0" step="0.01"
                                           x-model="grupo.monto_ingresado"
                                           placeholder="0.00"
                                           class="w-32 px-2 py-1.5 border border-amber-300 rounded-md text-right focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent font-bold text-amber-900">
                                </div>
                            </td>
                        </tr>
                    </template>
                </template>

                </tbody>
            </template>
        </table>
    </div>

    <div class="flex items-center justify-between mt-6">
        <p class="text-sm font-medium px-2 py-1 rounded"
           x-show="mensaje !== ''"
           :class="error ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
           x-text="mensaje"></p>

        <div class="flex gap-2" x-show="departamentos.length > 0" x-cloak>
            <button @click="guardarGastos()"
                    :disabled="guardando"
                    class="px-5 py-2 bg-amber-600 text-white font-bold rounded-lg hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm flex items-center gap-2 text-xs">
                <svg x-show="guardando" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="guardando ? 'Guardando...' : 'Registrar Gastos Manuales'"></span>
            </button>
        </div>
    </div>

</div>
