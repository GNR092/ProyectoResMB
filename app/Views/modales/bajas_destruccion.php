<div id="bajas-destruccion-modal" class="p-4">
    
    <button onclick="abrirModal('almacen')" class="text-sm text-gray-600 hover:text-gray-900 transition">
        &larr; Regresar a Almacen
    </button>

    <h2 class="text-lg font-bold mb-4 cent">Bajas de Material por Destrucción</h2>

    <form id="form-bajas-destruccion" class="space-y-4">
        <!-- Campo para seleccionar Producto -->
        <div>
            <label for="productoSelect" class="block text-sm font-medium text-gray-700">Producto:</label>
            <select id="productoSelect" name="id_producto" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">Cargando productos...</option>
            </select>
        </div>

        <!-- Campo para mostrar existencia actual (solo lectura) -->
        <div>
            <label for="existenciaActual" class="block text-sm font-medium text-gray-700">Existencia Actual:</label>
            <input type="text" id="existenciaActual" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
        </div>

        <!-- Campo para cantidad a dar de baja -->
        <div>
            <label for="cantidadBaja" class="block text-sm font-medium text-gray-700">Cantidad a Dar de Baja:</label>
            <input type="number" id="cantidadBaja" name="cantidad_baja" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" min="1" required>
        </div>

        <!-- Campo para motivo de la baja -->
        <div>
            <label for="motivoBaja" class="block text-sm font-medium text-gray-700">Motivo de la Baja:</label>
            <textarea id="motivoBaja" name="motivo_baja" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required></textarea>
        </div>

        <!-- Campo para fecha de baja -->
        <div>
            <label for="fechaBaja" class="block text-sm font-medium text-gray-700">Fecha de Baja:</label>
            <input type="date" id="fechaBaja" name="fecha_baja" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700">Confirmar Baja</button>
        </div>
    </form>
</div>
