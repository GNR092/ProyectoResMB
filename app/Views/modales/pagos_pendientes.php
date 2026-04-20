<?php
// Clases del semáforo para el "Purge" de TailwindCSS:
// bg-gray-900 text-white hover:bg-gray-800
// bg-red-100 text-red-800 hover:bg-red-200
// bg-yellow-100 text-yellow-800 hover:bg-yellow-200
// hover:bg-gray-50
?>

<div x-data="{ screen: 'menu' }" x-init="initFichasPago()">
    <!-- Pantalla principal -->
    <div x-show="screen === 'menu'" id="pagos-menu" class="p-6">
        <div class="flex flex-col sm:flex-row gap-2">
            <button @click="screen = 'contado'"
                    class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition ">
                <p class="font-medium">Pago de contado</p>
                <p id="count-contado-fichas" class="text-xs opacity-75">Cargando...</p>
            </button>

            <button @click="screen = 'credito'"
                    class="w-full sm:w-auto flex-grow px-4 py-3 m-1 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <p class="font-medium">Pago a crédito</p>
                <p id="count-credito-fichas" class="text-xs opacity-75">Cargando...</p>
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
                    <input type="text" id="filter-search-contado" placeholder="Buscar por Folio o Proveedor..." 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                           onkeyup="filtrarFichasPago('0')">
                </div>
                <div class="w-full md:w-48">
                    <select id="filter-depto-contado" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                            onchange="filtrarFichasPago('0')">
                        <option value="">Todos los Deptos</option>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <select id="filter-complejo-contado" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                            onchange="filtrarFichasPago('0')">
                        <option value="">Todos los Complejos</option>
                    </select>
                </div>
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
                    <th class="px-4 py-2 border-b text-center">Acciones</th>
                </tr>
                </thead>
                <tbody id="body-contado">
                <!-- Colspan ajustado a 7 -->
                <tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
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
                    <input type="text" id="filter-search-credito" placeholder="Buscar por Folio o Proveedor..." 
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                           onkeyup="filtrarFichasPago('1')">
                </div>
                <div class="w-full md:w-48">
                    <select id="filter-depto-credito" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                            onchange="filtrarFichasPago('1')">
                        <option value="">Todos los Deptos</option>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <select id="filter-complejo-credito" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" 
                            onchange="filtrarFichasPago('1')">
                        <option value="">Todos los Complejos</option>
                    </select>
                </div>
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
                <tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="detalle-credito" class="hidden p-4 border border-gray-200 rounded-lg bg-gray-50"></div>
    </div>
</div>