<div id="lista-pagos-modal" x-data="ListaPagos()" x-init="init()" class="p-4">
    <h2 class="text-lg font-bold mb-4">Lista de Pagos</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300">
            <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left">Folio Solicitud</th>
                    <th class="py-3 px-6 text-left">Proveedor</th>
                    <th class="py-3 px-6 text-right">Total a Pagar</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaListaPagos">
                <template x-if="loading">
                    <tr>
                        <td colspan="5" class="text-center py-4">Cargando pagos...</td>
                    </tr>
                </template>
                <template x-if="!loading && pagos.length === 0">
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-500">No hay pagos programados.</td>
                    </tr>
                </template>
                <template x-for="pago in pagos" :key="pago.ID_Solicitud">
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-t" x-text="pago.No_Folio"></td>
                        <td class="py-2 px-4 border-t" x-text="pago.Proveedor"></td>
                        <td class="py-2 px-4 border-t text-right" x-text="formatCurrency(pago.Total)"></td>
                        <td class="py-2 px-4 border-t">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800" x-text="pago.Estado">
                            </span>
                        </td>
                        <td class="py-2 px-4 border-t text-center">
                            <button @click="abrirModal('detalles_pago', { id: pago.ID_Solicitud })" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                Ver
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div id="paginacion-lista-pagos" class="flex justify-center mt-4 space-x-2"></div>
</div>
