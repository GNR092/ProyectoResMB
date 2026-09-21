<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<div class="h-full flex flex-col relative" x-data="calendarioApp()" x-init="init()">
    <div id="calendar" class="flex-1 min-h-[400px] max-h-[calc(100vh-160px)] bg-white rounded-xl border border-carbon-100 overflow-hidden"></div>

    <!-- Overlay flechas Semana: absolute, no afecta flex-1 ni altura del calendario -->
    <button type="button" x-show="isWeekView" x-cloak x-transition
            @click="prevWeek()"
            class="pointer-events-auto absolute left-2 top-1/2 -translate-y-1/2 z-10 size-9 bg-white border border-carbon-200 rounded-full shadow-md hover:bg-carbon-50 hover:border-carbon-300 text-carbon-700 flex items-center justify-center transition"
            aria-label="Semana anterior" title="Semana anterior">
        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button type="button" x-show="isWeekView" x-cloak x-transition
            @click="nextWeek()"
            class="pointer-events-auto absolute right-2 top-1/2 -translate-y-1/2 z-10 size-9 bg-white border border-carbon-200 rounded-full shadow-md hover:bg-carbon-50 hover:border-carbon-300 text-carbon-700 flex items-center justify-center transition"
            aria-label="Semana siguiente" title="Semana siguiente">
        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
    </button>

    <!-- Modal crear/editar — click simple (1 slot 30min) o arrastre (rango marcado) -->
    <div x-show="showEventModal" x-cloak x-transition.opacity
         class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 p-4"
         @click.self="closeEventModal"
         @keydown.escape.window="closeEventModal">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4" @click.outside="closeEventModal">
            <h3 class="text-lg font-semibold mb-4 text-carbon-900" x-text="eventModalMode === 'create' ? 'Nuevo Evento' : 'Editar Evento'"></h3>
            <form @submit.prevent="saveEvent" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-carbon-700">Título</label>
                    <input type="text" x-model="eventForm.title" required class="w-full border border-carbon-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" x-ref="titleInput">
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
                <div x-show="eventModalMode === 'edit'" class="text-center mb-2">
                    <button type="button" @click="deleteEvent" class="text-sm text-red-600 hover:text-red-700 underline">Eliminar evento</button>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="closeEventModal" class="px-4 py-2 border border-carbon-300 rounded-lg text-carbon-700 hover:bg-carbon-50 transition-colors">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors" x-text="eventModalMode === 'create' ? 'Crear' : 'Guardar'"></button>
                </div>
            </form>
        </div>
    </div>
</div>