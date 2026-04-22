<?php 
    $deptoUsuario = session('departamento_usuario');
    $tieneAccesoEdicion = in_array($deptoUsuario, ['Administración', 'Direccion', 'Dirección', 'Contaduría']);
?>
<div id="pantalla-lista-grupos" 
     class="p-6 bg-white rounded-xl shadow-md"
     data-unidades-json='<?= json_encode($unidades_operativas ?? []) ?>'>
    

    <h2 class="text-2xl font-semibold mb-4 text-center">Partidas Presupuestales</h2>

    <div id="form-filtros-grupos" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4 items-end">
            <div class="flex-1">
                <label for="buscar-nombre-grupo" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre</label>
                <input type="text" id="buscar-nombre-grupo" placeholder="Buscar por nombre..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Complejos</label>
                <select id="filtro-lugar-grupo" multiple>
                    <?php 
                    $placesUnicos = [];
                    if (!empty($unidades_operativas)) {
                        foreach ($unidades_operativas as $uo) {
                            $placesUnicos[$uo['ID_Place']] = $uo['PlaceNombre'];
                        }
                    }
                    asort($placesUnicos);
                    foreach ($placesUnicos as $id => $nombre): ?>
                        <option value="<?= esc($nombre) ?>"><?= esc($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Depto. de Op.</label>
                <select id="filtro-unidad-grupo" multiple>
                    <?php 
                    $unidadesUnicas = [];
                    if (!empty($unidades_operativas)) {
                        foreach ($unidades_operativas as $uo) {
                            $unidadesUnicas[] = $uo['Nombre'];
                        }
                    }
                    $unidadesUnicas = array_unique($unidadesUnicas);
                    asort($unidadesUnicas);
                    foreach ($unidadesUnicas as $nombre): ?>
                        <option value="<?= esc($nombre) ?>"><?= esc($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php if ($tieneAccesoEdicion): ?>
        <div class="self-end mb-1">
            <a href="#" id="btn-agregar-grupos" class="inline-block px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors uppercase text-sm">
                AGREGAR
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead>
            <tr>
                <th class="w-1/5 px-3 py-2 border-b text-left">Nombre</th>
                <th class="w-1/5 px-3 py-2 border-b text-left">Descripción</th>
                <th class="w-1/5 px-3 py-2 border-b text-left">Departamento De Operación</th>
                <th class="w-1/5 px-3 py-2 border-b text-center">Indirecto</th>                <th class="w-1/5 px-3 py-2 border-b text-center">Estado</th>
                <?php if ($tieneAccesoEdicion): ?>
                <th class="w-1/5 px-3 py-2 border-b text-center">Acciones</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody id="tabla-grupos">
            <?php if (!empty($grupos)): ?>
                <?php foreach ($grupos as $index => $grupo): ?>
                    <?php 
                        $valActivo = $grupo['activo'];
                        $esActivo = ($valActivo === true || $valActivo === 't' || $valActivo === 1 || $valActivo === '1'); 
                        $esManual = (!empty($grupo['es_manual']) && ($grupo['es_manual'] === true || $grupo['es_manual'] === 't' || $grupo['es_manual'] === 1 || $grupo['es_manual'] === '1'));
                    ?>
                    <tr data-id="<?= $grupo['ID_GrupoPresupuestal'] ?>"
                        data-nombre="<?= esc($grupo['Nombre']) ?>"
                        data-descripcion="<?= esc($grupo['Descripcion']) ?>"
                        data-id-unidad="<?= esc($grupo['ID_UnidadOperativa'] ?? '') ?>"
                        data-activo="<?= $esActivo ? '1' : '0' ?>"
                        data-es-manual="<?= $esManual ? '1' : '0' ?>"
                        class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> <?= !$esActivo ? 'opacity-60' : '' ?>">

                        <td class="px-3 py-2 border-b nombre-grupo"><?= esc($grupo['Nombre']) ?></td>
                        <td class="px-3 py-2 border-b descripcion-grupo"><?= esc($grupo['Descripcion']) ?></td>
                        <td class="px-3 py-2 border-b unidad-grupo"><?= esc($grupo['UnidadNombre'] ?? 'N/A') ?> (<?= esc($grupo['PlaceNombre'] ?? 'N/A') ?>)</td>
                        <td class="px-3 py-2 border-b text-center">
                            <?php if ($esManual): ?>
                                <span class="text-blue-600 font-bold">SÍ</span>
                            <?php else: ?>
                                <span class="text-gray-400">NO</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 border-b text-center">
                            <?php if ($esActivo): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-bold rounded-full">Activo</span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded-full">Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <?php if ($tieneAccesoEdicion): ?>
                        <td class="px-2 py-2 border-b align-top text-center acciones">
                            <div class="flex flex-col items-center space-y-1 h-full justify-center">
                                <a href="#"
                                   id="btn-editar-grupos-<?= $grupo['ID_GrupoPresupuestal'] ?>"
                                   class="btn-editar text-green-600 hover:text-green-800"
                                   data-id="<?= $grupo['ID_GrupoPresupuestal'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                </a>
                                <?php if ($esActivo): ?>
                                <a href="#"
                                   id="btn-eliminar-grupos-<?= $grupo['ID_GrupoPresupuestal'] ?>"
                                   class="btn-eliminar text-red-600 hover:text-red-800"
                                   title="Desactivar Grupo"
                                   data-id="<?= $grupo['ID_GrupoPresupuestal'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= ($tieneAccesoEdicion) ? '5' : '4' ?>" class="px-3 py-4 text-center text-gray-500">No hay partidas registradas</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="paginacion-grupos" class="flex justify-center mt-4 space-x-2"></div>
</div>

<div id="pantalla-agregar-grupos" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-grupos" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Agregar Partida</h2>

    <form id="form-agregar-grupos" class="space-y-4">
        <div class="grid grid-cols-1 gap-4">
            <div class="flex flex-col">
                <label for="Nombre" class="mb-1 font-medium">Nombre</label>
                <input type="text" name="Nombre" id="Nombre" placeholder="Ej. Materiales" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="Descripcion" class="mb-1 font-medium">Descripción</label>
                <input type="text" name="Descripcion" id="Descripcion" placeholder="Ej. Grupo de materiales" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="ID_UnidadOperativa" class="mb-1 font-medium">Departamento De Operación</label>
                <select name="ID_UnidadOperativa" id="ID_UnidadOperativa" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Seleccionar Departamento De Operación</option>
                    <?php if (!empty($unidades_operativas)): ?>
                        <?php foreach ($unidades_operativas as $uni): ?>
                            <option value="<?= esc($uni['ID_UnidadOperativa']) ?>">
                                <?= esc($uni['Nombre']) ?> (<?= esc($uni['PlaceNombre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="es_manual" id="es_manual" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="es_manual" class="font-medium text-gray-700">Partida de Gastos Indirectos</label>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">Guardar</button>
    </form>
</div>

<div id="pantalla-editar-grupos" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar-grupos" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Editar Partida</h2>

    <form id="form-editar-grupos" class="space-y-4">
        <input type="hidden" name="ID_GrupoPresupuestal" id="editar-ID_GrupoPresupuestal">
        <div class="grid grid-cols-1 gap-4">
            <div class="flex flex-col">
                <label for="editar-Nombre" class="mb-1 font-medium">Nombre</label>
                <input type="text" name="Nombre" id="editar-Nombre" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="editar-Descripcion" class="mb-1 font-medium">Descripción</label>
                <input type="text" name="Descripcion" id="editar-Descripcion" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="editar-ID_UnidadOperativa" class="mb-1 font-medium">Departamento De Operación</label>
                <select name="ID_UnidadOperativa" id="editar-ID_UnidadOperativa" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Seleccionar Departamento De Operación</option>
                    <?php if (!empty($unidades_operativas)): ?>
                        <?php foreach ($unidades_operativas as $uni): ?>
                            <option value="<?= esc($uni['ID_UnidadOperativa']) ?>">
                                <?= esc($uni['Nombre']) ?> (<?= esc($uni['PlaceNombre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="es_manual" id="editar-es_manual" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="editar-es_manual" class="font-medium text-gray-700">Partida de ingresos Indirectos</label>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="activo" id="editar-activo" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="editar-activo" class="font-medium text-gray-700">Activo</label>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-yellow-500 text-black font-semibold rounded-lg shadow hover:bg-yellow-600 transition">Guardar Cambios</button>
    </form>
</div>
