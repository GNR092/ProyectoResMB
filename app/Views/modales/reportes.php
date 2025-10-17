<div id="div-reportes" class="p-4">

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('ajustes')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar
        </button>
    </div>

    <h2 class="text-2xl font-semibold mb-4">Reportes de Ordenes de Compra</h2>

    <!-- Filtros -->
    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
        <div class="flex items-center gap-2">
            <input type="date" id="filtro-fecha-reportes" class="border p-2 rounded w-full md:w-auto">
            <label class="flex items-center gap-1 text-sm text-gray-700">
                <input type="checkbox" id="filtrar-por-mes-reportes" class="accent-blue-600">
                Filtrar por mes
            </label>
        </div>

        <select id="filtro-estado-reportes" class="border p-2 rounded w-full md:w-auto">
            <option value="">Todos los estados</option>
            <option value="En Proceso de Pago">🟡 En Proceso de Pago</option>
            <option value="Completada">🟢 Completada</option>
            <option value="Cancelada">🔴 Cancelada</option>
        </select>

        <select id="filtroDepartamento-reportes" class="border p-2 rounded w-full md:w-auto">
            <option value="">Todos los departamentos</option>
            <?php if (!empty($departamentos)): ?>
                <?php foreach ($departamentos as $dpto): ?>
                    <option value="<?= esc($dpto['Nombre']) ?>"><?= esc($dpto['Nombre']) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300" id="tabla-reportes">
            <thead class="bg-gray-100">
            <tr>
                <th class="border px-4 py-2">ID Orden</th>
                <th class="border px-4 py-2">ID Cotización</th>
                <th class="border px-4 py-2">ID Solicitud</th>
                <th class="border px-4 py-2">Estado</th>
                <th class="border px-4 py-2">Acción</th>
            </tr>
            </thead>
            <tbody></tbody>
        </table>
        <div id="paginacion-reportes" class="flex justify-center mt-4 space-x-2"></div>
    </div>
</div>

<!-- Detalle de Orden -->
<div id="div-ver-reporte" class="hidden p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold">Detalle de Orden de Compra</h3>
        <div class="cursor-pointer p-2 rounded-full hover:bg-gray-200" onclick="regresarReportes()" title="Regresar a la lista">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>
    <div id="detalles-reporte"></div>
</div>
