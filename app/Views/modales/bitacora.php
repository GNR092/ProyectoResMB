<div class="flex flex-col h-[calc(100vh-200px)] bg-gray-50 font-sans text-slate-900" x-data="bitacoraApp()">
    <!-- Header / Filtros -->
    <div class="p-6 bg-white border-b border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800 tracking-tight">Auditoría de Bitácora</h2>
                <p class="text-sm text-slate-500 mt-1">Trazabilidad completa de eventos y cambios en el sistema.</p>
            </div>
            <div class="flex gap-3">
                <button @click="fetchData()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-lg shadow-indigo-100 active:scale-95">
                    <i class="fas fa-sync-alt" :class="loading ? 'animate-spin' : ''"></i>
                    Actualizar Datos
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Módulo del Sistema</label>
                <select x-model="filters.modulo" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                    <option value="">Todos los módulos</option>
                    <template x-for="mod in catalogos.modulos" :key="mod">
                        <option :value="mod" x-text="mod"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tipo de Operación</label>
                <select x-model="filters.tipo_accion" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                    <option value="">Todas las acciones</option>
                    <option value="LOGIN">Login</option>
                    <option value="INSERTAR">Creación (Insert)</option>
                    <option value="ACTUALIZAR">Modificación (Update)</option>
                    <option value="ELIMINAR">Eliminación (Delete)</option>
                    <option value="SISTEMA_ERROR">Errores Críticos</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fecha Inicial</label>
                <input type="date" x-model="filters.fecha_inicio" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fecha Final</label>
                <input type="date" x-model="filters.fecha_fin" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="flex flex-1 overflow-hidden relative">
        <!-- Tabla -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <div class="flex-1 overflow-auto p-6">
                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm h-full flex flex-col">
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-left border-collapse relative">
                            <thead class="bg-slate-50 border-b border-slate-200 sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha / Hora</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Usuario</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Módulo</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Acción</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID Referencia</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Dirección IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="item in items" :key="item.id">
                                    <tr @click="selectItem(item)" 
                                        :class="selectedItem && selectedItem.id === item.id ? 'bg-indigo-50/50' : 'hover:bg-slate-50'" 
                                        class="cursor-pointer transition-all group border-l-4 border-transparent"
                                        :style="selectedItem && selectedItem.id === item.id ? 'border-left-color: #4f46e5;' : ''">
                                        <td class="px-6 py-4 text-[11px] font-mono font-bold text-slate-500" x-text="formatDate(item.fecha_hora)"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-[10px] font-black text-white shadow-md" x-text="getInitials(item.nombre_usuario_real || item.nombre_usuario)"></div>
                                                <div class="text-xs font-bold text-slate-700" x-text="item.nombre_usuario_real || item.nombre_usuario"></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-500 border border-slate-200" x-text="item.modulo"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="getActionClass(item.tipo_accion)" class="px-2.5 py-1 rounded-lg text-[10px] font-black border uppercase tracking-tighter" x-text="item.tipo_accion"></span>
                                        </td>
                                        <td class="px-6 py-4 text-[11px] font-mono font-bold text-slate-400" x-text="item.solicitud_id || item.orden_compra_id || '-'"></td>
                                        <td class="px-6 py-4 text-[11px] text-slate-400 font-mono" x-text="item.ip_address"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer Paginación Rediseñado -->
            <div class="px-8 py-6 bg-white border-t border-slate-200 flex items-center justify-between shadow-[0_-4px_20px_-5px_rgba(0,0,0,0.05)]">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Resumen de vista</span>
                    <div class="text-xs text-slate-600">
                        Mostrando <span class="font-black text-indigo-600" x-text="items.length"></span> de <span class="font-black text-slate-800" x-text="total"></span> registros encontrados
                    </div>
                </div>
                
                <div class="flex items-center gap-4 bg-slate-50 p-1.5 rounded-2xl border border-slate-200">
                    <button @click="changePage(pagination.page - 1)" 
                            :disabled="pagination.page === 1" 
                            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm active:scale-95">
                        <i class="fas fa-arrow-left"></i>
                        Anterior
                    </button>
                    
                    <div class="px-4 flex flex-col items-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase">Página</span>
                        <span class="text-sm font-black text-indigo-600" x-text="pagination.page + ' / ' + pagination.totalPages"></span>
                    </div>
                    
                    <button @click="changePage(pagination.page + 1)" 
                            :disabled="pagination.page === pagination.totalPages" 
                            class="flex items-center gap-2 px-4 py-2 rounded-xl border border-indigo-200 bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-md shadow-indigo-100 active:scale-95">
                        Siguiente
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel Lateral Rediseñado -->
        <div class="w-[500px] border-l border-slate-200 bg-white shadow-2xl flex flex-col absolute right-0 top-0 h-full z-30" 
             x-show="selectedItem !== null" 
             x-transition:enter="transition ease-out duration-300" 
             x-transition:enter-start="transform translate-x-full" 
             x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="transform translate-x-0"
             x-transition:leave-end="transform translate-x-full">
            
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                        <i class="fas fa-database text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase text-xs tracking-widest">Detalles del Evento</h3>
                        <p class="text-[10px] text-slate-500 font-medium" x-text="'ID Registro: #' + selectedItem?.id"></p>
                    </div>
                </div>
                
                <!-- Botón de Cerrar Visual e Intuitivo -->
                <button @click="closeDetails()" class="group flex items-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-xl border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm active:scale-95">
                    <span class="text-xs font-black uppercase tracking-tight">Cerrar Detalles</span>
                    <i class="fas fa-times-circle text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-auto p-8 space-y-8 bg-slate-50/30">
                <!-- Info Rápida -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
                        <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Dirección IP</label>
                        <span class="text-xs font-mono font-bold text-slate-800" x-text="selectedItem?.ip_address"></span>
                    </div>
                    <div class="p-4 rounded-2xl bg-white border border-slate-100 shadow-sm">
                        <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Clasificación</label>
                        <span class="text-xs font-bold text-slate-800" x-text="selectedItem?.clasificacion || 'General'"></span>
                    </div>
                </div>

                <!-- Comparador de Cambios con Estilo "Code" -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Auditoría de Datos</span>
                        <span :class="getActionClass(selectedItem?.tipo_accion)" class="px-3 py-1 rounded-full text-[9px] font-black border uppercase tracking-wider" x-text="selectedItem?.tipo_accion"></span>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <div class="text-[10px] font-black text-red-500 uppercase flex items-center gap-2 px-1">
                                <i class="fas fa-minus-circle"></i>
                                Valores Antes del Cambio
                            </div>
                            <div class="p-5 bg-slate-900 border border-slate-800 rounded-2xl text-[11px] font-mono text-red-300 overflow-auto max-h-64 leading-relaxed shadow-2xl">
                                <pre class="whitespace-pre-wrap" x-text="formatJSON(selectedItem?.valores_antiguos)"></pre>
                            </div>
                        </div>

                        <div class="flex justify-center py-2">
                            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-[10px] font-black text-emerald-600 uppercase flex items-center gap-2 px-1">
                                <i class="fas fa-plus-circle"></i>
                                Valores Después del Cambio
                            </div>
                            <div class="p-5 bg-slate-900 border border-slate-800 rounded-2xl text-[11px] font-mono text-emerald-300 overflow-auto max-h-64 leading-relaxed shadow-2xl">
                                <pre class="whitespace-pre-wrap" x-text="formatJSON(selectedItem?.valores_nuevos)"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-4 bg-white border-t border-slate-100 text-center">
                <p class="text-[10px] text-slate-400 font-medium">Este registro es permanente y no puede ser editado.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', 'Roboto Mono', monospace; }
    [x-cloak] { display: none !important; }
    .divide-y tr { transition: all 0.2s ease; }
    .divide-y tr:hover { transform: translateX(4px); }
</style>
