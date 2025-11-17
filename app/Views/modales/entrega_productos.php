<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";

// Preparar los productos para pasarlos a JS de forma segura
$productos_json = empty($productos)
    ? '[]'
    : htmlspecialchars(
        json_encode(
            array_map(function ($p) {
                // Asegurarse de que los campos numéricos sean números
                $p['ID_Producto'] = (int) $p['ID_Producto'];
                $p['Existencia'] = (int) $p['Existencia'];
                return $p;
            }, $productos),
        ),
    );
?>

<div x-data="Almacen(<?= $productos_json ?>)">
    <!-- Pantalla: Entrega de Material -->
    <div x-show="!mostrarBusqueda" id="entrega-material-content" class="p-6">
        <div class="flex justify-between items-center mb-4">
            <button onclick="abrirModal('almacen')" class="text-sm text-gray-600 hover:text-gray-900 transition">
                &larr; Regresar a Almacen
            </button>
        </div>

        <div class="flex justify-center">
            <h2 class="text-2xl font-bold">Entrega de productos/materiales</h2>
        </div>

        <div class="p-6">
            <!-- Persona que entrega -->
            <h3 class="text-md font-medium mb-2">Persona que entrega</h3>
            <div class="flex justify-between gap-4 mb-4">
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                    <input type="date" class="w-full px-3 py-2 border rounded" name="fecha" x-ref="a-date" value="<?= date(
                        'Y-m-d',
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="usuario"
                        value="<?= esc($nombre_usuario) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="departamento"
                        value="<?= esc($departamento_usuario) ?>" readonly>
                </div>
            </div>

            <div class="mb-6">
                <label for="entrega-nombre-emisor" class="text-sm text-gray-700 font-medium">Nombre de la persona que
                    entrega</label>
                <input id="entrega-nombre-emisor" type="text" class="w-full px-3 py-2 border rounded" x-ref="a-user-t"
                    placeholder="Nombre completo">
            </div>


            <!-- Persona que recibe -->
            <h3 class="text-md font-medium mb-2">Persona que recibe</h3>

            <div class="mb-4">
                <label class="text-sm text-gray-700 font-medium">Departamento Receptor:</label>
                <select id="entrega-departamento-receptor" name="departamento_receptor" x-ref="a-departament-r"
                    class="w-full px-3 py-2 border rounded bg-white">
                    <option value="">Seleccione un departamento</option>
                    <?php if (!empty($departamentos)): ?>
                    <?php foreach ($departamentos as $depto): ?>
                    <option value="<?= $depto['ID_Dpto'] ?>">
                        <?= esc($depto['Nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-6">
                <label for="entrega-nombre-receptor" class="text-sm text-gray-700 font-medium">Nombre de la persona que
                    recibe</label>
                <input id="entrega-nombre-receptor" type="text" class="w-full px-3 py-2 border rounded" x-ref="a-user-r"
                    placeholder="Nombre completo">
            </div>

            <!-- Tabla de materiales seleccionados -->
            <div class="overflow-x-auto mb-6">
                <table class="min-w-full border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-4 text-left">Código</th>
                            <th class="py-2 px-4 text-left">Nombre</th>
                            <th class="py-2 px-4 text-left">Cantidad a entregar</th>
                            <th class="py-2 px-4 text-left">Existencia actual</th>
                            <th class="py-2 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaEntregaMateriales">
                        <template x-if="productosParaEntregar.length === 0">
                            <tr>
                                <td colspan="5" class="py-2 px-4 text-center text-gray-500">
                                    No hay materiales seleccionados.
                                </td>
                            </tr>
                        </template>
                        <template x-for="producto in productosParaEntregar" :key="producto.ID_Producto">
                            <tr :id="'entrega-' + producto.ID_Producto">
                                <td class="py-2 px-4" x-text="producto.Codigo"></td>
                                <td class="py-2 px-4" x-text="producto.Nombre"></td>
                                <td class="py-2 px-4">
                                    <input type="number" class="w-full px-2 py-1 border rounded" min="1"
                                        :max="producto.Existencia" x-model.number="producto.cantidadAEntregar">
                                </td>
                                <td class="py-2 px-4" x-text="producto.Existencia"></td>
                                <td class="py-2 px-4 text-center">
                                    <button type="button" @click="eliminarFilaEntrega(producto.ID_Producto)"
                                        class="text-red-600 hover:text-red-800">
                                        <svg fill="none" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                                            <use xlink:href="/icons/icons.svg#eliminar-fila"></use>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Botones inferiores -->
            <div class="flex justify-between">
                <!-- Izquierda -->
                <button id="btn-buscar-materiales"
                    class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    @click="mostrarBuscarMateriales()">
                    Buscar materiales
                </button>

                <!-- Derecha -->
                <button id="btn-entregar-materiales" @click="entregarMateriales()"
                    class="mb-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                    Entregar materiales
                </button>
            </div>
        </div>
    </div>

    <!-- Pantalla: Buscar Materiales -->
    <div x-show="mostrarBusqueda" id="buscar-materiales-content" class="p-6" style="display: none;">
        <div class="flex items-center mb-4">
            <button id="btn-regresar-buscar"
                class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                @click="regresarBuscarMateriales()">
                ← Regresar
            </button>
        </div>

        <h3 class="text-lg font-bold mb-4">Buscar materiales</h3>

        <!-- Barra de búsqueda -->
        <div id="div-busqueda" class="mb-4">
            <input type="text" id="buscarMaterial" placeholder="Buscar por código o nombre..."
                class="w-full px-4 py-2 border rounded-md" x-model="terminoBusqueda">
        </div>

        <!-- Tabla de productos -->
        <div id="div-tabla" class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">Código</th>
                        <th class="py-2 px-4 text-left">Nombre</th>
                        <th class="py-2 px-4 text-left">Existencia</th>
                        <th class="py-2 px-4 text-center">Seleccionar</th>
                    </tr>
                </thead>
                <tbody id="tablaBuscarMateriales">
                    <template x-if="productosFiltrados.length === 0">
                        <tr>
                            <td colspan="4" class="py-2 px-4 text-center text-gray-500">
                                No se encontraron productos.
                            </td>
                        </tr>
                    </template>
                    <template x-for="producto in paginatedProducts" :key="producto.ID_Producto">
                        <tr :class="{'bg-green-100': productosSeleccionados.has(producto.ID_Producto)}">
                            <td class="py-2 px-4" x-text="producto.Codigo"></td>
                            <td class="py-2 px-4" x-text="producto.Nombre"></td>
                            <td class="py-2 px-4" x-text="producto.Existencia"></td>
                            <td class="py-2 px-4 text-center">
                                <button type="button" @click="toggleSeleccionProducto(producto.ID_Producto)">
                                    <svg class="size-6" fill="none" stroke-width="1.5" stroke="green">
                                        <use xlink:href="<?= $iconUrl ?>#agregar-fila"></use>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div x-show="totalPages > 1" class="flex justify-between items-center mt-4">
            <button @click="prevPage()" :disabled="currentPage === 1"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
            </button>
            <span x-text="`Página ${currentPage} de ${totalPages}`" class="text-sm text-gray-600"></span>
            <button @click="nextPage()" :disabled="currentPage === totalPages"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>

        <!-- Botón agregar productos -->
        <div class="mt-4 text-right">
            <button id="btn-agregar-seleccionados"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                @click="agregarProductosSeleccionados()" :disabled="totalSeleccionados === 0">
                Agregar <span x-text="totalSeleccionados"></span> productos
            </button>
        </div>
    </div>
</div>