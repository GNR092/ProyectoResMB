<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<tr class="fila-producto">
    <td class="numero-fila px-3 py-2 border text-center"></td>
    <td class="px-3 py-2 border">
        <select name="producto[]" class="w-full px-2 py-1 border rounded select-producto-catalogo" required>
            <option value="">Seleccione un producto...</option>
            <?php if (!empty($catalogo_productos)): ?>
                <?php foreach ($catalogo_productos as $prod): ?>
                    <option value="<?= esc($prod['Nombre']) ?>" 
                            data-codigo="<?= esc($prod['ID_CatalogoProd']) ?>" 
                            data-grupo="<?= esc($prod['ID_GrupoPresupuestal']) ?>">
                        <?= esc($prod['Nombre']) ?> (<?= esc($prod['UnidadNombre'] ?? 'General') ?>)
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </td>
    <td class="px-3 py-2 border">
        <input type="number" name="cantidad[]" class="w-full px-2 py-1 border rounded cantidad" min="1" value="1">
    </td>
    <td class="px-3 py-2 border text-center">
        <button type="button" class="eliminar-fila text-red-600 hover:text-red-800" title="Eliminar fila">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>                                    
        </button>
    </td>
</tr>