<div class="p-4">
    <h2 class="text-lg font-bold mb-4">Solicitudes en dictamen</h2>

    <!-- Div Tabla Principal -->
    <div id="div-tabla">
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left">Usuario</th>
                    <th class="py-3 px-6 text-left">Departamento</th>
                    <th class="py-3 px-6 text-left">Fecha</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th></th> <!-- VER -->
                </tr>
                </thead>
                <tbody id="tablaDictamenSolicitudes">
                <?php if (!empty($solicitudes)): ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['UsuarioNombre']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['DepartamentoNombre']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['Fecha']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['Estado']) ?></td>
                            <td class="py-3 px-6 text-left text-blue-600 cursor-pointer" onclick="mostrarVerDictamen(<?= $solicitud['ID_Solicitud'] ?>)">VER</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center px-4 py-2 border">No hay solicitudes en dictamen.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <div id="paginacion-dictamen" class="flex justify-center mt-4 space-x-2"></div>
    </div>

    <!-- Div VER -->
    <div id="div-ver-dictamen" class="hidden">
        <h3 class="text-lg font-bold mb-4">Detalles de la solicitud</h3>
        <div class="cursor-pointer w-6 h-6 text-gray-600" onclick="regresarTablaDictamen()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
            </svg>
        </div>
        <div id="detallesDictamen">
            <!-- Aquí se cargarán los detalles vía JS -->
        </div>
    </div>
</div>
