<div class="p-4">
    <h2 class="text-2xl font-semibold mb-4">Ver Historial</h2>

    <!-- Filtros -->
    <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
        <input type="date" id="filtro-fecha" class="border p-2 rounded w-full md:w-auto" placeholder="Fecha">
        <select id="filtro-estado" class="border p-2 rounded w-full md:w-auto">
            <option value="">Todos los estados</option>
            <option value="en_espera">🟡 En espera</option>
            <option value="aceptado">🟢 Aceptado</option>
            <option value="rechazado">🔴 Rechazado</option>
        </select>
        
    </div>



    <!-- Tabla -->
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300" id="tabla-historial">
            <thead class="bg-gray-100">
            <tr>
                <th class="border px-4 py-2">ID</th>
                <th class="border px-4 py-2">Fecha</th>
                <th class="border px-4 py-2">Departamento</th>
                <th class="border px-4 py-2">Descripción</th>
                <th class="border px-4 py-2">Estado</th>
                <th class="border px-4 py-2">Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php
            for ($i = 1; $i <= 15; $i++) :
                $estado_index = $i % 3;
                $estado_actual = ['en_espera', 'aceptado', 'rechazado'][$estado_index];
                $fecha = "2025-08-" . str_pad($i, 2, '0', STR_PAD_LEFT);

                // Texto visible para filtrar
                $texto_estado = ['en_espera' => 'en_espera', 'aceptado' => 'aceptado', 'rechazado' => 'rechazado'][$estado_actual];

                // SVG por estado
                $svg = '';
                if ($estado_actual === 'aceptado') {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-green-600 mx-auto"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
                } elseif ($estado_actual === 'en_espera') {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-yellow-500 mx-auto"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
                } elseif ($estado_actual === 'rechazado') {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-red-600 mx-auto"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
                }
                ?>
                <tr class="text-center">
                    <td class="border px-4 py-2"><?= $i ?></td>
                    <td class="border px-4 py-2 col-fecha"><?= $fecha ?></td>
                    <td class="border px-4 py-2">Departamento <?= ($i % 3) + 1 ?></td>
                    <td class="border px-4 py-2">Solicitud de prueba <?= $i ?></td>
                    <td class="border px-4 py-2 col-estado" data-estado="<?= $estado_actual ?>">
                        <?= $svg ?>
                        <span class="hidden"><?= $texto_estado ?></span>
                    </td>
                    <td class="border px-4 py-2">
                        <a href="#" class="text-blue-600 hover:underline">ver</a>
                    </td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>
