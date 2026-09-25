<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<div id="agenda-vista" class="h-[min(85dvh,92vh)] sm:h-full flex flex-col min-h-0 flex-1 relative @container" x-data="calendarioApp()" x-init="init()" style="container-type:inline-size; container-name:agenda">
    <!-- Filtros lista (solo visible en listWeek) — filtran fc-list-table en tiempo real -->
    <div x-show="isListView && !showDetalle" x-cloak x-transition
         class="mb-3 p-2 sm:p-3 bg-white rounded-xl border border-carbon-100 shadow-sm">
        <div class="grid grid-cols-1 sm:flex sm:flex-wrap sm:items-end gap-2 sm:gap-3">
            <div class="flex flex-col gap-1 min-w-0">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Folio</label>
                <input type="text" x-model="filtrosLista.folio" @input.debounce.300ms="filtrarLista()" placeholder="MBSP-..." class="border border-gray-300 p-2.5 sm:p-2 rounded-md text-sm w-full sm:w-32 outline-blue-500 min-h-[44px] sm:min-h-0">
            </div>
            <div class="flex flex-col gap-1 min-w-0">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Estado</label>
                <select x-model="filtrosLista.estado" @change="filtrarLista()" class="border border-gray-300 p-2.5 sm:p-2 rounded-md text-sm w-full sm:w-36 outline-blue-500 min-h-[44px] sm:min-h-0">
                    <option value="">Todos</option>
                    <option value="En espera">En espera</option>
                    <option value="Aprobada">Aprobada</option>
                    <option value="Rechazada">Rechazada</option>
                    <option value="Cotizando">Cotizando</option>
                    <option value="Aprobacion pendiente">Aprobación Pendiente</option>
                    <option value="En revision">En revisión</option>
                    <option value="Espera_Programacion">Espera Programación</option>
                    <option value="Programada">Programada</option>
                    <option value="Por Pagar">Por Pagar</option>
                    <option value="Pagada">Pagada</option>
                </select>
            </div>
            <div class="flex flex-col gap-1 min-w-0">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Tipo</label>
                <select x-model="filtrosLista.tipo" @change="filtrarLista()" class="border border-gray-300 p-2.5 sm:p-2 rounded-md text-sm w-full sm:w-28 outline-blue-500 min-h-[44px] sm:min-h-0">
                    <option value="">Todos</option>
                    <option value="Producto">Producto</option>
                    <option value="Servicio">Servicio</option>
                </select>
            </div>
            <div class="flex flex-col gap-1 flex-1 min-w-[140px] sm:min-w-[180px] max-w-full">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Proveedor</label>
                <select x-ref="filtroProveedorLista" class="w-full border border-gray-300 p-2.5 sm:p-2 rounded-md text-sm outline-blue-500 min-h-[44px] sm:min-h-0">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="flex flex-col gap-1 flex-1 min-w-[140px] sm:min-w-[180px] max-w-full">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Complejo</label>
                <select x-ref="filtroComplejoLista" class="w-full border border-gray-300 p-2.5 sm:p-2 rounded-md text-sm outline-blue-500 min-h-[44px] sm:min-h-0">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="flex flex-col gap-1 flex-1 min-w-[140px] sm:min-w-[180px] max-w-full">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Departamento</label>
                <select x-ref="filtroDepartamentoLista" class="w-full border border-gray-300 p-2.5 sm:p-2 rounded-md text-sm outline-blue-500 min-h-[44px] sm:min-h-0">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="flex items-center gap-2 py-1 sm:col-auto sm:pb-2">
                <button type="button" @click="limpiarFiltrosLista()" class="text-xs text-indigo-600 hover:text-indigo-800 underline min-h-[44px] px-2">Limpiar</button>
                <span class="text-xs text-gray-400" x-text="eventosCache.length ? eventosCache.length + ' salidas (filtradas de ' + eventosCacheOriginal.length + ')' : ''"></span>
            </div>
        </div>
    </div>
    <!-- Contenedor calendario — container query target -->
    <div id="calendar" class="flex-1 min-h-[50dvh] sm:min-h-0 bg-white rounded-xl border border-carbon-100 overflow-auto sm:overflow-hidden" x-show="!showDetalle" style="container-type:inline-size; container-name:cal"></div>
    <p class="sm:hidden text-center text-[11px] text-gray-400 py-1 px-2">Mantén pulsado 0.4s y arrastra para crear bloque horario</p>

    <!-- Detalle requisición (pantalla 2) — envuelto para scroll-x en móvil -->
    <div id="div-calendario-detalle" x-show="showDetalle" x-cloak
         class="flex-1 bg-white rounded-xl border border-carbon-100 overflow-auto p-3 sm:p-4 min-h-0">
        <div class="flex justify-between items-center gap-2 mb-4">
            <h3 class="text-base sm:text-lg font-bold leading-tight">Detalles de la requisicion</h3>
            <button type="button" @click="regresarCalendario()" class="p-2.5 sm:p-2 rounded-full hover:bg-gray-200 transition shrink-0 min-h-[44px] min-w-[44px] flex items-center justify-center" title="Regresar al calendario">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <div id="detalles-calendario-solicitud" class="overflow-x-auto -mx-3 sm:mx-0 px-3 sm:px-0">
            <!-- Inyectado por verDetalleSolicitud() -->
        </div>
    </div>

    <!-- Modal crear/editar — teletransportado a body para centrado viewport real, no cortado por modal principal -->
    <template x-teleport="body">
    <div id="agenda-modal" x-show="showEventModal" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-[60] p-2 sm:p-4 overflow-y-auto"
         @click.self="closeEventModal"
         @keydown.escape.window="closeEventModal">
        <div class="bg-white rounded-xl shadow-xl p-4 sm:p-6 w-[calc(100vw-1rem)] sm:w-full max-w-[min(32rem,95vw)] mx-auto my-auto max-h-[85dvh] sm:max-h-[90vh] overflow-y-auto" @click.outside="closeEventModal">
            <h3 class="text-base sm:text-lg font-semibold mb-4 text-carbon-900" x-text="eventModalMode === 'create' ? 'Nuevo Evento' : 'Editar Evento'"></h3>
            <form @submit.prevent="saveEvent" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-carbon-700">Título</label>
                    <input type="text" x-model="eventForm.title" required class="w-full border border-carbon-300 rounded-lg px-3 py-2.5 sm:py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm min-h-[44px] sm:min-h-0" x-ref="titleInput">
                </div>
                <!-- Selector requisición obligatorio -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-carbon-700">Requisición vinculada <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        </div>
                        <input type="text" x-model="busquedaModal" @input.debounce.400ms="filtrarModal()" placeholder="Buscar por número de folio — ej. 123" class="w-full border border-carbon-300 rounded-lg pl-10 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none min-h-[44px] sm:min-h-0">
                    </div>
                    <p class="text-xs text-gray-400">Escribe solo el número, sin MBSP-</p>
                    <!-- Filtros avanzados colapsables -->
                    <details class="group rounded-lg border border-carbon-100 bg-carbon-50/50" x-ref="detallesFiltros" @toggle="onToggleFiltrosAvanzados($event)">
                        <summary class="flex items-center justify-between px-3 py-2.5 sm:py-2 text-xs font-medium text-indigo-600 cursor-pointer list-none min-h-[44px] sm:min-h-0">
                            <span>Filtros avanzados</span>
                            <svg class="size-3 shrink-0 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 pt-0">
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Estado</label>
                                <select x-model="filtrosModal.estado" @change="filtrarModal()" class="w-full border border-carbon-200 rounded-lg px-2 py-2 sm:py-1.5 mt-1 text-sm outline-none focus:border-indigo-400 min-h-[44px] sm:min-h-0">
                                    <option value="">Todos</option>
                                    <option value="En espera">En espera</option>
                                    <option value="Aprobada">Aprobada</option>
                                    <option value="Rechazada">Rechazada</option>
                                    <option value="Cotizando">Cotizando</option>
                                    <option value="Aprobacion pendiente">Aprobación Pendiente</option>
                                    <option value="En revision">En revisión</option>
                                    <option value="Espera_Programacion">Espera Programación</option>
                                    <option value="Programada">Programada</option>
                                    <option value="Por Pagar">Por Pagar</option>
                                    <option value="Pagada">Pagada</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Tipo</label>
                                <select x-model="filtrosModal.tipo" @change="filtrarModal()" class="w-full border border-carbon-200 rounded-lg px-2 py-2 sm:py-1.5 mt-1 text-sm outline-none focus:border-indigo-400 min-h-[44px] sm:min-h-0">
                                    <option value="">Todos</option>
                                    <option value="Producto">Producto</option>
                                    <option value="Servicio">Servicio</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Proveedor</label>
                                <select x-ref="filtroProveedor" id="cal-filtro-proveedor"
                                        x-model="filtrosModal.proveedor"
                                        @change="filtrarModal()"
                                        class="w-full border border-carbon-200 rounded-lg px-2 py-2 sm:py-1.5 mt-1 text-sm outline-none focus:border-indigo-400 min-h-[44px] sm:min-h-0">
                                    <option value="">Todos los proveedores</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Complejo</label>
                                <select x-ref="filtroComplejo" id="cal-filtro-complejo"
                                        x-model="filtrosModal.complejo"
                                        @change="filtrarModal()"
                                        class="w-full border border-carbon-200 rounded-lg px-2 py-2 sm:py-1.5 mt-1 text-sm outline-none focus:border-indigo-400 min-h-[44px] sm:min-h-0">
                                    <option value="">Todos los complejos</option>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-carbon-600">Departamento</label>
                                <select x-ref="filtroDepartamento" id="cal-filtro-departamento"
                                        x-model="filtrosModal.departamento"
                                        @change="filtrarModal()"
                                        class="w-full border border-carbon-200 rounded-lg px-2 py-2 sm:py-1.5 mt-1 text-sm outline-none focus:border-indigo-400 min-h-[44px] sm:min-h-0">
                                    <option value="">Todos los departamentos</option>
                                </select>
                            </div>
                        </div>
                    </details>
                    <select x-model="eventForm.ID_Solicitud" required class="w-full border border-carbon-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm max-h-[28vh] sm:max-h-none" id="cal-solicitud-select" size="4">
                        <option value="">Seleccione una requisición</option>
                        <template x-for="sol in solicitudesFiltradasModal" :key="sol.ID_Solicitud">
                            <option :value="sol.ID_Solicitud" x-text="formatoOpcionSolicitud(sol)"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400" x-show="solicitudesFiltradasModal.length===0">Sin resultados — prueba otro número o ajusta filtros avanzados</p>
                </div>
<!-- Botón ver detalles — idéntico a historial -->
                <div x-show="eventForm.ID_Solicitud" class="flex justify-end">
                    <button type="button" @click="verDetalleDesdeModal()" class="inline-flex items-center gap-1.5 px-3 py-2.5 sm:py-1.5 bg-white border border-carbon-200 rounded-lg text-sm text-carbon-700 hover:bg-carbon-50 transition min-h-[44px] sm:min-h-0">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ver detalles
                    </button>
                </div>
                <!-- Estatus del evento -->
                <div>
                    <label class="block text-sm font-medium text-carbon-700">Estatus</label>
                    <select x-model="eventForm.estatus" class="w-full border border-carbon-300 rounded-lg px-3 py-2.5 sm:py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm min-h-[44px] sm:min-h-0"
                            :disabled="archivos.length > 0">
                        <option value="pendiente">Pendiente</option>
                        <option value="cancelado">Cancelado</option>
                        <option value="evidencia">Evidencia</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1" x-show="archivos.length > 0">
                        El estatus se administra automáticamente al adjuntar evidencias.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-sm font-medium text-carbon-700">Inicio</label>
                        <input type="datetime-local" x-model="eventForm.start" required step="1800" class="w-full border border-carbon-300 rounded-lg px-3 py-2.5 sm:py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm min-h-[44px] sm:min-h-0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-carbon-700">Fin</label>
                        <input type="datetime-local" x-model="eventForm.end" required step="1800" class="w-full border border-carbon-300 rounded-lg px-3 py-2.5 sm:py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm min-h-[44px] sm:min-h-0">
                    </div>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2 pt-4">
                    <button x-show="eventModalMode === 'edit' && eventForm.estatus !== 'cancelado'"
                            type="button" @click="cancelarEvento"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 active:bg-red-800 transition-colors text-sm font-medium shadow-sm min-h-[44px] sm:min-h-0 w-full sm:w-auto">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Cancelar evento
                    </button>
                    <span x-show="eventModalMode !== 'edit'" class="hidden sm:block"></span>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button type="button" @click="closeEventModal" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 border border-carbon-300 rounded-lg text-carbon-700 hover:bg-carbon-50 transition-colors min-h-[44px] sm:min-h-0 text-sm font-medium">Cancelar</button>
                        <button type="submit" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors min-h-[44px] sm:min-h-0 text-sm font-medium" x-text="eventModalMode === 'create' ? 'Crear' : 'Guardar'"></button>
                    </div>
                </div>
                <!-- Evidencias (solo en modo edición) -->
                <div x-show="eventModalMode === 'edit'" class="mt-6 pt-4 border-t border-carbon-200">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-carbon-700">Evidencias</h4>
                        <span class="text-xs text-gray-500" x-text="archivos.length + ' archivo(s)'"></span>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-carbon-600 mb-1">Adjuntar archivos</label>
                        <input type="file" x-ref="archivosInput" multiple @change="subirArchivos"
                               class="w-full border border-carbon-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none min-h-[44px] sm:min-h-0"
                               :disabled="subiendoArchivos">
                        <p class="text-xs text-gray-400 mt-1">PDF, imágenes, Word, Excel, TXT (máx. 10 MB c/u)</p>
                    </div>
                    <div x-show="archivos.length > 0" class="space-y-2 max-h-60 overflow-auto">
                        <template x-for="a in archivos" :key="a.id_archivo">
                            <div class="flex items-center justify-between gap-2 p-3 bg-carbon-50 rounded-lg border border-carbon-100">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-carbon-900 truncate" x-text="a.nombre_archivo"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="a.nombre_usuario ? 'Subido por: ' + a.nombre_usuario : ''"></p>
                                    <p class="text-xs text-gray-400" x-text="formatoFecha(a.fecha_subida)"></p>
                                </div>
                                <button type="button" @click="descargarArchivo(a)"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition shrink-0 min-h-[36px]"
                                        :disabled="subiendoArchivos">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Descargar
                                </button>
                            </div>
                        </template>
                    </div>
                    <p x-show="archivos.length === 0" class="text-xs text-gray-400 text-center py-4">Sin evidencias adjuntas</p>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
