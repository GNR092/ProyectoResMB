<?php
$jsonData = !empty($tabledata) ? json_encode($tabledata) : '[]'; ?>

<div id="div-reportes" x-data="Reportes(<?= htmlspecialchars($jsonData, ENT_QUOTES, 'UTF-8') ?>)">

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('ajustes')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar a Ajustes
        </button>
    </div>

    <!-- Controles de Filtro -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <!-- Filtro Fecha -->
        <div class="w-full sm:w-auto shrink-0">
            <label class="flex items-center gap-2 border p-2 rounded cursor-pointer min-w-[190px]"
                onclick="document.getElementById('filtro-fecha-reportes').showPicker()">
                <span class="text-gray-500 text-sm">Fecha:</span>
                <input type="date" x-model="fecha" id="filtro-fecha-reportes"
                    class="border-none p-0 focus:ring-0 bg-transparent grow min-w-0">
                <label class="flex items-center gap-1 text-xs text-gray-600 whitespace-nowrap"
                    onclick="event.stopPropagation();">
                    <input type="checkbox" x-model="porMes" id="filtrar-por-mes-reportes"
                        class="accent-blue-600 h-4 w-4">
                    Mes
                </label>
            </label>
        </div>

        <!-- Filtro Estado -->
        <div class="w-full sm:w-auto shrink-0">
            <select x-model="estado" id="filtro-estado-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Estado</option>
                <option value="Por Pagar">🟠 Por Pagar</option>
                <option value="En Proceso de Pago">🟡 En Proceso</option>
                <option value="Pagada">🟢 Pagada</option>
                <option value="Cancelada">🔴 Cancelada</option>
            </select>
        </div>

        <!-- Filtro Departamento -->
        <div class="w-full sm:w-auto shrink-0">
            <select x-model="departamento" id="filtroDepartamento-reportes"
                class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Departamento</option>
                <?php if (!empty($departamentos)): ?>
                <?php foreach ($departamentos as $dpto): ?>
                <option value="<?= esc($dpto['Nombre']) ?>"><?= esc($dpto['Nombre']) ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- Filtro Razón Social -->
        <div class="w-full sm:w-auto shrink-0">
            <select x-model="razonSocial" id="filtroRazonSocial-reportes"
                class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Razón Social</option>
                <?php if (!empty($razones_sociales)): ?>
                <?php foreach ($razones_sociales as $rs): ?>
                <option value="<?= esc($rs['Nombre']) ?>"><?= esc($rs['Nombre']) ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- Filtro Proveedor -->
        <div class="w-full sm:w-auto shrink-0">
            <select x-model="proveedor" id="filtroProveedor-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Proveedor</option>
                <?php if (!empty($proveedores)): ?>
                <?php foreach ($proveedores as $prov): ?>
                <option value="<?= esc($prov['RazonSocial']) ?>"><?= esc(
    $prov['RazonSocial'],
) ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- Filtro Método de Pago -->
        <div class="w-full sm:w-auto shrink-0">
            <select x-model="metodoPago" id="filtroMetodoPago-reportes" class="border p-2 rounded w-full min-w-[150px]">
                <option value="">Método de Pago</option>
                <option value="0">Efectivo</option>
                <option value="1">Crédito</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto shadow">
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
                <template x-if="paginatedData.length === 0">
                    <tr>
                        <td colspan="7" class="text-center py-4">No se encontraron datos que coincidan con los filtros.
                        </td>
                    </tr>
                </template>
                <template x-for="(item, index) in paginatedData" :key="item.ID_Solicitud">
                    <tr class="text-center hover:bg-gray-50 text-sm">
                        <td class="border px-3 py-2 text-left" x-text="item.No_Folio"></td>
                        <td class="border px-3 py-2 text-left" x-text="item.DepartamentoNombre"></td>
                        <td class="border px-3 py-2 text-left" x-text="item.Complejo"></td>
                        <td class="border px-3 py-2 text-left" x-text="item.Proveedor"></td>
                        <td class="border px-3 py-2 text-left" x-text="item.Fecha"></td>
                        <td class="border px-3 py-2 col-estado" :data-estado="item.EstadoOrden"
                            :title="item.EstadoOrden" x-text="item.EstadoOrden"></td>
                        <td class="border px-3 py-2">
                            <button class="text-blue-600 hover:underline" @click="mostrarVerReporte(index)">Ver</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Controles de Paginación -->
    <div id="paginacion-reportes" class="flex justify-between items-center mt-4" x-show="totalPages > 1">
        <!-- Información de la página -->
        <div>
            <span class="text-sm text-gray-700">
                Mostrando <span x-text="(currentPage - 1) * rowsPerPage + 1"></span> a <span
                    x-text="Math.min(currentPage * rowsPerPage, filteredData.length)"></span> de <span
                    x-text="filteredData.length"></span> resultados
            </span>
        </div>

        <!-- Botones de Paginación -->
        <div class="flex space-x-2">
            <button @click="prevPage()" :disabled="currentPage === 1"
                class="px-3 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
            </button>
            <button @click="nextPage()" :disabled="currentPage === totalPages"
                class="px-3 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>
    </div>

    <!-- Botón Generar Reporte -->
    <div class="mt-6 flex justify-end">
        <button @click="generarReporteCSV" type="button"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition">
            Generar Reporte
        </button>
    </div>
</div>

<div id="div-ver-reporte">

</div>