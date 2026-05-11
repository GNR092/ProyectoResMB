<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<tr class="fila-producto">
    <td class="numero-fila border px-3 py-1 text-center">1</td>
    <td class="border px-3 py-1">
        <input type="hidden" name="id_grupo_presupuestal[]" class="id_grupo_presupuestal">
        <input type="text" name="codigo[]" class="w-full border rounded px-2 py-1 codigo" placeholder="SKU/Cod" readonly required>
    </td>
    <td class="border px-3 py-1 relative">
        <div class="flex items-center gap-2">
            <div class="relative flex-grow">
                <input type="hidden" name="producto[]" class="id-producto-val">
                <input type="text" class="w-full border rounded px-2 py-1 search-producto-input bg-gray-50 cursor-pointer" 
                       placeholder="Haga clic para elegir producto..." autocomplete="off" readonly required>
                
                <!-- Dropdown de resultados (Autocomplete) -->
                <div class="absolute left-0 right-0 z-[100] mt-1 bg-white border rounded-md shadow-xl hidden container-resultados-busqueda max-h-64 overflow-y-auto">
                    <!-- Los resultados se cargarán dinámicamente -->
                </div>
            </div>
            <button type="button" class="btn-favorito text-gray-300 hover:text-yellow-500 transition-colors" title="Marcar como favorito">
                <svg fill="currentColor" viewBox="0 0 24 24" class="size-5">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </button>
        </div>
    </td>
    <td class="border px-3 py-1">
        <input type="number" name="cantidad[]" class="cantidad w-full border rounded px-2 py-1" min="1" step="1" value="1" required>
    </td>
    <td class="border px-3 py-1">
        <input type="number" name="importe[]" class="importe w-full border rounded px-2 py-1" min="0" step="0.01" value="0" required>
    </td>
    <td class="costo border px-3 py-1 text-right">$0.00</td>
    <td class="border px-3 py-1 text-center">
        <button type="button" class="eliminar-fila text-red-600 hover:text-red-800" title="Eliminar fila">
            <svg fill="none" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                <use xlink:href="<?= $iconUrl ?>#eliminar-fila"></use>
            </svg>
        </button>
    </td>
</tr>
