<!-- Pantalla 1: lista de cuentas -->
<div id="pantalla-lista-cuentas" class="p-6 bg-white rounded-xl shadow-md">


    <h2 class="text-2xl font-semibold mb-4 text-center">Lista de Cuentas</h2>

    <!-- Buscadores -->
    <div id="form-filtros-cuentas" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4">
            <label for="buscar-razonsocial-cuenta" class="sr-only">Buscar por Razón Social</label>
            <input type="text" id="buscar-razonsocial-cuenta" placeholder="Buscar por Razón Social..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">

            <label for="buscar-rfc-cuenta" class="sr-only">Buscar por RFC</label>
            <input type="text" id="buscar-rfc-cuenta" placeholder="Buscar por RFC..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
        </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead class="bg-gray-100">
            <tr>
                <th class="w-1/3 px-3 py-2 border-b text-left">Razón Social</th>
                <th class="w-1/3 px-3 py-2 border-b text-left">RFC</th>
                <th class="w-1/3 px-3 py-2 border-b text-left">Banco</th>
                <th class="w-1/6 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-cuentas">
            <noscript>
                <tr>
                    <td colspan="4" class="px-3 py-4 text-center text-red-500">
                        Se requiere JavaScript para cargar la tabla de cuentas.
                    </td>
                </tr>
            </noscript>
            <!-- Las filas se cargan vía API: GET api/providers/paginated (createPaginatedTableServer) -->
            </tbody>
        </table>
    </div>

    <div id="paginacion-cuentas" class="flex justify-center mt-4 space-x-2"></div>
</div>

<!-- Pantalla 2: editar cuenta -->
<div id="pantalla-editar-cuenta" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Información de la Cuenta</h2>

    <form id="form-editar-cuenta" class="space-y-6">
        <input type="hidden" name="ID_Ref" id="editar-ID_Ref">

        <!-- Datos del Proveedor (Solo lectura) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col">
                <label for="editar-RazonSocial" class="mb-1 font-medium">Razón Social</label>
                <input type="text" id="editar-RazonSocial" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
            </div>
            <div class="flex flex-col">
                <label for="editar-RFC" class="mb-1 font-medium">RFC</label>
                <input type="text" id="editar-RFC" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
            </div>
        </div>

        <hr class="border-gray-200">

        <!-- 1. VISTA TABLA -->
        <div id="vista-tabla-cuentas-detalle">
            <div class="flex justify-between items-center mb-2 border-b pb-1">
                <h3 class="text-lg font-semibold">Cuentas Bancarias Asociadas</h3>
                <button type="button" id="btn-agregar-cuenta-detalle" class="px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors text-sm">
                    AGREGAR
                </button>
            </div>

            <div class="overflow-x-auto border border-gray-300 rounded-lg">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="w-3/4 px-4 py-2 text-left text-sm font-medium text-gray-700">Cuenta</th>
                        <!-- Columna Acciones agregada -->
                        <th class="w-1/4 px-4 py-2 text-center text-sm font-medium text-gray-700">Acciones</th>
                    </tr>
                    </thead>
                    <tbody id="tabla-cuentas-detalle">
                    <!-- Colspan ajustado a 2 por la nueva columna -->
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-center text-gray-500 text-sm">Cargando...</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. VISTA FORMULARIO NUEVA CUENTA -->
        <div id="vista-form-nueva-cuenta" class="hidden p-4 border border-green-200 rounded-lg bg-green-50">
            <h3 class="text-lg font-semibold mb-4 text-green-800" id="titulo-form-cuenta">Nueva Cuenta</h3>

            <div class="flex flex-col gap-4">
                <!-- Input oculto para saber si estamos editando -->
                <input type="hidden" id="id_cuenta_edicion" name="id_cuenta_edicion">

                <div class="flex flex-col">
                    <label for="nueva-cuenta-input" class="mb-1 font-medium text-gray-700">Número de Cuenta / CLABE</label>
                    <input type="number" id="nueva-cuenta-input" name="nueva_cuenta" minlength="16" maxlength="20" placeholder="Ingrese la cuenta (16-20 dígitos)" class="w-full px-3 py-2 border rounded-lg bg-white focus:ring focus:ring-green-300 focus:outline-none">
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" id="btn-confirmar-nueva-cuenta" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                        Confirmar
                    </button>
                    <button type="button" id="btn-cancelar-nueva-cuenta" class="px-4 py-2 bg-gray-400 text-white font-semibold rounded-md hover:bg-gray-500 transition">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
