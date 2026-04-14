<div id="pantalla-lista-segmentos" class="p-6 bg-white rounded-xl shadow-md">
    <h2 class="text-2xl font-semibold mb-4 text-center">Segmentos de Negocio</h2>

    <div id="form-filtros-segmentos" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4 items-end">
            <div class="flex-1">
                <label for="buscar-nombre-segmento" class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre</label>
                <input type="text" id="buscar-nombre-segmento" placeholder="Buscar por nombre..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="filtro-rs-segmento" class="block text-xs font-bold text-gray-500 uppercase mb-1">Razón Social</label>
                <select id="filtro-rs-segmento" multiple>
                    <?php if (!empty($razones_sociales)): ?>
                        <?php foreach ($razones_sociales as $rs): ?>
                            <option value="<?= esc($rs['Nombre']) ?>"><?= esc($rs['Nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="self-end mb-1">
            <a href="#" id="btn-agregar-segmentos" class="inline-block px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors uppercase text-sm">
                AGREGAR
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed text-sm">
            <thead>
            <tr>
                <th class="w-1/4 px-3 py-2 border-b text-left">Nombre</th>
                <th class="w-1/4 px-3 py-2 border-b text-left">Descripción</th>
                <th class="w-1/4 px-3 py-2 border-b text-left">Razón Social</th>
                <th class="w-1/4 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-segmentos">
            <?php if (!empty($segmentos)): ?>
                <?php foreach ($segmentos as $index => $s): ?>
                    <tr data-id="<?= $s['id'] ?>"
                        data-nombre="<?= esc($s['nombre']) ?>"
                        data-descripcion="<?= esc($s['descripcion']) ?>"
                        data-id-rs="<?= esc($s['id_razon_social']) ?>"
                        class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">

                        <td class="px-3 py-2 border-b nombre-segmento"><?= esc($s['nombre']) ?></td>
                        <td class="px-3 py-2 border-b descripcion-segmento"><?= esc($s['descripcion']) ?></td>
                        <td class="px-3 py-2 border-b razon-social-segmento"><?= esc($s['RazonSocial_Nombre']) ?></td>

                        <td class="px-2 py-2 border-b align-top text-center acciones">
                            <div class="flex flex-col items-center space-y-1 h-full justify-center">
                                <a href="#" id="btn-editar-segmentos-<?= $s['id'] ?>" class="btn-editar text-green-600 hover:text-green-800" data-id="<?= $s['id'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                </a>
                                <a href="#" id="btn-eliminar-segmentos-<?= $s['id'] ?>" class="btn-eliminar text-red-600 hover:text-red-800" data-id="<?= $s['id'] ?>">
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
                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">No hay segmentos registrados</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="paginacion-segmentos" class="flex justify-center mt-4 space-x-2"></div>
</div>

<div id="pantalla-agregar-segmentos" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-segmentos" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Agregar Segmento</h2>

    <form id="form-agregar-segmentos" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 text-sm">
            <div class="flex flex-col">
                <label for="nombre" class="mb-1 font-medium">Nombre del Segmento</label>
                <input type="text" name="nombre" id="nombre" placeholder="Ej. Residencial" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="id_razon_social" class="mb-1 font-medium">Razón Social</label>
                <select name="id_razon_social" id="id_razon_social" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Seleccionar Razón Social</option>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= esc($rs['ID_RazonSocial']) ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col">
                <label for="descripcion" class="mb-1 font-medium">Descripción</label>
                <textarea name="descripcion" id="descripcion" class="w-full px-3 py-2 border rounded-lg" rows="3"></textarea>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">Guardar</button>
    </form>
</div>

<div id="pantalla-editar-segmentos" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar-segmentos" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Editar Segmento</h2>

    <form id="form-editar-segmentos" class="space-y-4">
        <input type="hidden" name="id" id="editar-id">
        <div class="grid grid-cols-1 gap-4 text-sm">
            <div class="flex flex-col">
                <label for="editar-nombre" class="mb-1 font-medium">Nombre</label>
                <input type="text" name="nombre" id="editar-nombre" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-id_razon_social" class="mb-1 font-medium">Razón Social</label>
                <select name="id_razon_social" id="editar-id_razon_social" required class="w-full px-3 py-2 border rounded-lg">
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= esc($rs['ID_RazonSocial']) ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col">
                <label for="editar-descripcion" class="mb-1 font-medium">Descripción</label>
                <textarea name="descripcion" id="editar-descripcion" class="w-full px-3 py-2 border rounded-lg" rows="3"></textarea>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-yellow-500 text-black font-semibold rounded-lg shadow hover:bg-yellow-600 transition">Guardar Cambios</button>
    </form>
</div>
