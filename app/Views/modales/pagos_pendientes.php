<div x-data="Object.assign(FichasPago(), { screen: 'menu' })" x-init="init()" @reload-pagos-fichas.window="init()">
    <!-- Pantalla principal -->
    <div x-show="screen === 'menu'" id="pagos-menu" class="p-6">
        <div class="flex flex-col sm:flex-row gap-2">
            <button @click="screen = 'contado'"
                    class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition ">
                <p class="font-medium">Pagos Realizados de contado</p>
                <p id="count-contado-fichas" class="text-xs opacity-75" x-text="countContado + ' pendientes'"></p>
            </button>

            <button @click="screen = 'credito'"
                    class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <p class="font-medium">Pagos Realizados a crédito</p>
                <p id="count-credito-fichas" class="text-xs opacity-75" x-text="countCredito + ' pendientes'"></p>
            </button>
        </div>
    </div>

    <!-- ================== Pago de contado ================== -->
    <div x-show="screen === 'contado'" id="pago-contado" class="p-6">

        <!-- Tabla de solicitudes de contado -->
        <div id="tabla-contado" class="overflow-x-auto">

            <div class="flex justify-between items-center mb-4">
                <button @click="screen = 'menu'" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
                <h2 class="text-lg font-semibold">Pago de contado</h2>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="flex-grow">
                    <input type="text" x-model="filtros.contado.search" placeholder="Buscar por Folio o Proveedor..."
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.contado.depto" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Deptos</option>
                        <template x-for="d in opcionesFiltro.deptos" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.contado.complejo" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Complejos</option>
                        <template x-for="c in opcionesFiltro.complejos" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
            </div>

            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800 font-bold">
                <tr>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b">Fecha de pago</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-contado">
                    <template x-for="f in getFichas('0')" :key="f.ID_Solicitud">
                        <tr class="hover:bg-gray-50 transition border-b">
                            <td class="px-4 py-2" x-text="f.DepartamentoNombre"></td>
                            <td class="px-4 py-2" x-text="f.Complejo"></td>
                            <td class="px-4 py-2 font-mono text-blue-600 font-bold" x-text="f.No_Folio"></td>
                            <td class="px-4 py-2" x-text="f.RazonSocial"></td>
                            <td class="px-4 py-2 text-gray-500" x-text="f.Banco || '-'"></td>
                            <td class="px-4 py-2 font-semibold" x-text="formatCurrency(f.Total)"></td>
                            <td class="px-4 py-2" x-text="formatFecha(f.Fecha_Comprobante)"></td>
                            <td class="px-4 py-2 text-center">
                                <button @click="mostrarDetalleFicha(f.ID_Solicitud, '0')"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-xs uppercase">
                                    VER
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="getFichas('0').length === 0">
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500" x-text="loading ? 'Cargando datos...' : 'No hay registros disponibles.'"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="detalle-contado" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
    </div>

    <!-- ================== Pago a crédito ================== -->
    <div x-show="screen === 'credito'" id="pago-credito" class="p-6">

        <!-- Tabla de solicitudes a crédito -->
        <div id="tabla-credito" class="overflow-x-auto">

            <div class="flex justify-between items-center mb-4">
                <button @click="screen = 'menu'" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
                <h2 class="text-lg font-semibold">Pago a crédito</h2>
                <div></div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="flex-grow">
                    <input type="text" x-model="filtros.credito.search" placeholder="Buscar por Folio o Proveedor..."
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.credito.depto" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Deptos</option>
                        <template x-for="d in opcionesFiltro.deptos" :key="d">
                            <option :value="d" x-text="d"></option>
                        </template>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <select x-model="filtros.credito.complejo" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Todos los Complejos</option>
                        <template x-for="c in opcionesFiltro.complejos" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>
            </div>

            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800 font-bold">
                <tr>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b">Fecha de pago</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-credito">
                    <template x-for="f in getFichas('1')" :key="f.ID_Solicitud">
                        <tr class="transition border-b">
                            <td class="px-4 py-2" x-text="f.DepartamentoNombre"></td>
                            <td class="px-4 py-2" x-text="f.Complejo"></td>
                            <td class="px-4 py-2 font-mono font-bold" x-text="f.No_Folio"></td>
                            <td class="px-4 py-2" x-text="f.RazonSocial"></td>
                            <td class="px-4 py-2" x-text="f.Banco || '-'"></td>
                            <td class="px-4 py-2 font-bold" x-text="formatCurrency(f.Total)"></td>
                            <td class="px-4 py-2" x-text="formatFecha(f.Fecha_Comprobante)"></td>
                            <td class="px-4 py-2 text-center">
                                <button @click="mostrarDetalleFicha(f.ID_Solicitud, '1')"
                                        class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-xs uppercase">
                                    VER
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="getFichas('1').length === 0">
                        <td colspan="8" class="px-4 py-3 text-center text-gray-500" x-text="loading ? 'Cargando datos...' : 'No hay registros disponibles.'"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="detalle-credito" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
    </div>
</div>
<script src="<?= base_url() ?>js/pago.js?v=<?= filemtime(FCPATH . 'js/pago.js') ?>"></script>
