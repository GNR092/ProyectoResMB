<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<td class="numero-fila border px-3 py-1 text-center"></td>
<td class="border px-3 py-1">
    <input type="text" name="codigo[]" class="w-full border rounded px-2 py-1 codigo" placeholder="Código" required></td>
<td class="border px-3 py-1">
    <input type="text" name="producto[]" class="w-full border rounded px-2 py-1" placeholder="Producto" required></td>
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
