<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<div class="h-full flex flex-col" x-data="calendarioApp()" x-init="init()">
    <div id="calendar" class="flex-1 min-h-[400px] max-h-[calc(100vh-160px)] bg-white rounded-xl border border-carbon-100 overflow-hidden"></div>
</div>

<template x-if="false">
    <div class="hidden" id="event-modal-template">
        <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50" @click.outside="closeEventModal">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
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
                    <div>
                        <label class="block text-sm font-medium text-carbon-700">Color</label>
                        <select x-model="eventForm.color" class="w-full border border-carbon-300 rounded-lg px-3 py-2 mt-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                            <template x-for="c in colors" :key="c.value">
                                <option :value="c.value" :style="'background:' + c.value + '; color: white;'" x-text="c.label"></option>
                            </template>
                        </select>
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
</template>