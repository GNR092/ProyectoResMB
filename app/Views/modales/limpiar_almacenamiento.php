<div class="flex items-center mb-4">
    <button onclick="abrirModal('ajustes')"
            class="text-sm text-gray-600 hover:text-gray-900 transition">
        &larr; Regresar a Ajustes
    </button>
</div>

<div class="p-6 bg-gray-50 min-h-screen">

    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestor de Almacenamiento</h1>
            <p class="text-gray-500 text-sm">Administra los archivos en <code class="bg-gray-200 px-1 rounded">writable/uploads</code></p>
        </div>
    </div>

    <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200 mb-4 flex items-center text-sm text-gray-600 overflow-x-auto">
        <span class="font-semibold text-gray-400 mr-2">Ruta actual:</span>
        <div id="breadcrumbs" class="flex items-center space-x-1">
        </div>
    </div>

    <div id="back-button-container" class="mb-4 hidden">
        <button onclick="navegarArriba()" class="flex items-center text-sm text-blue-600 hover:text-blue-800 transition font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
            </svg>
            Subir un nivel
        </button>
    </div>

    <div id="toolbar" class="bg-blue-50 p-3 rounded-lg border border-blue-100 mb-4 hidden flex justify-between items-center transition-all">
        <span class="text-blue-800 font-medium text-sm">
            <span id="selected-count">0</span> elementos seleccionados
        </span>
        <div class="flex space-x-3">
            <button onclick="limpiarSeleccion()" class="flex items-center px-3 py-1.5 bg-white text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Borrar seleccion
            </button>

            <button onclick="ejecutarAccion('comprimir')" class="flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Comprimir
            </button>
            <button onclick="ejecutarAccion('eliminar')" class="flex items-center px-3 py-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Eliminar
            </button>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left w-10">
                    <input type="checkbox" id="select-all" onclick="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tamaño</th>
            </tr>
            </thead>
            <tbody id="file-list" class="bg-white divide-y divide-gray-200">
            <tr>
                <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                    Cargando archivos...
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>