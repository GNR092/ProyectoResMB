<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="reporte-presupuesto-main-div"
     class="p-6 bg-white rounded-xl shadow-md min-h-[400px]"
     x-data="reportePresupuestoComponent"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <!-- Pantalla 1: Menú Principal -->
    <div x-show="pantalla === 'menu'" x-cloak class="animate-fadeIn">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span class="text-blue-600">📊</span> Central de Reportes
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <button @click="irAPantalla('presupuesto')" class="flex flex-col items-center p-8 border-2 border-blue-100 rounded-2xl hover:border-blue-500 hover:bg-blue-50 transition-all group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">📉</div>
                <span class="font-bold text-gray-700 group-hover:text-blue-700">Presupuesto vs Ejecutado</span>
            </button>
            <button @click="irAPantalla('cuentas')" class="flex flex-col items-center p-8 border-2 border-green-100 rounded-2xl hover:border-green-500 hover:bg-green-50 transition-all group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🏦</div>
                <span class="font-bold text-gray-700 group-hover:text-green-700">Cuentas Bancarias</span>
            </button>
            <button @click="irAPantalla('completo')" class="flex flex-col items-center p-8 border-2 border-purple-100 rounded-2xl hover:border-purple-500 hover:bg-purple-50 transition-all group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">📋</div>
                <span class="font-bold text-gray-700 group-hover:text-purple-700">Reporte Completo</span>
            </button>
        </div>
    </div>

    <!-- Pantalla 2: Reporte Presupuesto vs Ejecutado -->
    <div x-show="pantalla === 'presupuesto'" x-cloak class="animate-fadeIn">
        <div class="flex items-center justify-between mb-6">
            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-blue-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
            <h2 class="text-xl font-bold text-gray-800">Presupuesto vs Ejecutado</h2>
        </div>

        <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                <select x-model="idRazonSocial" @change="idPlace = ''; departamentos = []" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 min-w-[200px] text-sm">
                    <option value="">Seleccione Razón Social</option>
                    <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                <select x-model="idPlace" @change="cargarComparativo()" :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 min-w-[200px] text-sm disabled:bg-gray-100">
                    <option value="">Seleccione Place</option>
                    <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Mes y año</label>
                <input type="month" x-model="mesAnio" @change="cargarComparativo()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 text-sm">
            </div>
            <div class="flex flex-col gap-1 self-center ml-auto">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="verGlobal" @change="cargarGlobal()" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ms-3 text-sm font-bold text-gray-700 uppercase tracking-tighter">🌍 Presupuesto Global</span>
                </label>
            </div>
        </div>

        <div x-show="departamentosOriginales.length > 0" class="mb-6 animate-fadeIn" x-cloak>
            <div class="flex flex-col gap-1 w-full md:w-1/2">
                <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Departamentos</label>
                <select x-ref="filtroDptos" multiple>
                    <template x-for="d in departamentosOriginales" :key="d.ID_Dpto">
                        <option :value="d.ID_Dpto" x-text="(verGlobal ? d.RazonSocialNombre + ' > ' + d.PlaceNombre + ' > ' : '') + d.Nombre"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="border border-gray-300 rounded-lg overflow-x-auto shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-800 text-white text-[10px] uppercase tracking-wider">
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
                    <tr x-show="cargando"><td colspan="6" class="px-4 py-12 text-center text-gray-500 italic">Cargando datos...</td></tr>
                    <tr x-show="!cargando && departamentos.length === 0"><td colspan="6" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el reporte.</td></tr>
                </tbody>

                <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                    <tbody class="border-t-4 border-gray-300">
                        <tr class="bg-gray-100 font-black text-sm">
                            <td class="px-6 py-2 text-gray-800 uppercase tracking-wider text-[10px]" x-text="grupoRS.nombre"></td>
                            <td class="px-4 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.asignado)"></td>
                            <td class="px-4 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.comprometido)"></td>
                            <td class="px-4 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.ejecutado)"></td>
                            <td class="px-4 py-2 text-right" :class="grupoRS.totales.disponible < 0 ? 'text-red-600' : 'text-green-600'" x-text="formatearMoneda(grupoRS.totales.disponible)"></td>
                            <td class="px-4 py-2 text-center" :class="getClaseSemaforo(grupoRS.totales.porcentaje)" x-text="grupoRS.totales.porcentaje + '%'"></td>
                        </tr>

                        <template x-for="dpto in grupoRS.departamentos" :key="dpto.ID_Dpto">
                            <template x-if="true">
                                <template x-for="(item, index) in [dpto, ...dpto.analisis]">
                                    <tr :class="index === 0 ? 'bg-blue-50 font-bold' : 'hover:bg-gray-50 border-b border-gray-100'">
                                        <td class="px-6 py-2" :class="index === 0 ? 'text-blue-900 text-xs' : 'pl-12 text-gray-700'">
                                            <span x-show="index === 0">🏢 </span>
                                            <span x-text="index === 0 ? (dpto.Nombre + ' (' + dpto.PlaceNombre + ')') : item.grupo"></span>
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.totales?.asignado) : formatearMoneda(item.asignado)"></td>
                                        <td class="px-4 py-2 text-right" :class="index === 0 ? 'text-gray-900' : 'text-orange-600 italic'" x-text="index === 0 ? formatearMoneda(dpto.totales?.comprometido) : formatearMoneda(item.comprometido)"></td>
                                        <td class="px-4 py-2 text-right" :class="index === 0 ? 'text-gray-900' : 'text-blue-700 font-semibold'" x-text="index === 0 ? formatearMoneda(dpto.totales?.ejecutado) : formatearMoneda(item.ejecutado)"></td>
                                        <td class="px-4 py-2 text-right font-bold" :class="index === 0 ? (dpto.totales?.disponible < 0 ? 'text-red-600' : 'text-green-600') : (item.disponible < 0 ? 'text-red-600' : 'text-green-700')" x-text="index === 0 ? formatearMoneda(dpto.totales?.disponible) : formatearMoneda(item.disponible)"></td>
                                        <td class="px-4 py-2 text-center" :class="index === 0 ? getClaseSemaforo(dpto.totales?.porcentaje) : getClaseSemaforo(item.porcentaje)" x-text="(index === 0 ? (dpto.totales?.porcentaje || 0) : item.porcentaje) + '%'"></td>
                                    </tr>
                                </template>
                            </template>
                        </template>
                    </tbody>
                </template>

                <tfoot x-show="!cargando && departamentos.length > 0" class="bg-gray-800 text-white">
                    <tr>
                        <td class="px-6 py-4 font-black uppercase tracking-widest text-right">Total:</td>
                        <td class="px-4 py-4 text-right font-bold text-lg" x-text="formatearMoneda(totalesGenerales?.asignado)"></td>
                        <td class="px-4 py-4 text-right font-bold text-lg text-orange-300" x-text="formatearMoneda(totalesGenerales?.comprometido)"></td>
                        <td class="px-4 py-4 text-right font-bold text-lg text-blue-300" x-text="formatearMoneda(totalesGenerales?.ejecutado)"></td>
                        <td class="px-4 py-4 text-right font-bold text-lg" :class="totalesGenerales?.disponible < 0 ? 'text-red-400' : 'text-green-400'" x-text="formatearMoneda(totalesGenerales?.disponible)"></td>
                        <td class="px-4 py-4 text-center font-bold text-lg" :class="totalesGenerales?.porcentaje >= 100 ? 'text-red-400' : 'text-green-400'" x-text="(totalesGenerales?.porcentaje || 0) + '%'"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Pantalla 3: Reporte Cuentas Bancarias -->
    <div x-show="pantalla === 'cuentas'" x-cloak class="animate-fadeIn">
        <div class="flex items-center justify-between mb-6">
            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-green-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
            <h2 class="text-xl font-bold text-gray-800">Reporte de Cuentas Bancarias</h2>
        </div>

        <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                <select x-model="idRazonSocial" @change="idPlace = ''; departamentosBancos = []" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-green-300 min-w-[200px] text-sm">
                    <option value="">Seleccione Razón Social</option>
                    <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                <select x-model="idPlace" @change="cargarComparativoBancos()" :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-green-300 min-w-[200px] text-sm disabled:bg-gray-100">
                    <option value="">Seleccione Place</option>
                    <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Mes y año</label>
                <input type="month" x-model="mesAnio" @change="cargarComparativoBancos()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-green-300 text-sm">
            </div>
            <div class="flex flex-col gap-1 self-center ml-auto">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="verGlobal" @change="cargarGlobal()" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    <span class="ms-3 text-sm font-bold text-gray-700 uppercase tracking-tighter">🌍 Global</span>
                </label>
            </div>
        </div>

        <div class="border border-gray-300 rounded-lg overflow-x-auto shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-800 text-white text-[10px] uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Departamento / Banco</th>
                        <th class="px-4 py-3 text-right">Saldo Inicial</th>
                        <th class="px-4 py-3 text-right">Saldo Final</th>
                        <th class="px-4 py-3 text-right">Diferencia (Uso)</th>
                        <th class="px-4 py-3 text-center">% Variación</th>
                    </tr>
                </thead>

                <tbody x-show="cargando || departamentosBancos.length === 0">
                    <tr x-show="cargando"><td colspan="5" class="px-4 py-12 text-center text-gray-500 italic">Cargando datos bancarios...</td></tr>
                    <tr x-show="!cargando && departamentosBancos.length === 0"><td colspan="5" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el reporte de bancos.</td></tr>
                </tbody>

                <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                    <tbody class="border-t-4 border-gray-300">
                        <tr class="bg-gray-100 font-black text-sm">
                            <td class="px-6 py-2 text-gray-800 uppercase tracking-wider text-[10px]" x-text="grupoRS.nombre"></td>
                            <td class="px-4 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.inicial)"></td>
                            <td class="px-4 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.final)"></td>
                            <td class="px-4 py-2 text-right" :class="grupoRS.totales.final < grupoRS.totales.inicial ? 'text-red-600' : 'text-green-600'" x-text="formatearMoneda(grupoRS.totales.usado)"></td>
                            <td class="px-4 py-2 text-center font-bold" :class="grupoRS.totales.final < grupoRS.totales.inicial ? 'text-red-600' : 'text-green-600'" x-text="grupoRS.totales.porcentaje + '%'"></td>
                        </tr>

                        <template x-for="dpto in grupoRS.departamentos" :key="dpto.ID_Dpto">
                            <template x-for="(item, index) in [dpto, ...dpto.analisis]">
                                <tr :class="index === 0 ? 'bg-green-50 font-bold' : 'hover:bg-gray-50 border-b border-gray-100'">
                                    <td class="px-6 py-2" :class="index === 0 ? 'text-green-900 text-xs' : 'pl-12 text-gray-700'">
                                        <span x-show="index === 0">🏢 </span>
                                        <span x-text="index === 0 ? (dpto.Nombre + ' (' + dpto.PlaceNombre + ')') : item.banco"></span>
                                        <div x-show="index !== 0" class="text-[10px] text-gray-400" x-text="'CLABE: ' + item.clabe"></div>
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.totales?.inicial) : formatearMoneda(item.inicial)"></td>
                                    <td class="px-4 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.totales?.final) : formatearMoneda(item.final)"></td>
                                    <td class="px-4 py-2 text-right" :class="(index === 0 ? dpto.totales?.final < dpto.totales?.inicial : item.final < item.inicial) ? 'text-red-600' : 'text-green-600'" x-text="index === 0 ? formatearMoneda(dpto.totales?.usado) : formatearMoneda(item.usado)"></td>
                                    <td class="px-4 py-2 text-center font-bold" :class="(index === 0 ? dpto.totales?.final < dpto.totales?.inicial : item.final < item.inicial) ? 'text-red-600' : 'text-green-600'" x-text="(index === 0 ? (dpto.totales?.porcentaje || 0) : item.porcentaje) + '%'"></td>
                                </tr>
                            </template>
                        </template>
                    </tbody>
                </template>
            </table>
        </div>
    </div>

    <!-- Pantalla 4: Reporte Completo Consolidado -->
    <div x-show="pantalla === 'completo'" x-cloak class="animate-fadeIn">
        <div class="flex items-center justify-between mb-6">
            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-purple-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
            <h2 class="text-xl font-bold text-gray-800">Reporte Consolidado Maestro</h2>
        </div>

        <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                <select x-model="idRazonSocial" @change="idPlace = ''; departamentosCompleto = []" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-purple-300 min-w-[200px] text-sm">
                    <option value="">Seleccione Razón Social</option>
                    <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                <select x-model="idPlace" @change="cargarReporteCompleto()" :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-purple-300 min-w-[200px] text-sm disabled:bg-gray-100">
                    <option value="">Seleccione Place</option>
                    <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Mes y año</label>
                <input type="month" x-model="mesAnio" @change="cargarReporteCompleto()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-purple-300 text-sm">
            </div>
            <div class="flex flex-col gap-1 self-center ml-auto">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="verGlobal" @change="cargarGlobal()" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    <span class="ms-3 text-sm font-bold text-gray-700 uppercase tracking-tighter">🌍 Global</span>
                </label>
            </div>
        </div>

        <div class="border border-gray-300 rounded-lg overflow-x-auto shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-800 text-white text-[9px] uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Departamento / Grupo</th>
                        <th class="px-2 py-3 text-right">P. Asignado</th>
                        <th class="px-2 py-3 text-right">P. Gastado</th>
                        <th class="px-2 py-3 text-right">P. Disponible</th>
                        <th class="px-2 py-3 text-right">B. Inicial</th>
                        <th class="px-2 py-3 text-right">B. Final</th>
                        <th class="px-2 py-3 text-right">B. Diferencia</th>
                    </tr>
                </thead>

                <tbody x-show="cargando || departamentosCompleto.length === 0">
                    <tr x-show="cargando"><td colspan="7" class="px-4 py-12 text-center text-gray-500 italic">Consolidando información financiera...</td></tr>
                    <tr x-show="!cargando && departamentosCompleto.length === 0"><td colspan="7" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el consolidado.</td></tr>
                </tbody>

                <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                    <tbody class="border-t-4 border-gray-300">
                        <tr class="bg-gray-100 font-black text-sm">
                            <td class="px-6 py-2 text-gray-800 uppercase tracking-wider text-[10px]" x-text="grupoRS.nombre"></td>
                            <td class="px-2 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.pAsignado)"></td>
                            <td class="px-2 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.pGastado)"></td>
                            <td class="px-2 py-2 text-right" :class="grupoRS.totales.pDisponible < 0 ? 'text-red-600' : 'text-green-600'" x-text="formatearMoneda(grupoRS.totales.pDisponible)"></td>
                            <td class="px-2 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.bInicial)"></td>
                            <td class="px-2 py-2 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.bFinal)"></td>
                            <td class="px-2 py-2 text-right" :class="grupoRS.totales.bFinal < grupoRS.totales.bInicial ? 'text-red-600' : 'text-green-600'" x-text="formatearMoneda(grupoRS.totales.bInicial - grupoRS.totales.bFinal)"></td>
                        </tr>

                        <template x-for="dpto in grupoRS.departamentos" :key="dpto.ID_Dpto">
                            <template x-if="true">
                                <template x-for="(item, index) in [dpto, ...dpto.grupos]">
                                    <tr :class="index === 0 ? 'bg-blue-50 font-bold' : 'hover:bg-gray-50 border-b border-gray-100'">
                                        <td class="px-6 py-2" :class="index === 0 ? 'text-blue-900 text-xs' : 'pl-12 text-gray-700'">
                                            <span x-show="index === 0">🏢 </span>
                                            <span x-text="index === 0 ? (dpto.Nombre + ' (' + dpto.PlaceNombre + ')') : item.nombre"></span>
                                        </td>
                                        <td class="px-2 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.presupuesto?.asignado) : formatearMoneda(item.asignado)"></td>
                                        <td class="px-2 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.presupuesto?.gastado) : formatearMoneda(item.gastado)"></td>
                                        <td class="px-2 py-2 text-right font-bold" :class="(index === 0 ? dpto.presupuesto?.disponible < 0 : item.disponible < 0) ? 'text-red-600' : 'text-green-600'" x-text="index === 0 ? formatearMoneda(dpto.presupuesto?.disponible) : formatearMoneda(item.disponible)"></td>
                                        <td class="px-2 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.bancos?.inicial) : ''"></td>
                                        <td class="px-2 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(dpto.bancos?.final) : ''"></td>
                                        <td class="px-2 py-2 text-right font-bold" :class="index === 0 ? (dpto.bancos?.final < dpto.bancos?.inicial ? 'text-red-600' : 'text-green-600') : ''" x-text="index === 0 ? formatearMoneda(dpto.bancos?.uso) : ''"></td>
                                    </tr>
                                </template>
                            </template>
                        </template>
                    </tbody>
                </template>
            </table>
        </div>
    </div>
</div>

<style>
    .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    [x-cloak] { display: none !important; }
</style>
