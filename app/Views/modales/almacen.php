<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <!-- Recepción de Material -->
    <button onclick="abrirModal('recepcion_material')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#registrar_productos"></use>
        </svg>
        <span>Recepción de Material</span>
    </button>

    <!-- Inventario -->
    <button onclick="abrirModal('crud_productos')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#crud_productos"></use>
        </svg>
        <span>Inventario</span>
    </button>

    <!-- Entrega de Material (salidas) -->
    <button onclick="abrirModal('entrega_productos')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#entrega_productos"></use>
        </svg>
        <span>Salidas de Almacen</span>
    </button>

    <!-- Reporte de almacen -->
    <button onclick="abrirModal('reporte_almacen')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#reportes"></use>
        </svg>
        <span>Reporte/Historial de Almacen</span>
    </button>

    <!-- Bajas por destrucción -->
    <button onclick="abrirModal('bajas_destruccion')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#bajas"></use>
        </svg>
        <span>Bajas por Destrucción</span>
    </button>

</div>
