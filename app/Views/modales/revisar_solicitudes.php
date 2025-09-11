<div class="p-4">
    <h2 class="text-lg font-bold mb-4">Solicitudes Pendientes</h2>

    <!-- Div Tabla Principal -->
    <div id="div-tabla">
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-3 px-6 text-left">Folio</th>
                    <th class="py-3 px-6 text-left">Usuario</th>
                    <th class="py-3 px-6 text-left">Departamento</th>
                    <th class="py-3 px-6 text-left">Fecha</th>
                    <th class="py-3 px-6 text-left">Estado</th>
                    <th></th> <!-- VER -->
                    <th></th> <!-- COTIZAR -->
                </tr>
                </thead>
                <tbody id="tablaRevisarSolicitud">
                <?php if (!empty($solicitudes)): ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['No_Folio']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['UsuarioNombre']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['DepartamentoNombre']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['Fecha']) ?></td>
                            <td class="py-3 px-6 text-left"><?= esc($solicitud['Estado']) ?></td>
                            <td class="py-3 px-6 text-left text-blue-600 cursor-pointer" onclick="mostrarVer(<?= $solicitud['ID_Solicitud'] ?>)">VER</td>
                            <td class="py-3 px-6 text-left text-green-600 cursor-pointer" onclick="mostrarCotizar(<?= $solicitud['ID_Solicitud'] ?>)">COTIZAR</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center px-4 py-2 border">No hay solicitudes registradas.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <div id="paginacion-enviar-revision" class="flex justify-center mt-4 space-x-2"></div>
    </div>

    <!-- Div VER -->
    <div id="div-ver" class="hidden">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Detalles de la Solicitud</h3>
            <div class="cursor-pointer p-2 rounded-full hover:bg-gray-200" onclick="regresarTabla()" title="Regresar a la lista">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-gray-600">
                    <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
                </svg>
            </div>
        </div>
        <div id="detalles-solicitud">
            <!-- Los detalles de la solicitud se cargarán aquí dinámicamente -->
        </div>
    </div>

    <!-- Div COTIZAR -->
    <div id="div-cotizar" class="hidden">
        <h3 class="text-lg font-bold mb-4">Selecciona Proveedores para Cotizar</h3>
        <div class="cursor-pointer w-6 h-6 text-gray-600 mb-4" onclick="regresarTabla()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25Zm-4.28 9.22a.75.75 0 0 0 0 1.06l3 3a.75.75 0 1 0 1.06-1.06l-1.72-1.72h5.69a.75.75 0 0 0 0-1.5h-5.69l1.72-1.72a.75.75 0 0 0-1.06-1.06l-3 3Z" clip-rule="evenodd" />
            </svg>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-4 text-center">Seleccionar</th>
                    <th class="py-2 px-4 text-left">Nombre</th>
                    <th class="py-2 px-4 text-left">Nombre Comercial</th>
                    <th class="py-2 px-4 text-left">Teléfono</th>
                    <th class="py-2 px-4 text-left">RFC</th>
                </tr>
                </thead>
                <tbody>
                    <!-- Las filas de proveedores se cargarán aquí dinámicamente vía JS -->
                </tbody>
            </table>
        </div>

        <!-- Contenedor de paginación de proveedores -->
        <div id="paginacion-proveedores" class="flex justify-center mt-4 space-x-2"></div>

        <!-- Botón para generar cotización -->
        <div class="flex justify-end mt-4">
            <input type="hidden" id="cotizar_id_solicitud">
            <button id="btn-generar-cotizacion" class="bg-carbon text-white px-4 py-2 rounded hover:bg-gray-900 disabled:bg-gray-400" disabled>
                Generar Solicitud de Cotización
            </button>
        </div>
    </div>

</div>
