<div class="p-4">
    <h2 class="text-xl font-bold mb-4">Enviar a Revisión</h2>

    <div class="overflow-x-auto">
        <table id="tabla-enviar" class="min-w-full border border-gray-300">
            <thead class="bg-gray-100">
            <tr>
                <th class="py-3 px-6 text-left">Usuario</th>
                <th class="py-3 px-6 text-left">Departamento</th>
                <th class="py-3 px-6 text-left">Fecha</th>
                <th class="py-3 px-6 text-left">Estado</th>
                <th class="py-3 px-6 text-left">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($solicitudes)): ?>
                <?php foreach ($solicitudes as $s): ?>
                    <tr class="hover:bg-gray-50" data-id="<?= $s['ID_Solicitud'] ?>">
                        <td class="py-3 px-6 text-left"><?= esc($s['UsuarioNombre']) ?></td>
                        <td class="py-3 px-6 text-left"><?= esc($s['DepartamentoNombre']) ?></td>
                        <td class="py-3 px-6 text-left"><?= esc($s['Fecha']) ?></td>
                        <td class="py-3 px-6 text-left"><?= esc($s['Estado']) ?></td>
                        <td class="py-3 px-6 text-left">
                            <button
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded btn-enviar">
                                Enviar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center px-4 py-2 border text-gray-500">
                        No hay solicitudes disponibles
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Contenedor de paginación -->
    <div id="paginacion-enviar-revision" class="flex justify-center mt-4 space-x-2"></div>
</div>
