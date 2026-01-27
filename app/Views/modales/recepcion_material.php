<div id="recepcion-material-modal" class="p-6 bg-gray-50" x-data="RecepcionMateriales()" x-init="init()" x-cloak>

    <div class="flex items-center justify-between mb-6 border-b border-gray-200 pb-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Recepción Manual de Mercancía</h2>
            <p class="text-sm text-gray-500">Capture los datos de la factura y los productos recibidos.</p>
        </div>
        <button onclick="abrirModal('almacen')" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 bg-white px-3 py-1 rounded border shadow-sm">
            &larr; Regresar al Almacén
        </button>
    </div>

    <form @submit.prevent="guardarIngreso" class="space-y-6">

        <div class="bg-white p-5 rounded-lg shadow-sm border border-blue-100">
            <h3 class="text-sm font-bold text-blue-800 mb-4 uppercase tracking-wide border-b pb-1">1. Datos del Documento (Factura/Remisión)</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Proveedor *</label>
                    <select x-model="form.id_proveedor" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Seleccione Proveedor --</option>
                        <template x-for="prov in listaProveedores" :key="prov.ID_Proveedor">
                            <option :value="prov.ID_Proveedor" x-text="prov.RazonSocial"></option>
                        </template>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Empresa Receptora (RFC) *</label>
                    <select x-model="form.rfc_receptor" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="">-- Seleccione Su Empresa --</option>
                        <template x-for="empresa in listaReceptores" :key="empresa.RFC">
                            <option :value="empresa.RFC" x-text="empresa.Nombre"></option>
                        </template>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Folio Fiscal (UUID) o Referencia *</label>
                    <input type="text" x-model="form.uuid" placeholder="Ej. A1B2-C3D4..." class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm font-mono">
                    <p class="text-xs text-gray-400 mt-1">Si no tiene factura, escriba una referencia interna.</p>
                </div>

                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Fecha de Emisión *</label>
                    <input type="datetime-local" x-model="form.fecha_emision" class="w-full border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 relative">
            <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide border-b pb-1">2. Detalle de Productos</h3>

            <div class="flex gap-4 items-end mb-4">
                <div class="flex-1 relative">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Buscar Producto Existente</label>
                    <input type="text"
                           x-model="terminoBusqueda"
                           placeholder="Escriba nombre o código..."
                           class="w-full border-gray-300 rounded pl-10 focus:ring-green-500 focus:border-green-500"
                           autocomplete="off">
                    <div class="absolute left-3 top-8 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>

                    <div x-show="terminoBusqueda.length > 0"
                         class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded shadow-xl max-h-60 overflow-y-auto"
                         style="display: none;">

                        <template x-for="prod in productosFiltrados" :key="prod.ID_Producto">
                            <div @click="agregarProductoALista(prod)"
                                 class="px-4 py-3 hover:bg-green-50 cursor-pointer border-b flex justify-between items-center group">
                                <div>
                                    <p class="font-bold text-gray-800 text-sm" x-text="prod.Nombre"></p>
                                    <p class="text-xs text-gray-500">COD: <span x-text="prod.Codigo"></span></p>
                                </div>
                                <span class="text-green-600 text-xs font-bold uppercase">+ Agregar</span>
                            </div>
                        </template>

                        <div class="p-3 bg-blue-50 border-t border-blue-100 text-center cursor-pointer hover:bg-blue-100 transition"
                             @click="abrirModalCrear()">
                            <p class="text-sm text-blue-800 font-bold">¿No encuentra el producto?</p>
                            <p class="text-xs text-blue-600">+ Click aquí para registrar nuevo producto</p>
                        </div>
                    </div>
                </div>

                <button type="button" @click="abrirModalCrear()" class="bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 px-4 py-2 rounded shadow-sm text-sm font-medium h-10">
                    + Nuevo Producto
                </button>
            </div>

            <table class="min-w-full divide-y divide-gray-200 border">
                <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Descripción</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase w-32">Cantidad</th>
                    <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase w-24">Acción</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <template x-for="(item, index) in itemsIngreso" :key="item.id_producto">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="text-sm font-medium text-gray-900" x-text="item.nombre"></div>
                            <div class="text-xs text-gray-500" x-text="item.codigo"></div>
                            <span x-show="item.esNuevo" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                  Nuevo
                                </span>
                        </td>
                        <td class="px-4 py-2">
                            <input type="number" x-model="item.cantidad" min="1.00" step="1.00" class="w-full text-center border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                        </td>
                        <td class="px-4 py-2 text-center">
                            <button type="button" @click="eliminarFila(index)" class="text-red-500 hover:text-red-700 font-bold">&times;</button>
                        </td>
                    </tr>
                </template>
                <tr x-show="itemsIngreso.length === 0">
                    <td colspan="3" class="px-6 py-8 text-center text-gray-400 text-sm bg-gray-50">
                        No hay productos en la lista. Use el buscador arriba.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit"
                    :disabled="cargando || itemsIngreso.length === 0"
                    class="flex items-center justify-center w-full md:w-auto px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!cargando">✅ Confirmar Entrada al Inventario</span>
                <span x-show="cargando">Guardando...</span>
            </button>
        </div>
    </form>


    <div x-show="modalCrearAbierto"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="cerrarModalCrear()"></div>

        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-md w-full p-6 relative">

                <h3 class="text-lg font-bold text-gray-900 mb-4">Registrar Nuevo Producto</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre del Producto *</label>
                        <input type="text" x-model="nuevoProducto.nombre" placeholder="Ej. Martillo Industrial" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-2">El código interno se generará automáticamente basado en el ID.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="cerrarModalCrear()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="guardarProductoNuevo()" :disabled="creandoProducto" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                        <span x-text="creandoProducto ? 'Generando...' : 'Crear y Agregar'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div x-show="notificacion.show"
         class="fixed bottom-5 right-5 px-6 py-4 rounded shadow-lg text-white z-50"
         :class="notificacion.tipo === 'error' ? 'bg-red-600' : (notificacion.tipo === 'success' ? 'bg-green-600' : 'bg-blue-600')"
         x-transition.duration.300ms
         style="display: none;">
        <p x-text="notificacion.mensaje"></p>
    </div>

</div>