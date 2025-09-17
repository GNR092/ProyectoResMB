<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<!-- Pantalla 1: Selección -->
<div id="seleccion-opcion" class="p-6">
    <h2 class="text-lg font-semibold mb-4">Elija una opción</h2>
    <div class="flex flex-col gap-4">
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-blue-600"
            onclick="mostrarSubmenuMaterial()">
            Solicitar Material
        </div>
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-blue-600"
            onclick="mostrarSolicitarServicio()">
            Solicitar Servicio
        </div>
    </div>
</div>

<!-- Pantalla 1.5: Submenú de Material -->
<div id="submenu-material" class="hidden p-6">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSeleccionOpciones()">
            &larr; Regresar
        </button>
        <h2 class="text-lg font-semibold">Seleccione tipo de material</h2>
        <div></div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-green-600"
            onclick="mostrarSolicitarMaterialCotizado()">
            Material Cotizado
        </div>
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-green-600"
            onclick="mostrarSolicitarMaterialSinCotizar()">
            Material sin Cotizar
        </div>
    </div>
</div>

<!-- Pantalla 2: Solicitar Material -->
<div id="solicitar-material-content" class="hidden">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSubmenuMaterial()">&larr;
            Regresar</button>
        <h2 class="text-lg font-semibold">Solicitud de Material</h2>
        <div></div>
    </div>

    <div class="p-6">
        <!-- Formulario -->
        <form id="form-upload" class="space-y-4" enctype="multipart/form-data">
            <!-- Encabezado -->
            <div class="flex justify-between gap-4">
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                    <input type="date" class="w-full px-3 py-2 border rounded" name="fecha" value="<?= date(
                        'Y-m-d',
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="usuario" value="<?= esc(
                        $nombre_usuario,
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="departamento" value="<?= esc(
                        $departamento_usuario,
                    ) ?>" readonly>
                </div>
            </div>
            <!-- Proveedores -->
            <div>
                <label class="text-sm text-gray-700 font-medium">Proveedor:</label>
                <select id="ProvSelect" class="w-full px-3 py-2 border rounded" name="razon_social" required>
                    <option value="">Seleccione una opción</option>
                </select>
            </div>

            <div class="overflow-auto">
                <!-- Tabla de productos -->
                <table class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 border">No.</th>
                            <th class="px-3 py-2 border">Código</th>
                            <th class="px-3 py-2 border">Producto</th>
                            <th class="px-3 py-2 border">Cantidad</th>
                            <th class="px-3 py-2 border">Importe</th>
                            <th class="px-3 py-2 border">Costo</th>
                            <th class="px-3 py-2 border text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productos">
                        <tr class="fila-producto">
                            <?= $this->include('layout/productTable') ?>
                        </tr>
                    </tbody>
                </table>
                <!-- Total costo -->
                <!-- Subtotal -->
                <div class="w-full overflow-auto mt-3">
                    <table class="w-full text-sm border border-gray-300">
                        <tbody>
                            <tr class="bg-gray-200 text-gray-700 font-semibold">
                                <td class="px-3 py-2 border text-left" style="width: 70%;">
                                    Subtotal:
                                </td>
                                <td id="subtotal-costo" class="px-3 py-2 border text-right" style="width: 30%;">
                                    $0.00
                                </td>
                            </tr>
                            <!-- Total con IVA opcional -->
                            <tr class="bg-gray-100 text-gray-700 font-semibold">
                                <td class="px-3 py-2 border text-left">
                                    <div class="flex items-center gap-2">
                                        Total:
                                        <label class="flex items-center gap-1 text-sm font-normal">
                                            <input type="checkbox" id="agregar-iva" class="accent-blue-600" name="iva">
                                            Agregar IVA
                                        </label>
                                    </div>
                                </td>
                                <td id="total-costo" class="px-3 py-2 border text-right">
                                    $0.00
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                <!-- Botón para agregar fila -->
                <div class="flex justify-end mt-2">
                    <button id="agregar-fila" type="button"
                        class="flex items-center gap-2 px-3 py-2 text-green-600 rounded" title="Agregar fila">
                        <!-- SVG + -->
                        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
                            <use xlink:href="<?= $iconUrl ?>#agregar-fila"></use>
                        </svg>
                    </button>
                </div>
            </div>


            <!-- Referencia o cotización -->
            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
                <input type="file" name="archivo"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2"
                    accept="image/jpg,image/jpeg,image/png,application/pdf">
            </div>
            <!-- Contenedor para mensajes -->
            <div class="my-2 form-message-container"></div>
            <!-- Botón para enviar -->
            <div class="flex justify-end">
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    <!-- SVG enviar -->
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Enviar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Pantalla 4: Material sin Cotizar -->
<div id="solicitar-material-sin-cotizar" class="hidden p-6">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSubmenuMaterial()">
            &larr; Regresar
        </button>
        <h2 class="text-lg font-semibold">Solicitud de Material (sin cotizar)</h2>
        <div></div>
    </div>

    <div class="p-6">
        <!-- Formulario -->
        <form id="form-upload-sin-cotizar" class="space-y-4" enctype="multipart/form-data">
            <!-- Encabezado -->
            <div class="flex justify-between gap-4">
                <input type="checkbox" name="sin_cotizar" class="hidden accent-blue-600" checked>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                    <input type="date" class="w-full px-3 py-2 border rounded" name="fecha" value="<?= date(
                        'Y-m-d',
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="usuario" value="<?= esc(
                        $nombre_usuario,
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="departamento" value="<?= esc(
                        $departamento_usuario,
                    ) ?>" readonly>
                </div>
            </div>
            <!-- Proveedores -->
            <div>
                <label class="text-sm text-gray-700 font-medium">Proveedor:</label>
                <select id="ProvSelectSinCotizar" class="w-full px-3 py-2 border rounded" name="razon_social">
                    <option value="">Seleccione una opción</option>
                </select>
            </div>

            <!-- Tabla de productos -->
            <div class="overflow-auto">
                <table class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 border w-12">No.</th>
                            <th class="px-3 py-2 border w-3/4">Producto</th>
                            <th class="px-3 py-2 border w-32">Cantidad</th>
                            <th class="px-3 py-2 border text-center w-24">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productos-sin-cotizar">
                        <tr class="fila-producto">
                            <td class="numero-fila px-3 py-2 border text-center">1</td>
                            <td class="px-3 py-2 border">
                                <input type="text" name="producto[]" class="w-full px-2 py-1 border rounded"
                                    placeholder="Nombre del producto">
                            </td>
                            <td class="px-3 py-2 border">
                                <input type="number" name="cantidad[]" class="w-full px-2 py-1 border rounded cantidad"
                                    min="1" value="1">
                            </td>
                            <td class="px-3 py-2 border text-center">
                                <!-- botón eliminar con el SVG correcto -->
                                <button type="button" class="eliminar-fila text-red-600 hover:text-red-800"
                                    title="Eliminar fila">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>                                    
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <!-- Botón para agregar fila -->
                <div class="flex justify-end mt-2">
                    <button id="agregar-fila-sin-cotizar" type="button"
                        class="flex items-center gap-2 px-3 py-2 text-green-600 rounded" title="Agregar fila">
                        <!-- SVG + -->
                        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
                            <use xlink:href="<?= $iconUrl ?>#agregar-fila"></use>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Referencia o cotización (opcional) -->
            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
                <input type="file" name="archivo"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2"
                    accept="image/jpg,image/jpeg,image/png,application/pdf">
            </div>

            <!-- Contenedor para mensajes -->
            <div class="my-2 form-message-container"></div>

            <!-- Botón enviar -->
            <div class="flex justify-end">
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Enviar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Pantalla 3: Solicitud de servicios -->
<div id="solicitar-servicio-content" class="hidden">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSeleccionOpciones()">&larr;
            Regresar</button>
        <h2 class="text-lg font-semibold">Solicitud de Servicio</h2>
        <div></div>
    </div>

    <div class="p-6">
        <!-- Formulario -->
        <form id="form-servicio-upload" class="space-y-4" enctype="multipart/form-data">
            <!-- Encabezado -->
            <div class="flex justify-between gap-4">
                <input type="checkbox" name="servicio" class="hidden accent-blue-600" checked>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                    <input type="date" class="w-full px-3 py-2 border rounded" name="fecha" value="<?= date(
                        'Y-m-d',
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="usuario" value="<?= esc(
                        $nombre_usuario,
                    ) ?>" readonly>
                </div>
                <div class="w-1/3">
                    <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                    <input type="text" class="w-full px-3 py-2 border rounded" name="departamento" value="<?= esc(
                        $departamento_usuario,
                    ) ?>" readonly>
                </div>
            </div>

            <!-- Razón social -->
            <div>
                <label class="text-sm text-gray-700 font-medium">Razón social:</label>
                <select id="razonSocialServicioSelect" class="w-full px-3 py-2 border rounded" name="razon_social"
                    required>
                    <option value="">Seleccione una opción</option>
                </select>
            </div>


            <div class="overflow-auto">
                <!-- Tabla de servicios -->
                <table class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 border">No.</th>
                            <th class="px-3 py-2 border">Nombre</th>
                            <th class="px-3 py-2 border">Costo</th>
                            <th class="px-3 py-2 border text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-servicios">
                        <tr class="fila-producto">
                            <td class="numero-fila px-3 py-2 border text-center">1</td>
                            <td class="px-3 py-2 border">
                                <input type="text" name="servicio[]" class="w-full px-2 py-1 border rounded" placeholder="Descripción del servicio">
                            </td>
                            <td class="px-3 py-2 border">
                                <input type="number" name="importe[]" class="w-full px-2 py-1 border rounded importe" min="0" step="0.01" placeholder="0.00">
                            </td>
                            <td class="px-3 py-2 border text-center">
                                <button type="button" class="eliminar-fila text-red-600 hover:text-red-800"
                                    title="Eliminar fila">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Subtotal -->
                <div class="w-full overflow-auto mt-3">
                    <table class="w-full text-sm border border-gray-300">
                        <tbody>
                            <tr class="bg-gray-200 text-gray-700 font-semibold">
                                <td class="px-3 py-2 border text-left" style="width: 70%;">
                                    Subtotal:
                                </td>
                                <td id="subtotal-servicio" class="px-3 py-2 border text-right" style="width: 30%;">
                                    $0.00
                                </td>
                            </tr>
                            <tr class="bg-gray-100 text-gray-700 font-semibold">
                                <td class="px-3 py-2 border text-left">
                                    <div class="flex items-center gap-2">
                                        Total:
                                        <label class="flex items-center gap-1 text-sm font-normal">
                                            <input type="checkbox" id="agregar-iva-servicio" class="accent-blue-600"
                                                name="iva">
                                            Agregar IVA
                                        </label>
                                    </div>
                                </td>
                                <td id="total-servicio" class="px-3 py-2 border text-right">
                                    $0.00
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Botón para agregar fila -->
                <div class="flex justify-end mt-2">
                    <button id="agregar-fila-servicio" type="button"
                        class="flex items-center gap-2 px-3 py-2 text-green-600 rounded" title="Agregar fila">
                        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
                            <use xlink:href="<?= $iconUrl ?>#agregar-fila"></use>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Referencia o cotización -->
            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
                <input type="file" name="archivo"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2"
                    accept="image/jpg,image/jpeg,image/png,application/pdf">
            </div>

            <!-- Contenedor para mensajes -->
            <div class="my-2 form-message-container"></div>

            <!-- Botón para enviar -->
            <div class="flex justify-end">
                <button type="submit" id="btn-enviar-servicio"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    <!-- SVG enviar -->
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Enviar</span>
                </button>
            </div>
        </form>
    </div>
</div>