<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <!-- CRUD Usuarios -->
    <button onclick="abrirModal('crud_usuarios')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#usuarios"></use>
        </svg>
        <span>Administrar Usuarios</span>
    </button>

    <!-- Limpiar Almacenamiento -->
    <button onclick="abrirModal('limpiar_almacenamiento')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#limpiar_almacenamiento"></use>
        </svg>
        <span>Limpiar Almacenamiento</span>
    </button>

    <!-- CRUD Proveedores -->
    <button onclick="abrirModal('crud_proveedores')"
            class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="/icons/icons.svg#crud_proveedores"></use>
        </svg>
        <span>Proveedores</span>
    </button>
</div>
