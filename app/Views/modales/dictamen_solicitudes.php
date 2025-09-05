<div class="p-4">
    <h2 class="text-lg font-bold mb-4">Solicitudes en dictamen</h2>

    <!-- Div Tabla Principal -->
    <div id="div-tabla">
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left">Folio</th>
                    <th class="py-3 px-6 text-left">Usuario</th>
                    <th class="py-3 px-6 text-left">Departamento</th>
                    <th class="py-3 px-6 text-left">Fecha</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th></th> <!-- VER -->
                </tr>
                </thead>
                <tbody id="tablaDictamenSolicitudes">
                <!-- Las filas se insertarán aquí dinámicamente vía JS -->
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <div id="paginacion-dictamen" class="flex justify-center mt-4 space-x-2"></div>
    </div>

    <!-- Div VER -->
    <div id="div-ver-dictamen" class="hidden">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Detalles de la solicitud</h3>
            <div class="cursor-pointer p-2 rounded-full hover:bg-gray-200" onclick="regresarTablaDictamen()" title="Regresar a la lista">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div id="detallesDictamen">
            <!-- Aquí se cargarán los detalles vía JS -->
        </div>
    </div>
</div>
