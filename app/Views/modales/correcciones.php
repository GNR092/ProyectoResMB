<div id="div-control-maestro" class="p-4">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold text-gray-800">Editar/Corregir Solicitudes</h2>
    </div>

    <div id="filtros-maestro-container" class="flex flex-col md:flex-row md:items-center gap-4 mb-4 bg-white p-3 rounded shadow-sm border border-gray-200">

        <div class="flex items-center gap-2">
            <input type="date" id="filtro-fecha-maestro" class="border border-gray-300 p-2 rounded w-full md:w-auto text-sm">
            <label class="flex items-center gap-1 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" id="filtrar-por-mes-maestro" class="accent-blue-600">
                Filtrar por mes
            </label>
        </div>

        <select id="filtro-estado-maestro" class="border border-gray-300 p-2 rounded w-full md:w-auto text-sm">
            <option value="">Todos los estados</option>
            <option value="En espera">🟡 En espera</option>
            <option value="Aprobada">🟢 Aprobada</option>
            <option value="Rechazada">🔴 Rechazada</option>
            <option value="Cotizando">🔵 Cotizando</option>
            <option value="Aprobacion pendiente">🟠 Aprobación Pendiente</option>
            <option value="En revision">🔵 En revisión</option>
            <option value="Espera_Programacion">🟠 Espera Programación</option>
            <option value="Programada">🔵 Programada</option>
            <option value="Por Pagar">⚪ En espera de factura</option>
            <option value="Pagada">🟢 Pagada</option>
        </select>

        <select id="filtro-metodo-maestro" class="border border-gray-300 p-2 rounded w-full md:w-auto text-sm">
            <option value="">Todos los métodos</option>
            <option value="0">Contado</option>
            <option value="1">Crédito</option>
        </select>

        <div id="wrapper-depto-maestro" class="hidden w-full md:w-auto">
            <select id="filtroDepartamentoMaestro" class="border p-2 rounded w-full" multiple>
                <option value="">Todos los departamentos</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto bg-white border border-gray-300 rounded-lg shadow-sm">
        <table class="w-full border-collapse" id="tabla-maestro">
            <thead class="bg-gray-100 border-b border-gray-300">
            <tr>
                <th class="hidden border-r px-4 py-2 text-sm font-semibold text-gray-600">ID</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Folio</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Fecha</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Departamento</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Proveedor</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Monto</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Estado</th>
                <th class="border-r px-4 py-2 text-sm font-semibold text-gray-600">Método Pago</th>
                <th class="px-4 py-2 text-sm font-semibold text-gray-600 text-center">Acción</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
            </tbody>
        </table>
        <div id="paginacion-maestro" class="flex justify-center p-3 space-x-2 bg-gray-50 border-t border-gray-200"></div>
    </div>
</div>

<div id="div-editor-maestro" class="hidden p-4 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <div>
            <h3 class="text-xl font-bold text-gray-800" id="titulo-editor">Editar Requisición</h3>
        </div>
        <div class="flex gap-2">
            <button onclick="regresarMaestro()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition font-medium">
                Cancelar
            </button>
            <button onclick="guardarCambiosMaestros()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition font-medium shadow flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Guardar Cambios
            </button>
        </div>
    </div>

    <form id="form-editor-maestro" onsubmit="event.preventDefault();" class="max-w-7xl mx-auto">
        <input type="hidden" name="id_solicitud" id="maestro_id_solicitud">
        <div id="contenido-editor-maestro" class="grid grid-cols-1 gap-6">
        </div>
    </form>
</div>