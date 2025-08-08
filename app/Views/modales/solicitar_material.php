<div class="p-6">
    <form class="space-y-4" action="<?= base_url('archivo/subir') ?>" method="post" enctype="multipart/form-data">

        <div class="flex justify-between gap-4">
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                <input type="date" class="w-full px-3 py-2 border rounded" value="<?= date('Y-m-d') ?>" readonly>
            </div>
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                <input type="text" class="w-full px-3 py-2 border rounded" value="Juan Pérez" readonly>
            </div>
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                <input type="text" class="w-full px-3 py-2 border rounded" value="Compras" readonly>
            </div>
        </div>

        <div>
            <label class="text-sm text-gray-700 font-medium">Razón social:</label>
            <select class="w-full px-3 py-2 border rounded" name="razon_social">
                <option value="">Seleccione una opción</option>
                <option>MB Signature</option>
                <option>MB Orlando</option>
                <option>Otros</option>
            </select>
        </div>

        <div class="overflow-auto">
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
                    <td class="numero-fila border px-3 py-1 text-center">1</td>

                    <!-- Código editable -->
                    <td class="border px-3 py-1">
                        <input type="text" name="codigo[]" class="w-full border rounded px-2 py-1 codigo" placeholder="Código">
                    </td>

                    <td class="border px-3 py-1">
                        <input type="text" name="producto[]" class="w-full border rounded px-2 py-1" placeholder="Producto">
                    </td>
                    <td class="border px-3 py-1">
                        <input type="number" name="cantidad[]" class="cantidad w-full border rounded px-2 py-1" min="1" step="1" value="1">
                    </td>
                    <td class="border px-3 py-1">
                        <input type="number" name="importe[]" class="importe w-full border rounded px-2 py-1" min="0" step="1" value="0">
                    </td>
                    <td class="costo border px-3 py-1 text-right">$0.00</td>
                    <td class="border px-3 py-1 text-center">
                        <button type="button" class="eliminar-fila text-red-600 hover:text-red-800" title="Eliminar fila">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </button>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="w-full overflow-auto mt-3">
                <table class="w-full text-sm border border-gray-300">
                    <tbody>
                    <tr class="bg-gray-200 text-gray-700 font-semibold">
                        <td class="px-3 py-2 border text-left" style="width: 70%;">
                            Total:
                        </td>
                        <td id="total-costo" class="px-3 py-2 border text-right" style="width: 30%;">
                            $0.00
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-2">
                <button id="agregar-fila" type="button" class="flex items-center gap-2 px-3 py-2 text-green-600 rounded" title="Agregar fila">
                    <!-- SVG + -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="mt-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Referencia o cotización</h2>
            <input type="file" name="archivo" class="block w-full text-sm text-gray-700 border border-gray-300 rounded px-3 py-2">
        </div>

        <?php if (session()->getFlashdata('mensaje')): ?>
            <div class="text-green-600 mt-2"><?= session()->getFlashdata('mensaje') ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="text-red-600 mt-2"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>

        <div class="flex justify-end">
            <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                <!-- SVG enviar -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <span>Enviar</span>
            </button>
        </div>
    </form>
</div>
