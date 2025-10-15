<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <!-- Registrar Productos -->
    <button onclick="abrirModal('registrar_productos')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#registrar_productos"></use>
        </svg>
        <span>Registrar Productos</span>
    </button>

    <!-- Existencias -->
    <button onclick="abrirModal('crud_productos')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#crud_productos"></use>
        </svg>
        <span>Existencias</span>
    </button>

    <!-- Entrega de Material -->
    <button onclick="abrirModal('entrega_productos')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#entrega_productos"></use>
        </svg>
        <span>Entrega de Material</span>
    </button>
</div>
