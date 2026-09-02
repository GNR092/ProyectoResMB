<div id="lista-pagos-modal" x-data="ListaPagos()" x-init="init()" @reload-pagos.window="init()" class="p-4">

    <!-- ================== CONTENEDOR 1: LISTA  ================== -->
    <div id="div-lista-pagos">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-xl font-bold text-gray-800">Lista de Pagos Programados</h2>
            <button @click="exportarExcel()" class="w-full md:w-auto px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Exportar a Excel
            </button>
        </div>

        <!-- Barra de Filtros -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Buscar</label>
                <input type="text" x-model="filtroSearch" @input="currentPage = 1" placeholder="Folio o Proveedor..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Método de Pago</label>
                <select x-model="filtroMetodoPago" @change="currentPage = 1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="todos" x-text="'Todos (' + pagos.length + ')'"></option>
                    <option value="0" x-text="'Contado (' + conteoContado + ')'"></option>
                    <option value="1" x-text="'Crédito (' + conteoCredito + ')'"></option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Departamento</label>
                <select x-model="filtroDepto" @change="currentPage = 1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todos los Deptos</option>
                    <template x-for="depto in deptosDisponibles" :key="depto">
                        <option :value="depto" x-text="depto"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Complejo / Razón Social</label>
                <select x-model="filtroRazonSocial" @change="currentPage = 1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Todos los Complejos</option>
                    <template x-for="rs in razonesDisponibles" :key="rs">
                        <option :value="rs" x-text="rs"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
            <table class="min-w-[1100px] w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Folio</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">F. Solicitud</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Fecha Aprob.</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">F. Programación</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Complejo</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Depto.</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Proveedor</th>
                    <th class="py-3 px-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Total</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Método</th>
                    <th class="py-3 px-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Estado</th>
                    <th class="py-3 px-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Acciones</th>
                </tr>
                </thead>
                <tbody id="tablaListaPagos" class="bg-white divide-y divide-gray-200">
                <tr x-show="loading">
                    <td colspan="11" class="text-center py-4">Cargando pagos...</td>
                </tr>
                <tr x-show="!loading && pagosFiltrados.length === 0">
                    <td colspan="11" class="text-center py-4 text-gray-500">
                        No hay pagos programados que coincidan con los filtros.
                    </td>
                </tr>
                <template x-for="(pago, idx) in paginatedPagos" :key="'pago-' + idx">
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="py-3 px-4 text-sm font-medium text-gray-900 whitespace-nowrap" x-text="pago.No_Folio"></td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap" x-text="formatDate(pago.FechaSolicitud)"></td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap" x-text="formatDate(pago.FechaOrden)"></td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap" x-text="formatDate(pago.FechaProgramacion)"></td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap" x-text="pago.RazonSocial"></td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap" x-text="pago.Departamento"></td>
                        <td class="py-3 px-4 text-sm text-gray-600 whitespace-nowrap" x-text="pago.Proveedor"></td>
                        <td class="py-3 px-4 text-sm font-bold text-blue-700 text-right whitespace-nowrap" x-text="formatCurrency(pago.Total)"></td>
                        <td class="py-3 px-4 text-sm">
                            <span :class="pago.MetodoPago == 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800'" 
                                  class="px-2 py-0.5 rounded text-xs font-medium" 
                                  x-text="pago.MetodoPago == 0 ? 'Contado' : 'Crédito'"></span>
                        </td>
                        <td class="py-3 px-4 text-sm">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800" x-text="pago.Estado"></span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button @click="mostrarDetallePago(pago.ID_Solicitud)" class="bg-blue-600 text-white px-4 py-1.5 rounded-lg hover:bg-blue-700 transition shadow-sm text-sm font-medium">
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
                <template x-for="(item, i) in pageNumbers" :key="i">
                    <span class="inline-flex">
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
                    </span>
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