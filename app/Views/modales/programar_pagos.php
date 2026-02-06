<div x-data="Pagos()" x-init="cargardatos()">
    <div x-show="screen === 'menu'" class="p-6">
        <div class="flex flex-col sm:flex-row gap-2">
            <button @click="screen = 'contado'" class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <p>Pagos de contado</p>
                <p class="text-xs opacity-75" x-show="ordenesContado.length > 0" x-text="`${ordenesContado.length} pendientes`"></p>
            </button>
            <button @click="screen = 'credito'" class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <p>Pagos a crédito</p>
                <p class="text-xs opacity-75" x-show="ordenesCredito.length > 0" x-text="`${ordenesCredito.length} pendientes`"></p>
            </button>
        </div>
    </div>

    <div x-show="screen === 'contado'" class="p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
            <button @click="screen = 'menu'" class="text-sm text-gray-600 hover:text-gray-900 font-medium">
                &larr; Regresar al menú
            </button>

            <div class="flex flex-col items-end gap-1 self-end sm:self-center">
                <button @click="programarPago()"
                        :disabled="selectedOrdenes.length === 0"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed shadow-sm">
                    Programar pago (<span x-text="selectedOrdenes.length"></span>)
                </button>

                <div x-show="selectedOrdenes.length > 0" x-transition class="text-sm font-semibold text-gray-700 text-right">
                    Costo Total a programar: <span class="text-green-600" x-text="formatCurrency(totalSeleccionado)"></span>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800">
                <tr>
                    <th class="px-4 py-3 border-b">
                        <input type="checkbox"
                               @click="toggleSelectAll($event, 'contado')"
                               :checked="isPageSelected('contado')">
                    </th>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>

                <tbody id="body-contado">
                <template x-if="loading">
                    <tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
                </template>
                <template x-if="!loading && ordenesContado.length === 0">
                    <tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros de contado.</td></tr>
                </template>

                <template x-for="orden in paginatedContado" :key="orden.ID_Solicitud">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 border-b">
                            <input type="checkbox" :value="orden.ID_Solicitud" x-model="selectedOrdenes">
                        </td>
                        <td class="px-4 py-2 border-b" x-text="orden.DepartamentoNombre || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.Complejo || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.No_Folio || '-'"></td>

                        <td class="px-4 py-2 border-b" x-text="orden.RazonSocial || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.Banco || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="formatCurrency(orden.Total)"></td>

                        <td class="px-4 py-2 border-b text-center">
                            <button @click="mostrarDetalle(orden.ID_Solicitud, orden.MetodoPago)"
                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                VER
                            </button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center mt-4 px-2" x-show="ordenesContado.length > itemsPerPage">
            <span class="text-sm text-gray-600">
                Página <span x-text="pageContado"></span> de <span x-text="totalPagesContado"></span>
            </span>
            <div class="flex gap-2">
                <button @click="changePage('contado', 'prev')" :disabled="pageContado === 1" class="px-3 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">Anterior</button>
                <button @click="changePage('contado', 'next')" :disabled="pageContado === totalPagesContado" class="px-3 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">Siguiente</button>
            </div>
        </div>
    </div>

    <div x-show="screen === 'credito'" class="p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-4">
            <button @click="screen = 'menu'" class="text-sm text-gray-600 hover:text-gray-900 font-medium">
                &larr; Regresar al menú
            </button>

            <div class="flex flex-col items-end gap-1 self-end sm:self-center">
                <button @click="programarPago()"
                        :disabled="selectedOrdenes.length === 0"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed shadow-sm">
                    Programar pago (<span x-text="selectedOrdenes.length"></span>)
                </button>

                <div x-show="selectedOrdenes.length > 0" x-transition class="text-sm font-semibold text-gray-700 text-right">
                    Costo Total a programar: <span class="text-green-600" x-text="formatCurrency(totalSeleccionado)"></span>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800">
                <tr>
                    <th class="px-4 py-3 border-b">
                        <input type="checkbox"
                               @click="toggleSelectAll($event, 'credito')"
                               :checked="isPageSelected('credito')">
                    </th>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-credito">
                <template x-if="loading">
                    <tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
                </template>
                <template x-if="!loading && ordenesCredito.length === 0">
                    <tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros a crédito.</td></tr>
                </template>

                <template x-for="orden in paginatedCredito" :key="orden.ID_Solicitud">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 border-b">
                            <input type="checkbox" :value="orden.ID_Solicitud" x-model="selectedOrdenes">
                        </td>
                        <td class="px-4 py-2 border-b" x-text="orden.DepartamentoNombre || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.Complejo || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.No_Folio || '-'"></td>

                        <td class="px-4 py-2 border-b" x-text="orden.RazonSocial || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.Banco || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="formatCurrency(orden.Total)"></td>

                        <td class="px-4 py-2 border-b text-center">
                            <button @click="mostrarDetalle(orden.ID_Solicitud, orden.MetodoPago)"
                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                                VER
                            </button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>

        <div class="flex justify-between items-center mt-4 px-2" x-show="ordenesCredito.length > itemsPerPage">
            <span class="text-sm text-gray-600">
                Página <span x-text="pageCredito"></span> de <span x-text="totalPagesCredito"></span>
            </span>
            <div class="flex gap-2">
                <button @click="changePage('credito', 'prev')" :disabled="pageCredito === 1" class="px-3 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">Anterior</button>
                <button @click="changePage('credito', 'next')" :disabled="pageCredito === totalPagesCredito" class="px-3 py-1 border rounded bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">Siguiente</button>
            </div>
        </div>
    </div>

    <div x-show="screen === 'detalle'" class="p-6">
        <div x-show="loadingDetalle" class="text-center text-gray-500">Cargando detalles...</div>
        <div x-show="!loadingDetalle && detalleOrden">
            <div class="flex justify-between items-center mb-4">
                <button id="btn-volver-pagos" @click="volverATabla()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
                <h2 class="text-lg font-semibold">Detalle Orden #<span x-text="detalleOrden ? (detalleOrden.No_Folio || detalleOrden.ID_Solicitud) : ''"></span></h2>
                <div></div>
            </div>
            <div x-html="generarDetalleHtml()"></div>
        </div>
    </div>
</div>
<script src="public/js/pago.js"></script>