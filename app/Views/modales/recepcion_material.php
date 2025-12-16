<div id="recepcion-material-modal" class="p-4">

    <button onclick="abrirModal('almacen')" class="text-sm text-gray-600 hover:text-gray-900 transition">
        &larr; Regresar a Almacen
    </button>

    <h2 class="text-lg font-bold mb-4">Recepción de Material</h2>

    <form id="form-recepcion-material" class="space-y-4">
        <!-- Campo para seleccionar Orden de Compra -->
        <div>
            <label for="ordenCompraSelect" class="block text-sm font-medium text-gray-700">Seleccionar Orden de Compra:</label>
            <select id="ordenCompraSelect" name="id_orden_compra" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">Cargando órdenes de compra...</option>
            </select>
        </div>

        <!-- Campos de información de la OC seleccionada (solo lectura) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Folio de Solicitud:</label>
                <input type="text" id="solicitudFolio" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Proveedor:</label>
                <input type="text" id="proveedorNombre" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
            </div>
        </div>

        <!-- Tabla de productos de la OC y cantidad a recibir -->
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Producto</th>
                        <th class="py-2 px-4 text-right">Cantidad Pedida</th>
                        <th class="py-2 px-4 text-right">Cantidad Recibida</th>
                    </tr>
                </thead>
                <tbody id="productosRecepcionTable">
                    <tr><td colspan="3" class="text-center py-2">Seleccione una Orden de Compra para ver los productos.</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Campo para adjuntar remisión (placeholder para futura tarea) -->
        <div>
            <label for="remisionArchivo" class="block text-sm font-medium text-gray-700">Adjuntar Remisión (Opcional):</label>
            <input type="file" id="remisionArchivo" name="remision" accept="image/*,.pdf" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
        </div>

        <!-- Campo para adjuntar factura de entrada -->
        <div>
            <label for="facturaEntradaArchivo" class="block text-sm font-medium text-gray-700">Adjuntar Factura de Entrada (Opcional):</label>
            <input type="file" id="facturaEntradaArchivo" name="factura_entrada" accept="image/*,.pdf" class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">Confirmar Recepción</button>
        </div>
    </form>
</div>
