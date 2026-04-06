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
        <div id="pantalla-lista-proveedores" class="animate-fadeIn bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-orange-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button @click="exportarProveedoresExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-2xl font-semibold text-center text-gray-800">Lista de Proveedores</h2>
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
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
                    <label for="buscar-servicio-rep" class="sr-only">Buscar por servicio</label>
                    <input type="text" id="buscar-servicio-rep" 
                           x-model="filtroServicioProveedor"
                           @input.debounce.300ms="aplicarFiltrosProveedor()"
                           placeholder="Buscar por servicio..." 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
                </div>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
                    <thead class="bg-gray-100">
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
                        <template x-for="(prov, index) in paginatedProveedores" :key="prov.ID_Proveedor">
                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'" class="hover:bg-blue-50 transition-colors">
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
                <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
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

    <!-- Pantalla 6: Reporte de Compras -->
    <template x-if="pantalla === 'compras'">
        <div id="div-reportes" x-data="Reportes(<?= htmlspecialchars(json_encode($tabledata ?? []), ENT_QUOTES, 'UTF-8') ?>)" class="animate-fadeIn">
            <div class="flex items-center justify-between mb-6">
                <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-red-600 flex items-center gap-1 font-medium">&larr; Volver al menú</button>
                <div class="flex items-center gap-4">
                    <button @click="generarReporteCSV" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-all text-xs font-bold shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </button>
                    <h2 class="text-xl font-bold text-gray-800">Reporte de Compras</h2>
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
                            <th class="border px-3 py-2 text-center">Estado</th>
                            <th class="border px-3 py-2 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <template x-if="paginatedData.length === 0">
                            <tr>
                                <td colspan="7" class="text-center py-12 text-gray-400 italic">No se encontraron datos que coincidan con los filtros.</td>
                            </tr>
                        </template>
                        <template x-for="(item, index) in paginatedData" :key="item.ID_Solicitud || ('row-' + index)">
                            <tr class="text-center hover:bg-gray-50 text-xs">
                                <td class="border px-3 py-2 text-left font-mono" x-text="item.No_Folio"></td>
                                <td class="border px-3 py-2 text-left" x-text="item.DepartamentoNombre"></td>
                                <td class="border px-3 py-2 text-left" x-text="item.Complejo"></td>
                                <td class="border px-3 py-2 text-left" x-text="item.proveedor?.RazonSocial"></td>
                                <td class="border px-3 py-2 text-left" x-text="item.Fecha"></td>
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
            <div id="div-ver-reporte" class="hidden animate-fadeIn"></div>
        </div>
    </template>

    <!-- Pantalla 7: Movimientos de Proveedor -->
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
                        <h2 class="text-xl font-bold text-gray-800">Movimientos de Proveedor</h2>
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
                                <th class="border px-3 py-2 text-left">Depto.</th>
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
                            <template x-for="(m, index) in paginatedMovimientos" :key="m.ID_Solicitud || index">
                                <tr class="text-center hover:bg-gray-50 text-xs">
                                    <td class="border px-3 py-2 text-left font-mono font-bold text-blue-700" x-text="m.No_Folio"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.Fecha"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.Fecha_Aprobacion || '-'"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.RazonSocialNombre"></td>
                                    <td class="border px-3 py-2 text-left font-medium" x-text="m.ProveedorNombre"></td>
                                    <td class="border px-3 py-2 text-right font-bold" x-text="formatearMoneda(m.MontoTotal)"></td>
                                    <td class="border px-3 py-2 text-left" x-text="m.DepartamentoNombre"></td>
                                    <td class="border px-3 py-2 text-left font-bold text-green-700" x-text="m.FechaPagoRealizado || 'Pendiente'"></td>
                                    <td class="border px-3 py-2">
                                        <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1 rounded transition text-[10px] uppercase shadow-sm" @click="mostrarVerMovimiento(m.ID_Solicitud)">Ver</button>
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
