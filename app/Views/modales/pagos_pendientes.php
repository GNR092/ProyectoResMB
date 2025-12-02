<?php
// Clases del semáforo para el "Purge" de TailwindCSS:
//
// bg-gray-900 text-white hover:bg-gray-800
// bg-red-100 text-red-800 hover:bg-red-200
// bg-yellow-100 text-yellow-800 hover:bg-yellow-200
// hover:bg-gray-50
?>

<div x-data="{ screen: 'menu' }">
    <!-- Pantalla principal -->
    <div x-show="screen === 'menu'" id="pagos-menu" class="p-6">
        <h2 class="text-lg font-semibold mb-4">Facturas Pendientes</h2>

        <div class="grid gap-4">
            <button @click="screen = 'contado'"
                    class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                Pago de contado
            </button>

            <button @click="screen = 'credito'"
                    class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                Pago a crédito
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

            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800">
                <tr>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b">Días Restantes</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-contado">
                <tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Detalle de la orden de contado (se genera dinámicamente) -->
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

            <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                <thead class="bg-gray-100 text-gray-800">
                <tr>
                    <th class="px-4 py-2 border-b">Departamento</th>
                    <th class="px-4 py-2 border-b">Complejo</th>
                    <th class="px-4 py-2 border-b">No. Folio</th>
                    <th class="px-4 py-2 border-b">Proveedor</th>
                    <th class="px-4 py-2 border-b">Banco</th>
                    <th class="px-4 py-2 border-b">Importe</th>
                    <th class="px-4 py-2 border-b">Días Restantes</th>
                    <th class="px-4 py-2 border-b text-center">Acciones</th>

                </tr>
                </thead>
                <tbody id="body-credito">
                <tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Detalle de la orden de crédito (se genera dinámicamente) -->
        <div id="detalle-credito" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
    </div>
</div>