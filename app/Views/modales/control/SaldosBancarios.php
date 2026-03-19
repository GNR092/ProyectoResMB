<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="saldos-bancarios-main-div"
     class="p-6 bg-white rounded-xl shadow-md"
     x-data="saldosBancariosComponent"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <!-- Filtros Superiores -->
    <div class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
        <div class="flex flex-col gap-1">
            <label for="sb-razon-social" class="text-sm font-medium text-gray-700">Razón Social</label>
            <select id="sb-razon-social"
                    x-model="idRazonSocial"
                    @change="cargarEstructura()"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300 min-w-[200px]">
                <option value="">Seleccione Razón Social</option>
                <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial">
                    <option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option>
                </template>
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label for="sb-mes-anio" class="text-sm font-medium text-gray-700">Mes y año</label>
            <input type="month"
                   id="sb-mes-anio"
                   x-model="mesAnio"
                   @change="cargarEstructura()"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300">

            <button @click="copiarAnterior()"
                    x-show="!cargando && razonesData.length > 0 && !bloqueadoPorRevision"
                    class="mt-1 px-1 py-0.5 border border-orange-500 text-orange-600 hover:bg-orange-50 text-[9px] font-bold uppercase rounded transition-colors w-full text-center"
                    title="Copiar saldos finales del mes anterior como iniciales de este mes">
                Copiar Mes Anterior
            </button>
        </div>
    </div>

    <!-- Tabla de Saldos -->
    <div class="border border-gray-300 rounded-lg overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-blue-50">
            <tr>
                <th class="px-6 py-3 border-b border-gray-300 text-left font-semibold text-gray-700">Razón Social / Cuenta Bancaria</th>
                <th class="px-6 py-3 border-b border-gray-300 text-right font-semibold text-gray-700 border-l border-l-gray-300">Saldo Inicial</th>
                <th class="px-6 py-3 border-b border-gray-300 text-right font-semibold text-gray-700 border-l border-l-gray-300">Saldo Final</th>
            </tr>
            </thead>

            <tbody x-show="cargando || razonesData.length === 0">
            <tr x-show="cargando">
                <td colspan="3" class="px-4 py-12 text-center text-gray-500">
                    <span class="inline-block animate-pulse">Cargando datos de bancos...</span>
                </td>
            </tr>
            <tr x-show="!cargando && razonesData.length === 0">
                <td colspan="3" class="px-4 py-12 text-center text-gray-400">
                    Seleccione una Razón Social y fecha para visualizar las cuentas bancarias.
                </td>
            </tr>
            </tbody>

            <template x-for="rs in razonesData" :key="rs.ID_RazonSocial">
                <tbody x-show="!cargando && razonesData.length > 0">
                    <!-- Cabecera de Razón Social -->
                    <tr class="bg-gray-100 border-y border-gray-300">
                        <td colspan="3" class="px-6 py-2 font-bold text-gray-800">
                            <span class="text-blue-600 mr-2">🏢</span>
                            <span x-text="rs.Nombre"></span>
                        </td>
                    </tr>

                    <!-- Filas de Bancos -->
                    <template x-for="banco in rs.bancos" :key="banco.ID_BancoDpto">
                        <tr class="bg-white hover:bg-gray-50 transition-colors duration-150 border-b border-gray-200">
                            <td class="px-6 py-3 pl-12">
                                <div class="font-medium text-gray-800" x-text="banco.Banco"></div>
                                <div class="text-xs text-gray-500" x-text="'Clabe: ' + banco.Clabe"></div>
                            </td>
                            <!-- Input Saldo Inicial -->
                            <td class="px-6 py-3 border-l border-l-gray-200">
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-gray-400">$</span>
                                    <input type="number" step="0.01"
                                           x-model="banco.saldo_inicial"
                                           :disabled="bloqueadoPorRevision"
                                           :class="bloqueadoPorRevision ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-32 px-2 py-1.5 border border-gray-300 rounded text-right focus:ring-2 focus:ring-blue-400 outline-none">
                                </div>
                            </td>
                            <!-- Input Saldo Final -->
                            <td class="px-6 py-3 border-l border-l-gray-200">
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-gray-400">$</span>
                                    <input type="number" step="0.01"
                                           x-model="banco.saldo_final"
                                           :disabled="bloqueadoPorRevision"
                                           :class="bloqueadoPorRevision ? 'bg-gray-100 cursor-not-allowed' : ''"
                                           class="w-32 px-2 py-1.5 border border-gray-300 rounded text-right focus:ring-2 focus:ring-blue-400 outline-none">
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
                <p class="text-xs text-amber-700">Existe una solicitud de cambio pendiente para este periodo y razón social. Debe esperar a que sea procesada para realizar nuevos ajustes.</p>
            </div>
        </div>
    </div>

    <!-- Botón de Guardado -->
    <div class="flex items-center justify-between mt-6">
        <p class="text-sm font-medium px-2 py-1 rounded"
           x-show="mensaje !== ''"
           :class="error ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600'"
           x-text="mensaje"></p>

        <div x-show="mensaje === ''"></div>

        <button x-show="razonesData.length > 0"
                @click="guardarSaldos()"
                :disabled="guardando || bloqueadoPorRevision"
                class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2">
            
            <svg x-show="guardando" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <span x-text="guardando ? 'Guardando...' : 'Guardar Saldos'"></span>
        </button>
    </div>

</div>
