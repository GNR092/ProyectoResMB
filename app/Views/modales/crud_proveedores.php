<!-- Pantalla 1: lista de proveedores -->
<div id="pantalla-lista-proveedores" class="p-6 bg-white rounded-xl shadow-md">

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('catalogos')"
                class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar a Catálogos
        </button>
    </div>

    <h2 class="text-2xl font-semibold mb-4 text-center">Directorio de Proveedores</h2>


    <!-- Buscadores y botón AGREGAR -->
    <form id="form-filtros-proveedores" class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <div class="flex flex-1 gap-4">
            <label for="buscar-nombre" class="sr-only">Buscar por nombre</label>
            <input type="text" id="buscar-nombre" name="buscar_nombre" placeholder="Buscar por nombre..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
            <label for="buscar-servicio" class="sr-only">Buscar por servicio</label>
            <input type="text" id="buscar-servicio" name="buscar_servicio" placeholder="Buscar por servicio..." class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-ring-blue-300">
        </div>
        <div>
            <a href="#" id="btn-agregar-proveedor" class="inline-block mt-4 px-4 py-2 bg-green-500 text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors">
                AGREGAR
            </a>
        </div>
    </form>

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead class="bg-gray-100">
            <tr>
                <th class="w-1/6 px-3 py-2 border-b text-left">Razón Social</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">RFC</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Banco</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Teléfono</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Servicio</th>
                <th class="w-1/6 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-proveedores">
            <noscript>
                <tr>
                    <td colspan="6" class="px-3 py-4 text-center text-red-500">
                        Se requiere JavaScript para cargar la tabla de proveedores.
                    </td>
                </tr>
            </noscript>
            <!-- Las filas se cargan vía API: GET api/providers/paginated (createPaginatedTableServer) -->
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="paginacion-proveedores" class="flex flex-wrap justify-center mt-4 gap-2"></div>
</div>

<!-- Pantalla 2: agregar proveedor -->
<div id="pantalla-agregar-proveedor" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Agregar Proveedor</h2>

    <form id="form-agregar-proveedor" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col">
                <label for="RazonSocial" class="mb-1 font-medium">Razón Social</label>
                <input type="text" name="RazonSocial" id="RazonSocial" placeholder="Razón Social" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="correo" class="mb-1 font-medium">correo</label>
                <input type="text" name="correo" id="correo" placeholder="example@example.com" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="RFC" class="mb-1 font-medium">RFC</label>
                <input type="text" name="RFC" id="RFC" placeholder="RFC" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="Banco" class="mb-1 font-medium">Banco</label>
                <input type="text" name="Banco" id="Banco" placeholder="Banco" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="Cuenta" class="mb-1 font-medium">Cuenta</label>
                <input type="text" name="Cuenta" id="Cuenta" placeholder="Cuenta" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="Clabe" class="mb-1 font-medium">Clabe</label>
                <input type="text" name="Clabe" id="Clabe" placeholder="Clabe" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="Tel_Contacto" class="mb-1 font-medium">Teléfono de contacto</label>
                <input type="text" name="Tel_Contacto" id="Tel_Contacto" placeholder="Teléfono de contacto" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="Nombre_Contacto" class="mb-1 font-medium">Nombre del contacto</label>
                <input type="text" name="Nombre_Contacto" id="Nombre_Contacto" placeholder="Nombre del contacto" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="Servicio" class="mb-1 font-medium">Servicio</label>
                <input type="text" name="Servicio" id="Servicio" placeholder="Servicio" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="archivos_proveedor" class="mb-1 font-medium">Adjuntar Archivos</label>
                <input type="file" name="archivos_proveedor[]" id="archivos_proveedor" multiple accept=".pdf,.docx,.xml,image/*" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>

        <div x-data="{ tiene_credito: false }" class="space-y-4 border-t pt-4">
            <div class="flex items-center">
                <input type="checkbox" name="tiene_credito" id="tiene_credito" x-model="tiene_credito" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="tiene_credito" class="ml-2 block text-sm text-gray-900">¿Tiene crédito?</label>
            </div>

            <div x-show="tiene_credito" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label for="dias_credito" class="mb-1 font-medium">Días de crédito</label>
                    <input type="number" name="dias_credito" id="dias_credito" placeholder="0" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex flex-col">
                    <label for="monto_credito" class="mb-1 font-medium">Monto de crédito</label>
                    <input type="number" step="0.01" name="monto_credito" id="monto_credito" placeholder="0.00" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <button type="submit" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg shadow hover:bg-green-700 transition">Guardar Proveedor</button>
    </form>

    <div id="msg-agregar-proveedor" class="mt-4 text-center"></div>
</div>

<!-- Pantalla 3: editar proveedor -->
<div id="pantalla-editar-proveedor" class="hidden p-6 bg-white rounded-xl shadow-md">
    <button id="btn-regresar-lista-editar" class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">← Regresar</button>
    <h2 class="text-2xl font-semibold mb-4 text-center">Editar Proveedor</h2>

    <form id="form-editar-proveedor" class="space-y-4">
        <input type="hidden" name="ID_Proveedor" id="editar-ID_Proveedor">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="flex flex-col">
                <label for="editar-RazonSocial" class="mb-1 font-medium">Razón Social</label>
                <input type="text" name="RazonSocial" id="editar-RazonSocial" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-correo" class="mb-1 font-medium">Correo</label>
                <input type="email" name="correo" id="editar-correo" placeholder="example@example.com" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-RFC" class="mb-1 font-medium">RFC</label>
                <input type="text" name="RFC" id="editar-RFC" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-Banco" class="mb-1 font-medium">Banco</label>
                <input type="text" name="Banco" id="editar-Banco" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-Cuenta" class="mb-1 font-medium">Cuenta</label>
                <input type="text" name="Cuenta" id="editar-Cuenta" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-Clabe" class="mb-1 font-medium">Clabe</label>
                <input type="text" name="Clabe" id="editar-Clabe" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-Tel_Contacto" class="mb-1 font-medium">Teléfono de contacto</label>
                <input type="text" name="Tel_Contacto" id="editar-Tel_Contacto" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-Nombre_Contacto" class="mb-1 font-medium">Nombre del contacto</label>
                <input type="text" name="Nombre_Contacto" id="editar-Nombre_Contacto" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-Servicio" class="mb-1 font-medium">Servicio</label>
                <input type="text" name="Servicio" id="editar-Servicio" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex flex-col">
                <label for="editar-archivos_proveedor" class="mb-1 font-medium">Adjuntar más Archivos</label>
                <input type="file" name="archivos_proveedor[]" id="editar-archivos_proveedor" multiple accept=".pdf,.docx,.xml,image/*" class="w-full px-3 py-2 border rounded-lg">
            </div>
        </div>

        <!-- Lista de archivos existentes -->
        <div id="contenedor-archivos-existentes" class="hidden space-y-2 border-t pt-4">
            <h3 class="font-medium text-gray-700">Archivos Adjuntos</h3>
            <div id="lista-archivos-proveedor" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Se llenará vía JS -->
            </div>
        </div>

        <div id="editar-credito-section" x-data="{ tiene_credito: false }" class="space-y-4 border-t pt-4">
            <div class="flex items-center">
                <input type="checkbox" name="tiene_credito" id="editar-tiene_credito" x-model="tiene_credito" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="editar-tiene_credito" class="ml-2 block text-sm text-gray-900">¿Tiene crédito?</label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label for="editar-dias_credito" class="mb-1 font-medium">Días de crédito</label>
                    <input type="number" name="dias_credito" id="editar-dias_credito" placeholder="0" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex flex-col">
                    <label for="editar-monto_credito" class="mb-1 font-medium">Monto de crédito</label>
                    <input type="number" step="0.01" name="monto_credito" id="editar-monto_credito" placeholder="0.00" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <button type="submit" class="px-6 py-2 bg-yellow-500 text-black font-semibold rounded-lg shadow hover:bg-yellow-600 transition">Guardar Cambios</button>
    </form>

    <div id="msg-editar-proveedor" class="mt-4 text-center"></div>
</div>
