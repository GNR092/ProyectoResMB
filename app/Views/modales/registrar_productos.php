<div class="p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4">Registrar Producto</h2>

    <form id="formRegistrarProducto" class="space-y-4" action="<?= site_url('modales/registrarMaterial') ?>">
    <div>
            <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
            <input type="text" id="codigo" name="Codigo" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="nombre" name="Nombre" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="existencia" class="block text-sm font-medium text-gray-700">Existencia</label>
            <input type="number" id="existencia" name="Existencia" min="0" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Guardar
            </button>
        </div>
    </form>
</div>