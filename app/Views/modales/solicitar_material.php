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
        <!-- Razón social -->
        <div>
            <label class="text-sm text-gray-700 font-medium">Razón social:</label>
            <select class="w-full px-3 py-2 border rounded" name="razon_social" required>
                <option value="">Seleccione una opción</option>
                <?php foreach ($departamentos as $depto): ?>
                    <option value="<?= esc($depto['ID_Dpto']) ?>">
                        <?= esc($depto['Nombre']) ?>
                    </option>
                <?php endforeach; ?>
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
                <tr>
                    <?= $this->include('layout/productTable') ?>
                    <?= $this->renderSection('ProductTable') ?>
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
                <button id="agregar-fila" type="button" class="flex items-center gap-2 px-3 py-2 text-green-600 rounded" title="Agregar fila">
                    <!-- SVG + -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </button>
            </div>
        </div>


        <!-- Referencia o cotización -->
        <div class="mt-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
            <input type="file" name="archivo" class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2" accept="image/jpg,image/jpeg,image/png,application/pdf">
        </div>
        <!-- Contenedor para mensajes s-->
        <div id="mensajes-form" class="my-2"></div>
        <!-- Botón para enviar -->
        <div class="flex justify-end">
            <button id="btn-enviar" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                <!-- SVG enviar -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span>Enviar</span>
            </button>
        </div>
    </form>
</div>
