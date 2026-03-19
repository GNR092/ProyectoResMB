<div id="lista-pagos-modal" x-data="ListaPagos()" x-init="init()" @reload-pagos.window="init()" class="p-4">

    <!-- ================== CONTENEDOR 1: LISTA  ================== -->
    <div id="div-lista-pagos">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">Lista de Pagos</h2>
            <div class="flex items-center space-x-4">
                <div>
                    <label for="filtro-metodo" class="text-sm font-medium text-gray-700">Filtrar por:</label>
                    <select id="filtro-metodo" x-model="filtroMetodoPago" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="todos">Todos</option>
                        <option value="0">Contado</option>
                        <option value="1">Crédito</option>
                    </select>
                </div>
                <button @click="exportarExcel()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition self-end">Exportar a Excel</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left">Folio Solicitud</th>
                    <th class="py-3 px-6 text-left">Fecha Aprobación</th>
                    <th class="py-3 px-6 text-left">Razón Social</th>

                    <th class="py-3 px-6 text-left">Departamento</th>

                    <th class="py-3 px-6 text-left">Proveedor</th>
                    <th class="py-3 px-6 text-right">Total a Pagar</th>
                    <th class="py-3 px-6 text-left">Método de Pago</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="tablaListaPagos">
                <tr x-show="loading">
                    <td colspan="9" class="text-center py-4">Cargando pagos...</td>
                </tr>
                <tr x-show="!loading && pagosFiltrados.length === 0">
                    <td colspan="9" class="text-center py-4 text-gray-500">No hay pagos programados que coincidan con el filtro.</td>
                </tr>
                <template x-for="(pago, idx) in paginatedPagos" :key="`pago-${idx}`">
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-t" x-text="pago.No_Folio"></td>
                        <td class="py-2 px-4 border-t" x-text="formatDate(pago.FechaOrden)"></td>
                        <td class="py-2 px-4 border-t" x-text="pago.RazonSocial"></td>

                        <td class="py-2 px-4 border-t" x-text="pago.Departamento"></td>

                        <td class="py-2 px-4 border-t" x-text="pago.Proveedor"></td>
                        <td class="py-2 px-4 border-t text-right" x-text="formatCurrency(pago.Total)"></td>
                        <td class="py-2 px-4 border-t" x-text="pago.MetodoPago == '0' ? 'Contado' : 'Crédito'"></td>
                        <td class="py-2 px-4 border-t">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800" x-text="pago.Estado"></span>
                        </td>
                        <td class="py-2 px-4 border-t text-center">
                            <button @click="mostrarDetallePago(pago.ID_Solicitud)" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                Ver
                            </button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        <!-- Paginación Estilo Google -->
        <div id="paginacion-lista-pagos" class="flex justify-center items-center mt-4" x-show="totalPages > 1">
            <div class="flex items-center gap-1">
                <template x-for="item in pageNumbers" :key="item.value || item.type">
                    <button x-show="item.type === 'first'" @click="firstPage()"
                        :disabled="currentPage === 1"
                        class="px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Primera página">
                        &laquo;
                    </button>
                    <button x-show="item.type === 'prev'" @click="prevPage()"
                        :disabled="currentPage === 1"
                        class="px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Página anterior">
                        &lsaquo;
                    </button>
                    <span x-show="item.type === '...'" class="px-2 text-gray-400 cursor-default">...</span>
                    <button x-show="item.type === 'number'" @click="goToPage(item.value)"
                        :class="item.active ? 'bg-blue-500 text-white' : 'bg-white text-black hover:bg-gray-100'"
                        class="px-3 py-1 border rounded">
                        <span x-text="item.value"></span>
                    </button>
                    <button x-show="item.type === 'next'" @click="nextPage()"
                        :disabled="currentPage === totalPages"
                        class="px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Página siguiente">
                        &rsaquo;
                    </button>
                    <button x-show="item.type === 'last'" @click="lastPage()"
                        :disabled="currentPage === totalPages"
                        class="px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Última página">
                        &raquo;
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ================== CONTENEDOR 2: DETALLES (Nuevo, Oculto) ================== -->
    <div id="div-detalle-pago" class="hidden">
        <div class="flex justify-between items-center mb-4">
            <button onclick="regresarListaPagos()" class="text-sm text-gray-600 hover:text-gray-900 transition">&larr; Regresar a la lista</button>
            <h2 class="text-lg font-bold">Detalle del Pago</h2>
            <div></div>
        </div>

        <!-- Aquí se cargará el contenido dinámico -->
        <div id="contenido-detalle-pago" class="p-4 border rounded-lg bg-gray-50">
            <p class="text-center text-gray-500">Cargando detalles...</p>
        </div>
    </div>

</div>
<script src="<?= base_url() ?>js/pago.js?v=<?= filemtime(FCPATH . 'js/pago.js') ?>"></script>