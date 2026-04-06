<div id="pantalla-lista-banco-dpto" class="p-6 bg-white rounded-xl shadow-md">

    <h2 class="text-2xl font-semibold mb-4 text-center">Bancos por Razón Social</h2>

    <div id="form-filtros-banco-dpto" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4">
            <label for="buscar-rs" class="sr-only">Buscar Razón Social</label>
            <input type="text" id="buscar-rs" placeholder="Buscar Razón Social..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">

            <label for="buscar-banco" class="sr-only">Buscar Banco</label>
            <input type="text" id="buscar-banco" placeholder="Buscar Banco..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
        </div>
        <div>
            <a href="#" id="btn-agregar-banco-dpto" class="inline-block mt-4 px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors">
                AGREGAR
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead>
            <tr>
                <th class="w-1/4 px-3 py-2 border-b text-left">Razón Social</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Alias</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Banco</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Cuenta</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">CLABE</th>
                <th class="w-1/6 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-banco-dpto">
            <?php if (!empty($bancos_dpto)): ?>
                <?php foreach ($bancos_dpto as $index => $item): ?>
                    <?php 
                        $enRevision = isset($registros_bloqueados) && in_array($item['ID_BancoDpto'], $registros_bloqueados);
                    ?>
                    <tr data-id="<?= $item['ID_BancoDpto'] ?>"
                        data-id-rs="<?= esc($item['ID_RazonSocial'] ?? $item['id_razonsocial'] ?? '') ?>"
                        data-alias="<?= esc($item['Alias'] ?? '') ?>"
                        data-banco="<?= esc($item['Banco'] ?? $item['banco'] ?? '') ?>"
                        data-cuenta="<?= esc($item['Cuenta'] ?? '') ?>"
                        data-sucursal="<?= esc($item['Sucursal'] ?? '') ?>"
                        data-clabe="<?= esc($item['Clabe'] ?? $item['clabe'] ?? '') ?>"
                        class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> <?= $enRevision ? 'bg-yellow-50 opacity-80' : '' ?>">

                        <td class="px-3 py-2 border-b nombre-rs"><?= esc($item['razonsocial_nombre'] ?? 'Sin Razón Social') ?></td>
                        <td class="px-3 py-2 border-b alias-banco"><?= esc($item['Alias'] ?? '-') ?></td>
                        <td class="px-3 py-2 border-b nombre-banco"><?= esc($item['Banco'] ?? $item['banco'] ?? '') ?></td>
                        <td class="px-3 py-2 border-b cuenta-banco"><?= esc($item['Cuenta'] ?? '-') ?></td>
                        <td class="px-3 py-2 border-b clabe-banco font-mono text-sm"><?= esc($item['Clabe'] ?? $item['clabe'] ?? '') ?></td>

                        <td class="px-2 py-2 border-b align-top text-center acciones">
                            <?php if ($enRevision): ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full border border-yellow-300">
                                    ⏳ En Revisión
                                </span>
                            <?php else: ?>
                            <div class="flex flex-col items-center space-y-1 h-full justify-center">
                                <a href="#"
                                   id="btn-editar-banco-dpto-<?= $item['ID_BancoDpto'] ?>"
                                   class="btn-editar text-green-600 hover:text-green-800"
                                   data-id="<?= $item['ID_BancoDpto'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                </a>
                                <a href="#"
                                   id="btn-eliminar-banco-dpto-<?= $item['ID_BancoDpto'] ?>"
                                   class="btn-eliminar text-red-600 hover:text-red-800"
                                   data-id="<?= $item['ID_BancoDpto'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="px-3 py-4 text-center text-gray-500">No hay bancos registrados</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="paginacion-banco-dpto" class="flex justify-center mt-4 space-x-2"></div>
</div>

<div id="pantalla-agregar-banco-dpto" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-banco-dpto" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Agregar Banco a Razón Social</h2>

    <form id="form-agregar-banco-dpto" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">

            <div class="flex flex-col sm:col-span-2">
                <label for="ID_RazonSocial" class="mb-1 font-medium">Razón Social</label>
                <select name="ID_RazonSocial" id="ID_RazonSocial" required class="w-full px-3 py-2 border rounded-lg bg-white">
                    <option value="">-- Seleccione Razón Social --</option>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= $rs['ID_RazonSocial'] ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-col">
                <label for="Alias" class="mb-1 font-medium">Alias de la Cuenta</label>
                <input type="text" name="Alias" id="Alias" placeholder="Ej. Nómina / Principal" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="Banco" class="mb-1 font-medium">Nombre del Banco</label>
                <input type="text" name="Banco" id="Banco" placeholder="Ej. BBVA" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="Cuenta" class="mb-1 font-medium">Número de Cuenta</label>
                <input type="text" name="Cuenta" id="Cuenta" placeholder="11 o 16 dígitos..." maxlength="16" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="Sucursal" class="mb-1 font-medium">Sucursal</label>
                <input type="text" name="Sucursal" id="Sucursal" placeholder="Nombre o número de sucursal..." class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="Clabe" class="mb-1 font-medium">CLABE Interbancaria</label>
                <input type="text" name="Clabe" id="Clabe" placeholder="18 dígitos..." required minlength="18" maxlength="18" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">Guardar</button>
    </form>
    <div id="msg-agregar-banco-dpto" class="mt-4 text-center"></div>
</div>

<div id="pantalla-editar-banco-dpto" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar-banco-dpto" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Editar Banco</h2>

    <form id="form-editar-banco-dpto" class="space-y-4">
        <input type="hidden" name="ID_BancoDpto" id="editar-ID_BancoDpto">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">

            <div class="flex flex-col sm:col-span-2">
                <label for="editar-ID_RazonSocial" class="mb-1 font-medium">Razón Social</label>
                <select name="ID_RazonSocial" id="editar-ID_RazonSocial" required class="w-full px-3 py-2 border rounded-lg bg-white">
                    <option value="">-- Seleccione Razón Social --</option>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= $rs['ID_RazonSocial'] ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-col">
                <label for="editar-Alias" class="mb-1 font-medium">Alias de la Cuenta</label>
                <input type="text" name="Alias" id="editar-Alias" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="editar-Banco" class="mb-1 font-medium">Nombre del Banco</label>
                <input type="text" name="Banco" id="editar-Banco" required class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="editar-Cuenta" class="mb-1 font-medium">Número de Cuenta</label>
                <input type="text" name="Cuenta" id="editar-Cuenta" maxlength="16" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col">
                <label for="editar-Sucursal" class="mb-1 font-medium">Sucursal</label>
                <input type="text" name="Sucursal" id="editar-Sucursal" class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="flex flex-col sm:col-span-2">
                <label for="editar-Clabe" class="mb-1 font-medium">CLABE Interbancaria</label>
                <input type="text" name="Clabe" id="editar-Clabe" required minlength="18" maxlength="18" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-yellow-500 text-black font-semibold rounded-lg shadow hover:bg-yellow-600 transition">Guardar Cambios</button>
    </form>
    <div id="msg-editar-banco-dpto" class="mt-4 text-center"></div>
</div>
