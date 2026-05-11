<?php
// Vista de historial
?>

<!-- Pantalla 1: historial -->
<div id="div-historial" class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h2 id="titulo-historial" class="text-2xl font-semibold">Ver Historial de requisiciones</h2>
        <button id="btn-toggle-declinadas" onclick="toggleVistaDeclinadas()" class="px-4 py-2 bg-red-100 text-red-700 text-sm font-bold rounded-md hover:bg-red-200 transition shadow-sm border border-red-200 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            Archivo De Requisiciones Declinadas
        </button>
    </div>

    <!-- Filtros -->
    <div class="flex flex-col gap-6 mb-6">
        <!-- Primera Línea: Filtros Básicos y Acciones -->
        <div class="flex flex-wrap items-end gap-4">
            <!-- Filtro por Fecha -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Fecha</label>
                <div class="flex items-center gap-2 bg-white border border-gray-300 rounded-md px-2 py-1">
                    <input type="date" id="filtro-fecha" class="outline-none text-sm p-1">
                    <label class="flex items-center gap-1 text-xs text-gray-600 border-l pl-2 cursor-pointer">
                        <input type="checkbox" id="filtrar-por-mes" class="accent-blue-600">
                        Mes
                    </label>
                </div>
            </div>

            <!-- Filtro por Folio -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Folio</label>
                <input type="text" id="filtro-folio" placeholder="Buscar folio..." class="border border-gray-300 p-2 rounded-md text-sm w-40 outline-blue-500">
            </div>

            <!-- Filtro por Tipo -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Tipo</label>
                <select id="filtro-tipo-historial" class="border border-gray-300 p-2 rounded-md text-sm w-36 outline-blue-500">
                    <option value="">Todos</option>
                    <option value="Producto">📦 Producto</option>
                    <option value="Servicio">🛠️ Servicio</option>
                </select>
            </div>

            <!-- Filtro por Estado -->
            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Estado</label>
                <select id="filtro-estado" class="border border-gray-300 p-2 rounded-md text-sm w-48 outline-blue-500">
                    <option value="">Todos los estados</option>
                    <option value="En espera">🟡 En espera</option>
                    <option value="Aprobada">🟢 Aprobada</option>
                    <option value="Rechazada">🔴 Rechazada</option>
                    <option value="Cotizando">🔵 Cotizando</option>
                    <option value="Aprobacion pendiente" id="filtro-pendiente-aprobacion" class="hidden">🟠 Aprobación Pendiente</option>
                    <option value="En revision">🔵 En revisión</option>
                    <option value="Espera_Programacion">🟠 Espera Programación</option>
                    <option value="Programada">🔵 Programada</option>
                    <option value="Por Pagar">⚪ En espera de factura</option>
                    <option value="Pagada">🟢Pagada </option>
                </select>
            </div>

            <!-- Botón de Exportar -->
            <div class="flex-grow flex justify-end">
                <button onclick="exportarHistorialExcel()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700 transition shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Exportar Excel
                </button>
            </div>
        </div>

        <!-- Segunda Línea: Selectores de Búsqueda Avanzada (Choices) -->
        <div class="flex flex-wrap items-end gap-4">
            <!-- Filtro por Proveedor -->
            <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Proveedores</label>
                <select id="filtro-proveedor" class="border p-2 rounded w-full" multiple>
                    <option value="">Todos los proveedores</option>
                    <?php if (isset($proveedores) && !empty($proveedores)): ?>
                        <?php foreach ($proveedores as $prov): ?>
                            <option value="<?= esc($prov['RazonSocial']) ?>">
                                <?= esc($prov['RazonSocial']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Filtro por Razón Social -->
            <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Razones Sociales</label>
                <select id="filtro-razon-social" class="border p-2 rounded w-full text-sm" multiple>
                    <option value="">Todas las razones sociales</option>
                    <?php if (isset($razones_sociales) && !empty($razones_sociales)): ?>
                        <?php foreach ($razones_sociales as $rs): ?>
                            <option value="<?= esc($rs['Nombre']) ?>">
                                <?= esc($rs['Nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Filtro por Departamento -->
            <?php if (session('login_type') === 'boss'): ?>
                <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                    <label class="text-[10px] font-bold text-gray-500 uppercase ml-1">Departamentos</label>
                    <select id="filtroDepartamento" class="border p-2 rounded w-full" multiple>
                        <option value="">Todos los departamentos</option>
                        <?php if (isset($departamentos) && !empty($departamentos)): ?>
                            <?php foreach ($departamentos as $dpto): ?>
                                <option value="<?= esc($dpto['Nombre']) ?>|<?= esc(
    $dpto['PlaceNombre'] ?? '',
) ?>">
                                    <?= esc($dpto['Nombre']) ?> - <?= esc(
     $dpto['PlaceNombre'] ?? '',
 ) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    </div>



    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300" id="tabla-historial">
            <thead class="bg-gray-100">
            <tr>
                <th class="hidden border px-4 py-2">ID</th>
                <th class="border px-4 py-2">Folio</th>
                <th class="border px-4 py-2">Fecha</th>
                <th class="border px-4 py-2">Razón Social</th>
                <th class="border px-4 py-2">Departamento</th>
                <th class="border px-4 py-2">Proveedor</th>
                <th class="border px-4 py-2">Monto</th>
                <th class="border px-4 py-2">Estado</th>
                <th class="border px-4 py-2">Metodo de pago</th>
                <th class="border px-4 py-2">Acción</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <div id="paginacion-historial" class="flex flex-wrap justify-center mt-4 gap-2"></div>
    </div>
</div>


<!-- Pantalla2 2: Ver Solicitud-->
<div id="div-ver-historial" class="hidden p-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold">Detalles de la requisicion</h3>
        <div class="cursor-pointer p-2 rounded-full hover:bg-gray-200" onclick="regresarHistorial()" title="Regresar a la lista">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>
    <div id="detalles-historial-solicitud">
        <!-- Los detalles de la solicitud se cargarán aquí dinámicamente -->
    </div>
</div>
