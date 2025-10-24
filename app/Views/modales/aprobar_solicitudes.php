<div x-data="aprobarSolicitudes()" class="p-4">
    <h2 class="text-lg font-bold mb-4">Requisiciones Pendientes de Aprobación</h2>

    <!-- Vista de Tabla -->
    <div id="div-tabla-aprobacion">
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-3 px-6 text-left">Folio</th>
                        <th class="py-3 px-6 text-left">Fecha</th>
                        <th class="py-3 px-6 text-left">Solicitante</th>
                        <th class="py-3 px-6 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaAprobarSolicitudes">
                    <?php if (empty($solicitudes_pendientes)): ?>
                        <tr>
                            <td colspan="7" class="text-center px-4 py-2 border">No hay solicitudes pendientes de su departamento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes_pendientes as $solicitud): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-6"><?= esc($solicitud['No_Folio']) ?></td>
                                <td class="py-3 px-6"><?= esc($solicitud['Fecha']) ?></td>
                                <td class="py-3 px-6"><?= esc($solicitud['UsuarioNombre']) ?></td>
                                <td class="py-3 px-6 text-center">
                                    <button @click="verDetalle(<?= $solicitud['ID_Solicitud'] ?>)" class="text-blue-600 hover:underline">Revisar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div id="paginacion-aprobar-solicitudes" class="flex justify-center mt-4 space-x-2"></div>

    </div>

    <!-- Vista de Detalles -->
    <div id="div-ver-aprobacion" class="hidden">
        <button @click="regresarATabla()" class="mb-4 text-blue-600 hover:underline">&larr; Regresar a la lista</button>
        <div id="detalles-aprobacion-solicitud" class="space-y-4">
            <!-- Contenido cargado por JS -->
        </div>
    </div>
</div>
