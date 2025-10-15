<div class="p-4">
    <h2 class="text-2xl font-bold mb-4">CRUD Productos</h2>

    <!-- Encabezado con botón de regresar -->
    <div class="flex justify-between items-center mb-4">
        <button onclick="abrirModal('almacen')"
                class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar
        </button>
        <h2 class="text-2xl font-bold">CRUD Productos</h2>
        <div></div>
    </div>

    <!-- Barra de búsqueda -->
    <div id="div-busqueda" class="mb-4">
        <input
                type="text"
                id="buscarProducto"
                placeholder="Buscar por código o nombre..."
                class="w-full px-4 py-2 border rounded-md"
        >
    </div>


    <!-- Tabla de productos -->
    <div id="div-tabla" class="overflow-x-auto">
        <table class="min-w-full border border-gray-300">
            <thead>
            <tr class="bg-gray-100">
                <th class="py-2 px-4 text-left">Código</th>
                <th class="py-2 px-4 text-left">Nombre</th>
                <th class="py-2 px-4 text-left">Existencia</th>
                <th class="py-2 px-4"></th>
                <th class="py-2 px-4"></th>
            </tr>
            </thead>
            <tbody id="tablaCrudProductos">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $producto): ?>
                    <tr data-id="<?= $producto['ID_Producto'] ?>">
                        <td class="py-2 px-4"><?= esc($producto['Codigo']) ?></td>
                        <td class="py-2 px-4"><?= esc($producto['Nombre']) ?></td>
                        <td class="py-2 px-4"><?= esc($producto['Existencia']) ?></td>
                        <td class="py-2 px-4 text-left text-green-600 cursor-pointer" onclick="editarProducto(<?= $producto['ID_Producto'] ?>)">EDITAR</td>
                        <td class="py-2 px-4 text-left text-red-600 cursor-pointer" onclick="eliminarProducto(<?= $producto['ID_Producto'] ?>)">ELIMINAR</td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="py-2 px-4 text-center text-gray-500">
                        No hay productos registrados.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div id="paginacion-crud-productos" class="flex justify-center mt-4 space-x-2"></div>
    </div>

    <!-- Div EDITAR -->

    <div id="div-editar" class="hidden p-4">
        <div class="flex items-center mb-4 cursor-pointer text-gray-600" onclick="regresarTablaProductos()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
            </svg>
            <span class="ml-2 font-medium">Regresar</span>
        </div>

        <h3 class="text-lg font-bold mb-4">Editar Producto</h3>

        <form id="form-editar-producto" class="space-y-4">
            <input type="hidden" id="editarID_Producto">

            <!-- Campos solo lectura -->
            <div>
                <label class="block mb-1 font-medium">Código (actual)</label>
                <input type="text" id="mostrarCodigo" class="w-full px-4 py-2 border rounded-md bg-gray-100" readonly>
            </div>

            <div>
                <label class="block mb-1 font-medium">Nombre (actual)</label>
                <input type="text" id="mostrarNombre" class="w-full px-4 py-2 border rounded-md bg-gray-100" readonly>
            </div>

            <div>
                <label class="block mb-1 font-medium">Existencia (actual)</label>
                <input type="number" id="mostrarExistencia" class="w-full px-4 py-2 border rounded-md bg-gray-100" readonly>
            </div>

            <!-- Campos editables -->
            <div>
                <label class="block mb-1 font-medium" for="editarCodigo">Código (nuevo)</label>
                <input type="text" id="editarCodigo" class="w-full px-4 py-2 border rounded-md" readonly>
            </div>

            <div>
                <label class="block mb-1 font-medium" for="editarNombre">Nombre (nuevo)</label>
                <input type="text" id="editarNombre" class="w-full px-4 py-2 border rounded-md">
            </div>

            <div>
                <label class="block mb-1 font-medium" for="editarExistencia">Existencia (nueva)</label>
                <input type="number" id="editarExistencia" class="w-full px-4 py-2 border rounded-md" min="0">
            </div>

            <!-- Comentarios / Razones -->
            <div>
                <label class="block mb-1 font-medium" for="editarComentarios">Comentarios / Razones</label>
                <textarea id="editarComentarios" class="w-full px-4 py-2 border rounded-md" rows="3"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="button" onclick="guardarEdicion()" class="px-4 py-2 bg-blue-500 text-white rounded-md">Guardar</button>
            </div>
        </form>
    </div>


</div>


