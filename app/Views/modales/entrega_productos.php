<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>

<!-- Pantalla: Entrega de Material -->
<div id="entrega-material-content" class="p-6">
    <div class="flex justify-between mb-4">
        <h2 class="text-lg font-semibold">Entrega de Material</h2>
        <div></div>
    </div>

    <div class="p-6">
        <!-- Persona que entrega -->
        <h3 class="text-md font-medium mb-2">Persona que entrega</h3>
        <div class="flex justify-between gap-4 mb-6">
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                <input id="entrega-fecha" type="date" class="w-full px-3 py-2 border rounded" name="fecha" readonly>
            </div>
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                <input id="entrega-usuario" type="text" class="w-full px-3 py-2 border rounded" name="usuario" readonly>
            </div>
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                <input id="entrega-departamento" type="text" class="w-full px-3 py-2 border rounded" name="departamento" readonly>
            </div>
        </div>

        <!-- Persona que recibe -->
        <h3 class="text-md font-medium mb-2">Persona que recibe</h3>

        <div class="mb-4">
            <label for="entrega-departamento-receptor" class="text-sm text-gray-700 font-medium">Departamento</label>
            <select id="entrega-departamento-receptor" class="w-full px-3 py-2 border rounded">
                <option value="">Seleccione un departamento</option>
                <!-- Opciones cargadas desde backend -->
            </select>
        </div>

        <div class="mb-6">
            <label for="entrega-nombre-receptor" class="text-sm text-gray-700 font-medium">Nombre de la persona que recibe</label>
            <input id="entrega-nombre-receptor" type="text" class="w-full px-3 py-2 border rounded" placeholder="Nombre completo">
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
                <tr>
                    <td colspan="5" class="py-2 px-4 text-center text-gray-500">
                        No hay materiales seleccionados.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Botones inferiores -->
        <div class="flex justify-between">
            <!-- Izquierda -->
            <button id="btn-buscar-materiales"
                    class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    onclick="mostrarBuscarMateriales()">
                Buscar materiales
            </button>

            <!-- Derecha -->
            <button id="btn-entregar-materiales"
                    class="mb-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                Entregar materiales
            </button>
        </div>
    </div>
</div>

<!-- Pantalla: Buscar Materiales -->
<div id="buscar-materiales-content" class="hidden p-6">
    <div class="flex items-center mb-4">
        <button id="btn-regresar-buscar"
                class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                onclick="regresarBuscarMateriales()">
            ← Regresar
        </button>
    </div>

    <h3 class="text-lg font-bold mb-4">Buscar materiales</h3>

    <!-- Barra de búsqueda -->
    <div id="div-busqueda" class="mb-4">
        <input
                type="text"
                id="buscarMaterial"
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
                <th class="py-2 px-4 text-center">Seleccionar</th>
            </tr>
            </thead>
            <tbody id="tablaBuscarMateriales">
            <?php if (!empty($productos)): ?>
                <?php foreach ($productos as $p): ?>
                    <tr id="fila-producto-<?= $p['ID_Producto'] ?>">
                        <td class="py-2 px-4"><?= esc($p['Codigo']) ?></td>
                        <td class="py-2 px-4"><?= esc($p['Nombre']) ?></td>
                        <td class="py-2 px-4"><?= esc($p['Existencia']) ?></td>
                        <td class="py-2 px-4 text-center">
                            <button type="button" onclick="toggleSeleccionProducto(<?= $p['ID_Producto'] ?>)">
                                <svg class="size-6" fill="none" stroke-width="1.5" stroke="green">
                                    <use xlink:href="<?= $iconUrl ?>#agregar-fila"></use>
                                </svg>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="py-2 px-4 text-center text-gray-500">
                        No hay productos registrados.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div id="paginacion-buscar-materiales" class="flex justify-center mt-4 space-x-2"></div>
    </div>

    <!-- Botón agregar productos -->
    <div class="mt-4 text-right">
        <button id="btn-agregar-seleccionados"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                onclick="agregarProductosSeleccionados()"
                disabled>
            Agregar 0 productos
        </button>
    </div>
</div>
