<div id="ajustes-presupuesto-container" class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Dictamen de Ajustes de Presupuesto</h2>
    </div>

    <!-- Contenedor Principal (Tabla) -->
    <div id="div-tabla-ajustes" class="w-full">
        <div class="overflow-x-auto shadow-sm border border-gray-200 rounded-lg">
            <table id="tablaAjustesPresupuesto" class="min-w-full leading-normal">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="py-3 px-6 text-left font-semibold">ID</th>
                        <th class="py-3 px-6 text-left font-semibold">Solicitante</th>
                        <th class="py-3 px-6 text-left font-semibold">Módulo Afectado</th>
                        <th class="py-3 px-6 text-left font-semibold">Acción</th>
                        <th class="py-3 px-6 text-left font-semibold">Fecha de Solicitud</th>
                        <th class="py-3 px-6 text-center font-semibold">Revisar</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 text-sm">
                    <!-- Filas inyectadas por JS -->
                </tbody>
            </table>
        </div>
        <div id="paginacion-ajustes-presupuesto" class="flex justify-center items-center mt-6 space-x-2">
            <!-- Paginación -->
        </div>
    </div>

    <!-- Contenedor Detalles de Revisión -->
    <div id="div-ver-detalle-ajuste" class="hidden w-full max-w-5xl mx-auto bg-white p-8 rounded-lg shadow-lg border border-gray-200">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h3 class="text-xl font-bold text-gray-800">Detalles de la Solicitud de Cambio</h3>
            <button onclick="regresarTablaAjustes()" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                Regresar
            </button>
        </div>

        <div id="detalles-ajuste-contenido" class="space-y-6">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>
