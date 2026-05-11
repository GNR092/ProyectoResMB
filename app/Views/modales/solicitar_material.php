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
            Requisicion de Material
        </div>
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-blue-600"
            onclick="mostrarSolicitarServicio()">
            Requisicion de Servicio
        </div>
    </div>
</div>

<!-- Pantalla 1.5: Submenú de Material -->
<div id="submenu-material" class="hidden p-6">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSeleccionOpciones()">
            &larr; Regresar
        </button>
        <h2 class="text-lg font-semibold">Seleccione tipo de requisicion</h2>
        <div></div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-green-600"
            onclick="mostrarSolicitarMaterialCotizado()">
            Requisicion con cotizacion
        </div>
        <div class="cursor-pointer p-4 border rounded hover:bg-gray-100 text-green-600"
            onclick="mostrarSolicitarMaterialSinCotizar()">
            Requisicion sin cotizacion
        </div>
    </div>
</div>

<!-- Pantalla 2: Solicitar Material -->
<div id="solicitar-material-content" class="hidden p-6">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSubmenuMaterial()">&larr;
            Regresar
        </button>
        <h2 class="text-lg font-semibold">Requisicion de Material</h2>
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

            <!-- Razon social -->
            <div class="mb-4">
                <label for="razonSocialMaterial" class="block text-sm font-medium text-gray-700">
                    Razón Social
                </label>
                <select name="razon_social" id="razonSocialMaterial"
                        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                    <option value="">Seleccione una razón social</option>
                    <?php foreach ($razones_sociales as $razon): ?>
                        <option value="<?= esc($razon['ID_RazonSocial']) ?>">
                            <?= esc($razon['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Selector de Place (Global) -->
            <div class="mb-4" id="contenedor-place-material">
                <label for="placeMaterial" class="block text-sm font-medium text-gray-700">
                    Complejo / Condominio
                </label>
                <select name="id_place" id="placeMaterial"
                        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                    <option value="">Seleccione un complejo</option>
                </select>
            </div>


            <!-- Proveedores -->
            <div>
                <label class="text-sm text-gray-700 font-medium">Proveedor:</label>
                <select id="ProvSelect" class="w-full px-3 py-2 border rounded" name="ID_Proveedor" required>
                    <option value="">Seleccione una opción</option>
                </select>
            </div>

            <div class="overflow-visible">
                <!-- Tabla de productos -->
                <table class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 border">No.</th>
                            <th class="px-3 py-2 border">Código o SKU</th>
                            <th class="px-3 py-2 border">Producto</th>
                            <th class="px-3 py-2 border">Cantidad</th>
                            <th class="px-3 py-2 border">Importe</th>
                            <th class="px-3 py-2 border">Costo</th>
                            <th class="px-3 py-2 border text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productos">
                    <?= $this->include('layout/productTable') ?>
                    </tbody>
                </table>
                <!-- Total costo -->
                <!-- Subtotal -->
                <div class="w-full overflow-auto mt-3">
                    <table class="w-full text-sm border border-gray-300">
                        <tbody>
                            <tr class="bg-gray-200 text-gray-700 font-semibold">
                                <td class="px-3 py-2 border text-left" style="width: 70%;">
                                    Total:
                                </td>
                                <td id="subtotal-costo" class="px-3 py-2 border text-right" style="width: 30%;">
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

            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <textarea name="comentariosuser" rows="3" class="w-full border-gray-300 border-2 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>


            <!-- Referencia o cotización -->
            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
                <p class="text-orange-500 text-xs mt-1">Evite caracteres especiales en el nombre  (como `;`, `&`, `#`)  para prevenir errores.</p>
                <input type="file" name="archivo[]"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2"
                    accept="image/jpg,image/jpeg,image/png,application/pdf" multiple>
            </div>
            <!-- Contenedor para mensajes -->
            <div class="my-2 form-message-container"></div>
            <!-- Botón para enviar -->
            <div class="flex justify-end gap-2">
                <button type="button" id="btn-enviar-direccion-material"
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Enviar a Dirección</span>
                </button>
                <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    <!-- SVG enviar -->
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Solicitar</span>
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
        <h2 class="text-lg font-semibold">Requisicion de Material (sin cotizar)</h2>
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

            <!-- Razon social -->
            <div class="mb-4">
                <label for="razonSocialSinCotizar" class="block text-sm font-medium text-gray-700">
                    Razón Social
                </label>
                <select name="razon_social" id="razonSocialSinCotizar"
                        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                    <option value="">Seleccione una razón social</option>
                    <?php foreach ($razones_sociales as $razon): ?>
                        <option value="<?= esc($razon['ID_RazonSocial']) ?>">
                            <?= esc($razon['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Selector de Place (Global) -->
            <div class="mb-4" id="contenedor-place-sincotizar">
                <label for="placeSinCotizar" class="block text-sm font-medium text-gray-700">
                    Complejo / Condominio
                </label>
                <select name="id_place" id="placeSinCotizar"
                        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                    <option value="">Seleccione un complejo</option>
                </select>
            </div>

            <!-- Proveedores -->
            <div class="hidden">
                <label class="text-sm text-gray-700 font-medium">Proveedor:</label>
                <select id="ProvSelectSinCotizar" class="w-full px-3 py-2 border rounded" name="ID_Proveedor">
                    <option value="">Seleccione una opción</option>
                </select>
            </div>

            <!-- Tabla de productos -->
            <div class="overflow-visible">
                <table class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="px-3 py-2 border w-12">No.</th>
                            <th class="px-3 py-2 border w-1/2">Producto</th>
                            <th class="px-3 py-2 border w-32">Cantidad</th>
                            <th class="px-3 py-2 border text-center w-24">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-productos-sin-cotizar">
                        <tr class="fila-producto">
                            <td class="numero-fila px-3 py-2 border text-center w-12">1</td>
                            <td class="px-3 py-2 border relative w-1/2">
                                <input type="hidden" name="id_grupo_presupuestal[]" class="id_grupo_presupuestal">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-grow">
                                        <input type="hidden" name="producto[]" class="id-producto-val">
                                        <input type="text" class="w-full border rounded px-2 py-1 search-producto-input bg-gray-50 cursor-pointer" 
                                               placeholder="Elegir material..." autocomplete="off" readonly required>
                                        
                                        <!-- Dropdown de resultados -->
                                        <div class="absolute left-0 right-0 z-[100] mt-1 bg-white border rounded shadow-xl hidden container-resultados-busqueda max-h-60 overflow-y-auto">
                                            <!-- Resultados dinámicos -->
                                        </div>
                                    </div>
                                    <button type="button" class="btn-favorito text-gray-300 hover:text-yellow-500 transition-colors" title="Marcar como favorito">
                                        <svg fill="currentColor" viewBox="0 0 24 24" class="size-5">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-3 py-2 border w-32">
                                <input type="number" name="cantidad[]" class="w-full px-2 py-1 border rounded cantidad"
                                    min="1" value="1">
                            </td>
                            <td class="px-3 py-2 border text-center w-24">
                                <!-- botón eliminar con el SVG correcto -->
                                <button type="button" class="eliminar-fila text-red-600 hover:text-red-800"
                                    title="Eliminar fila">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>                                    
                                </button>
                            </td>
                        </tr>
                    </tbody>                </table>
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

            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <textarea name="comentariosuser" rows="3" class="w-full border-gray-300 border-2 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Referencia o cotización (opcional) -->
            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
                <p class="text-orange-500 text-xs mt-1">Evite caracteres especiales en el nombre  (como `;`, `&`, `#`)  para prevenir errores.</p>
                <input type="file" name="archivo[]"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2"
                    accept="image/jpg,image/jpeg,image/png,application/pdf" multiple>
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
                    <span>Solicitar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Pantalla 3: Solicitud de servicios -->
<div id="solicitar-servicio-content" class="hidden p-6">
    <div class="flex justify-between mb-4">
        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="regresarSeleccionOpciones()">&larr;
            Regresar
        </button>
        <h2 class="text-lg font-semibold">Requisicion de pago o servicio</h2>
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

            <!-- Razon social -->
            <div class="mb-4">
                <label for="razonSocialServicio" class="block text-sm font-medium text-gray-700">
                    Razón Social
                </label>
                <select name="razon_social" id="razonSocialServicio"
                        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                    <option value="">Seleccione una razón social</option>
                    <?php foreach ($razones_sociales as $razon): ?>
                        <option value="<?= esc($razon['ID_RazonSocial']) ?>">
                            <?= esc($razon['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Selector de Place (Global) -->
            <div class="mb-4" id="contenedor-place-servicio">
                <label for="placeServicio" class="block text-sm font-medium text-gray-700">
                    Complejo / Condominio
                </label>
                <select name="id_place" id="placeServicio"
                        class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        required>
                    <option value="">Seleccione un complejo</option>
                </select>
            </div>

            <!-- Proveedor Servicio -->
            <div>
                <label class="text-sm text-gray-700 font-medium">Proveedor</label>
                <select id="razonSocialServicioSelect" class="w-full px-3 py-2 border rounded" name="ID_Proveedor" required>
                    <option value="">Seleccione una opción</option>
                </select>
            </div>

            <div class="overflow-visible">
                <!-- Tabla de servicios -->
                <table class="w-full text-sm text-left border border-gray-300">
                    <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 border">No.</th>
                        <th class="px-3 py-2 border">Nombre</th>
                        <th class="px-3 py-2 border">Importe</th>
                        <th class="px-3 py-2 border text-center">Acción</th>
                    </tr>
                    </thead>

                    <tbody id="tabla-servicios">
                    <tr class="fila-servicio">
                        <td class="numero-fila-servicio px-3 py-2 border text-center w-12">1</td>
                        <td class="px-3 py-2 border relative w-1/2">
                            <input type="hidden" name="id_grupo_presupuestal[]" class="id_grupo_presupuestal">
                            <div class="flex items-center gap-2">
                                <div class="relative flex-grow">
                                    <input type="hidden" name="servicio[]" class="id-producto-val">
                                    <input type="text"
                                           class="w-full border rounded px-2 py-1 search-producto-input bg-gray-50 cursor-pointer"
                                           10 placeholder="Elegir servicio..." autocomplete="off" readonly required>

                                    <!-- Dropdown de resultados -->
                                    <div class="absolute left-0 right-0 z-[100] mt-1 bg-white border rounded shadow-xl hidden container-resultados-busqueda max-h-60 overflow-y-auto">
                                        <!-- Resultados dinámicos -->
                                    </div>
                                </div>
                                <button type="button"
                                        class="btn-favorito text-gray-300 hover:text-yellow-500 transition-colors"
                                        title="Marcar como favorito">
                                    <svg fill="currentColor" viewBox="0 0 24 24" class="size-5">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-3 py-2 border w-32">
                            <input type="number" name="importe[]" class="costo-servicio w-full px-2 py-1 border rounded"
                                   min="1" step="0.01" placeholder="0.00" required>
                        </td>
                        <td class="px-3 py-2 border text-center w-24">
                            <button type="button" class="eliminar-fila-servicio text-red-600 hover:text-red-800"
                                    29 title="Eliminar fila">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
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
                                Total:
                            </td>
                            <td id="subtotal-servicio" class="px-3 py-2 border text-right" style="width: 30%;">
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

            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <textarea name="comentariosuser" rows="3" class="w-full border-gray-300 border-2 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Referencia o cotización -->
            <div class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Factura</h2>
                <p class="text-orange-500 text-xs mt-1">Evite caracteres especiales en el nombre  (como `;`, `&`, `#`) para prevenir errores.</p>
                <input type="file" name="archivo[]"
                       class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2"
                       accept="image/jpg,image/jpeg,image/png,application/pdf,text/xml" multiple>
            </div>

            <!-- Contenedor para mensajes -->
            <div class="my-2 form-message-container"></div>

            <!-- Botón para enviar -->
            <div class="flex justify-end gap-2">
                <button type="button" id="btn-enviar-direccion-servicio"
                        class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Enviar a Dirección</span>
                </button>
                <button type="submit" id="btn-enviar-servicio"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                    <!-- SVG enviar -->
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <use xlink:href="<?= $iconUrl ?>#btn-enviar"></use>
                    </svg>
                    <span>Solicitar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Confirmación Enviar a Dirección -->
<div id="modal-confirmacion-direccion" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-[100] hidden">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4 text-red-600">Advertencia de Responsabilidad</h3>
        <p class="mb-4 text-sm text-gray-700">
            En caso de continuar, el usuario que está realizando la requisición es responsable de los precios, productos, evidencia y demás información colocada en la misma.
        </p>
        
        <form id="form-direccion-archivos" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Evidencia de Autorización Externa (Obligatorio)</label>
                <input type="file" id="archivo-evidencia" name="archivo_evidencia[]" accept="image/*,.pdf" class="mt-1 block w-full text-sm border border-gray-300 rounded-md shadow-sm" multiple required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Cotización de la Requisición (Obligatorio)</label>
                <input type="file" id="archivo-cotizacion-dir" name="archivo_cotizacion[]" accept="image/*,.pdf" class="mt-1 block w-full text-sm border border-gray-300 rounded-md shadow-sm" multiple required>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Tipo de Pago</label>
                <div class="flex items-center space-x-4 mt-1">
                    <label class="flex items-center">
                        <input type="radio" name="tipo_pago_dir" value="efectivo" checked class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Contado</span>
                    </label>
                    <label class="flex items-center" id="label-credito-dir">
                        <input type="radio" name="tipo_pago_dir" value="credito" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Crédito</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="iva-dir" name="iva_dir" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="iva-dir" class="ml-2 block text-sm text-gray-900 cursor-pointer">
                    ¿Agregar IVA a los precios?
                </label>
            </div>

            <div class="mt-6 flex justify-end space-x-4 border-t pt-4">
                <button type="button" id="btn-cancelar-direccion" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                <button type="submit" id="btn-confirmar-direccion-full" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition">Confirmar y Enviar</button>
            </div>
        </form>
    </div>
</div>

<?php if (isset($all_places) && !empty($all_places)): ?>
    <!-- Pasamos los datos de los lugares a través de un input oculto para que el JS pueda leerlo al inicializar -->
    <input type="hidden" id="ALL_PLACES_DATA_STORE" value='<?= htmlspecialchars(json_encode($all_places ?? []), ENT_QUOTES, 'UTF-8') ?>'>
<?php endif; ?>
