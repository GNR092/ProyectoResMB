              <?= $this->section('ProductTable') ?>
               <td class="numero-fila border px-3 py-1 text-center"></td>
                <td class="border px-3 py-1">
                    <input type="text" name="codigo[]" class="w-full border rounded px-2 py-1 codigo" placeholder="Código" required></td>
                <td class="border px-3 py-1">
                    <input type="text" name="producto[]" class="w-full border rounded px-2 py-1" placeholder="Producto"></td>
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
                <?= $this->endSection() ?>
