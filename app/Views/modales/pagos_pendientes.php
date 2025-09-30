<!-- Pantalla principal -->
<div id="pagos-menu" class="p-6">
    <h2 class="text-lg font-semibold mb-4">Pagos Pendientes</h2>

    <div class="grid gap-4">
        <button onclick="mostrarPagoContado()"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Pago de contado
        </button>

        <button onclick="mostrarPagoCredito()"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Pago a crédito
        </button>
    </div>
</div>

<!-- Pantalla Pago de contado -->
<div id="pago-contado" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarPagosMenu()"
                class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Pago de contado</h2>
        <div></div>
    </div>
    <p class="text-gray-700">Aquí irá el contenido para pagos de contado.</p>
</div>

<!-- Pantalla Pago a crédito -->
<div id="pago-credito" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarPagosMenu()"
                class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Pago a crédito</h2>
        <div></div>
    </div>
    <p class="text-gray-700">Aquí irá el contenido para pagos a crédito.</p>
</div>
