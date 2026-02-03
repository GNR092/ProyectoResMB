<!-- Pantalla 1: historial -->
<div id="div-historial" class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Ver Historial de requisiciones</h2>

    <!-- Filtros -->
    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">

        <!-- Filtro por Fecha -->
        <div class="flex items-center gap-2">
            <input type="date" id="filtro-fecha" class="border p-2 rounded w-full md:w-auto">
            <label class="flex items-center gap-1 text-sm text-gray-700">
                <input type="checkbox" id="filtrar-por-mes" class="accent-blue-600">
                Filtrar por mes
            </label>
        </div>

        <!-- Filtro por Estado -->
        <select id="filtro-estado" class="border p-2 rounded w-full md:w-auto">
            <option value="">Todos los estados</option>
            <option value="En espera">🟡 En espera</option>
            <option value="Aprobada">🟢 Aprobada</option>
            <option value="Rechazada">🔴 Rechazada</option>
            <option value="Cotizando">🔵 Cotizando</option>
            <option value="Aprobacion pendiente" id="filtro-pendiente-aprobacion" class="hidden">🟠 Aprobación Pendiente</option>
            <option value="Cotizada">🟣 Cotizada</option>
            <!-- Nuevas opciones -->
            <option value="Espera_Programacion">🟠 Espera Programación</option>
            <option value="Programada">🔵 Programada</option>
            <option value="Por Pagar">⚪ En espera de factura</option>
        </select>

        <!-- Filtro por Departamento -->
         <?php if (session('login_type') === 'boss'): ?>
        <select id="filtroDepartamento" class="border p-2 rounded w-full md:w-auto">
            <option value="">Todos los departamentos</option>
            <?php if (isset($departamentos) && !empty($departamentos)): ?>
                <?php foreach ($departamentos as $dpto): ?>
                    <option value="<?= esc($dpto['Nombre']) ?>|<?= esc($dpto['PlaceNombre'] ?? '') ?>">
                        <?= esc($dpto['Nombre']) ?> - <?= esc($dpto['PlaceNombre'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        <?php endif; ?>

        <!-- Botón de Exportar -->
        <button onclick="exportarHistorialExcel()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition self-start md:self-auto">
            Exportar a Excel
        </button>
    </div>



    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300" id="tabla-historial">
            <thead class="bg-gray-100">
                <tr>
                    <th class="hidden border px-4 py-2">ID</th>
                    <th class="border px-4 py-2">Folio</th>
                    <th class="border px-4 py-2">Fecha</th>
                    <th class="border px-4 py-2">Departamento</th>
                    <th class="border px-4 py-2">Estado</th>
                    <th class="border px-4 py-2">Acción</th>
                </tr>
            </thead>
            <tbody>
                <!-- Las filas se insertarán aquí dinámicamente -->
            </tbody>
        </table>
        <!-- Paginación -->
        <div id="paginacion-historial" class="flex justify-center mt-4 space-x-2"></div>
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
