<!-- Pantalla principal -->
<div id="ficha-menu" class="p-6">
    <h2 class="text-lg font-semibold mb-4">Fichas de Pago</h2>

    <div class="grid gap-4">
        <button onclick="mostrarFichaContado()"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Fichas de contado
        </button>

        <button onclick="mostrarFichaCredito()"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Fichas a crédito
        </button>
    </div>
</div>

<!-- ================== Ficha de contado ================== -->
<div id="ficha-contado" class="hidden p-6">


    <!-- Tabla de fichas de contado -->
    <div id="tabla-contado" class="overflow-x-auto">

        <div class="flex justify-between items-center mb-4">
            <button onclick="regresarFichaMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
            <h2 class="text-lg font-semibold">Ficha de contado</h2>
        </div>
        <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
            <thead class="bg-gray-100 text-gray-800">
            <tr>
                <th class="px-4 py-2 border-b">No. Folio</th>
                <th class="px-4 py-2 border-b">Departamento</th>
                <th class="px-4 py-2 border-b">Complejo</th>
                <th class="px-4 py-2 border-b">Proveedor</th>
                <th class="px-4 py-2 border-b">Banco</th>
                <th class="px-4 py-2 border-b">Importe</th>
                <th class="px-4 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="body-contado">
            <tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="detalle-contado" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
</div>

<!-- ================== Ficha a crédito ================== -->
<div id="ficha-credito" class="hidden p-6">


    <!-- Tabla de fichas a crédito -->
    <div id="tabla-credito" class="overflow-x-auto">

        <div class="flex justify-between items-center mb-4">
            <button onclick="regresarFichaMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
            <h2 class="text-lg font-semibold">Ficha a crédito</h2>
        </div>

        <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
            <thead class="bg-gray-100 text-gray-800">
            <tr>
                <th class="px-4 py-2 border-b">No. Folio</th>
                <th class="px-4 py-2 border-b">Departamento</th>
                <th class="px-4 py-2 border-b">Complejo</th>
                <th class="px-4 py-2 border-b">Proveedor</th>
                <th class="px-4 py-2 border-b">Banco</th>
                <th class="px-4 py-2 border-b">Importe</th>
                <th class="px-4 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="body-credito">
            <tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="detalle-credito" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
</div>
