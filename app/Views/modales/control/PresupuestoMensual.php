<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="presupuesto-mensual-main-div"
     class="p-6 bg-white rounded-xl shadow-md"
     x-data="presupuestoEscalonado"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <div class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div class="flex flex-col gap-1">
            <label for="pm-razon-social" class="text-sm font-medium text-gray-700">Razón Social</label>
            <select id="pm-razon-social"
                    x-model="idRazonSocial"
                    @change="actualizarChoicesPlace()"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300 min-w-[200px]">
                <option value="">Seleccione Razón Social</option>
                <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial">
                    <option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option>
                </template>
            </select>
        </div>

        <div class="flex flex-col gap-1 min-w-[300px]">
            <label for="pm-place" class="text-sm font-medium text-gray-700">Complejos</label>
            <select id="pm-place"
                    x-ref="filtroPlace"
                    multiple
                    class="disabled:bg-gray-100 disabled:cursor-not-allowed">
                <!-- Las opciones se inyectan dinámicamente vía JS -->
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label for="pm-mes-anio" class="text-sm font-medium text-gray-700">Mes y año</label>
            <input type="month"
                   id="pm-mes-anio"
                   x-model="mesAnio"
                   @change="cargarEstructura()"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
            
            <button @click="copiarAnterior()"
                    x-show="!cargando && departamentos.length > 0 && !bloqueadoPorRevision"
                    class="mt-1 px-1 py-0.5 border border-orange-500 text-orange-600 hover:bg-orange-50 text-[9px] font-bold uppercase rounded transition-colors w-full text-center"
                    title="Copiar montos del mes anterior">
                Copiar Mes Anterior
            </button>
        </div>

        <div class="flex flex-col gap-1 self-end mb-0.5" x-show="departamentos.length > 0" x-cloak>
            <div class="flex items-center gap-2">
                <button @click="exportarAsignacionExcel()" 
                        class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar
                </button>

                <div x-show="<?= (session('departamento_usuario') === 'Dirección') ? 'true' : 'false' ?> || usoCopia">
                    <button @click="guardarMasivo()"
                            :disabled="guardando || bloqueadoPorRevision"
                            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2">

                        <svg x-show="guardando" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span x-text="guardando ? 'Guardando...' : 'Guardar Presupuestos'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="!cargando && departamentosOriginales.length > 0" class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-white p-4 rounded-lg border border-gray-200 shadow-sm" x-cloak>
        <div class="flex flex-col gap-1 w-full md:w-[45%]">
            <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Departamentos De Operación</label>
            <select x-ref="filtroUnidad" multiple>
                <template x-for="uni in departamentosOriginales" :key="uni.ID_UnidadOperativa">
                    <option :value="uni.ID_UnidadOperativa" x-text="uni.Nombre"></option>
                </template>
            </select>
        </div>
        <div class="flex flex-col gap-1 w-full md:w-[40%]">
            <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Partidas </label>
            <select x-ref="filtroGrupo" multiple>
                <template x-for="g in gruposUnicos" :key="g.id">
                    <option :value="g.id" x-text="g.nombre"></option>
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
        <h3 class="text-lg font-semibold text-gray-800 bg-blue-50 px-4 py-2 rounded-lg border border-blue-200 shadow-sm flex items-center gap-2">
            Presupuesto Consecuente:
            <span class="text-blue-700 font-bold text-xl" x-text="formatearMoneda(sumaTotal)">$0.00</span>
        </h3>
    </div>

    <div class="border border-gray-300 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-blue-50">
            <tr>
                <th class="w-2/3 px-6 py-3 border-b border-gray-300 text-left font-semibold text-gray-700">Departamento De Operación / Partida Presupuestal</th>
                <th class="w-1/3 px-6 py-3 border-b border-gray-300 text-right font-semibold text-gray-700 border-l border-l-gray-300">Monto Asignado</th>
            </tr>
            </thead>

            <tbody x-show="cargando || departamentos.length === 0">
            <tr x-show="cargando">
                <td colspan="2" class="px-4 py-12 text-center text-gray-500">
                    <span class="inline-block animate-pulse">Cargando estructura financiera...</span>
                </td>
            </tr>
            <tr x-show="!cargando && departamentos.length === 0">
                <td colspan="2" class="px-4 py-12 text-center text-gray-400">
                    Seleccione una Razón Social, un Place y una Fecha para visualizar los departamentos de operación.
                </td>
            </tr>
            </tbody>

            <template x-for="uni in departamentos" :key="uni.ID_UnidadOperativa">
                <tbody x-show="!cargando && departamentos.length > 0">

                <tr class="bg-gray-100 border-y border-gray-300">
                    <td class="px-6 py-3 font-bold text-gray-800">
                        <span class="text-blue-600 mr-2">⚙️</span>
                        <span x-text="uni.Nombre"></span>
                    </td>
                    <td class="px-6 py-3 border-l border-l-gray-300 text-right font-bold text-blue-700">
                        <span x-text="'Total: ' + formatearMoneda(getDptoTotal(uni))"></span>
                    </td>
                </tr>

                <template x-for="grupo in uni.grupos" :key="grupo.ID_GrupoPresupuestal">
                    <tr class="bg-white hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-2 border-b border-gray-200 text-gray-600 pl-12" x-text="grupo.Nombre"></td>
                        <td class="px-6 py-2 border-b border-gray-200 border-l border-l-gray-200">
                            <div class="flex items-center justify-end gap-1">
                                <span class="text-gray-500 font-medium">$</span>
                                <input type="number"
                                       min="0" step="0.01"
                                       x-model="grupo.Monto_Asignado"
                                       :disabled="bloqueadoPorRevision || <?= (session('departamento_usuario') !== 'Dirección') ? 'true' : 'false' ?>"
                                       :class="(bloqueadoPorRevision || <?= (session('departamento_usuario') !== 'Dirección') ? 'true' : 'false' ?>) ? 'bg-gray-100 cursor-not-allowed' : ''"
                                       placeholder="0.00"
                                       class="w-32 px-2 py-1.5 border border-gray-300 rounded-md text-right focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent">
                            </div>
                        </td>
                    </tr>
                </template>

                </tbody>
            </template>
        </table>
    </div>

    <div class="mt-4 p-4 bg-amber-50 border-l-4 border-amber-400 rounded" x-show="bloqueadoPorRevision" x-cloak>
        <div class="flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 15.667c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="text-sm font-bold text-amber-800">Atención: Edición Bloqueada</p>
                <p class="text-xs text-amber-700">Existe una solicitud de cambio pendiente para este periodo y complejo(s). Debe esperar a que sea procesada para realizar nuevos ajustes.</p>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mt-6">
        <p class="text-sm font-medium px-2 py-1 rounded"
           x-show="mensaje !== ''"
           :class="error ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
           x-text="mensaje"></p>

        <div x-show="mensaje === ''"></div>
    </div>

</div>

</div>
