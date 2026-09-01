<div x-data="Object.assign(FichasPago(), { screen: 'menu' })" x-init="init()" @reload-pagos-fichas.window="init()">
    <!-- Pantalla principal -->
    <div x-show="screen === 'menu'" id="pagos-menu" class="p-6">
        <!-- Descargas del reporte (contado + crédito) -->
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Descargar Reporte Global</span>
            <div class="flex items-center bg-gray-100/[0.05] border border-gold-metallic/20 rounded-full overflow-hidden shadow-sm">
                <a @click="exportarFacturasPendientes(null, 'pdf')" class="cursor-pointer px-5 py-2.5 text-[10px] font-black text-rose-600 hover:bg-rose-600 hover:text-white transition-all border-r border-gold-metallic/20 flex items-center gap-2 group">
                    <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    PDF
                </a>
                <a @click="exportarFacturasPendientes(null, 'excel')" class="cursor-pointer px-5 py-2.5 text-[10px] font-black text-emerald-500 hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-2 group">
                    <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    EXCEL
                </a>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            <button @click="screen = 'contado'"
                    class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition ">
                <p class="font-medium">Facturas Pendientes Contado</p>
                <p id="count-contado-fichas" class="text-xs opacity-75" x-text="countContado + ' pendientes'"></p>
            </button>

            <button @click="screen = 'credito'"
                    class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <p class="font-medium">Facturas Pendientes Crédito</p>
                <p id="count-credito-fichas" class="text-xs opacity-75" x-text="countCredito + ' pendientes'"></p>
            </button>
        </div>
    </div>

    <!-- ================== Pago de contado ================== -->
    <div x-show="screen === 'contado'" id="pago-contado" class="p-6">

        <!-- Tabla de solicitudes de contado -->
        <div id="tabla-contado" class="overflow-x-auto">

            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                <button @click="screen = 'menu'" class="text-base text-black hover:text-emerald-700 flex items-center gap-1 font-semibold">&larr; Regresar</button>
                <div class="flex items-center bg-gray-100/[0.05] border border-gold-metallic/20 rounded-full overflow-hidden shadow-sm">
                    <a @click="exportarFacturasPendientes('0', 'pdf')" class="cursor-pointer px-5 py-2.5 text-[10px] font-black text-rose-600 hover:bg-rose-600 hover:text-white transition-all border-r border-gold-metallic/20 flex items-center gap-2 group">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        PDF
                    </a>
                    <a @click="exportarFacturasPendientes('0', 'excel')" class="cursor-pointer px-5 py-2.5 text-[10px] font-black text-emerald-500 hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-2 group">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        EXCEL
                    </a>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Pago de contado</h2>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="flex-grow">
                    <input type="text" x-model="filtros.contado.search" placeholder="Buscar por Folio o Proveedor..."
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.contado.depto" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Deptos</option>
                        <template x-for="d in opcionesFiltro.deptos" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.contado.complejo" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Complejos</option>
                        <template x-for="c in opcionesFiltro.complejos" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
            </div>

            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800 font-bold">
                <tr>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b">Fecha de pago</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-contado">
                    <template x-for="f in getFichas('0')" :key="f.ID_Solicitud">
                        <tr class="hover:bg-gray-50 transition border-b">
                            <td class="px-4 py-2" x-text="f.DepartamentoNombre"></td>
                            <td class="px-4 py-2" x-text="f.Complejo"></td>
                            <td class="px-4 py-2 font-mono text-blue-600 font-bold" x-text="f.No_Folio"></td>
                            <td class="px-4 py-2" x-text="f.RazonSocial"></td>
                            <td class="px-4 py-2 text-gray-500" x-text="f.Banco || '-'"></td>
                            <td class="px-4 py-2 font-semibold" x-text="formatCurrency(f.Total)"></td>
                            <td class="px-4 py-2" x-text="formatFecha(f.Fecha_Comprobante)"></td>
                            <td class="px-4 py-2 text-center">
                                <button @click="mostrarDetalleFicha(f.ID_Solicitud, '0')"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-xs uppercase">
                                    VER
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="getFichas('0').length === 0">
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500" x-text="loading ? 'Cargando datos...' : 'No hay registros disponibles.'"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="detalle-contado" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
    </div>

    <!-- ================== Pago a crédito ================== -->
    <div x-show="screen === 'credito'" id="pago-credito" class="p-6">

        <!-- Tabla de solicitudes a crédito -->
        <div id="tabla-credito" class="overflow-x-auto">

            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
                <button @click="screen = 'menu'" class="text-base text-black hover:text-emerald-700 flex items-center gap-1 font-semibold">&larr; Regresar</button>
                <div class="flex items-center bg-gray-100/[0.05] border border-gold-metallic/20 rounded-full overflow-hidden shadow-sm">
                    <a @click="exportarFacturasPendientes('1', 'pdf')" class="cursor-pointer px-5 py-2.5 text-[10px] font-black text-rose-600 hover:bg-rose-600 hover:text-white transition-all border-r border-gold-metallic/20 flex items-center gap-2 group">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        PDF
                    </a>
                    <a @click="exportarFacturasPendientes('1', 'excel')" class="cursor-pointer px-5 py-2.5 text-[10px] font-black text-emerald-500 hover:bg-emerald-600 hover:text-white transition-all flex items-center gap-2 group">
                        <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        EXCEL
                    </a>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Pago a crédito</h2>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="flex-grow">
                    <input type="text" x-model="filtros.credito.search" placeholder="Buscar por Folio o Proveedor..."
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.credito.depto" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Deptos</option>
                        <template x-for="d in opcionesFiltro.deptos" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.credito.complejo" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Complejos</option>
                        <template x-for="c in opcionesFiltro.complejos" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
            </div>

            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800 font-bold">
                <tr>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b">Fecha de pago</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-credito">
                    <template x-for="f in getFichas('1')" :key="f.ID_Solicitud">
                        <tr class="transition border-b">
                            <td class="px-4 py-2" x-text="f.DepartamentoNombre"></td>
                            <td class="px-4 py-2" x-text="f.Complejo"></td>
                            <td class="px-4 py-2 font-mono font-bold" x-text="f.No_Folio"></td>
                            <td class="px-4 py-2" x-text="f.RazonSocial"></td>
                            <td class="px-4 py-2" x-text="f.Banco || '-'"></td>
                            <td class="px-4 py-2 font-bold" x-text="formatCurrency(f.Total)"></td>
                            <td class="px-4 py-2" x-text="formatFecha(f.Fecha_Comprobante)"></td>
                            <td class="px-4 py-2 text-center">
                                <button @click="mostrarDetalleFicha(f.ID_Solicitud, '1')"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-xs uppercase">
                                    VER
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="getFichas('1').length === 0">
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500" x-text="loading ? 'Cargando datos...' : 'No hay registros disponibles.'"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="detalle-credito" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
    </div>
</div>
<script src="<?= base_url() ?>js/pago.js?v=<?= filemtime(FCPATH . 'js/pago.js') ?>"></script>
