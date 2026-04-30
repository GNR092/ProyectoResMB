<td class="numero-fila-servicio border px-3 py-1 text-center"></td>

<td class="border px-3 py-1">
    <select name="servicio[]" class="w-full border rounded px-2 py-1 select-producto-catalogo" required>
        <option value="">Seleccione un servicio...</option>
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

<td class="border px-3 py-1">
    <input type="number"
           name="importe[]"
           class="costo-servicio w-full border rounded px-2 py-1"
           min="0"
           step="0.01"
           value="0"
           required>
</td>

<td class="border px-3 py-1 text-center">
    <button type="button" class="eliminar-fila-servicio text-red-600 hover:text-red-800" title="Eliminar fila">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    </button>
</td>