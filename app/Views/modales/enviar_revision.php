<div class="p-4">
    <h2 class="text-xl font-bold mb-4">Adjuntar/Confirmar Cotización</h2>

    <!-- Div Tabla Principal -->
    <div id="div-tabla-enviar">
        <div class="overflow-x-auto">
            <table id="tabla-enviar" class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left">Folio</th>
                    <th class="py-3 px-6 text-left">Usuario</th>
                    <th class="py-3 px-6 text-left">Departamento</th>
                    <th class="py-3 px-6 text-left">Proveedor</th>
                    <th class="py-3 px-6 text-left">Monto</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th class="py-3 px-6 text-left">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>
        <!-- Contenedor de paginación -->
        <div id="paginacion-enviar-revision" class="flex justify-center mt-4 space-x-2"></div>
    </div>

    <!-- Div Enviar a Revisión -->
    <div id="div-enviar-revision" class="hidden">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Enviar a Revisión</h3>
            <div class="cursor-pointer p-2 rounded-full hover:bg-gray-200" onclick="regresarEnviarRevision()" title="Regresar a la lista">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>

        <!-- Detalles -->
        <div id="detalles-para-revision" class="mb-6">
            <!-- Cargados vía JS -->
        </div>

        <!-- Formulario -->
        <form id="form-enviar-revision" class="mt-4">
            <div>
                <label for="archivos-revision" class="block text-sm font-medium text-gray-700">Adjuntar Cotización (Imágenes o PDF)</label>
                <input type="file" id="archivos-revision" name="archivos[]" multiple accept="image/*,.pdf" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                <p class="mt-1 text-sm text-gray-500">Puede seleccionar múltiples archivos.</p>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Tipo de Pago</label>
                <div class="flex items-center space-x-4 mt-1">
                    <label class="flex items-center">
                        <input type="radio" name="tipo_pago" value="efectivo" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Efectivo</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="tipo_pago" value="credito" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Crédito</span>
                    </label>
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-4 border-t pt-4">
                <button type="submit" id="btn-confirmar-revision" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition">Confirmar y Enviar</button>
            </div>
        </form>
    </div>
</div>
