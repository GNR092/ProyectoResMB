<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);

$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = base_url("icons/icons.svg?v=$version");
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button @click="irAPantalla('presupuesto')" class="flex flex-col items-center p-4 border-2 border-blue-100 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all group">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-blue-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#PresupuestoVsEjecutado"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-blue-700 text-xs">Presupuesto vs Ejecutado</span>
            </button>
            <button @click="irAPantalla('cuentas')" class="flex flex-col items-center p-4 border-2 border-green-100 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all group">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-green-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#CuentasBancarias"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-green-700 text-xs">Cuentas Bancarias</span>
            </button>
            <button @click="irAPantalla('completo')" class="flex flex-col items-center p-4 border-2 border-purple-100 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all group">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-purple-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReporteCompleto"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-purple-700 text-xs">Reporte Completo</span>
            </button>
            <button @click="irAPantalla('proveedores')" class="flex flex-col items-center p-4 border-2 border-orange-100 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all group">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-orange-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ListaProveedores"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-orange-700 text-xs">Lista De Proveedores</span>
            </button>
            <button @click="irAPantalla('compras')" class="flex flex-col items-center p-4 border-2 border-red-100 rounded-xl hover:border-red-500 hover:bg-red-50 transition-all group">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-red-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReporteCompras"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-red-700 text-xs">Reporte De Compras</span>
            </button>
            <button @click="irAPantalla('movimientos')" class="flex flex-col items-center p-4 border-2 border-teal-100 rounded-xl hover:border-teal-500 hover:bg-teal-50 transition-all group">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-teal-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#MovimientosProveedor"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-teal-700 text-xs">Movimientos De Proveedor</span>
            </button>
        </div>
    </div>

    <!-- Pantalla 5: Lista de Proveedores -->
    <template x-if="pantalla === 'proveedores'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-orange-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <h2 class="text-xl font-bold text-gray-800">Lista de Proveedores</h2>
            </div>
            <div class="p-8 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 italic">
                Contenido de Lista de Proveedores próximamente...
            </div>
        </div>
    </template>

    <!-- Pantalla 6: Reporte de Compras -->
    <template x-if="pantalla === 'compras'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-red-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <h2 class="text-xl font-bold text-gray-800">Reporte de Compras</h2>
            </div>
            <div class="p-8 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 italic">
                Contenido de Reporte de Compras próximamente...
            </div>
        </div>
    </template>

    <!-- Pantalla 7: Movimientos de Proveedor -->
    <template x-if="pantalla === 'movimientos'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-teal-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <h2 class="text-xl font-bold text-gray-800">Movimientos de Proveedor</h2>
            </div>
            <div class="p-8 border-2 border-dashed border-gray-200 rounded-2xl text-center text-gray-400 italic">
                Contenido de Movimientos de Proveedor próximamente...
            </div>
        </div>
    </template>

    <!-- Pantalla 2: Reporte Presupuesto vs Ejecutado -->
    <template x-if="pantalla === 'presupuesto'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-blue-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button x-show="departamentos.length > 0" @click="exportarExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">Presupuesto vs Ejecutado</h2>
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                    <select x-model="idRazonSocial" @change="actualizarRazonSocial('presupuesto')" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 min-w-[200px] text-sm">
                        <option value="">Seleccione Razón Social</option>
                        <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                    <select x-ref="placesSelectorPresupuesto" multiple :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 min-w-[200px] text-sm disabled:bg-gray-100">
                        <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Año</label>
                    <select x-model="anio" @change="cargarComparativo()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 text-sm">
                        <template x-for="a in years" :key="a">
                            <option :value="a" x-text="a" :selected="a === anio"></option>
                        </template>
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[220px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Meses</label>
                    <select x-ref="mesesSelectorPresupuesto" multiple>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
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
                    <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Unidades</label>
                    <select x-ref="filtroDptos" multiple>
                        <template x-for="d in departamentosOriginales" :key="d.ID_UnidadOperativa">
                            <option :value="d.ID_UnidadOperativa" x-text="(verGlobal ? d.RazonSocialNombre + ' > ' + d.PlaceNombre + ' > ' : '') + d.Nombre"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="border border-gray-300 rounded-lg overflow-x-auto shadow-sm">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-800 text-white text-[10px] uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Departamento / Partida</th>
                            <th class="px-4 py-3 text-right">Importe Asignado</th>
                            <th class="px-4 py-3 text-right">Importe Comprometido</th>
                            <th class="px-4 py-3 text-right">Importe Pagado</th>
                            <th class="px-4 py-3 text-right">Compras del mes</th>
                            <th class="px-4 py-3 text-right">Importe Disponible</th>
                            <th class="px-4 py-3 text-right" x-show="hayExcedidos" x-cloak>Importe Excedido</th>
                            <th class="px-4 py-3 text-center">% Ejecución</th>
                        </tr>
                    </thead>

                    <tbody x-show="cargando || departamentos.length === 0">
                        <tr x-show="cargando"><td :colspan="hayExcedidos ? 8 : 7" class="px-4 py-12 text-center text-gray-500 italic">Cargando datos...</td></tr>
                        <tr x-show="!cargando && departamentos.length === 0"><td :colspan="hayExcedidos ? 8 : 7" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el reporte.</td></tr>
                    </tbody>

                    <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                        <tbody class="border-t-4 border-gray-500" x-show="grupoRS.totales.asignado > 0">
                            <tr class="bg-gray-200 font-black text-sm shadow-sm">
                                <td class="px-6 py-3 text-gray-900 uppercase tracking-wider text-[11px]" x-text="grupoRS.nombre"></td>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.asignado)"></td>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.comprometido)"></td>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.ejecutado)"></td>
                                <td class="px-4 py-3 text-right font-bold text-gray-700" x-text="formatearMoneda(grupoRS.totales.comprometido + grupoRS.totales.ejecutado)"></td>
                                <td class="px-4 py-3 text-right font-black" :class="grupoRS.totales.disponible <= 0 ? 'text-gray-400' : 'text-green-700'" x-text="formatearMoneda(grupoRS.totales.disponible)"></td>
                                <td class="px-4 py-3 text-right font-black text-red-700" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(grupoRS.totales.excedido)"></td>
                                <td class="px-4 py-3 text-center" :class="getClaseSemaforo(grupoRS.totales.porcentaje)" x-text="grupoRS.totales.porcentaje + '%'"></td>
                            </tr>

                            <template x-for="seg in grupoRS.segmentos" :key="seg.nombre">
                                <tbody class="contents" x-show="seg.totales.asignado > 0">
                                    <tr class="bg-blue-100 border-l-4 border-blue-500 font-bold text-xs">
                                        <td class="px-10 py-2 text-blue-900 uppercase tracking-tight border-l-4 border-blue-600" x-text="'📁 ' + seg.nombre"></td>
                                        <td class="px-4 py-2 text-right text-blue-900" x-text="formatearMoneda(seg.totales.asignado)"></td>
                                        <td class="px-4 py-2 text-right text-blue-900" x-text="formatearMoneda(seg.totales.comprometido)"></td>
                                        <td class="px-4 py-2 text-right text-blue-900" x-text="formatearMoneda(seg.totales.ejecutado)"></td>
                                        <td class="px-4 py-2 text-right font-bold text-blue-800" x-text="formatearMoneda(seg.totales.comprometido + seg.totales.ejecutado)"></td>
                                        <td class="px-4 py-2 text-right font-bold" :class="seg.totales.disponible <= 0 ? 'text-blue-300' : 'text-green-800'" x-text="formatearMoneda(seg.totales.disponible)"></td>
                                        <td class="px-4 py-2 text-right font-bold text-red-700" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(seg.totales.excedido)"></td>
                                        <td class="px-4 py-2 text-center font-black" :class="getClaseSemaforo(seg.totales.porcentaje)" x-text="seg.totales.porcentaje + '%'"></td>
                                    </tr>

                                    <template x-for="complex in seg.complejos" :key="complex.nombre">
                                        <tbody class="contents" x-show="complex.totales.asignado > 0">
                                            <tr class="bg-white font-semibold text-[11px] text-gray-500 border-b border-gray-100">
                                                <td class="px-14 py-1 uppercase tracking-tighter" x-text="'📍 ' + complex.nombre"></td>
                                                <td class="px-4 py-1 text-right" x-text="formatearMoneda(complex.totales.asignado)"></td>
                                                <td class="px-4 py-1 text-right" x-text="formatearMoneda(complex.totales.comprometido)"></td>
                                                <td class="px-4 py-1 text-right" x-text="formatearMoneda(complex.totales.ejecutado)"></td>
                                                <td class="px-4 py-1 text-right font-bold text-gray-700" x-text="formatearMoneda(complex.totales.comprometido + complex.totales.ejecutado)"></td>
                                                <td class="px-4 py-1 text-right" :class="complex.totales.disponible <= 0 ? 'text-gray-300' : 'text-green-600'" x-text="formatearMoneda(complex.totales.disponible)"></td>
                                                <td class="px-4 py-1 text-right text-red-600 font-bold" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(complex.totales.excedido)"></td>
                                                <td class="px-4 py-1 text-center font-bold" :class="getClaseSemaforo(complex.totales.porcentaje)" x-text="complex.totales.porcentaje + '%'"></td>
                                            </tr>

                                            <template x-for="uni in complex.departamentos" :key="uni.ID_UnidadOperativa">
                                                <tbody class="contents" x-show="uni.totales?.asignado > 0">
                                                    <template x-for="(item, index) in [uni, ...(uni.detalles || [])]" :key="index">
                                                        <tr :class="index === 0 ? 'bg-gray-50/30 font-bold border-l-2 border-gray-300' : 'hover:bg-gray-50 border-b border-gray-50'"
                                                            x-show="index === 0 ? (uni.totales?.asignado > 0) : (parseFloat(item.asignado) > 0)">
                                                            <td class="px-6 py-2" :class="index === 0 ? 'text-gray-900 text-xs pl-20' : 'pl-28 text-gray-400 text-[11px]'">
                                                                <span x-show="index === 0">⚙️ </span>
                                                                <span x-text="index === 0 ? uni.Nombre : item.etiqueta"></span>
                                                            </td>
                                                            <td class="px-4 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(uni.totales?.asignado) : formatearMoneda(item.asignado)"></td>
                                                            <td class="px-4 py-2 text-right" :class="index === 0 ? 'text-gray-900' : 'text-orange-600 italic'" x-text="index === 0 ? formatearMoneda(uni.totales?.comprometido) : formatearMoneda(item.comprometido)"></td>
                                                            <td class="px-4 py-2 text-right" :class="index === 0 ? 'text-gray-900' : 'text-blue-700 font-semibold'" x-text="index === 0 ? formatearMoneda(uni.totales?.ejecutado) : formatearMoneda(item.ejecutado)"></td>
                                                            <td class="px-4 py-2 text-right font-medium text-gray-600" x-text="index === 0 ? formatearMoneda(uni.totales?.comprometido + uni.totales?.ejecutado) : formatearMoneda(item.comprometido + item.ejecutado)"></td>
                                                            <td class="px-4 py-2 text-right font-bold" :class="index === 0 ? (uni.totales?.disponible <= 0 ? 'text-gray-300' : 'text-green-600') : (item.disponible <= 0 ? 'text-gray-300' : 'text-green-700')" x-text="index === 0 ? formatearMoneda(uni.totales?.disponible) : formatearMoneda(item.disponible)"></td>
                                                            <td class="px-4 py-2 text-right text-red-600 font-bold" x-show="hayExcedidos" x-cloak x-text="index === 0 ? formatearMoneda(uni.totales?.excedido) : formatearMoneda(item.excedido)"></td>
                                                            <td class="px-4 py-2 text-center" :class="index === 0 ? getClaseSemaforo(uni.totales?.porcentaje) : getClaseSemaforo(item.porcentaje)" x-text="(index === 0 ? (uni.totales?.porcentaje || 0) : item.porcentaje) + '%'"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </template>
                                        </tbody>
                                    </template>
                                </tbody>
                            </template>
                        </tbody>
                    </template>

                    <tfoot x-show="!cargando && departamentos.length > 0" class="bg-gray-800 text-white">
                        <tr>
                            <td class="px-6 py-4 font-black uppercase tracking-widest text-right">Total:</td>
                            <td class="px-4 py-4 text-right font-bold text-lg" x-text="formatearMoneda(totalesGenerales?.asignado)"></td>
                            <td class="px-4 py-4 text-right font-bold text-lg text-orange-300" x-text="formatearMoneda(totalesGenerales?.comprometido)"></td>
                            <td class="px-4 py-4 text-right font-bold text-lg text-blue-300" x-text="formatearMoneda(totalesGenerales?.ejecutado)"></td>
                            <td class="px-4 py-4 text-right font-bold text-lg text-gray-300" x-text="formatearMoneda(totalesGenerales?.comprometido + totalesGenerales?.ejecutado)"></td>
                            <td class="px-4 py-4 text-right font-bold text-lg" :class="totalesGenerales?.disponible <= 0 ? 'text-gray-400' : 'text-green-400'" x-text="formatearMoneda(totalesGenerales?.disponible)"></td>
                            <td class="px-4 py-4 text-right font-bold text-lg text-red-400" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(totalesGenerales?.excedido)"></td>
                            <td class="px-4 py-4 text-center font-bold text-lg" :class="totalesGenerales?.porcentaje >= 100 ? 'text-red-400' : 'text-green-400'" x-text="(totalesGenerales?.porcentaje || 0) + '%'"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </template>

    <!-- Pantalla 3: Reporte Cuentas Bancarias -->
    <template x-if="pantalla === 'cuentas'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-green-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button x-show="departamentosBancos.length > 0" @click="exportarBancosExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">Reporte de Cuentas Bancarias</h2>
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                    <select x-model="idRazonSocial" @change="actualizarRazonSocial('cuentas')" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-green-300 min-w-[200px] text-sm">
                        <option value="">Seleccione Razón Social</option>
                        <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                    <select x-ref="placesSelectorCuentas" multiple :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-green-300 min-w-[200px] text-sm disabled:bg-gray-100">
                        <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Año</label>
                    <select x-model="anio" @change="cargarComparativoBancos()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-green-300 text-sm">
                        <template x-for="a in years" :key="a">
                            <option :value="a" x-text="a" :selected="a === anio"></option>
                        </template>
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[220px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Meses</label>
                    <select x-ref="mesesSelectorCuentas" multiple>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
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
                            <th class="px-6 py-3 text-left">Razón Social / Cuenta Bancaria</th>
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

                    <template x-for="rs in departamentosBancos" :key="rs.ID_RazonSocial">
                        <tbody class="border-t-4 border-gray-500">
                            <!-- Cabecera de Razón Social -->
                            <tr class="bg-gray-200 font-black text-sm shadow-sm">
                                <td class="px-6 py-3 text-gray-900 uppercase tracking-wider text-[11px]">
                                    <div class="flex flex-col">
                                        <span x-text="rs.Nombre"></span>
                                        <span class="text-[9px] text-gray-500 font-normal normal-case" x-text="'Incluye complejos: ' + (rs.places || []).join(', ')"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(rs.totales.inicial)"></td>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(rs.totales.final)"></td>
                                <td class="px-4 py-3 text-right font-black" :class="rs.totales.final < rs.totales.inicial ? 'text-red-700' : 'text-green-700'" x-text="formatearMoneda(rs.totales.usado)"></td>
                                <td class="px-4 py-3 text-center font-black" :class="rs.totales.final < rs.totales.inicial ? 'text-red-700' : 'text-green-700'" x-text="rs.totales.porcentaje + '%'"></td>
                            </tr>

                            <!-- Cuentas Bancarias -->
                            <template x-for="banco in rs.bancos" :key="banco.clabe">
                                <tr class="bg-white hover:bg-gray-50 border-b border-gray-100">
                                    <td class="px-10 py-2">
                                        <div class="font-bold text-blue-900 text-xs" x-text="banco.banco"></div>
                                        <div class="text-[10px] text-gray-400 font-mono" x-text="'CLABE: ' + banco.clabe"></div>
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-600" x-text="formatearMoneda(banco.inicial)"></td>
                                    <td class="px-4 py-2 text-right text-gray-600" x-text="formatearMoneda(banco.final)"></td>
                                    <td class="px-4 py-2 text-right font-semibold" :class="banco.final < banco.inicial ? 'text-red-600' : 'text-green-600'" x-text="formatearMoneda(banco.usado)"></td>
                                    <td class="px-4 py-2 text-center font-medium text-gray-500" x-text="banco.porcentaje + '%'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </template>
                </table>
            </div>
        </div>
    </template>

    <!-- Pantalla 4: Reporte Completo Consolidado -->
    <template x-if="pantalla === 'completo'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-purple-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button x-show="departamentosCompleto.length > 0" @click="exportarReporteCompletoExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">Reporte Consolidado Maestro</h2>
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                    <select x-model="idRazonSocial" @change="actualizarRazonSocial('completo')" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-purple-300 min-w-[200px] text-sm">
                        <option value="">Seleccione Razón Social</option>
                        <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                    <select x-ref="placesSelectorCompleto" multiple :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-purple-300 min-w-[200px] text-sm disabled:bg-gray-100">
                        <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Año</label>
                    <select x-model="anio" @change="cargarReporteCompleto()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-purple-300 text-sm">
                        <template x-for="a in years" :key="a">
                            <option :value="a" x-text="a" :selected="a === anio"></option>
                        </template>
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[220px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Meses</label>
                    <select x-ref="mesesSelectorCompleto" multiple>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
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
                            <th class="px-4 py-3 text-left">Departamento / Partida</th>
                            <th class="px-2 py-3 text-right">Importe asignado</th>
                            <th class="px-2 py-3 text-right">Importe Comprometido</th>
                            <th class="px-2 py-3 text-right">Importe Pagado</th>
                            <th class="px-2 py-3 text-right">Compras del mes</th>
                            <th class="px-2 py-3 text-right">Importe Disponible</th>
                            <th class="px-2 py-3 text-right" x-show="hayExcedidos" x-cloak>Importe Excedido</th>
                            <th class="px-2 py-3 text-center">% Ejec.</th>
                            <th class="px-2 py-3 text-right border-l border-gray-600">B. Inicial</th>
                            <th class="px-2 py-3 text-right">B. Final</th>
                            <th class="px-4 py-3 text-right">B. Diferencia</th>
                        </tr>
                    </thead>

                    <tbody x-show="cargando || departamentosCompleto.length === 0">
                        <tr x-show="cargando"><td :colspan="hayExcedidos ? 11 : 10" class="px-4 py-12 text-center text-gray-500 italic">Consolidando información financiera...</td></tr>
                        <tr x-show="!cargando && departamentosCompleto.length === 0"><td :colspan="hayExcedidos ? 11 : 10" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el consolidado.</td></tr>
                    </tbody>

                    <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                        <tbody class="border-t-4 border-gray-500" x-show="grupoRS.totales.pAsignado > 0">
                            <tr class="bg-gray-200 font-black text-sm shadow-sm">
                                <td class="px-6 py-3 text-gray-800 uppercase tracking-wider text-[11px]" x-text="grupoRS.nombre"></td>
                                <td class="px-2 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.pAsignado)"></td>
                                <td class="px-2 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.pComprometido)"></td>
                                <td class="px-2 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.pEjecutado)"></td>
                                <td class="px-2 py-3 text-right font-bold text-gray-700" x-text="formatearMoneda(grupoRS.totales.pComprometido + grupoRS.totales.pEjecutado)"></td>
                                <td class="px-2 py-3 text-right font-black" :class="grupoRS.totales.pDisponible <= 0 ? 'text-gray-400' : 'text-green-700'" x-text="formatearMoneda(grupoRS.totales.pDisponible)"></td>
                                <td class="px-2 py-3 text-right font-black text-red-700" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(grupoRS.totales.pExcedido)"></td>
                                <td class="px-2 py-3 text-center text-[10px]" :class="getClaseSemaforo(grupoRS.totales.pPorcentaje)" x-text="grupoRS.totales.pPorcentaje + '%'"></td>
                                <td class="px-2 py-3 text-right text-gray-900 border-l border-gray-400" x-text="formatearMoneda(grupoRS.totales.bInicial)"></td>
                                <td class="px-2 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.bFinal)"></td>
                                <td class="px-4 py-3 text-right font-black" :class="grupoRS.totales.bFinal < grupoRS.totales.bInicial ? 'text-red-700' : 'text-green-700'" x-text="formatearMoneda(grupoRS.totales.bInicial - grupoRS.totales.bFinal)"></td>
                            </tr>

                            <template x-for="seg in grupoRS.segmentos" :key="seg.nombre">
                                <tbody class="contents" x-show="seg.totales.pAsignado > 0">
                                    <tr class="bg-blue-100 border-l-4 border-blue-500 font-bold text-[11px]">
                                        <td class="px-10 py-2 text-blue-900 uppercase tracking-tight border-l-4 border-blue-600" x-text="'📁 ' + seg.nombre"></td>
                                        <td class="px-2 py-2 text-right text-blue-900" x-text="formatearMoneda(seg.totales.pAsignado)"></td>
                                        <td class="px-2 py-2 text-right text-blue-900" x-text="formatearMoneda(seg.totales.pComprometido)"></td>
                                        <td class="px-2 py-2 text-right text-blue-900" x-text="formatearMoneda(seg.totales.pEjecutado)"></td>
                                        <td class="px-2 py-2 text-right font-bold text-blue-800" x-text="formatearMoneda(seg.totales.pComprometido + seg.totales.pEjecutado)"></td>
                                        <td class="px-2 py-2 text-right font-bold" :class="seg.totales.pDisponible <= 0 ? 'text-blue-300' : 'text-green-800'" x-text="formatearMoneda(seg.totales.pDisponible)"></td>
                                        <td class="px-2 py-2 text-right font-bold text-red-700" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(seg.totales.pExcedido)"></td>
                                        <td class="px-2 py-2 text-center" :class="getClaseSemaforo(seg.totales.pPorcentaje)" x-text="seg.totales.pPorcentaje + '%'"></td>
                                        <td class="px-2 py-2 text-right text-gray-400 border-l border-blue-200">-</td>
                                        <td class="px-2 py-2 text-right text-gray-400">-</td>
                                        <td class="px-4 py-2 text-right text-gray-400">-</td>
                                    </tr>

                                    <template x-for="complex in seg.complejos" :key="complex.nombre">
                                        <tbody class="contents" x-show="complex.totales.pAsignado > 0">
                                            <tr class="bg-white font-semibold text-[10px] text-gray-500 border-b border-gray-100">
                                                <td class="px-14 py-1 uppercase tracking-tighter" x-text="'📍 ' + complex.nombre"></td>
                                                <td class="px-2 py-1 text-right" x-text="formatearMoneda(complex.totales.pAsignado)"></td>
                                                <td class="px-2 py-1 text-right" x-text="formatearMoneda(complex.totales.pComprometido)"></td>
                                                <td class="px-2 py-1 text-right" x-text="formatearMoneda(complex.totales.pEjecutado)"></td>
                                                <td class="px-2 py-1 text-right font-bold text-gray-700" x-text="formatearMoneda(complex.totales.pComprometido + complex.totales.pEjecutado)"></td>
                                                <td class="px-2 py-1 text-right" :class="complex.totales.pDisponible <= 0 ? 'text-gray-300' : 'text-green-600'" x-text="formatearMoneda(complex.totales.pDisponible)"></td>
                                                <td class="px-2 py-1 text-right text-red-600 font-bold" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(complex.totales.pExcedido)"></td>
                                                <td class="px-2 py-1 text-center" :class="getClaseSemaforo(complex.totales.pPorcentaje)" x-text="complex.totales.pPorcentaje + '%'"></td>
                                                <td class="px-2 py-1 text-right text-gray-300 border-l border-gray-100">-</td>
                                                <td class="px-2 py-1 text-right text-gray-300">-</td>
                                                <td class="px-4 py-1 text-right text-gray-300">-</td>
                                            </tr>

                                            <template x-for="uni in complex.departamentos" :key="uni.ID_UnidadOperativa">
                                                <tbody class="contents" x-show="uni.presupuesto?.asignado > 0">
                                                    <template x-for="(item, index) in [uni, ...(uni.detalles || [])]" :key="index">
                                                        <tr :class="index === 0 ? 'bg-gray-50/30 font-bold border-l-2 border-gray-300' : 'hover:bg-gray-50 border-b border-gray-50'"
                                                            x-show="index === 0 ? (uni.presupuesto?.asignado > 0) : (parseFloat(item.asignado) > 0)">
                                                            <td class="px-6 py-2" :class="index === 0 ? 'text-blue-900 text-xs pl-20' : 'pl-28 text-gray-500 text-[10px]'">
                                                                <span x-show="index === 0">⚙️ </span>
                                                                <span x-text="index === 0 ? uni.Nombre : item.etiqueta"></span>
                                                            </td>
                                                            <td class="px-2 py-2 text-right text-gray-900" x-text="index === 0 ? formatearMoneda(uni.presupuesto?.asignado) : formatearMoneda(item.asignado)"></td>
                                                            <td class="px-2 py-2 text-right" :class="index === 0 ? 'text-gray-900' : 'text-orange-600 italic'" x-text="index === 0 ? formatearMoneda(uni.presupuesto?.comprometido) : formatearMoneda(item.comprometido)"></td>
                                                            <td class="px-2 py-2 text-right" :class="index === 0 ? 'text-gray-900' : 'text-blue-700 font-semibold'" x-text="index === 0 ? formatearMoneda(uni.presupuesto?.ejecutado) : formatearMoneda(item.ejecutado)"></td>
                                                            <td class="px-2 py-2 text-right font-medium text-gray-600" x-text="index === 0 ? formatearMoneda(uni.presupuesto?.comprometido + uni.presupuesto?.ejecutado) : formatearMoneda(item.comprometido + item.ejecutado)"></td>
                                                            <td class="px-2 py-2 text-right font-bold" :class="index === 0 ? (uni.presupuesto?.disponible <= 0 ? 'text-gray-300' : 'text-green-600') : (item.disponible <= 0 ? 'text-gray-300' : 'text-green-700')" x-text="index === 0 ? formatearMoneda(uni.presupuesto?.disponible) : formatearMoneda(item.disponible)"></td>
                                                            <td class="px-2 py-2 text-right text-red-600 font-bold" x-show="hayExcedidos" x-cloak x-text="index === 0 ? formatearMoneda(uni.presupuesto?.excedido) : formatearMoneda(item.excedido)"></td>
                                                            <td class="px-2 py-2 text-center" :class="index === 0 ? getClaseSemaforo(uni.presupuesto?.porcentaje) : getClaseSemaforo(item.porcentaje)" x-text="(index === 0 ? (uni.presupuesto?.porcentaje || 0) : (item.porcentaje || 0)) + '%'"></td>
                                                            <td class="px-2 py-2 text-right text-gray-200 border-l border-gray-100">-</td>
                                                            <td class="px-2 py-2 text-right text-gray-200">-</td>
                                                            <td class="px-4 py-2 text-right text-gray-200">-</td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </template>
                                        </tbody>
                                    </template>
                                </tbody>
                            </template>
                        </tbody>
                    </template>
                </table>
            </div>
        </div>
    </template>
</div>

<style>
    .animate-fadeIn { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    [x-cloak] { display: none !important; }
</style>