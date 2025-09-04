<!-- Pantalla 1: historial -->
<div id="div-historial" class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Ver Historial</h2>

    <!-- Filtros -->
    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
        <input type="date" id="filtro-fecha" class="border p-2 rounded w-full md:w-auto" placeholder="Fecha">
        <select id="filtro-estado" class="border p-2 rounded w-full md:w-auto">
            <option value="">Todos los estados</option>
            <option value="En espera">🟡 En espera</option>
            <option value="Aprobada">🟢 Aprobada</option>
            <option value="Rechazada">🔴 Rechazado</option>
            <option value="Cotizando">🔵 Cotizando</option>
            <option value="Cotizada">🟣 Cotizada</option>
        </select>

    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300" id="tabla-historial">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-4 py-2">ID</th>
                    <th class="border px-4 py-2">Fecha</th>
                    <th class="border px-4 py-2">Departamento</th>
                    <th class="border px-4 py-2">Folio</th>
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
    <h3 class="text-lg font-bold mb-4">Detalles de la Solicitud</h3>
    <button class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300" onclick="regresarHistorial()">
        Regresar
    </button>
</div>
