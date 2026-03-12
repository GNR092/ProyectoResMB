<!-- Pantalla 1: lista de departamentos -->
<div id="pantalla-lista-departamentos" class="p-6 bg-white rounded-xl shadow-md">

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('ajustes')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar a Ajustes
        </button>
    </div>

    <h2 class="text-2xl font-semibold mb-4 text-center">Lista de Departamentos</h2>

    <div id="form-filtros-departamentos" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4">
            <label for="buscar-nombre-depto" class="sr-only">Buscar por nombre</label>
            <input type="text" id="buscar-nombre-depto" placeholder="Buscar por nombre..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">

            <label for="buscar-unidad-depto" class="sr-only">Buscar por Unidad</label>
            <input type="text" id="buscar-unidad-depto" placeholder="Buscar por Unidad..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <a href="#" id="btn-agregar-departamento" class="inline-block mt-4 px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors">
                AGREGAR
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead class="bg-gray-100">
            <tr>
                <th class="w-1/3 px-3 py-2 border-b text-left">Departamento</th>
                <th class="w-1/3 px-3 py-2 border-b text-left">Unidad Operativa (Lugar)</th>
                <th class="w-1/3 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-departamentos">
            <?php if (!empty($departamentos)): ?>
                <?php foreach ($departamentos as $index => $depto): ?>
                    <tr data-id="<?= $depto['ID_Dpto'] ?>"
                        data-nombre="<?= esc($depto['Nombre']) ?>"
                        data-id-unidad="<?= esc($depto['ID_UnidadOperativa']) ?>"
                        class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">

                        <td class="px-3 py-2 border-b nombre-depto"><?= esc($depto['Nombre']) ?></td>
                        <td class="px-3 py-2 border-b unidad-depto"><?= esc($depto['UnidadNombre'] ?? 'N/A') ?> (<?= esc($depto['PlaceNombre'] ?? 'N/A') ?>)</td>

                        <td class="px-2 py-2 border-b align-top text-center acciones">
                            <div class="flex flex-col items-center space-y-1 h-full justify-center">
                                <a href="#"
                                   id="btn-editar-departamento-<?= $depto['ID_Dpto'] ?>"
                                   class="btn-editar text-green-600 hover:text-green-800"
                                   data-id="<?= $depto['ID_Dpto'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                </a>
                                <a href="#"
                                   id="btn-eliminar-departamento-<?= $depto['ID_Dpto'] ?>"
                                   class="btn-eliminar text-red-600 hover:text-red-800"
                                   data-id="<?= $depto['ID_Dpto'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="px-3 py-4 text-center text-gray-500">No hay departamentos registrados</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="paginacion-departamentos" class="flex justify-center mt-4 space-x-2"></div>
</div>

<!-- Pantalla 2: agregar departamento -->
<div id="pantalla-agregar-departamento" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Agregar Departamento</h2>

    <form id="form-agregar-departamento" class="space-y-4">
        <div class="grid grid-cols-1 gap-4">
            <div class="flex flex-col">
                <label for="Nombre" class="mb-1 font-medium">Nombre del Departamento</label>
                <input type="text" name="Nombre" id="Nombre" placeholder="Ej. Ventas" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="ID_UnidadOperativa" class="font-medium mb-1">Unidad Operativa</label>
                <select name="ID_UnidadOperativa" id="ID_UnidadOperativa" required class="w-full px-3 py-2 border rounded-lg bg-white">
                    <option value="">Seleccione Unidad Operativa</option>
                    <?php if (!empty($unidades_operativas)): ?>
                        <?php foreach ($unidades_operativas as $unidad): ?>
                            <option value="<?= $unidad['ID_UnidadOperativa'] ?>">
                                <?= esc($unidad['Nombre']) ?> (<?= esc($unidad['PlaceNombre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">Guardar</button>
    </form>
</div>

<!-- Pantalla 3: editar departamento -->
<div id="pantalla-editar-departamento" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Editar Departamento</h2>

    <form id="form-editar-departamento" class="space-y-4">
        <input type="hidden" name="ID_Dpto" id="editar-ID_Dpto">

        <div class="grid grid-cols-1 gap-4">
            <div class="flex flex-col">
                <label for="editar-Nombre" class="mb-1 font-medium">Nombre del Departamento</label>
                <input type="text" name="Nombre" id="editar-Nombre" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="editar-ID_UnidadOperativa" class="mb-1 font-medium">Unidad Operativa</label>
                <select name="ID_UnidadOperativa" id="editar-ID_UnidadOperativa" required class="w-full px-3 py-2 border rounded-lg bg-white">
                    <option value="">Seleccione Unidad Operativa</option>
                    <?php if (!empty($unidades_operativas)): ?>
                        <?php foreach ($unidades_operativas as $unidad): ?>
                            <option value="<?= $unidad['ID_UnidadOperativa'] ?>">
                                <?= esc($unidad['Nombre']) ?> (<?= esc($unidad['PlaceNombre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-yellow-500 text-black font-semibold rounded-lg shadow hover:bg-yellow-600 transition">Guardar Cambios</button>
    </form>
</div>
