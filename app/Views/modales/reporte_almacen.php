<div class="p-4">

    <div class="flex justify-between items-center mb-6">
        <button onclick="abrirModal('almacen')"
                class="text-sm text-gray-600 hover:text-gray-900 transition flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Regresar  a Almacen
        </button>
        <h2 class="text-xl font-semibold text-center">Reporte Historial de Cambios en Productos</h2>
        <div></div> <!-- Espaciador para centrar el título -->
    </div>

    <div id="div-tabla-reporte-almacen">

        <div class="overflow-x-auto shadow rounded-lg">
            <table class="min-w-full border-collapse border border-gray-300">
                <thead class="bg-gray-100">
                <tr>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Fecha</th>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">ID Producto</th>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Cambio</th>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Valor Anterior</th>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Valor Nuevo</th>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Razón</th>
                    <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Usuario</th>
                </tr>
                </thead>
                <tbody id="tablaReporteAlmacen" class="bg-white">
                <?php if (!empty($historial)): ?>
                    <?php foreach ($historial as $registro): ?>
                        <tr class="hover:bg-gray-50 historial-row">
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?= esc(date('d/m/Y H:i', strtotime($registro['created_at']))) ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?= esc($registro['ID_Producto']) ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?php
                                // Determinar qué campo cambió
                                if ($registro['CodigoAnt'] !== $registro['CodigoNew']) echo 'Código';
                                elseif ($registro['NombreAnt'] !== $registro['NombreNew']) echo 'Nombre';
                                elseif ($registro['ExistenciaAnt'] !== $registro['ExistenciaNew']) echo 'Existencia';
                                else echo 'N/A';
                                ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?php
                                if ($registro['CodigoAnt'] !== $registro['CodigoNew']) echo esc($registro['CodigoAnt']);
                                elseif ($registro['NombreAnt'] !== $registro['NombreNew']) echo esc($registro['NombreAnt']);
                                elseif ($registro['ExistenciaAnt'] !== $registro['ExistenciaNew']) echo esc($registro['ExistenciaAnt']);
                                else echo '-';
                                ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?php
                                if ($registro['CodigoAnt'] !== $registro['CodigoNew']) echo esc($registro['CodigoNew']);
                                elseif ($registro['NombreAnt'] !== $registro['NombreNew']) echo esc($registro['NombreNew']);
                                elseif ($registro['ExistenciaAnt'] !== $registro['ExistenciaNew']) echo esc($registro['ExistenciaNew']);
                                else echo '-';
                                ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?= esc($registro['Razon'] ?? 'N/A') ?>
                            </td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200">
                                <?= esc($registro['ID_Usuario']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="py-4 px-3 text-center text-gray-500">
                            No hay registros en el historial.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Div para la paginación -->
        <div id="paginacion-reporte-almacen" class="flex justify-center mt-6 space-x-2"></div>
    </div>

</div>