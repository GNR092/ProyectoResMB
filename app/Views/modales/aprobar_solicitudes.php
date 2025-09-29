<div x-data="aprobarSolicitudes()">

    <!-- Vista de Tabla -->
    <div id="div-tabla-aprobacion">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Solicitudes Pendientes de Aprobación</h3>
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="py-2 px-4 text-left">Folio</th>
                        <th class="py-2 px-4 text-left">Fecha</th>
                        <th class="py-2 px-4 text-left">Solicitante</th>
                        <th class="py-2 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaAprobarSolicitudes">
                    <?php if (empty($solicitudes_pendientes)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">No hay solicitudes pendientes de su departamento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes_pendientes as $solicitud): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-4"><?= esc($solicitud['No_Folio']) ?></td>
                                <td class="py-2 px-4"><?= esc($solicitud['Fecha']) ?></td>
                                <td class="py-2 px-4"><?= esc($solicitud['UsuarioNombre']) ?></td>
                                <td class="py-2 px-4 text-center">
                                    <button @click="verDetalle(<?= $solicitud['ID_Solicitud'] ?>)" class="text-blue-600 hover:underline">Revisar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Vista de Detalles -->
    <div id="div-ver-aprobacion" class="hidden">
        <button @click="regresarATabla()" class="mb-4 text-blue-600 hover:underline">&larr; Regresar a la lista</button>
        <div id="detalles-aprobacion-solicitud" class="space-y-4">
            <!-- Contenido cargado por JS -->
        </div>
    </div>
</div>
