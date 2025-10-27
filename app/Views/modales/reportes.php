<div id="div-reportes" >

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('ajustes')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar
        </button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-6">

        <div class="w-full sm:w-auto flex-shrink-0">
            <label class="flex items-center gap-2 border p-2 rounded cursor-pointer min-w-[190px]" onclick="document.getElementById('filtro-fecha-reportes').showPicker()">
                <span class="text-gray-500 text-sm">Fecha:</span>
                <input type="date" id="filtro-fecha-reportes" class="border-none p-0 focus:ring-0 bg-transparent flex-grow min-w-0">
                <label class="flex items-center gap-1 text-xs text-gray-600 whitespace-nowrap" onclick="event.stopPropagation();">
                    <input type="checkbox" id="filtrar-por-mes-reportes" class="accent-blue-600 h-4 w-4">
                    Mes
                </label>
            </label>
        </div>

        <div class="w-full sm:w-auto flex-shrink-0">
            <select id="filtro-estado-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Estado</option>
                <option value="Por Pagar">🟠 Por Pagar</option>
                <option value="En Proceso de Pago">🟡 En Proceso</option>
                <option value="Completada">🟢 Completada</option>
                <option value="Cancelada">🔴 Cancelada</option>
            </select>
        </div>

        <div class="w-full sm:w-auto flex-shrink-0">
            <select id="filtroDepartamento-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Departamento</option>
                <?php if (!empty($departamentos)): ?>
                    <?php foreach ($departamentos as $dpto): ?>
                        <option value="<?= esc($dpto['Nombre']) ?>"><?= esc($dpto['Nombre']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="w-full sm:w-auto flex-shrink-0">
            <select id="filtroRazonSocial-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Razón Social</option>
                <?php if (!empty($razones_sociales)): ?>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= esc($rs['Nombre']) ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="w-full sm:w-auto flex-shrink-0">
            <select id="filtroProveedor-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Proveedor</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto shadow ">
        <table class="min-w-full border border-gray-300" id="tabla-reportes">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
                <th class="border px-3 py-2 text-left">Folio</th>
                <th class="border px-3 py-2 text-left">Departamento</th>
                <th class="border px-3 py-2 text-left">Razón social</th>
                <th class="border px-3 py-2 text-left">Proveedor</th>
                <th class="border px-3 py-2 text-left">Fecha</th>
                <th class="border px-3 py-2 text-center">Estado</th>
                <th class="border px-3 py-2 text-center">Acción</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <div id="paginacion-reportes" class="flex justify-center mt-4 space-x-2"></div>
    </div>
    <div class="mt-6 flex justify-end">
        <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition">
            Generar Reporte
        </button>
    </div>
</div>