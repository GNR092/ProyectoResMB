<div class="p-6 bg-white rounded-xl shadow-md">
    <h2 class="text-2xl font-semibold mb-4 text-center">Lista de Proveedores</h2>

    <!-- Buscadores y botón AGREGAR -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
        <!-- Buscadores -->
        <div class="flex flex-1 gap-4">
            <input
                    type="text"
                    id="buscar-nombre"
                    placeholder="Buscar por nombre..."
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300"
            >
            <input
                    type="text"
                    id="buscar-servicio"
                    placeholder="Buscar por servicio..."
                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring focus:ring-blue-300"
            >
        </div>

        <!-- Botón AGREGAR -->
        <div>
            <a href="#"
               id="btn-agregar-proveedor"
               class="inline-block mt-4 px-4 py-2 bg-green text-black font-semibold rounded-md hover:bg-green-700 shadow-sm transition-colors">
                AGREGAR
            </a>

        </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 rounded-lg table-fixed">
            <thead class="bg-gray-100">
            <tr>
                <th class="w-1/6 px-3 py-2 border-b text-left">Nombre</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">RFC</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Banco</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Teléfono</th>
                <th class="w-1/6 px-3 py-2 border-b text-left">Servicio</th>
                <th class="w-1/6 px-3 py-2 border-b text-center">Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-proveedores">
            <?php if (!empty($proveedores)): ?>
                <?php foreach ($proveedores as $index => $prov): ?>
                    <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                        <td class="px-3 py-2 border-b nombre"><?= esc($prov['Nombre']) ?></td>
                        <td class="px-3 py-2 border-b"><?= esc($prov['RFC']) ?></td>
                        <td class="px-3 py-2 border-b"><?= esc($prov['Banco']) ?></td>
                        <td class="px-3 py-2 border-b"><?= esc($prov['Tel_Contacto']) ?></td>
                        <td class="px-3 py-2 border-b servicio"><?= esc($prov['Servicio']) ?></td>
                        <td class="px-2 py-2 border-b align-top text-center acciones">
                            <div class="flex flex-col items-center space-y-1 h-full justify-center">
                                <!-- Icono verde (editar) -->
                                <a href="#" class="text-green-600 hover:text-green-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </a>
                                <!-- Icono rojo (eliminar) -->
                                <a href="#" class="text-red-600 hover:text-red-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </a>
                            </div>
                        </td>


                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-3 py-4 text-center text-gray-500">No hay proveedores registrados</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>


    </div>

    <!-- Controles de paginación -->
    <div class="flex justify-center items-center mt-4 space-x-2">
        <button id="prev-proveedores" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Anterior</button>
        <span id="info-proveedores" class="text-sm text-gray-700"></span>
        <button id="next-proveedores" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Siguiente</button>
    </div>
</div>


