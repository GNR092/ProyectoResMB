<!-- Pantalla: Entrega de Material -->
<div id="entrega-material-content" class="p-6">
    <div class="flex justify-between mb-4">
        <h2 class="text-lg font-semibold">Entrega de Material</h2>
        <div></div>
    </div>

    <div class="p-6">
        <!-- Persona que entrega -->
        <h3 class="text-md font-medium mb-2">Persona que entrega</h3>
        <div class="flex justify-between gap-4 mb-6">
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Fecha:</label>
                <input id="entrega-fecha" type="date" class="w-full px-3 py-2 border rounded" name="fecha" readonly>
            </div>
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Usuario:</label>
                <input id="entrega-usuario" type="text" class="w-full px-3 py-2 border rounded" name="usuario" readonly>
            </div>
            <div class="w-1/3">
                <label class="text-sm text-gray-700 font-medium">Departamento:</label>
                <input id="entrega-departamento" type="text" class="w-full px-3 py-2 border rounded" name="departamento" readonly>
            </div>
        </div>

        <!-- Persona que recibe -->
        <h3 class="text-md font-medium mb-2">Persona que recibe</h3>

        <div class="mb-4">
            <label for="entrega-departamento-receptor" class="text-sm text-gray-700 font-medium">Departamento</label>
            <select id="entrega-departamento-receptor" class="w-full px-3 py-2 border rounded">
                <option value="">Seleccione un departamento</option>
                <!-- Opciones se cargarán desde backend o JS más adelante -->
            </select>
        </div>

        <div class="mb-6">
            <label for="entrega-nombre-receptor" class="text-sm text-gray-700 font-medium">Nombre de la persona que recibe</label>
            <input id="entrega-nombre-receptor" type="text" class="w-full px-3 py-2 border rounded" placeholder="Nombre completo">
        </div>

        <!-- Tabla de materiales -->
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full border border-gray-300">
                <thead>
                <tr class="bg-gray-100">
                    <th class="py-2 px-4 text-left">Código</th>
                    <th class="py-2 px-4 text-left">Nombre</th>
                    <th class="py-2 px-4 text-left">Cantidad a entregar</th>
                    <th class="py-2 px-4 text-left">Existencia actual</th>
                    <th class="py-2 px-4"></th>
                </tr>
                </thead>
                <tbody id="tabla-materiales">
                <tr>
                    <td colspan="5" class="py-2 px-4 text-center text-gray-500">
                        No hay materiales seleccionados.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- Botón Buscar materiales y Entregar materiales -->
        <div class="flex justify-between">
            <!-- Izquierda -->
            <button id="btn-buscar-materiales"
                    class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                    onclick="mostrarBuscarMateriales()">
                Buscar materiales
            </button>

            <!-- Derecha -->
            <button id="btn-entregar-materiales"
                    class="mb-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                Entregar materiales
            </button>
        </div>
    </div>
</div>

<!-- Pantalla Buscar Materiales (oculta por defecto) -->
<div id="buscar-materiales-content" class="hidden p-6">
    <div class="flex items-center mb-4">
        <button id="btn-regresar-buscar"
                class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                onclick="regresarBuscarMateriales()">
            ← Regresar
        </button>
    </div>

    <h3 class="text-lg font-bold mb-4">Buscar materiales</h3>

    <!-- Contenido temporal: por ahora solo botón regresar y texto -->
    <p class="text-gray-600">Pantalla de búsqueda (vacía por ahora).</p>
</div>
