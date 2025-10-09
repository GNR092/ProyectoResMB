<!-- Pantalla principal -->
<div id="ficha-menu" class="p-6">
    <h2 class="text-lg font-semibold mb-4">Fichas de Pago</h2>

    <div class="grid gap-4">
        <button onclick="mostrarFichaContado()"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Fichas de contado
        </button>

        <button onclick="mostrarFichaCredito()"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Fichas a crédito
        </button>
    </div>
</div>

<!-- ================== Ficha de contado ================== -->
<div id="ficha-contado" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarFichaMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Ficha de contado</h2>
        <div></div>
    </div>

    <div id="tabla-contado">
        <table class="min-w-full border border-gray-300">
            <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 text-left">Folio</th>
                <th class="py-2 px-4 text-left">Usuario</th>
                <th class="py-2 px-4 text-left">Departamento</th>
                <th class="py-2 px-4 text-left">Fecha</th>
                <th class="py-2 px-4 text-left">Estado</th>
                <th class="py-2 px-4 text-left">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($solicitudes_contado)): ?>
                <?php foreach ($solicitudes_contado as $sol): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4"><?= esc($sol['No_Folio']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['UsuarioNombre']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['DepartamentoNombre']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['Fecha']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['Estado']) ?></td>
                        <td class="py-2 px-4">
                            <button class="text-blue-600 hover:underline" onclick="verFichaContado(<?= $sol['ID_Solicitud'] ?>)">Ver</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">No hay fichas de pago de contado en proceso.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="detalle-contado" class="hidden"></div>
</div>

<!-- ================== Ficha a crédito ================== -->
<div id="ficha-credito" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarFichaMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Ficha a crédito</h2>
        <div></div>
    </div>

    <div id="tabla-credito">
        <table class="min-w-full border border-gray-300">
            <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 text-left">Folio</th>
                <th class="py-2 px-4 text-left">Usuario</th>
                <th class="py-2 px-4 text-left">Departamento</th>
                <th class="py-2 px-4 text-left">Fecha</th>
                <th class="py-2 px-4 text-left">Estado</th>
                <th class="py-2 px-4 text-left">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($solicitudes_credito)): ?>
                <?php foreach ($solicitudes_credito as $sol): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-4"><?= esc($sol['No_Folio']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['UsuarioNombre']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['DepartamentoNombre']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['Fecha']) ?></td>
                        <td class="py-2 px-4"><?= esc($sol['Estado']) ?></td>
                        <td class="py-2 px-4">
                            <button class="text-green-600 hover:underline" onclick="verFichaCredito(<?= $sol['ID_Solicitud'] ?>)">Ver</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">No hay fichas de pago a crédito en proceso.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div id="detalle-credito" class="hidden"></div>
</div>
