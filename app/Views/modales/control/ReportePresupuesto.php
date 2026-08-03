<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);

$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = base_url("icons/icons.svg?v=$version");
?>

<div id="reporte-presupuesto-main-div"
     class="p-6 min-h-[400px] rounded-2xl bg-slate-50 border border-slate-200"
     x-data="reportePresupuestoComponent"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <!-- Pantalla 1: Menú Principal -->
    <div x-show="pantalla === 'menu'" x-cloak class="animate-fadeIn">
        <div class="mb-6">
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-800">Central de Reportes</h2>
            <p class="mt-1 text-sm text-slate-600">Presupuesto, ejecución y tesorería en un solo espacio de consulta.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button @click="irAPantalla('solo_presupuesto')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-indigo-300 hover:bg-indigo-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-indigo-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReportePresupuesto"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-indigo-700 text-xs">Presupuesto Mensual</span>
            </button>
            <button @click="irAPantalla('solo_ejecutado')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-cyan-300 hover:bg-cyan-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-cyan-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReporteEjecutado"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-cyan-700 text-xs">Ejecutado Mensual</span>
            </button>
            <button @click="irAPantalla('presupuesto')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-blue-300 hover:bg-blue-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-blue-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#PresupuestoVsEjecutado"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-blue-700 text-xs">Presupuesto vs Ejecutado Mensual</span>
            </button>
            <button @click="irAPantalla('cuentas')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-green-300 hover:bg-green-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-green-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#CuentasBancarias"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-green-700 text-xs">Saldos Iniciales/Finales Bancarios</span>
            </button>
            <button @click="irAPantalla('completo')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-purple-300 hover:bg-purple-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-purple-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReporteCompleto"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-purple-700 text-xs">Reporte Consolidado Mensual </span>
            </button>
            <button @click="irAPantalla('proveedores')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-orange-300 hover:bg-orange-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-orange-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ListaProveedores"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-orange-700 text-xs">Directorio De Proveedores</span>
            </button>
            <button @click="irAPantalla('compras')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-red-300 hover:bg-red-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-red-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReporteCompras"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-red-700 text-xs">Reporte Pagado/Por Pagar Autorizado</span>
            </button>
            <button @click="irAPantalla('movimientos')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-teal-300 hover:bg-teal-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-teal-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#MovimientosProveedor"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-teal-700 text-xs">Reporte Pagado/Por Pagar Detallado</span>
            </button>
            <button @click="irAPantalla('vencimientos')" class="flex flex-col items-center p-4 rounded-xl border border-slate-200 bg-white hover:border-yellow-300 hover:bg-yellow-50/70 transition-all duration-150 group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yellow-400">
                <div class="mb-2 group-hover:scale-110 transition-transform">
                    <svg class="size-8 text-yellow-600" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="<?= $iconUrl ?>#ReporteVencimientos"></use>
                    </svg>
                </div>
                <span class="font-bold text-gray-700 group-hover:text-yellow-700 text-xs">Reportes De Creditos</span>
            </button>
        </div>
    </div>

    <!-- Pantalla 5: Directorio de Proveedores -->
    <template x-if="pantalla === 'proveedores'">
        <div id="pantalla-lista-proveedores" class="animate-fadeIn bg-white rounded-xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-orange-600 flex items-center gap-1 font-semibold">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button @click="exportarProveedoresExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-2xl font-semibold text-center text-gray-800">Directorio de Proveedores</h2>
                </div>
            </div>

            <!-- Buscadores -->
            <div id="form-filtros-proveedores" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
                <div class="flex flex-1 gap-4">
                    <label for="buscar-nombre-rep" class="sr-only">Buscar por nombre</label>
                    <input type="text" id="buscar-nombre-rep" 
                           x-model="filtroNombreProveedor" 
                           @input.debounce.300ms="aplicarFiltrosProveedor()"
                           placeholder="Buscar por nombre..." 
                           class="w-full px-3 py-2 border border-slate-300 bg-slate-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:bg-white">
                    <label for="buscar-servicio-rep" class="sr-only">Buscar por servicio</label>
                    <input type="text" id="buscar-servicio-rep" 
                           x-model="filtroServicioProveedor"
                           @input.debounce.300ms="aplicarFiltrosProveedor()"
                           placeholder="Buscar por servicio..." 
                           class="w-full px-3 py-2 border border-slate-300 bg-slate-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:bg-white">
                </div>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="min-w-full border border-slate-200 rounded-lg table-fixed overflow-hidden">
                    <thead class="bg-slate-100/80">
                        <tr>
                            <th class="w-1/6 px-3 py-2 border-b text-left text-xs font-bold uppercase text-gray-600">Razón Social</th>
                            <th class="w-1/6 px-3 py-2 border-b text-left text-xs font-bold uppercase text-gray-600">RFC</th>
                            <th class="w-1/6 px-3 py-2 border-b text-left text-xs font-bold uppercase text-gray-600">Banco</th>
                            <th class="w-1/6 px-3 py-2 border-b text-left text-xs font-bold uppercase text-gray-600">Teléfono</th>
                            <th class="w-1/6 px-3 py-2 border-b text-left text-xs font-bold uppercase text-gray-600">Servicio</th>
                            <th class="w-1/6 px-3 py-2 border-b text-center text-xs font-bold uppercase text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-proveedores">
                        <template x-for="(prov, index) in paginatedProveedores" :key="prov.ID_Proveedor ? 'prov-' + prov.ID_Proveedor : 'prow-' + index">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'" class="hover:bg-emerald-50/60 transition-colors">
                                <td class="px-3 py-2 border-b text-sm razonsocial" x-text="prov.RazonSocial"></td>
                                <td class="px-3 py-2 border-b text-sm" x-text="prov.RFC || 'N/A'"></td>
                                <td class="px-3 py-2 border-b text-sm" x-text="prov.Banco || 'N/A'"></td>
                                <td class="px-3 py-2 border-b text-sm" x-text="prov.Tel_Contacto || 'N/A'"></td>
                                <td class="px-3 py-2 border-b text-sm servicio" x-text="prov.Servicio || 'N/A'"></td>
                                <td class="px-2 py-2 border-b align-top text-center acciones">
                                    <button @click="verDetalleProveedor(prov)" 
                                            class="inline-flex items-center gap-1 bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700 transition-all text-[10px] font-bold uppercase">
                                        Detalles
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="cargando">
                            <td colspan="6" class="px-3 py-12 text-center text-gray-500 italic">Cargando proveedores...</td>
                        </tr>
                        <tr x-show="!cargando && paginatedProveedores.length === 0">
                            <td colspan="6" class="px-3 py-12 text-center text-gray-400 italic">No hay proveedores registrados que coincidan con los filtros.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div id="paginacion-proveedores-rep" class="flex flex-wrap justify-center mt-4 gap-2">
                <template x-for="item in paginationDataProveedor" :key="item.type + (item.value || '')">
                    <button 
                        @click="cambiarPaginaProveedor(item.value)"
                        :disabled="item.disabled || (item.type === 'number' && item.active)"
                        :class="{
                            'bg-blue-600 text-white': item.active && item.type === 'number',
                            'bg-white text-gray-700 hover:bg-gray-100': !item.active && item.type !== '...',
                            'opacity-50 cursor-not-allowed': item.disabled,
                            'cursor-default text-gray-400': item.type === '...'
                        }"
                        class="px-3 py-1 border rounded text-xs font-medium transition-all"
                        x-html="item.type === 'first' ? '&laquo;' : (item.type === 'prev' ? '&lsaquo;' : (item.type === 'next' ? '&rsaquo;' : (item.type === 'last' ? '&raquo;' : (item.value || '...'))))"
                    ></button>
                </template>
            </div>

            <!-- Modal / Sección de Detalles -->
            <div x-show="proveedorSeleccionado" 
                 x-cloak 
                 class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 animate-fadeIn"
                 @click.self="proveedorSeleccionado = null">
                <div class="bg-white rounded-xl border border-slate-200 max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            🔍 Detalles del Proveedor
                        </h3>
                        <button @click="proveedorSeleccionado = null" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
                    </div>
                    
                    <div class="p-6 overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                            <!-- Columna 1 -->
                            <div class="space-y-4">
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Razón Social</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.RazonSocial"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Correo Electrónico</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Correo || 'N/A'"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">RFC</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.RFC"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Servicio</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Servicio"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Nombre del Contacto</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Nombre_Contacto"></div>
                                </div>
                            </div>

                            <!-- Columna 2 -->
                            <div class="space-y-4">
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Banco</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Banco"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Cuenta</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Cuenta"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">CLABE</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Clabe"></div>
                                </div>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Teléfono de Contacto</label>
                                    <div class="px-3 py-2 bg-gray-100 border rounded-lg text-sm text-gray-700 font-medium" x-text="proveedorSeleccionado?.Tel_Contacto"></div>
                                </div>
                                <div class="border-t pt-4">
                                    <div class="flex items-center mb-4">
                                        <div :class="proveedorSeleccionado?.Dias_Credito > 0 ? 'bg-green-500' : 'bg-gray-400'" class="w-4 h-4 rounded-full mr-2"></div>
                                        <span class="text-sm font-bold text-gray-700" x-text="proveedorSeleccionado?.Dias_Credito > 0 ? 'Este proveedor tiene crédito' : 'No tiene crédito registrado'"></span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4" x-show="proveedorSeleccionado?.Dias_Credito > 0">
                                        <div class="flex flex-col">
                                            <label class="text-xs font-bold text-gray-500 uppercase mb-1">Días</label>
                                            <div class="px-3 py-2 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-800 font-bold" x-text="proveedorSeleccionado?.Dias_Credito"></div>
                                        </div>
                                        <div class="flex flex-col">
                                            <label class="text-xs font-bold text-gray-500 uppercase mb-1">Monto</label>
                                            <div class="px-3 py-2 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-800 font-bold" x-text="formatearMoneda(proveedorSeleccionado?.Monto_Credito)"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 border-t bg-gray-50 text-right">
                        <button @click="proveedorSeleccionado = null" 
                                class="px-6 py-2 bg-gray-800 text-white rounded-lg font-bold text-xs uppercase hover:bg-gray-900 transition-all shadow-md">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Pantalla 6: Reporte Pagado/Por Pagar -->
    <template x-if="pantalla === 'compras'">
        <div class="animate-fadeIn">
            <div id="div-reportes" x-data="Reportes(<?= htmlspecialchars(json_encode($tabledata ?? []), ENT_QUOTES, 'UTF-8') ?>)">
                <div class="flex items-center justify-between mb-6">
                    <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-red-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                    <div class="flex items-center gap-4">
                        <button @click="generarReporteCSV" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar Excel
                        </button>
                        <h2 class="text-xl font-bold text-gray-800">Reporte Pagado/Por Pagar Autorizado</h2>
                    </div>
                </div>

                <!-- Controles de Filtro -->
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <!-- Filtro Fecha -->
                    <div class="w-full sm:w-auto shrink-0">
                        <div class="flex items-center gap-2 border p-2 rounded min-w-[190px] bg-white">
                            <span class="text-gray-500 text-sm cursor-default">Fecha:</span>
                            <input type="date" x-model="fecha" id="filtro-fecha-reportes"
                                @click="$el.showPicker()"
                                class="border-none p-0 focus:ring-0 bg-transparent grow min-w-0 cursor-pointer text-sm">
                            <label class="flex items-center gap-1 text-xs text-gray-600 whitespace-nowrap cursor-pointer">
                                <input type="checkbox" x-model="porMes" id="filtrar-por-mes-reportes"
                                    class="accent-blue-600 h-4 w-4">
                                Mes
                            </label>
                        </div>
                    </div>

                    <!-- Filtro Estado -->
                    <div class="w-full sm:w-auto shrink-0">
                        <select x-model="estado" id="filtro-estado-reportes" class="border p-2 rounded w-full min-w-[150px]">
                            <option value="">Estado (Todos)</option>
                            <option value="Por Pagar">🟠 Por Pagar</option>
                            <option value="Pagada">🟢 Pagada</option>
                        </select>
                    </div>

                    <!-- Filtro Dpto -->
                    <div class="w-full sm:w-auto shrink-0">
                        <select x-ref="deptoSelect" id="filtroDepartamento-reportes" class="border p-2 rounded w-full min-w-[200px]" multiple>
                            <option value="">Departamento (Todos)</option>
                            <?php if (!empty($departamentos)): ?>
                                <?php foreach ($departamentos as $dpto): ?>
                                    <option value="<?= esc($dpto['Nombre']) ?>"><?= esc($dpto['Nombre']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Filtro Razon Social -->
                    <div class="w-full sm:w-auto shrink-0">
                        <select x-ref="razonSelect" id="filtroRazonSocial-reportes" class="border p-2 rounded w-full min-w-[200px]" multiple>
                            <option value="">Razón Social (Todas)</option>
                            <?php if (!empty($razones_sociales)): ?>
                                <?php foreach ($razones_sociales as $rs): ?>
                                    <option value="<?= esc($rs['Nombre']) ?>"><?= esc($rs['Nombre']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Filtro Proveedor -->
                    <div class="w-full sm:w-auto shrink-0">
                        <select x-ref="provSelect" id="filtroProveedor-reportes" class="border p-2 rounded w-full min-w-[200px]" multiple>
                            <option value="">Proveedor (Todos)</option>
                            <?php if (!empty($proveedores)): ?>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= esc($prov['RazonSocial']) ?>"><?= esc($prov['RazonSocial']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Filtro Metodo de Pago -->
                    <div class="w-full sm:w-auto shrink-0">
                        <select x-model="metodoPago" id="filtroMetodoPago-reportes" class="border p-2 rounded w-full min-w-[150px]">
                            <option value="">Método de Pago</option>
                            <option value="0">Contado</option>
                            <option value="1">Crédito</option>
                        </select>
                    </div>

                    <!-- Botón Limpiar Filtros -->
                    <button @click="clearFilters()"
                        class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-4 py-2 rounded-md transition text-sm">
                        Limpiar Filtros
                    </button>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto shadow rounded-lg">
                    <table class="min-w-full border border-gray-300" id="tabla-reportes">
                        <thead class="bg-gray-100 text-gray-600 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="border px-3 py-2 text-left">Folio</th>
                                <th class="border px-3 py-2 text-left">Departamento</th>
                                <th class="border px-3 py-2 text-left">Razón social</th>
                                <th class="border px-3 py-2 text-left">Proveedor</th>
                                <th class="border px-3 py-2 text-left">Fecha</th>
                                <th class="border px-3 py-2 text-right">Importe Total</th>
                                <th class="border px-3 py-2 text-center">Estado</th>
                                <th class="border px-3 py-2 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <template x-if="paginatedData.length === 0">
                                <tr>
                                    <td colspan="8" class="text-center py-12 text-gray-400 italic">No se encontraron datos que coincidan con los filtros.</td>
                                </tr>
                            </template>
                            <template x-for="(item, index) in paginatedData" :key="item.ID_Solicitud || ('row-' + index)">
                                <tr class="text-center hover:bg-gray-50 text-xs">
                                    <td class="border px-3 py-2 text-left font-mono" x-text="item.No_Folio"></td>
                                    <td class="border px-3 py-2 text-left" x-text="item.DepartamentoNombre"></td>
                                    <td class="border px-3 py-2 text-left" x-text="item.Complejo"></td>
                                    <td class="border px-3 py-2 text-left" x-text="item.proveedor?.RazonSocial"></td>
                                    <td class="border px-3 py-2 text-left" x-text="item.Fecha"></td>
                                    <td class="border px-3 py-2 text-right font-bold" x-text="formatearMoneda(item.MontoTotal)"></td>
                                    <td class="border px-3 py-2 col-estado"
                                        :data-estado="item.EstadoOrden"
                                        :title="item.EstadoOrden"
                                        x-text="item.EstadoOrden">
                                    </td>
                                    <td class="border px-3 py-2">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1 rounded transition text-[10px] uppercase" @click="mostrarVerReporte(index)">Ver</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Controles de Paginación -->
                <div id="paginacion-reportes" class="flex justify-between items-center mt-4" x-show="totalPages > 1">
                    <div>
                        <span class="text-xs text-gray-600 font-medium">
                            Mostrando <span x-text="(currentPage - 1) * rowsPerPage + 1"></span> a <span
                                x-text="Math.min(currentPage * rowsPerPage, filteredData.length)"></span> de <span
                                x-text="filteredData.length"></span> resultados
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <template x-for="item in pageNumbers" :key="'page-' + item.type + '-' + item.value">
                            <div>
                                <button x-show="item.type === 'first'" @click="firstPage()"
                                    :disabled="currentPage === 1"
                                    class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs"
                                    title="Primera página">&laquo;</button>
                                <button x-show="item.type === 'prev'" @click="prevPage()"
                                    :disabled="currentPage === 1"
                                    class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs"
                                    title="Página anterior">&lsaquo;</button>
                                <span x-show="item.type === '...'" class="px-2 text-gray-400 cursor-default text-xs">...</span>
                                <button x-show="item.type === 'number'" @click="goToPage(item.value)"
                                    :class="item.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                                    class="px-3 py-1 border rounded text-xs font-bold" x-text="item.value"></button>
                                <button x-show="item.type === 'next'" @click="nextPage()"
                                    :disabled="currentPage === totalPages"
                                    class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs"
                                    title="Página siguiente">&rsaquo;</button>
                                <button x-show="item.type === 'last'" @click="lastPage()"
                                    :disabled="currentPage === totalPages"
                                    class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs"
                                    title="Última página">&raquo;</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div id="div-ver-reporte" class="hidden animate-fadeIn"></div>
        </div>
    </template>

    <!-- Pantalla 7: Pagado/Por Pagar Detallado -->
    <template x-if="pantalla === 'movimientos'">
        <div class="animate-fadeIn">
            
            <!-- CONTENEDOR PRINCIPAL: Tabla de Movimientos -->
            <div id="div-movimientos">
                <div class="flex items-center justify-between mb-6">
                    <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-red-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                    <div class="flex items-center gap-4">
                        <button x-show="movimientosProveedor.length > 0" @click="exportarMovimientosExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exportar Excel
                        </button>
                        <h2 class="text-xl font-bold text-gray-800">Reporte Pagado/Por Pagar Detallado</h2>
                    </div>
                </div>

                <!-- Filtros Superiores -->
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <!-- Rango de Fechas -->
                    <div class="flex items-center gap-2 bg-white border p-1 px-2 rounded-lg shadow-sm shrink-0">
                        <div class="flex flex-col">
                            <label class="text-[9px] text-gray-400 font-bold uppercase ml-1">Desde</label>
                            <input type="date" x-model="fechaInicioMovimientos" 
                                   class="border-none p-0 text-sm focus:ring-0 cursor-pointer">
                        </div>
                        <div class="h-6 w-[1px] bg-gray-200 mx-1"></div>
                        <div class="flex flex-col">
                            <label class="text-[9px] text-gray-400 font-bold uppercase ml-1">Hasta</label>
                            <input type="date" x-model="fechaFinMovimientos" 
                                   class="border-none p-0 text-sm focus:ring-0 cursor-pointer">
                        </div>
                    </div>

                    <!-- Filtro Complejo (Places) -->
                    <div class="w-full sm:w-auto shrink-0">
                        <select x-ref="placesSelectorMovimientos" multiple>
                            <template x-for="p in todosPlaces" :key="p.ID_Place">
                                <option :value="p.ID_Place" x-text="p.Nombre_Corto"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Buscador Local -->
                    <div class="w-full sm:w-auto grow max-w-md">
                        <input type="text" x-model="filtroTextoMovimientos" placeholder="Buscar por Folio o Proveedor..." 
                               class="border p-2 rounded w-full text-sm shadow-sm outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button @click="limpiarFiltrosMovimientos()"
                        class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-4 py-2 rounded-md transition text-sm ml-auto shadow-sm">
                        Limpiar Filtros
                    </button>
                </div>

                <!-- Tabla (Mismo Estilo que Compras) -->
                <div class="overflow-x-auto shadow rounded-lg border border-gray-300">
                    <table class="min-w-full border-collapse" id="tabla-movimientos">
                        <thead class="bg-gray-100 text-gray-600 uppercase text-[10px] font-bold">
                            <tr>
                                <th class="border px-3 py-2 text-left">Folio</th>
                                <th class="border px-3 py-2 text-left">Fecha Sol.</th>
                                <th class="border px-3 py-2 text-left">Aprobación</th>
                                <th class="border px-3 py-2 text-left">Razón Social</th>
                                <th class="border px-3 py-2 text-left">Proveedor</th>
                                <th class="border px-3 py-2 text-right">Importe Total</th>
                                <th class="border px-3 py-2 text-left">Área De Op.</th>
                                <th class="border px-3 py-2 text-left">F. Pago Realizado</th>
                                <th class="border px-3 py-2 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <template x-if="paginatedMovimientos.length === 0">
                                <tr>
                                    <td colspan="9" class="text-center py-12 text-gray-400 italic">No se encontraron datos que coincidan con los filtros.</td>
                                </tr>
                            </template>
                            <template x-for="(m, index) in paginatedMovimientos" :key="'mov-' + (m.ID_Solicitud || 'no-id') + '-' + index">
                                <tr x-show="m" class="text-center hover:bg-gray-50 text-xs">
                                    <td class="border px-3 py-2 text-left font-mono font-bold text-blue-700" x-text="m.No_Folio || 'N/A'"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.Fecha || 'N/A'"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.Fecha_Aprobacion || '-'"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.RazonSocialNombre || 'N/A'"></td>
                                    <td class="border px-3 py-2 text-left font-medium" x-text="m.ProveedorNombre || 'N/A'"></td>
                                    <td class="border px-3 py-2 text-right font-bold" x-text="formatearMoneda(m.MontoTotal)"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.DepartamentoNombre || 'N/A'"></td>
                                    <td class="border px-3 py-2 text-left font-bold text-green-700" x-text="m.FechaPagoRealizado || 'Pendiente'"></td>
                                    <td class="border px-3 py-2">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1 rounded transition text-[10px] uppercase shadow-sm" @click="m.ID_Solicitud && mostrarVerMovimiento(m.ID_Solicitud)">Ver</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Controles de Paginación -->
                <div class="flex justify-between items-center mt-4" x-show="totalPagesMovimientos > 1">
                    <div>
                        <span class="text-xs text-gray-600 font-medium">
                            Mostrando <span x-text="(currentPageMovimientos - 1) * rowsPerPageMovimientos + 1"></span> a <span
                                x-text="Math.min(currentPageMovimientos * rowsPerPageMovimientos, movimientosFiltrados.length)"></span> de <span
                                x-text="movimientosFiltrados.length"></span> resultados
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <button @click="cambiarPaginaMovimientos(1)" :disabled="currentPageMovimientos === 1"
                                class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs font-bold">&laquo;</button>
                        <button @click="cambiarPaginaMovimientos(currentPageMovimientos - 1)" :disabled="currentPageMovimientos === 1"
                                class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs font-bold">&lsaquo;</button>
                        
                        <span class="px-3 py-1 border rounded bg-blue-600 text-white text-xs font-bold" x-text="currentPageMovimientos"></span>
                        
                        <button @click="cambiarPaginaMovimientos(currentPageMovimientos + 1)" :disabled="currentPageMovimientos === totalPagesMovimientos"
                                class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs font-bold">&rsaquo;</button>
                        <button @click="cambiarPaginaMovimientos(totalPagesMovimientos)" :disabled="currentPageMovimientos === totalPagesMovimientos"
                                class="px-2 py-1 border rounded bg-white text-gray-700 hover:bg-gray-100 disabled:opacity-50 text-xs font-bold">&raquo;</button>
                    </div>
                </div>
            </div>

            <!-- CONTENEDOR SECUNDARIO: Ver Detalles Completos -->
            <div id="div-ver-movimiento" class="hidden">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Detalles de la Solicitud</h3>
                    <div class="cursor-pointer p-2 rounded-full hover:bg-gray-200 transition-colors" @click="regresarAMovimientos()" title="Regresar a la lista">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div id="detalles-movimiento-solicitud" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm min-h-[50vh]">
                    <!-- Renderizado dinámico desde JS (idéntico al historial) -->
                </div>
            </div>

            </div>
            </template>

            <!-- Pantalla 8: Reportes de Creditos -->
            <template x-if="pantalla === 'vencimientos'">
                <div class="animate-fadeIn bg-white rounded-xl border border-slate-200 p-4 sm:p-5">
                    <!-- CONTENEDOR PRINCIPAL: Tabla de Vencimientos -->
                    <div id="div-vencimientos">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
                            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-amber-700 flex items-center gap-1 font-semibold">&larr; Volver al menú</button>
                            <div class="flex flex-wrap items-center gap-3 md:gap-4">
                                <!-- Botón Exportar Excel -->
                                <button @click="exportarVencimientosExcel()" 
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Exportar Excel
                                </button>

                                <!-- Toggle Detallado (Estilo Presupuesto Global) -->
                                <div class="flex items-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="reporteDetallado" @change="currentPageVencimientos = 1" class="sr-only peer">
                                        <div class="relative w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
                                        <span class="ms-3 text-[10px] font-bold text-slate-700 uppercase tracking-tight">Reporte Detallado</span>
                                    </label>
                                </div>
                                <h2 class="text-xl font-bold text-slate-800">Reportes de Credito</h2>
                            </div>
                        </div>

                        <!-- Panel de Filtros Avanzados -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                <!-- Folio -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase ml-1 tracking-wide">Folio</label>
                                    <input type="text" x-model="filtrosFolioVenc" placeholder="Buscar folio..." 
                                           class="px-3 py-2 border border-slate-300 rounded-lg text-xs outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                                </div>

                                <!-- Proveedor -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase ml-1 tracking-wide">Proveedor</label>
                                    <select x-ref="choicesProvVenc" multiple>
                                        <?php if (!empty($proveedores)): ?>
                                            <?php foreach ($proveedores as $prov): ?>
                                                <option value="<?= esc($prov['ID_Proveedor']) ?>"><?= esc($prov['RazonSocial']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Razón Social -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase ml-1 tracking-wide">Razón Social</label>
                                    <select x-ref="choicesRazonVenc" multiple>
                                        <?php if (!empty($razones_sociales)): ?>
                                            <?php foreach ($razones_sociales as $rs): ?>
                                                <option value="<?= esc($rs['ID_RazonSocial']) ?>"><?= esc($rs['Nombre']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Complejo (Places) -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase ml-1 tracking-wide">Complejo</label>
                                    <select x-ref="choicesPlaceVenc" multiple>
                                        <?php if (!empty($places)): ?>
                                            <?php foreach ($places as $p): ?>
                                                <option value="<?= esc($p['ID_Place']) ?>"><?= esc($p['Nombre_Corto']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Departamento -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase ml-1 tracking-wide">Departamento</label>
                                    <select x-ref="choicesDeptoVenc" multiple>
                                        <?php if (!empty($departamentos)): ?>
                                            <?php foreach ($departamentos as $d): ?>
                                                <option value="<?= esc($d['ID_Dpto']) ?>"><?= esc($d['Nombre']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <!-- Estatus -->
                                <div class="flex flex-col gap-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase ml-1 tracking-wide">Estatus</label>
                                    <select x-model="filtroEstatusVenc" @change="currentPageVencimientos = 1"
                                            class="px-3 py-2 border border-slate-300 rounded-lg text-xs outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                                        <option value="">Todos</option>
                                        <option value="Vencido">Vencido</option>
                                        <option value="Pago Hoy">Pago Hoy</option>
                                        <option value="Por Vencer">Por Vencer</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex justify-end mt-4">
                                <button @click="limpiarFiltrosVencimientos()" class="px-4 py-1.5 bg-slate-800 text-white text-[10px] font-bold rounded-lg hover:bg-slate-900 transition-all uppercase tracking-widest">
                                    Limpiar Filtros
                                </button>
                            </div>
                        </div>

                        <!-- Tabla -->
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full border-collapse" id="tabla-vencimientos">
                                <thead class="bg-slate-100 text-slate-600 uppercase text-[9px] font-bold">
                                    <tr>
                                        <th class="border border-slate-200 px-2 py-2 text-center">Cód.</th>
                                        <th class="border border-slate-200 px-3 py-2 text-left" x-show="reporteDetallado">Folio</th>
                                        <th class="border border-slate-200 px-3 py-2 text-left">RFC</th>
                                        <th class="border border-slate-200 px-3 py-2 text-left">Razón Social</th>
                                        <th class="border border-slate-200 px-3 py-2 text-right" x-show="!reporteDetallado">Importe Crédito</th>
                                        <th class="border border-slate-200 px-3 py-2 text-right">Importe Por Pagar</th>
                                        <th class="border border-slate-200 px-3 py-2 text-right" x-show="hayExcedidosVencimientos" x-cloak>Importe Excedido</th>
                                        <th class="border border-slate-200 px-3 py-2 text-right" x-show="!reporteDetallado">Saldo Crédito</th>
                                        <th class="border border-slate-200 px-3 py-2 text-center">Días Créd.</th>
                                        <th class="border border-slate-200 px-3 py-2 text-center" x-show="reporteDetallado">Fecha Aprobación</th>
                                        <th class="border border-slate-200 px-3 py-2 text-center" x-show="reporteDetallado">Fecha Vencimiento</th>
                                        <th class="border border-slate-200 px-3 py-2 text-center">Estatus</th>
                                        <th class="border border-slate-200 px-3 py-2 text-center">Días Vencido</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <template x-if="cargando">
                                        <tr>
                                            <td colspan="11" class="text-center py-12 text-gray-500 italic">Cargando reporte de vencimientos...</td>
                                        </tr>
                                    </template>
                                    <template x-if="!cargando && paginatedVencimientos.length === 0">
                                        <tr>
                                            <td colspan="11" class="text-center py-12 text-gray-400 italic">No se encontraron datos para mostrar.</td>
                                        </tr>
                                    </template>
                                    <template x-for="(v, index) in paginatedVencimientos" :key="reporteDetallado ? 'venc-det-' + v.ID_Solicitud : 'venc-prov-' + v.ID_Proveedor">
                                        <tr class="text-center text-xs border-b border-slate-100 transition-colors" :class="v.claseSemaforo + (reporteDetallado ? ' cursor-pointer' : '')"
                                            @click="reporteDetallado && v.ID_Solicitud && mostrarVerMovimiento(v.ID_Solicitud)">
                                            <td class="px-2 py-2 font-mono font-bold" x-text="v.ID_Proveedor"></td>
                                            <td class="px-3 py-2 text-left font-bold text-blue-800" x-show="reporteDetallado" x-text="v.No_Folio"></td>
                                            <td class="px-3 py-2 text-left" x-text="v.RFC || 'N/A'"></td>
                                            <td class="px-3 py-2 text-left font-bold" x-text="v.RazonSocial"></td>
                                            <td class="px-3 py-2 text-right" x-show="!reporteDetallado" x-text="formatearMoneda(v.Monto_Credito)"></td>
                                            <td class="px-3 py-2 text-right font-bold" :class="reporteDetallado ? 'text-gray-700' : 'text-blue-700'" x-text="formatearMoneda(v.importePorPagar)"></td>
                                            <td class="px-3 py-2 text-right font-bold text-red-600" x-show="hayExcedidosVencimientos" x-cloak x-text="formatearMoneda(v.importeExcedido)"></td>
                                            <td class="px-3 py-2 text-right font-black" x-show="!reporteDetallado" :class="v.saldoCredito <= 0 ? 'text-gray-400' : 'text-green-700'" x-text="formatearMoneda(v.saldoCredito)"></td>
                                            <td class="px-3 py-2" x-text="v.Dias_Credito"></td>
                                            <td class="px-3 py-2" x-show="reporteDetallado" x-text="v.fechaReferenciaStr"></td>
                                            <td class="px-3 py-2 font-bold" x-show="reporteDetallado" x-text="v.fechaVencimientoStr"></td>
                                            <td class="px-3 py-2 font-bold" x-text="v.estatusVencimiento"></td>
                                            <td class="px-3 py-2 font-black uppercase tracking-tighter">
                                                <span x-text="v.textoVencimiento"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Resumen de Estados de Vencimiento -->
                        <div class="mt-8 grid grid-cols-1 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200" x-show="!cargando && vencimientosFiltrados.length > 0">
                            <div class="flex flex-col p-3 bg-white rounded-lg border-l-4 border-red-500">
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Total Vencido</span>
                                <span class="text-lg font-black text-red-700" x-text="formatearMoneda(resumenVencimientos.vencido)"></span>
                            </div>
                            <div class="flex flex-col p-3 bg-white rounded-lg border-l-4 border-blue-500">
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Pago Hoy</span>
                                <span class="text-lg font-black text-blue-700" x-text="formatearMoneda(resumenVencimientos.pagoHoy)"></span>
                            </div>
                            <div class="flex flex-col p-3 bg-white rounded-lg border-l-4 border-green-500">
                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Por Vencer</span>
                                <span class="text-lg font-black text-green-700" x-text="formatearMoneda(resumenVencimientos.porVencer)"></span>
                            </div>
                            <div class="flex flex-col p-3 bg-slate-800 rounded-lg">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total General</span>
                                <span class="text-lg font-black text-white" x-text="formatearMoneda(resumenVencimientos.total)"></span>
                            </div>
                        </div>

                        <!-- Controles de Paginación -->
                        <div class="flex justify-between items-center mt-4" x-show="totalPagesVencimientos > 1">
                            <span class="text-xs text-gray-600 font-medium">
                                Página <span x-text="currentPageVencimientos"></span> de <span x-text="totalPagesVencimientos"></span>
                            </span>

                            <div class="flex items-center gap-1">
                                <button @click="cambiarPaginaVencimientos(1)" :disabled="currentPageVencimientos === 1"
                                        class="px-2 py-1 border border-slate-300 rounded bg-white text-gray-700 hover:bg-slate-100 disabled:opacity-50 text-xs font-bold">&laquo;</button>
                                <button @click="cambiarPaginaVencimientos(currentPageVencimientos - 1)" :disabled="currentPageVencimientos === 1"
                                        class="px-2 py-1 border border-slate-300 rounded bg-white text-gray-700 hover:bg-slate-100 disabled:opacity-50 text-xs font-bold">&lsaquo;</button>

                                <span class="px-3 py-1 border rounded bg-yellow-600 text-white text-xs font-bold" x-text="currentPageVencimientos"></span>

                                <button @click="cambiarPaginaVencimientos(currentPageVencimientos + 1)" :disabled="currentPageVencimientos === totalPagesVencimientos"
                                        class="px-2 py-1 border border-slate-300 rounded bg-white text-gray-700 hover:bg-slate-100 disabled:opacity-50 text-xs font-bold">&rsaquo;</button>
                                <button @click="cambiarPaginaVencimientos(totalPagesVencimientos)" :disabled="currentPageVencimientos === totalPagesVencimientos"
                                        class="px-2 py-1 border border-slate-300 rounded bg-white text-gray-700 hover:bg-slate-100 disabled:opacity-50 text-xs font-bold">&raquo;</button>
                            </div>
                        </div>
                    </div>

                    <!-- CONTENEDOR SECUNDARIO: Ver Detalles Completos -->
                    <div id="div-ver-vencimiento" class="hidden bg-white rounded-xl border border-slate-200 p-4 sm:p-5">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Detalles de la Solicitud</h3>
                            <div class="cursor-pointer p-2 rounded-full hover:bg-slate-100 transition-colors" @click="regresarAMovimientos()" title="Regresar a la lista">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div id="detalles-vencimiento-solicitud" class="bg-white p-6 rounded-xl border border-slate-200 min-h-[50vh]">
                            <!-- Renderizado dinámico desde JS -->
                        </div>
                    </div>
                </div>
            </template>

            <!-- Pantalla: Solo Ejecutado -->
    <template x-if="pantalla === 'solo_ejecutado'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-cyan-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button x-show="departamentos.length > 0" @click="exportarSoloEjecutadoExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">Importe Ejecutado Mensual</h2>
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                    <select x-model="idRazonSocial" @change="actualizarRazonSocial('solo_ejecutado')" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-cyan-300 min-w-[200px] text-sm">
                        <option value="">Seleccione Razón Social</option>
                        <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                    <select x-ref="placesSelectorSoloEjecutado" multiple :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-cyan-300 min-w-[200px] text-sm disabled:bg-gray-100">
                        <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Año</label>
                    <select x-model="anio" @change="cargarComparativo()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-cyan-300 text-sm">
                        <template x-for="a in years" :key="a">
                            <option :value="a" x-text="a" :selected="a === anio"></option>
                        </template>
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[320px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Rango de Meses</label>
                    <div class="flex items-center gap-2">
                        <select x-model="mesInicio" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <span class="text-gray-400 font-bold">al</span>
                        <select x-model="mesFin" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Filtros Locales (Dptos y Partidas) -->
            <div x-show="!cargando && departamentosOriginales.length > 0" class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-white p-4 rounded-lg border border-gray-200 shadow-sm animate-fadeIn" x-cloak>
                <div class="flex flex-col gap-1 w-full md:w-[45%]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Áreas De Operación</label>
                    <select x-ref="filtroUnidad" multiple>
                        <template x-for="uni in departamentosOriginales" :key="uni.ID_UnidadOperativa">
                            <option :value="uni.ID_UnidadOperativa" x-text="(verGlobal ? uni.RazonSocialNombre + ' > ' + uni.PlaceNombre + ' > ' : '') + uni.Nombre"></option>
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

            <div class="border border-gray-300 rounded-lg overflow-x-auto shadow-sm w-full">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-800 text-white text-[10px] uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Departamento / Partida</th>
                            <!-- Columnas dinámicas de meses -->
                            <template x-if="mesesSeleccionados.length > 1">
                                <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                    <th class="px-4 py-3 text-right" x-text="mes.nombre"></th>
                                </template>
                            </template>
                            <th class="px-4 py-3 text-right" x-text="mesesSeleccionados.length > 1 ? 'Total Ejecutado' : 'Importe Ejecutado'"></th>
                        </tr>
                    </thead>

                    <tbody x-show="cargando || departamentos.length === 0">
                        <tr x-show="cargando"><td :colspan="mesesSeleccionados.length + 1" class="px-4 py-12 text-center text-gray-500 italic">Cargando datos...</td></tr>
                        <tr x-show="!cargando && departamentos.length === 0"><td :colspan="mesesSeleccionados.length + 1" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el reporte.</td></tr>
                    </tbody>

                    <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                        <tbody class="border-t-4 border-gray-500" x-show="grupoRS.totales.ejecutado > 0">
                            <tr class="bg-gray-200 font-black text-sm shadow-sm">
                                <td class="px-6 py-3 text-gray-900 uppercase tracking-wider text-[11px]" x-text="grupoRS.nombre"></td>
                                <template x-if="mesesSeleccionados.length > 1">
                                    <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                        <td class="px-4 py-3 text-right text-gray-900 font-bold" x-text="formatearMoneda(grupoRS.totales.importesPorMes?.[mes.id] || 0)"></td>
                                    </template>
                                </template>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.ejecutado)"></td>
                            </tr>

                            <template x-for="seg in grupoRS.segmentos" :key="seg.nombre">
                                <tbody class="contents" x-show="seg.totales.ejecutado > 0">
                                    <tr class="bg-cyan-50 border-l-4 border-cyan-500 font-bold text-xs">
                                        <td class="px-10 py-2 text-cyan-900 uppercase tracking-tight border-l-4 border-cyan-600" x-text="'📁 ' + seg.nombre"></td>
                                        <template x-if="mesesSeleccionados.length > 1">
                                            <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                                <td class="px-4 py-2 text-right text-cyan-900 font-bold" x-text="formatearMoneda(seg.totales.importesPorMes?.[mes.id] || 0)"></td>
                                            </template>
                                        </template>
                                        <td class="px-4 py-2 text-right text-cyan-900" x-text="formatearMoneda(seg.totales.ejecutado)"></td>
                                    </tr>

                                    <template x-for="complex in seg.complejos" :key="complex.nombre">
                                        <tbody class="contents" x-show="complex.totales.ejecutado > 0">
                                            <tr class="bg-white font-semibold text-[11px] text-gray-500 border-b border-gray-100">
                                                <td class="px-14 py-1 uppercase tracking-tighter" x-text="'📍 ' + complex.nombre"></td>
                                                <template x-if="mesesSeleccionados.length > 1">
                                                    <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                                        <td class="px-4 py-1 text-right text-gray-700 font-bold" x-text="formatearMoneda(complex.totales.importesPorMes?.[mes.id] || 0)"></td>
                                                    </template>
                                                </template>
                                                <td class="px-4 py-1 text-right" x-text="formatearMoneda(complex.totales.ejecutado)"></td>
                                            </tr>

                                            <template x-for="uni in complex.departamentos" :key="uni.ID_UnidadOperativa">
                                                <tbody class="contents" x-show="uni.totales?.ejecutado > 0">
                                                    <template x-for="(item, index) in [uni, ...(uni.detalles || [])]" :key="index">
                                                        <tr :class="index === 0 ? 'bg-gray-50/30 font-bold border-l-2 border-gray-300' : 'hover:bg-gray-50 border-b border-gray-50'"
                                                            x-show="index === 0 ? (uni.totales?.ejecutado > 0) : (parseFloat(item.ejecutado) > 0)">
                                                            <td class="px-6 py-2" :class="index === 0 ? 'text-gray-900 text-xs pl-20' : 'pl-28 text-gray-400 text-[11px]'">
                                                                <span x-show="index === 0">⚙️ </span>
                                                                <span x-text="index === 0 ? uni.Nombre : item.etiqueta"></span>
                                                                <template x-if="index !== 0 && (item.es_manual == 1 || item.es_manual === true || item.es_manual === 't')">
                                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Gastos ingresados como indirectos">
                                                                        INDIRECTO
                                                                    </span>
                                                                </template>
                                                            </td>
                                                            
                                                            <!-- Celdas dinámicas para cada mes -->
                                                            <template x-if="mesesSeleccionados.length > 1">
                                                                <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                                                    <td class="px-4 py-2 text-right text-gray-600 text-[11px]">
                                                                        <span :class="index === 0 ? 'font-bold text-gray-800' : ''" x-text="formatearMoneda(item.importesPorMes?.[mes.id] || 0)"></span>
                                                                    </td>
                                                                </template>
                                                            </template>

                                                            <td class="px-4 py-2 text-right text-gray-900 font-bold" x-text="index === 0 ? formatearMoneda(uni.totales?.ejecutado) : formatearMoneda(item.ejecutado)"></td>
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
                            <td class="px-6 py-4 font-black uppercase tracking-widest text-right">Total General:</td>
                            
                            <!-- Totales dinámicos por mes en el footer -->
                            <template x-if="mesesSeleccionados.length > 1">
                                <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                    <td class="px-4 py-4 text-right font-bold text-sm" x-text="formatearMoneda(totalesGeneralesCalculados.importesPorMes?.[mes.id] || 0)"></td>
                                </template>
                            </template>

                            <td class="px-4 py-4 text-right font-bold text-lg" x-text="formatearMoneda(totalesGeneralesCalculados.ejecutado)"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </template>

    <!-- Pantalla: Solo Presupuesto -->
    <template x-if="pantalla === 'solo_presupuesto'">
        <div class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-indigo-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button x-show="departamentos.length > 0" @click="exportarSoloPresupuestoExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <button x-show="departamentos.length > 0" @click="generarSoloPresupuestoPdf()" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7m-9 3h6m-6 3h6M6 9l6 3 6-3" />
                        </svg>
                        Generar PDF
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">Presupuesto Asignado Mensual</h2>
                </div>
            </div>

            <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                    <select x-model="idRazonSocial" @change="actualizarRazonSocial('solo_presupuesto')" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 min-w-[200px] text-sm">
                        <option value="">Seleccione Razón Social</option>
                        <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial"><option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                    <select x-ref="placesSelectorSoloPresupuesto" multiple :disabled="!idRazonSocial" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 min-w-[200px] text-sm disabled:bg-gray-100">
                        <template x-for="place in placesFiltrados" :key="place.ID_Place"><option :value="place.ID_Place" x-text="place.Nombre_Corto"></option></template>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-gray-500 uppercase">Año</label>
                    <select x-model="anio" @change="cargarComparativo()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-indigo-300 text-sm">
                        <template x-for="a in years" :key="a">
                            <option :value="a" x-text="a" :selected="a === anio"></option>
                        </template>
                    </select>
                </div>
                <div class="flex flex-col gap-1 min-w-[320px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Rango de Meses</label>
                    <div class="flex items-center gap-2">
                        <select x-model="mesInicio" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <span class="text-gray-400 font-bold">al</span>
                        <select x-model="mesFin" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col gap-1 self-center ml-auto">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="verGlobal" @change="cargarGlobal()" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ms-3 text-sm font-bold text-gray-700 uppercase tracking-tighter">🌍 Global</span>
                    </label>
                </div>
            </div>

            <!-- Filtros Locales (Dptos y Partidas) -->
            <div x-show="!cargando && departamentosOriginales.length > 0" class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-white p-4 rounded-lg border border-gray-200 shadow-sm animate-fadeIn" x-cloak>
                <div class="flex flex-col gap-1 w-full md:w-[45%]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Áreas De Operación</label>
                    <select x-ref="filtroUnidad" multiple>
                        <template x-for="uni in departamentosOriginales" :key="uni.ID_UnidadOperativa">
                            <option :value="uni.ID_UnidadOperativa" x-text="(verGlobal ? uni.RazonSocialNombre + ' > ' + uni.PlaceNombre + ' > ' : '') + uni.Nombre"></option>
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

            <div class="border border-gray-300 rounded-lg overflow-x-auto shadow-sm w-full">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-800 text-white text-[10px] uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Departamento / Partida</th>
                            <!-- Columnas dinámicas de meses -->
                            <template x-if="mesesSeleccionados.length > 1">
                                <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                    <th class="px-4 py-3 text-right" x-text="mes.nombre"></th>
                                </template>
                            </template>
                            <th class="px-4 py-3 text-right" x-text="mesesSeleccionados.length > 1 ? 'Total Asignado' : 'Importe Asignado'"></th>
                        </tr>
                    </thead>

                    <tbody x-show="cargando || departamentos.length === 0">
                        <tr x-show="cargando"><td :colspan="mesesSeleccionados.length + 1" class="px-4 py-12 text-center text-gray-500 italic">Cargando datos...</td></tr>
                        <tr x-show="!cargando && departamentos.length === 0"><td :colspan="mesesSeleccionados.length + 1" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el reporte.</td></tr>
                    </tbody>

                    <template x-for="grupoRS in departamentosAgrupados" :key="grupoRS.nombre">
                        <tbody class="border-t-4 border-gray-500" x-show="grupoRS.totales.asignado > 0">
                            <tr class="bg-gray-200 font-black text-sm shadow-sm">
                                <td class="px-6 py-3 text-gray-900 uppercase tracking-wider text-[11px]" x-text="grupoRS.nombre"></td>
                                <template x-if="mesesSeleccionados.length > 1">
                                    <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                        <td class="px-4 py-3 text-right text-gray-900 font-bold" x-text="formatearMoneda(grupoRS.totales.importesPorMes?.[mes.id] || 0)"></td>
                                    </template>
                                </template>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(grupoRS.totales.asignado)"></td>
                            </tr>

                            <template x-for="seg in grupoRS.segmentos" :key="seg.nombre">
                                <tbody class="contents" x-show="seg.totales.asignado > 0">
                                    <tr class="bg-indigo-50 border-l-4 border-indigo-500 font-bold text-xs">
                                        <td class="px-10 py-2 text-indigo-900 uppercase tracking-tight border-l-4 border-indigo-600" x-text="'📁 ' + seg.nombre"></td>
                                        <template x-if="mesesSeleccionados.length > 1">
                                            <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                                <td class="px-4 py-2 text-right text-indigo-900 font-bold" x-text="formatearMoneda(seg.totales.importesPorMes?.[mes.id] || 0)"></td>
                                            </template>
                                        </template>
                                        <td class="px-4 py-2 text-right text-indigo-900" x-text="formatearMoneda(seg.totales.asignado)"></td>
                                    </tr>

                                    <template x-for="complex in seg.complejos" :key="complex.nombre">
                                        <tbody class="contents" x-show="complex.totales.asignado > 0">
                                            <tr class="bg-white font-semibold text-[11px] text-gray-500 border-b border-gray-100">
                                                <td class="px-14 py-1 uppercase tracking-tighter" x-text="'📍 ' + complex.nombre"></td>
                                                <template x-if="mesesSeleccionados.length > 1">
                                                    <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                                        <td class="px-4 py-1 text-right text-gray-700 font-bold" x-text="formatearMoneda(complex.totales.importesPorMes?.[mes.id] || 0)"></td>
                                                    </template>
                                                </template>
                                                <td class="px-4 py-1 text-right" x-text="formatearMoneda(complex.totales.asignado)"></td>
                                            </tr>

                                            <template x-for="uni in complex.departamentos" :key="uni.ID_UnidadOperativa">
                                                <tbody class="contents" x-show="uni.totales?.asignado > 0">
                                                    <template x-for="(item, index) in [uni, ...(uni.detalles || [])]" :key="index">
                                                        <tr :class="index === 0 ? 'bg-gray-50/30 font-bold border-l-2 border-gray-300' : 'hover:bg-gray-50 border-b border-gray-50'"
                                                            x-show="index === 0 ? (uni.totales?.asignado > 0) : (parseFloat(item.asignado) > 0)">
                                                            <td class="px-6 py-2" :class="index === 0 ? 'text-gray-900 text-xs pl-20' : 'pl-28 text-gray-400 text-[11px]'">
                                                                <span x-show="index === 0">⚙️ </span>
                                                                <span x-text="index === 0 ? uni.Nombre : item.etiqueta"></span>
                                                                <template x-if="index !== 0 && (item.es_manual == 1 || item.es_manual === true || item.es_manual === 't')">
                                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Gastos ingresados como indirectos">
                                                                        INDIRECTO
                                                                    </span>
                                                                </template>
                                                            </td>
                                                            
                                                            <!-- Celdas dinámicas para cada mes -->
                                                            <template x-if="mesesSeleccionados.length > 1">
                                                                <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                                                    <td class="px-4 py-2 text-right text-gray-600 text-[11px]">
                                                                        <span :class="index === 0 ? 'font-bold text-gray-800' : ''" x-text="formatearMoneda(item.importesPorMes?.[mes.id] || 0)"></span>
                                                                    </td>
                                                                </template>
                                                            </template>

                                                            <td class="px-4 py-2 text-right text-gray-900 font-bold" x-text="index === 0 ? formatearMoneda(uni.totales?.asignado) : formatearMoneda(item.asignado)"></td>
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
                            <td class="px-6 py-4 font-black uppercase tracking-widest text-right">Total General:</td>
                            
                            <!-- Totales dinámicos por mes en el footer -->
                            <template x-if="mesesSeleccionados.length > 1">
                                <template x-for="mes in mesesSeleccionados" :key="mes.id">
                                    <td class="px-4 py-4 text-right font-bold text-sm" x-text="formatearMoneda(totalesGeneralesCalculados.importesPorMes?.[mes.id] || 0)"></td>
                                </template>
                            </template>

                            <td class="px-4 py-4 text-right font-bold text-lg" x-text="formatearMoneda(totalesGeneralesCalculados.asignado)"></td>
                        </tr>
                    </tfoot>
                </table>
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
                    <h2 class="text-xl font-bold text-gray-800">Presupuesto vs Ejecutado Mensual</h2>
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
                <div class="flex flex-col gap-1 min-w-[320px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Rango de Meses</label>
                    <div class="flex items-center gap-2">
                        <select x-model="mesInicio" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <span class="text-gray-400 font-bold">al</span>
                        <select x-model="mesFin" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col gap-1 self-center ml-auto">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="verGlobal" @change="cargarGlobal()" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-bold text-gray-700 uppercase tracking-tighter">🌍 Presupuesto Global</span>
                    </label>
                </div>
            </div>

            <!-- Filtros Locales (Dptos y Partidas) -->
            <div x-show="!cargando && departamentosOriginales.length > 0" class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-white p-4 rounded-lg border border-gray-200 shadow-sm animate-fadeIn" x-cloak>
                <div class="flex flex-col gap-1 w-full md:w-[45%]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Áreas De Operación</label>
                    <select x-ref="filtroUnidad" multiple>
                        <template x-for="uni in departamentosOriginales" :key="uni.ID_UnidadOperativa">
                            <option :value="uni.ID_UnidadOperativa" x-text="(verGlobal ? uni.RazonSocialNombre + ' > ' + uni.PlaceNombre + ' > ' : '') + uni.Nombre"></option>
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
                                                                <template x-if="index !== 0 && (item.es_manual == 1 || item.es_manual === true || item.es_manual === 't')">
                                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Gastos ingresados como indirectos">
                                                                        INDIRECTO
                                                                    </span>
                                                                </template>
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
                    <h2 class="text-xl font-bold text-gray-800">Saldos Iniciales/Finales De Bancos</h2>
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
                <div class="flex flex-col gap-1 min-w-[320px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Rango de Meses</label>
                    <div class="flex items-center gap-2">
                        <select x-model="mesInicio" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <span class="text-gray-400 font-bold">al</span>
                        <select x-model="mesFin" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                    </div>
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
                    <h2 class="text-xl font-bold text-gray-800">Reporte Consolidado Mensual</h2>
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
                <div class="flex flex-col gap-1 min-w-[320px]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Rango de Meses</label>
                    <div class="flex items-center gap-2">
                        <select x-model="mesInicio" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <span class="text-gray-400 font-bold">al</span>
                        <select x-model="mesFin" @change="actualizarMesesDesdeIntervalo()" class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none text-sm font-bold text-gray-700">
                            <option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col gap-1 self-center ml-auto">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="verGlobal" @change="cargarGlobal()" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        <span class="ms-3 text-sm font-bold text-gray-700 uppercase tracking-tighter">🌍 Global</span>
                    </label>
                </div>
            </div>

            <!-- Filtros Locales (Dptos y Partidas) -->
            <div x-show="!cargando && departamentosOriginales.length > 0" class="flex flex-wrap items-start gap-x-6 gap-y-4 mb-6 bg-white p-4 rounded-lg border border-gray-200 shadow-sm animate-fadeIn" x-cloak>
                <div class="flex flex-col gap-1 w-full md:w-[45%]">
                    <label class="text-xs font-bold text-gray-500 uppercase">Filtrar Áreas De Operación</label>
                    <select x-ref="filtroUnidad" multiple>
                        <template x-for="uni in departamentosOriginales" :key="uni.ID_UnidadOperativa">
                            <option :value="uni.ID_UnidadOperativa" x-text="(verGlobal ? uni.RazonSocialNombre + ' > ' + uni.PlaceNombre + ' > ' : '') + uni.Nombre"></option>
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
                                                                <template x-if="index !== 0 && (item.es_manual == 1 || item.es_manual === true || item.es_manual === 't')">
                                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold bg-amber-100 text-amber-800 border border-amber-200" title="Gastos ingresados como indirectos">
                                                                        INDIRECTO
                                                                    </span>
                                                                </template>
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

                    <tfoot x-show="!cargando && departamentosCompleto.length > 0" class="bg-gray-800 text-white">
                        <tr>
                            <td class="px-4 py-4 font-black uppercase tracking-widest text-right">Total:</td>
                            <td class="px-2 py-4 text-right font-bold text-[11px]" x-text="formatearMoneda(totalesGeneralesCalculados.pAsignado)"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px] text-orange-300" x-text="formatearMoneda(totalesGeneralesCalculados.pComprometido)"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px] text-blue-300" x-text="formatearMoneda(totalesGeneralesCalculados.pEjecutado)"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px] text-gray-300" x-text="formatearMoneda(totalesGeneralesCalculados.pComprometido + totalesGeneralesCalculados.pEjecutado)"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px]" :class="totalesGeneralesCalculados.pDisponible <= 0 ? 'text-gray-400' : 'text-green-400'" x-text="formatearMoneda(totalesGeneralesCalculados.pDisponible)"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px] text-red-400" x-show="hayExcedidos" x-cloak x-text="formatearMoneda(totalesGeneralesCalculados.pExcedido)"></td>
                            <td class="px-2 py-4 text-center font-bold text-[10px]" :class="totalesGeneralesCalculados.pPorcentaje >= 100 ? 'text-red-400' : 'text-green-400'" x-text="(totalesGeneralesCalculados.pPorcentaje || 0) + '%'"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px] border-l border-gray-600" x-text="formatearMoneda(totalesGeneralesCalculados.bInicial)"></td>
                            <td class="px-2 py-4 text-right font-bold text-[11px]" x-text="formatearMoneda(totalesGeneralesCalculados.bFinal)"></td>
                            <td class="px-4 py-4 text-right font-bold text-base" :class="totalesGeneralesCalculados.bFinal < totalesGeneralesCalculados.bInicial ? 'text-red-400' : 'text-green-400'" x-text="formatearMoneda(totalesGeneralesCalculados.bInicial - totalesGeneralesCalculados.bFinal)"></td>
                        </tr>
                    </tfoot>
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
