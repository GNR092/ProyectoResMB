<?php
// Preparar los datos del historial para pasarlos a JS de forma segura
$historial_json = empty($historial) ? '[]' : htmlspecialchars(json_encode($historial)); ?>
<div class="p-4" x-data="ReporteAlmacen(<?= $historial_json ?>)" x-init="init()">

    <div class="flex justify-between items-center mb-6">
        <button onclick="abrirModal('almacen')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar a Almacen
        </button>
        <h2 class="text-xl font-semibold text-center">Reporte Historial de Cambios en Productos</h2>
        <div></div> <!-- Espaciador para centrar el título -->
    </div>

    <!-- Barra de búsqueda -->
    <div class="mb-4">
        <input type="text" x-model="searchTerm" placeholder="Buscar en el reporte..."
            class="w-full px-4 py-2 border rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>


    <div id="div-tabla-reporte-almacen">
        <div class="overflow-x-auto shadow rounded-lg">
            <table class="min-w-full border-collapse border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Fecha
                        </th>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">ID
                            Producto</th>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">
                            Cambio</th>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Valor
                            Anterior</th>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Valor
                            Nuevo</th>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">Razón
                        </th>
                        <th class="py-2 px-3 text-left text-sm font-medium text-gray-600 border-b border-gray-300">
                            Usuario</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <template x-for="registro in paginatedReport" :key="registro.ID_Historial">
                        <tr class="hover:bg-gray-50 historial-row">
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="formatDate(registro.created_at)"></td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="registro.ID_Producto"></td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="getChangeType(registro)"></td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="getOldValue(registro)"></td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="getNewValue(registro)"></td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="registro.Razon || 'N/A'"></td>
                            <td class="py-2 px-3 text-sm text-gray-700 border-b border-gray-200"
                                x-text="registro.ID_Usuario"></td>
                        </tr>
                    </template>
                    <template x-if="filteredReport.length === 0">
                        <tr>
                            <td colspan="7" class="py-4 px-3 text-center text-gray-500">
                                No se encontraron registros con el término de búsqueda.
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Paginación con Alpine.js -->
        <div x-show="totalPages > 1" class="flex justify-between items-center mt-6">
            <button @click="prevPage()" :disabled="currentPage === 1"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                Anterior
            </button>
            <span x-text="`Página ${currentPage} de ${totalPages}`" class="text-sm text-gray-600"></span>
            <button @click="nextPage()" :disabled="currentPage === totalPages"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded disabled:opacity-50 disabled:cursor-not-allowed">
                Siguiente
            </button>
        </div>

    </div>

</div>