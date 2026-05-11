<td class="numero-fila-servicio border px-3 py-1 text-center w-12"></td>

<td class="border px-3 py-1 relative w-1/2">
    <input type="hidden" name="id_grupo_presupuestal[]" class="id_grupo_presupuestal">
    <div class="flex items-center gap-2">
        <div class="relative flex-grow">
            <input type="hidden" name="servicio[]" class="id-producto-val">
            <input type="text" class="w-full border rounded px-2 py-1 search-producto-input bg-gray-50 cursor-pointer" 
                   placeholder="Elegir servicio..." autocomplete="off" readonly required>
            
            <!-- Dropdown de resultados -->
            <div class="absolute left-0 right-0 z-[100] mt-1 bg-white border rounded shadow-xl hidden container-resultados-busqueda max-h-60 overflow-y-auto">
                <!-- Resultados dinámicos -->
            </div>
        </div>
        <button type="button" class="btn-favorito text-gray-300 hover:text-yellow-500 transition-colors" title="Marcar como favorito">
            <svg fill="currentColor" viewBox="0 0 24 24" class="size-5">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
        </button>
    </div>
</td>

<td class="border px-3 py-1 w-32">
    <input type="number"
           name="importe[]"
           class="costo-servicio w-full border rounded px-2 py-1"
           min="0"
           step="0.01"
           value="0"
           required>
</td>

<td class="border px-3 py-1 text-center w-24">
    <button type="button" class="eliminar-fila-servicio text-red-600 hover:text-red-800" title="Eliminar fila">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    </button>
</td>
