<div id="lista-pagos-modal" x-data="ListaPagos()" x-init="init()" @reload-pagos.window="init()" class="p-4">

    <!-- ================== CONTENEDOR 1: LISTA (Tu código original envuelto) ================== -->
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
                    <th class="py-3 px-6 text-left">Proveedor</th>
                    <th class="py-3 px-6 text-right">Total a Pagar</th>
                    <th class="py-3 px-6 text-left">Método de Pago</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="tablaListaPagos">
                <template x-if="loading">
                    <tr>
                        <td colspan="6" class="text-center py-4">Cargando pagos...</td>
                    </tr>
                </template>
                <template x-if="!loading && pagosFiltrados.length === 0">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-gray-500">No hay pagos programados que coincidan con el filtro.</td>
                    </tr>
                </template>
                <template x-for="pago in pagosFiltrados" :key="pago.ID_Solicitud">
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-t" x-text="pago.No_Folio"></td>
                        <td class="py-2 px-4 border-t" x-text="pago.Proveedor"></td>
                        <td class="py-2 px-4 border-t text-right" x-text="formatCurrency(pago.Total)"></td>
                        <td class="py-2 px-4 border-t" x-text="pago.MetodoPago == '0' ? 'Contado' : 'Crédito'"></td>
                        <td class="py-2 px-4 border-t">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800" x-text="pago.Estado">
                                </span>
                        </td>
                        <td class="py-2 px-4 border-t text-center">
                            <!-- CAMBIO AQUÍ: Llamada a mostrarDetallePago en lugar de abrirModal -->
                            <button @click="mostrarDetallePago(pago.ID_Solicitud)" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
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
<!-- Asegúrate de cargar tu script original -->
<script src="public/js/pago.js"></script>