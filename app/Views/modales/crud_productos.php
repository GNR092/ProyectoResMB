<div class="p-4" x-data="CrudProductos()" x-init="init()">

    <!-- Encabezado con botón de regresar -->
    <div class="flex justify-between items-center mb-4">
        <button @click="abrirModal('almacen')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar a Almacen
        </button>
        <h2 class="text-2xl font-bold">Administrar Existencias</h2>
        <div></div>
    </div>

    <!-- Vista de Lista de Productos -->
    <div x-show="!isEditing" id="div-lista-productos">
        <!-- Barra de búsqueda -->
        <div id="div-busqueda" class="mb-4">
            <label for="buscarProducto" class="sr-only">Buscar producto por código o nombre</label>
            <input type="text" id="buscarProducto" x-model.debounce.300ms="searchTerm" placeholder="Buscar por código o nombre..."
                   class="w-full px-4 py-2 border rounded-md shadow-sm">
        </div>

        <!-- Indicador de carga -->
        <template x-if="isLoading">
            <p class="text-center text-gray-500 py-10">Cargando productos...</p>
        </template>

        <!-- Tabla de productos -->
        <div id="div-tabla" class="overflow-x-auto" x-show="!isLoading">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left">Código</th>
                        <th class="py-2 px-4 text-left">Nombre</th>
                        <th class="py-2 px-4 text-left">Existencia</th>
                        <th class="py-2 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaCrudProductos">
                    <template x-for="producto in paginatedProductos" :key="producto.ID_Producto">
                        <tr>
                            <td class="py-2 px-4" x-text="producto.Codigo"></td>
                            <td class="py-2 px-4" x-text="producto.Nombre"></td>
                            <td class="py-2 px-4" x-text="producto.Existencia"></td>
                            <td class="py-2 px-4 text-center">
                                <button @click="editarProducto(producto)" class="text-blue-600 hover:underline">EDITAR</button>
                                <button @click="eliminarProducto(producto.ID_Producto)" class="text-red-600 hover:underline ml-4">ELIMINAR</button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!filteredProductos.length">
                        <tr>
                            <td colspan="4" class="py-10 text-center text-gray-500">No se encontraron productos.</td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Paginación -->
            <div x-show="totalPages > 1" class="flex justify-center items-center mt-6 space-x-2">
                 <button @click="prevPage()" :disabled="currentPage === 1" class="px-3 py-1 border rounded" :class="{'opacity-50 cursor-not-allowed': currentPage === 1}">&laquo; Anterior</button>
                <span x-text="`Página ${currentPage} de ${totalPages}`" class="text-sm text-gray-600 px-2"></span>
                 <button @click="nextPage()" :disabled="currentPage === totalPages" class="px-3 py-1 border rounded" :class="{'opacity-50 cursor-not-allowed': currentPage === totalPages}">Siguiente &raquo;</button>
            </div>
        </div>
    </div>

    <!-- Vista de Edición de Producto -->
    <template x-if="isEditing">
        <div id="div-editar" class="p-4">
            <div @click="regresarTabla()" class="flex items-center mb-4 cursor-pointer text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" /></svg>
                <span class="ml-2 font-medium">Regresar</span>
            </div>

            <h3 class="text-lg font-bold mb-4">Editar Producto</h3>
            
            <template x-if="editingProducto">
                <div class="space-y-4">
                    <!-- Campos solo lectura -->
                    <div>
                        <label class="block mb-1 font-medium">Código (actual)</label>
                        <input type="text" :value="originalProducto.Codigo" class="w-full px-4 py-2 border rounded-md bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Nombre (actual)</label>
                        <input type="text" :value="originalProducto.Nombre" class="w-full px-4 py-2 border rounded-md bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block mb-1 font-medium">Existencia (actual)</label>
                        <input type="number" :value="originalProducto.Existencia" class="w-full px-4 py-2 border rounded-md bg-gray-100" readonly>
                    </div>

                    <!-- Campos editables -->
                    <div>
                        <label for="editarNombre" class="block mb-1 font-medium">Nombre (nuevo)</label>
                        <input type="text" id="editarNombre" x-model="editingProducto.Nombre" class="w-full px-4 py-2 border rounded-md">
                    </div>
                    <div>
                        <label for="editarExistencia" class="block mb-1 font-medium">Existencia (nueva)</label>
                        <input type="number" id="editarExistencia" x-model.number="editingProducto.Existencia" class="w-full px-4 py-2 border rounded-md" min="0">
                    </div>
                    <div>
                        <label for="editarComentarios" class="block mb-1 font-medium">Comentarios / Razones del cambio</label>
                        <textarea id="editarComentarios" x-model="editingProducto.Razon" class="w-full px-4 py-2 border rounded-md" rows="3"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" @click="guardarEdicion()" :disabled="isLoading" class="px-4 py-2 bg-blue-500 text-white rounded-md">
                            <span x-text="isLoading ? 'Guardando...' : 'Guardar'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>
