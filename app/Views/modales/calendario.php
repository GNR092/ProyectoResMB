<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<div x-data="calendarioApp()" x-init="init()">
<div class="h-full flex flex-col">
    <!-- Filtros de búsqueda SOLO en modo Lista -->
    <div x-show="isListView" x-cloak x-transition
         class="mb-3 p-3 bg-white rounded-xl border border-carbon-100 shadow-sm">
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Folio</label>
                <input type="text" x-model="filtros.folio" @input.debounce.400ms="filtrarSolicitudes()" placeholder="Buscar folio..." class="border border-gray-300 p-2 rounded-md text-sm w-36 outline-blue-500">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Tipo</label>
                <select x-model="filtros.tipo" @change="filtrarSolicitudes()" class="border border-gray-300 p-2 rounded-md text-sm w-32 outline-blue-500">
                    <option value="">Todos</option>
                    <option value="Producto">Producto</option>
                    <option value="Servicio">Servicio</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Estado</label>
                <select x-model="filtros.estado" @change="filtrarSolicitudes()" class="border border-gray-300 p-2 rounded-md text-sm w-40 outline-blue-500">
                    <option value="">Todos los estados</option>
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
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Fecha</label>
                <input type="date" x-model="filtros.fecha" @change="filtrarSolicitudes()" class="border border-gray-300 p-2 rounded-md text-sm outline-blue-500">
            </div>
            <div class="flex items-center gap-2 pb-2">
                <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                    <input type="checkbox" x-model="filtros.por_mes" @change="filtrarSolicitudes()" class="accent-blue-600"> Mes
                </label>
                <button type="button" @click="limpiarFiltros()" class="text-xs text-indigo-600 hover:text-indigo-800 underline">Limpiar</button>
            </div>
            <div class="flex-grow flex justify-end items-end">
                <span class="text-xs text-gray-400" x-text="solicitudesCache.length + ' requisiciones'"></span>
            </div>
        </div>
    </div>

    <!-- Contenedor calendario -->
    <div id="calendar" class="flex-1 min-h-[400px] max-h-[calc(100vh-160px)] bg-white rounded-xl border border-carbon-100 overflow-hidden" x-show="!showDetalle"></div>

    <!-- Detalle requisición (pantalla 2) — reutiliza generar* de utils.js -->
    <div id="div-calendario-detalle" x-show="showDetalle" x-cloak
         class="flex-1 bg-white rounded-xl border border-carbon-100 overflow-auto p-4 max-h-[calc(100vh-160px)]">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Detalles de la requisicion</h3>
            <button type="button" @click="regresarCalendario()" class="p-2 rounded-full hover:bg-gray-200 transition" title="Regresar al calendario">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        <div id="detalles-calendario-solicitud">
            <!-- Inyectado por verDetalleSolicitud() -->
        </div>
    </div>
</div>

    <!-- Modal crear/editar — teletransportado a body para centrado viewport real, no cortado por modal principal -->
    <template x-teleport="body">
    <div x-show="showEventModal" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/40 flex items-center justify-center z-[60] p-4 overflow-y-auto"
         @click.self="closeEventModal"
         @keydown.escape.window="closeEventModal">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-lg mx-auto my-auto max-h-[90vh] overflow-y-auto" @click.outside="closeEventModal">
            <h3 class="text-lg font-semibold mb-4 text-carbon-900" x-text="eventModalMode === 'create' ? 'Nuevo Evento' : 'Editar Evento'"></h3>
            <form @submit.prevent="saveEvent" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-carbon-700">Título</label>
                    <input type="text" x-model="eventForm.title" required class="w-full border border-carbon-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" x-ref="titleInput">
                </div>
                <!-- Selector requisición obligatorio -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-carbon-700">Requisición vinculada <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                            <svg class="size-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                        </div>
                        <input type="text" x-model="busquedaModal" @input.debounce.400ms="filtrarModal()" placeholder="Buscar por número de folio — ej. 123" class="w-full border border-carbon-300 rounded-lg pl-10 pr-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                    <p class="text-xs text-gray-400">Escribe solo el número, sin MBSP-</p>
                    <!-- Filtros avanzados colapsables -->
                    <details class="group rounded-lg border border-carbon-100 bg-carbon-50/50">
                        <summary class="flex items-center justify-between px-3 py-2 text-xs font-medium text-indigo-600 cursor-pointer list-none">
                            <span>Filtros avanzados</span>
                            <svg class="size-3 shrink-0 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </summary>
                        <div class="grid grid-cols-2 gap-3 p-3 pt-0">
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Estado</label>
                                <select x-model="filtrosModal.estado" @change="filtrarModal()" class="w-full border border-carbon-200 rounded-lg px-2 py-1.5 mt-1 text-sm outline-none focus:border-indigo-400">
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
                                <select x-model="filtrosModal.tipo" @change="filtrarModal()" class="w-full border border-carbon-200 rounded-lg px-2 py-1.5 mt-1 text-sm outline-none focus:border-indigo-400">
                                    <option value="">Todos</option>
                                    <option value="Producto">Producto</option>
                                    <option value="Servicio">Servicio</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Proveedor</label>
                                <input type="text" x-model="filtrosModal.proveedor" @input.debounce.400ms="filtrarModal()" placeholder="Filtrar por proveedor" class="w-full border border-carbon-200 rounded-lg px-2 py-1.5 mt-1 text-sm outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-carbon-600">Complejo</label>
                                <input type="text" x-model="filtrosModal.complejo" @input.debounce.400ms="filtrarModal()" placeholder="Ej. MB Resort" class="w-full border border-carbon-200 rounded-lg px-2 py-1.5 mt-1 text-sm outline-none focus:border-indigo-400">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-carbon-600">Departamento</label>
                                <input type="text" x-model="filtrosModal.departamento" @input.debounce.400ms="filtrarModal()" placeholder="Ej. Mantenimiento" class="w-full border border-carbon-200 rounded-lg px-2 py-1.5 mt-1 text-sm outline-none focus:border-indigo-400">
                            </div>
                        </div>
                    </details>
                    <select x-model="eventForm.ID_Solicitud" required class="w-full border border-carbon-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm" id="cal-solicitud-select" size="6">
                        <option value="">Seleccione una requisición</option>
                        <template x-for="sol in solicitudesFiltradasModal" :key="sol.ID_Solicitud">
                            <option :value="sol.ID_Solicitud" x-text="formatoOpcionSolicitud(sol)"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400" x-show="solicitudesFiltradasModal.length===0">Sin resultados — prueba otro número o ajusta filtros avanzados</p>
                </div>
                <!-- Botón ver detalles — idéntico a historial -->
                <div x-show="eventForm.ID_Solicitud" class="flex justify-end">
                    <button type="button" @click="verDetalleDesdeModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-carbon-200 rounded-lg text-sm text-carbon-700 hover:bg-carbon-50 transition">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Ver detalles
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-carbon-700">Inicio</label>
                        <input type="datetime-local" x-model="eventForm.start" required step="1800" class="w-full border border-carbon-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-carbon-700">Fin</label>
                        <input type="datetime-local" x-model="eventForm.end" required step="1800" class="w-full border border-carbon-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    </div>
                </div>
                <div class="flex items-center justify-between pt-4">
                    <button x-show="eventModalMode === 'edit'" type="button" @click="deleteEvent" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 active:bg-red-800 transition-colors text-sm font-medium shadow-sm">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        Eliminar
                    </button>
                    <span x-show="eventModalMode !== 'edit'"></span>
                    <div class="flex gap-2">
                        <button type="button" @click="closeEventModal" class="px-4 py-2 border border-carbon-300 rounded-lg text-carbon-700 hover:bg-carbon-50 transition-colors">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors" x-text="eventModalMode === 'create' ? 'Crear' : 'Guardar'"></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    </template>
</div>
