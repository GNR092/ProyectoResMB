<!-- Pantalla 1: lista de unidades operativas -->
<div id="pantalla-lista-unidades" class="p-6 bg-white rounded-xl shadow-md">

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('ajustes')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar a Ajustes
        </button>
    </div>

    <h2 class="text-2xl font-semibold mb-4 text-center">Unidades Operativas</h2>

    <div id="form-filtros-unidades" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4">
            <input type="text" id="buscar-nombre-unidad" placeholder="Buscar unidad..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
            <input type="text" id="buscar-lugar-unidad" placeholder="Buscar por lugar..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <a href="#" id="btn-agregar-unidad" class="inline-block mt-4 px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors">
                AGREGAR
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead class="bg-gray-100">
            <tr>
                <th class="w-1/3 px-3 py-2 border-b text-left">Unidad Operativa</th>
                <th class="w-1/3 px-3 py-2 border-b text-left">Lugar (Complejo)</th>
                <th class="w-1/6 px-3 py-2 border-b text-center">Estado</th>
                <th class="w-1/6 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-unidades">
            <?php if (!empty($unidades)): ?>
                <?php foreach ($unidades as $index => $uni): ?>
                    <?php 
                        $valActivo = $uni['activo'];
                        $esActivo = ($valActivo === true || $valActivo === 't' || $valActivo === 1 || $valActivo === '1'); 
                        $enRevision = isset($registros_bloqueados) && in_array($uni['ID_UnidadOperativa'], $registros_bloqueados);
                    ?>
                    <tr data-id="<?= $uni['ID_UnidadOperativa'] ?>"
                        data-nombre="<?= esc($uni['Nombre']) ?>"
                        data-id-place="<?= esc($uni['ID_Place']) ?>"
                        data-activo="<?= $esActivo ? '1' : '0' ?>"
                        class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> <?= !$esActivo ? 'opacity-60' : '' ?> <?= $enRevision ? 'bg-yellow-50 opacity-80' : '' ?>">

                        <td class="px-3 py-2 border-b nombre-unidad"><?= esc($uni['Nombre']) ?></td>
                        <td class="px-3 py-2 border-b lugar-unidad"><?= esc($uni['PlaceNombre'] ?? 'N/A') ?></td>
                        <td class="px-3 py-2 border-b text-center">
                            <span class="px-2 py-1 <?= $esActivo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> text-xs font-bold rounded-full">
                                <?= $esActivo ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>

                        <td class="px-2 py-2 border-b align-top text-center acciones">
                            <?php if ($enRevision): ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full border border-yellow-300">
                                    ⏳ En Revisión
                                </span>
                            <?php else: ?>
                            <div class="flex flex-col items-center space-y-1 h-full justify-center">
                                <a href="#" id="btn-editar-unidad-<?= $uni['ID_UnidadOperativa'] ?>" class="btn-editar text-green-600 hover:text-green-800" data-id="<?= $uni['ID_UnidadOperativa'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                                </a>
                                <?php if ($esActivo): ?>
                                <a href="#" id="btn-eliminar-unidad-<?= $uni['ID_UnidadOperativa'] ?>" class="btn-eliminar text-red-600 hover:text-red-800" data-id="<?= $uni['ID_UnidadOperativa'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="px-3 py-4 text-center text-gray-500">No hay unidades registradas</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="paginacion-unidades" class="flex justify-center mt-4 space-x-2"></div>
</div>

<!-- Pantalla Agregar -->
<div id="pantalla-agregar-unidad" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-unidad" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Agregar Unidad Operativa</h2>
    <form id="form-agregar-unidad" class="space-y-4">
        <div class="flex flex-col">
            <label for="Nombre" class="mb-1 font-medium">Nombre de la Unidad</label>
            <input type="text" name="Nombre" id="Nombre" placeholder="Ej. Área Comercial" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="flex flex-col">
            <label for="ID_Place" class="font-medium mb-1">Lugar (Complejo)</label>
            <select name="ID_Place" id="ID_Place" required class="w-full px-3 py-2 border rounded-lg bg-white">
                <option value="">Seleccione un lugar</option>
                <?php if (!empty($places)): ?>
                    <?php foreach ($places as $place): ?>
                        <option value="<?= $place['ID_Place'] ?>"><?= esc($place['Nombre_Corto']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">Guardar</button>
    </form>
</div>

<!-- Pantalla Editar -->
<div id="pantalla-editar-unidad" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar-unidad" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Editar Unidad Operativa</h2>
    <form id="form-editar-unidad" class="space-y-4">
        <input type="hidden" name="ID_UnidadOperativa" id="editar-ID_UnidadOperativa">
        <div class="flex flex-col">
            <label for="editar-Nombre-unidad" class="mb-1 font-medium">Nombre de la Unidad</label>
            <input type="text" name="Nombre" id="editar-Nombre-unidad" required class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="flex flex-col">
            <label for="editar-ID_Place-unidad" class="font-medium mb-1">Lugar</label>
            <select name="ID_Place" id="editar-ID_Place-unidad" required class="w-full px-3 py-2 border rounded-lg bg-white">
                <?php if (!empty($places)): ?>
                    <?php foreach ($places as $place): ?>
                        <option value="<?= $place['ID_Place'] ?>"><?= esc($place['Nombre_Corto']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="activo" id="editar-activo-unidad" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="editar-activo-unidad" class="font-medium text-gray-700">Activo</label>
        </div>
        <button type="submit" class="px-6 py-2 bg-yellow-500 text-black font-semibold rounded-lg shadow hover:bg-yellow-600 transition">Guardar Cambios</button>
    </form>
</div>
