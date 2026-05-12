<div x-data="FichasPago()" x-init="init()" @reload-pagos-fichas.window="init()" class="p-4 bg-slate-50 min-h-[400px]">
    
    <!-- Pantalla 0: Menú Principal de Selección -->
    <div x-show="!screen || screen === 'menu'" class="p-6">
        <h2 class="text-xl font-black text-slate-800 mb-6 uppercase tracking-tighter">Gestión de Facturas Pendientes</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <button @click="screen = 'contado'"
                    class="group relative overflow-hidden p-6 bg-white border border-slate-200 rounded-2xl hover:border-blue-500 hover:shadow-xl transition-all text-left">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-slate-200 group-hover:text-blue-100 transition-colors" x-text="countContado"></span>
                </div>
                <p class="font-black text-slate-800 uppercase tracking-tight">Pago de Contado</p>
                <p class="text-xs text-slate-500 font-medium mt-1">Facturas listas para liquidación inmediata</p>
            </button>

            <button @click="screen = 'credito'"
                    class="group relative overflow-hidden p-6 bg-white border border-slate-200 rounded-2xl hover:border-emerald-500 hover:shadow-xl transition-all text-left">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-slate-200 group-hover:text-emerald-100 transition-colors" x-text="countCredito"></span>
                </div>
                <p class="font-black text-slate-800 uppercase tracking-tight">Pago a Crédito</p>
                <p class="text-xs text-slate-500 font-medium mt-1">Órdenes con fecha de vencimiento programada</p>
            </button>
        </div>
    </div>

    <!-- Pantalla 1: Listado de Contado -->
    <div x-show="screen === 'contado'" class="p-4 sm:p-6 animate-fadeIn">
        <div class="flex justify-between items-center mb-6">
            <button @click="screen = 'menu'" class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-slate-800 transition-colors uppercase tracking-widest">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Regresar
            </button>
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">Órdenes de Contado</h3>
        </div>

        <!-- Filtros Contado -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            <input type="text" x-model="filtros.contado.search" placeholder="Buscar por folio o proveedor..." 
                   class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            <select x-model="filtros.contado.depto" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none">
                <option value="">Todos los Departamentos</option>
                <template x-for="d in opcionesFiltro.deptos" :key="d"><option :value="d" x-text="d"></option></template>
            </select>
            <select x-model="filtros.contado.complejo" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none">
                <option value="">Todos los Complejos</option>
                <template x-for="c in opcionesFiltro.complejos" :key="c"><option :value="c" x-text="c"></option></template>
            </select>
        </div>

        <div id="tabla-contado" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-4 py-4">Depto / Proyecto</th>
                        <th class="px-4 py-4">Folio / Proveedor</th>
                        <th class="px-4 py-4">Banco</th>
                        <th class="px-4 py-4 text-right">Importe</th>
                        <th class="px-4 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="f in getFichas('0')" :key="f.ID_Solicitud">
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800" x-text="f.DepartamentoNombre"></div>
                                <div class="text-[10px] text-slate-400 uppercase font-medium" x-text="f.Complejo"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono text-blue-600 font-bold" x-text="f.No_Folio"></div>
                                <div class="text-xs text-slate-500 font-medium" x-text="f.RazonSocial"></div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-medium" x-text="f.Banco || '-'"></td>
                            <td class="px-4 py-3 text-right font-black text-slate-800" x-text="formatCurrency(f.Total)"></td>
                            <td class="px-4 py-3 text-center">
                                <button @click="mostrarDetalleFicha(f.ID_Solicitud, '0')" class="px-3 py-1 bg-slate-800 text-white rounded-lg text-[10px] font-black uppercase hover:bg-blue-600 transition-colors">Ver</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="getFichas('0').length === 0">
                        <td colspan="5" class="py-12 text-center text-slate-400 italic">No se encontraron facturas de contado con estos filtros.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="detalle-contado" class="hidden mt-6 p-6 bg-white border border-slate-200 rounded-2xl shadow-xl"></div>
    </div>

    <!-- Pantalla 2: Listado de Crédito -->
    <div x-show="screen === 'credito'" class="p-4 sm:p-6 animate-fadeIn">
        <div class="flex justify-between items-center mb-6">
            <button @click="screen = 'menu'" class="flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-slate-800 transition-colors uppercase tracking-widest">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                Regresar
            </button>
            <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter">Vencimientos de Crédito</h3>
        </div>

        <!-- Filtros Crédito -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            <input type="text" x-model="filtros.credito.search" placeholder="Buscar por folio o proveedor..." 
                   class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
            <select x-model="filtros.credito.depto" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none">
                <option value="">Todos los Departamentos</option>
                <template x-for="d in opcionesFiltro.deptos" :key="d"><option :value="d" x-text="d"></option></template>
            </select>
            <select x-model="filtros.credito.complejo" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none">
                <option value="">Todos los Complejos</option>
                <template x-for="c in opcionesFiltro.complejos" :key="c"><option :value="c" x-text="c"></option></template>
            </select>
        </div>

        <div id="tabla-credito" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-slate-50 text-slate-400 font-bold uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-4 py-4">Vencimiento</th>
                        <th class="px-4 py-4">Folio / Proveedor</th>
                        <th class="px-4 py-4">Importe</th>
                        <th class="px-4 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="f in getFichas('1')" :key="f.ID_Solicitud">
                        <tr :class="f.semaforo.clase" class="transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-bold uppercase text-[10px] tracking-tight" x-text="f.semaforo.diasTexto"></div>
                                <div class="text-[9px] opacity-60 font-medium" x-text="f.DepartamentoNombre"></div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-mono font-bold" x-text="f.No_Folio"></div>
                                <div class="text-xs opacity-80 font-medium truncate max-w-[150px]" x-text="f.RazonSocial"></div>
                            </td>
                            <td class="px-4 py-3 font-black" x-text="formatCurrency(f.Total)"></td>
                            <td class="px-4 py-3 text-center">
                                <button @click="mostrarDetalleFicha(f.ID_Solicitud, '1')" class="px-3 py-1 bg-white/20 border border-current rounded-lg text-[10px] font-black uppercase hover:bg-white hover:text-slate-900 transition-all">Ver</button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="getFichas('1').length === 0">
                        <td colspan="4" class="py-12 text-center text-slate-400 italic">No hay órdenes de crédito pendientes de pago.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="detalle-credito" class="hidden mt-6 p-6 bg-white border border-slate-200 rounded-2xl shadow-xl"></div>
    </div>

</div>
<script src="<?= base_url() ?>js/pago.js?v=<?= filemtime(FCPATH . 'js/pago.js') ?>"></script>