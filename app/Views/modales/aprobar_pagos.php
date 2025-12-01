<!-- Pantalla principal -->
<div id="pago-menu" class="p-6">
    <h2 class="text-lg font-semibold mb-4">Aprobar Pagos</h2>

    <div class="grid gap-4">
        <button onclick="mostrarAprobarPagoContado()"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Pagos de contado
        </button>

        <button onclick="mostrarAprobarPagoCredito()"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Pagos a crédito
        </button>
    </div>
</div>

<!-- ================== Pagos de contado ================== -->
<div id="pago-contado" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarAprobarPagoMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Pagos a crédito</h2>
    </div>
</div>


<!-- ================== Pagos a crédito ================== -->
<div id="pago-credito" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarAprobarPagoMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Pagos a crédito</h2>
    </div>
</div>