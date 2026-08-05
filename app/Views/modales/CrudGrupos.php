<?php 
    $deptoUsuario = session('departamento_usuario');
    $tieneAccesoEdicion = in_array($deptoUsuario, ['Administración', 'Direccion', 'Dirección', 'Contaduría']);
?>
<div id="pantalla-lista-grupos" 
     class="p-6 bg-white rounded-xl shadow-md"
     data-unidades-json='<?= json_encode($unidades_operativas ?? []) ?>'
     data-tiene-edicion="<?= $tieneAccesoEdicion ? '1' : '0' ?>">
    

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
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Áreas De Operación</label>
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
                <th class="w-1/5 px-3 py-2 border-b text-left">Áreas De Operación</th>
                <th class="w-1/5 px-3 py-2 border-b text-center">Indirecto</th>                <th class="w-1/5 px-3 py-2 border-b text-center">Estado</th>
                <?php if ($tieneAccesoEdicion): ?>
                <th class="w-1/5 px-3 py-2 border-b text-center">Acciones</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody id="tabla-grupos">
            <noscript>
                <tr>
                    <td colspan="<?= ($tieneAccesoEdicion) ? '6' : '5' ?>" class="px-3 py-4 text-center text-red-500">
                        Se requiere JavaScript para cargar la tabla de partidas presupuestales.
                    </td>
                </tr>
            </noscript>
            <!-- Las filas se cargan vía API: GET api/grupos-presupuestales/paginated (createPaginatedTableServer) -->
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
                <label for="ID_UnidadOperativa" class="mb-1 font-medium">Áreas De Operación</label>
                <select name="ID_UnidadOperativa" id="ID_UnidadOperativa" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Seleccionar Áreas De Operación</option>
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
