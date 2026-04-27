<div class="flex flex-col h-[calc(100vh-200px)] bg-gray-50 font-sans text-slate-900" x-data="bitacoraApp()">
    <!-- Header / Filtros -->
    <div class="p-6 bg-white border-b border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-slate-800 tracking-tight">Auditoría Humanizada de Bitácora</h2>
                <p class="text-sm text-slate-500 mt-1">Transformando datos técnicos en información comprensible para todos.</p>
            </div>
            <div class="flex gap-3">
                <button @click="clearFilters()" class="px-5 py-2.5 bg-white text-slate-600 border border-slate-200 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm active:scale-95">
                    <i class="fas fa-filter-circle-xmark text-slate-400"></i>
                    Limpiar Filtros
                </button>
                <button @click="fetchData()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-lg shadow-indigo-100 active:scale-95">
                    <i class="fas fa-sync-alt" :class="loading ? 'animate-spin' : ''"></i>
                    Actualizar
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Sección / Área</label>
                <select x-model="filters.modulo" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                    <option value="">Todas las secciones</option>
                    <template x-for="mod in catalogos.modulos" :key="mod">
                        <option :value="mod" x-text="getFriendlyModule(mod)"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tipo de Actividad</label>
                <select x-model="filters.tipo_accion" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                    <option value="">Todas las actividades</option>
                    <template x-for="(label, value) in friendly.acciones" :key="value">
                        <option :value="value" x-text="label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Desde</label>
                <input type="date" x-model="filters.fecha_inicio" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Hasta</label>
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
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha y Hora</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Responsable</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Área Afectada</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Acción Realizada</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Referencia Folio</th>
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
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-slate-700" x-text="item.nombre_usuario_real || item.nombre_usuario"></span>
                                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter" x-text="item.departamento_nombre || 'SISTEMA'"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-1">
                                                <span class="px-2 py-0.5 w-fit rounded-lg text-[10px] font-black bg-slate-100 text-slate-500 border border-slate-200" x-text="getFriendlyModule(item.modulo)"></span>
                                                <span class="text-[9px] text-slate-400 font-medium" x-text="item.complejo_nombre ? 'En: ' + item.complejo_nombre : ''"></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="getActionClass(item.tipo_accion)" class="px-2.5 py-1 rounded-lg text-[10px] font-black border uppercase tracking-tighter" x-text="getFriendlyAction(item.tipo_accion)"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <i class="fas fa-file-invoice text-slate-300 text-xs"></i>
                                                <span class="text-[11px] font-mono font-black text-slate-600" x-text="item.solicitud_folio || item.solicitud_id || '-'"></span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer Paginación -->
            <div class="px-8 py-6 bg-white border-t border-slate-200 flex items-center justify-between shadow-[0_-4px_20px_-5px_rgba(0,0,0,0.05)]">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Resumen de vista</span>
                    <div class="text-xs text-slate-600">
                        Mostrando <span class="font-black text-indigo-600" x-text="items.length"></span> de <span class="font-black text-slate-800" x-text="total"></span> registros encontrados
                    </div>
                </div>
                
                <div class="flex items-center gap-4 bg-slate-50 p-1.5 rounded-2xl border border-slate-200">
                    <button @click="changePage(pagination.page - 1)" :disabled="pagination.page === 1" class="flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-sm active:scale-95">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </button>
                    <div class="px-4 flex flex-col items-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase">Página</span>
                        <span class="text-sm font-black text-indigo-600" x-text="pagination.page + ' / ' + pagination.totalPages"></span>
                    </div>
                    <button @click="changePage(pagination.page + 1)" :disabled="pagination.page === pagination.totalPages" class="flex items-center gap-2 px-4 py-2 rounded-xl border border-indigo-200 bg-indigo-600 text-white text-xs font-bold hover:bg-indigo-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all shadow-md shadow-indigo-100 active:scale-95">
                        Siguiente <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Panel Lateral Humanizado -->
        <div class="w-[550px] border-l border-slate-200 bg-white shadow-2xl flex flex-col absolute right-0 top-0 h-full z-30" 
             x-show="selectedItem !== null" 
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="transform translate-x-full" x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="transform translate-x-0" x-transition:leave-end="transform translate-x-full">
            
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 shadow-inner">
                        <i class="fas fa-fingerprint text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-800 uppercase text-xs tracking-[0.1em]">Análisis Forense de Evento</h3>
                        <p class="text-[10px] text-slate-500 font-bold" x-text="'Referencia única: #' + selectedItem?.id"></p>
                    </div>
                </div>
                <button @click="closeDetails()" class="group flex items-center gap-2 px-4 py-2.5 bg-red-50 text-red-600 rounded-xl border border-red-100 hover:bg-red-600 hover:text-white transition-all shadow-sm active:scale-95">
                    <span class="text-xs font-black uppercase tracking-tight">Cerrar</span>
                    <i class="fas fa-times-circle text-lg"></i>
                </button>
            </div>

            <div class="flex-1 overflow-auto p-8 space-y-8 bg-slate-50/30">
                <!-- Contexto Humanizado -->
                <div class="space-y-4">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">¿Quién y Dónde?</div>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-start gap-4">
                            <div class="p-3 bg-slate-50 rounded-xl"><i class="fas fa-user-tie text-slate-400"></i></div>
                            <div class="flex-1">
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Responsable de la acción</label>
                                <div class="text-sm font-bold text-slate-800" x-text="selectedItem?.nombre_usuario_real || selectedItem?.nombre_usuario"></div>
                                <div class="text-[10px] text-indigo-600 font-bold" x-text="'Departamento: ' + (selectedItem?.departamento_nombre || 'N/A')"></div>
                                <div class="text-[10px] text-slate-400" x-text="'IP Origen: ' + selectedItem?.ip_address"></div>
                            </div>
                        </div>

                        <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm flex items-start gap-4">
                            <div class="p-3 bg-slate-50 rounded-xl"><i class="fas fa-map-marker-alt text-slate-400"></i></div>
                            <div class="flex-1">
                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">Ubicación del evento</label>
                                <div class="text-sm font-bold text-slate-800" x-text="'Módulo de ' + getFriendlyModule(selectedItem?.modulo)"></div>
                                <div class="text-[10px] text-slate-500 font-bold" x-text="'Complejo: ' + (selectedItem?.complejo_nombre || 'General')"></div>
                                <div class="text-[10px] text-slate-400 italic" x-text="'Empresa: ' + (selectedItem?.razon_social_nombre || 'N/A')"></div>
                            </div>
                        </div>

                        <!-- NUEVO: Objeto Afectado -->
                        <template x-if="selectedItem?.grupo_presupuestal_nombre || selectedItem?.unidad_operativa_nombre">
                            <div class="p-5 rounded-2xl bg-indigo-50 border border-indigo-100 shadow-sm flex items-start gap-4">
                                <div class="p-3 bg-white rounded-xl text-indigo-500 shadow-sm"><i class="fas fa-bullseye"></i></div>
                                <div class="flex-1">
                                    <label class="block text-[9px] font-black text-indigo-400 uppercase mb-1">Elemento Específico Afectado</label>
                                    <div class="text-sm font-black text-indigo-800" x-text="selectedItem?.grupo_presupuestal_nombre || selectedItem?.unidad_operativa_nombre"></div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] text-indigo-600 font-bold" x-text="selectedItem?.grupo_presupuestal_nombre ? 'Partida Presupuestal' : 'Áreas De Operación'"></span>                                        <template x-if="selectedItem?.valores_nuevos?.Anio || selectedItem?.valores_nuevos?.Mes">
                                            <span class="text-[10px] bg-indigo-200 text-indigo-800 px-1.5 py-0.5 rounded font-black" x-text="(selectedItem?.valores_nuevos?.Mes ? 'Mes ' + selectedItem?.valores_nuevos?.Mes + ' ' : '') + selectedItem?.valores_nuevos?.Anio"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Análisis de Datos -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Transformación de Información</span>
                        <span :class="getActionClass(selectedItem?.tipo_accion)" class="px-3 py-1 rounded-full text-[9px] font-black border uppercase tracking-wider" x-text="getFriendlyAction(selectedItem?.tipo_accion)"></span>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="space-y-3">
                            <div class="text-[10px] font-black text-red-500 uppercase flex items-center gap-2 px-1">
                                <i class="fas fa-history"></i> Antes del cambio (Estado Anterior)
                            </div>
                            <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl text-[11px] font-mono text-red-300 overflow-auto max-h-72 leading-relaxed shadow-2xl relative">
                                <pre class="whitespace-pre-wrap" x-text="formatJSON(selectedItem?.valores_antiguos)"></pre>
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <div class="w-12 h-12 rounded-full bg-white border border-slate-100 flex items-center justify-center text-indigo-500 shadow-lg ring-8 ring-indigo-50/50">
                                <i class="fas fa-long-arrow-alt-down text-xl"></i>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="text-[10px] font-black text-emerald-600 uppercase flex items-center gap-2 px-1">
                                <i class="fas fa-magic"></i> Después del cambio (Nuevo Estado)
                            </div>
                            <div class="p-6 bg-slate-900 border border-slate-800 rounded-3xl text-[11px] font-mono text-emerald-300 overflow-auto max-h-72 leading-relaxed shadow-2xl relative">
                                <pre class="whitespace-pre-wrap" x-text="formatJSON(selectedItem?.valores_nuevos)"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-6 bg-slate-50 border-t border-slate-200 text-center rounded-b-3xl">
                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Evidencia Inmutable</p>
                <p class="text-[9px] text-slate-400 font-medium italic">Este registro ha sido generado automáticamente por el motor de auditoría del sistema y es legalmente vinculante para fines internos.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .font-mono { font-family: 'JetBrains Mono', 'Fira Code', 'Roboto Mono', monospace; }
    [x-cloak] { display: none !important; }
    .divide-y tr { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .divide-y tr:hover { transform: scale(1.005); z-index: 5; position: relative; }
</style>
