<div x-data="Pagos()" x-init="cargardatos()">
    <!-- Pantalla principal -->
    <div x-show="screen === 'menu'" class="p-6">
        <div class="inline-flex">
            <button @click="screen = 'contado'" class="w-50 h-50 px-4 py-3 m-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <p>Pagos de contado</p>
            </button>
            <button @click="screen = 'credito'" class="w-50 h-50 px-4 py-3 m-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <p>Pagos a crédito</p>
            </button>
        </div>
    </div>

    <!-- ================== Pagos de contado ================== -->
    <div x-show="screen === 'contado'" class="p-6">
        <div class="flex justify-between items-center mb-4">
            <button @click="screen = 'menu'" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
            <button @click="programarPago()" :disabled="selectedOrdenes.length === 0" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400">Programar pago</button>
        </div>
        <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
            <thead class="bg-gray-100 text-gray-800">
                <tr>
                    <th class="px-4 py-2 border-b"><input type="checkbox" @click="toggleSelectAll($event, 'contado')"> Seleccionar todo</th>
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
                <template x-for="orden in ordenesContado" :key="orden.ID_Solicitud">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 border-b"><input type="checkbox" :value="orden.ID_Solicitud" x-model="selectedOrdenes"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.DepartamentoNombre || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.Complejo || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.No_Folio || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.proveedor?.RazonSocial || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.proveedor?.Banco || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="formatCurrency(orden.cotizacion?.Total)"></td>
                        <td class="px-4 py-2 border-b text-center">
                            <button @click="mostrarDetalle(orden.ID_Solicitud, orden.MetodoPago)" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">VER</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- ================== Pagos a crédito ================== -->
    <div x-show="screen === 'credito'" class="p-6">
        <div class="flex justify-between items-center mb-4">
            <button @click="screen = 'menu'" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
            <button @click="programarPago()" :disabled="selectedOrdenes.length === 0" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-400">Programar pago</button>
        </div>
        <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
            <thead class="bg-gray-100 text-gray-800">
                <tr>
                    <th class="px-4 py-2 border-b"><input type="checkbox" @click="toggleSelectAll($event, 'credito')"> Seleccionar todo</th>
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
                <template x-for="orden in ordenesCredito" :key="orden.ID_Solicitud">
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-2 border-b"><input type="checkbox" :value="orden.ID_Solicitud" x-model="selectedOrdenes"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.DepartamentoNombre || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.Complejo || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.No_Folio || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.proveedor?.RazonSocial || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="orden.proveedor?.Banco || '-'"></td>
                        <td class="px-4 py-2 border-b" x-text="formatCurrency(orden.cotizacion?.Total)"></td>
                        <td class="px-4 py-2 border-b text-center">
                            <button @click="mostrarDetalle(orden.ID_Solicitud, orden.MetodoPago)" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">VER</button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- ================== Detalle de Orden ================== -->
    <div x-show="screen === 'detalle'" class="p-6">
        <div x-show="loadingDetalle" class="text-center text-gray-500">Cargando detalles...</div>
        <div x-show="!loadingDetalle && detalleOrden">
            <div class="flex justify-between items-center mb-4">
                <button @click="volverATabla()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
                <h2 class="text-lg font-semibold">Detalle Orden #<span x-text="detalleOrden.No_Folio || detalleOrden.ID_Solicitud"></span></h2>
                <div></div>
            </div>
            <!-- Aquí iría el contenido del detalle, similar a mostrarDetalleFicha -->
            <div x-html="generarDetalleHtml()"></div>
        </div>
    </div>
</div>
<script src="public/js/pago.js"></script>