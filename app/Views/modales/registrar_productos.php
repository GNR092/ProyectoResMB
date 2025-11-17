<div class="p-6 bg-gray-50 rounded-lg shadow-xl" x-data="RegistrarProducto()">

    <!-- Encabezado -->
    <div class="flex justify-between items-center pb-4 mb-6 border-b border-gray-200">
        <button onclick="abrirModal('almacen')" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition duration-150 ease-in-out">
            &larr; Regresar a Almacen
        </button>
        <h2 class="text-xl font-bold text-gray-800">Registrar Nuevo Producto</h2>
        <div></div> <!-- Spacer -->
    </div>

    <form @submit.prevent="onSubmit()" x-ref="registerForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?= csrf_field() ?>

        <!-- Campo Código -->
        <div class="md:col-span-1">
            <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código o SKU</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><use xlink:href="/icons/icons.svg#hash-tag"></use></svg>
                </div>
                <input type="text" id="codigo" name="Codigo" required placeholder="Ej: PROD-001"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>

        <!-- Campo Existencia -->
        <div class="md:col-span-1">
            <label for="existencia" class="block text-sm font-medium text-gray-700 mb-1">Existencia Inicial</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                     <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><use xlink:href="/icons/icons.svg#stack"></use></svg>
                </div>
                <input type="number" id="existencia" name="Existencia" min="0" required placeholder="Ej: 100"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>
        
        <!-- Campo Nombre -->
        <div class="md:col-span-2">
            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
            <div class="relative">
                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><use xlink:href="/icons/icons.svg#tag"></use></svg>
                </div>
                <input type="text" id="nombre" name="Nombre" required placeholder="Descripción completa del producto"
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
        </div>

        <!-- Mensajes del formulario -->
        <div class="md:col-span-2 my-2" x-ref="formMessage"></div>

        <!-- Botón de envío -->
        <div class="md:col-span-2 flex justify-end pt-4">
            <button type="submit" :disabled="isLoading"
                class="inline-flex items-center justify-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-150 ease-in-out"
                :class="{ 'opacity-50 cursor-not-allowed': isLoading }">
                
                <!-- Spinner de carga -->
                <template x-if="isLoading">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"><use xlink:href="/icons/icons.svg#spinner"></use></svg>
                </template>
                
                <!-- Icono de guardado -->
                <template x-if="!isLoading">
                    <svg class="-ml-1 mr-2 h-5 w-5"><use xlink:href="/icons/icons.svg#save-disk"></use></svg>
                </template>
                
                <span x-text="isLoading ? 'Registrando...' : 'Registrar Producto'"></span>
            </button>
        </div>
    </form>
</div>