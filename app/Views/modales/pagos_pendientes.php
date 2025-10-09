<!-- Pantalla principal -->
<div id="pagos-menu" class="p-6">
    <h2 class="text-lg font-semibold mb-4">Facturas Pendientes</h2>

    <div class="grid gap-4">
        <button onclick="mostrarPagoContado()"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            Pago de contado
        </button>

        <button onclick="mostrarPagoCredito()"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            Pago a crédito
        </button>
    </div>
</div>

<!-- ================== Pago de contado ================== -->
<div id="pago-contado" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarPagosMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Pago de contado</h2>
        <div></div>
    </div>

    <!-- Tabla de solicitudes de contado -->
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
                            <button class="text-blue-600 hover:underline" onclick="verDetalleContado(<?= $sol['ID_Solicitud'] ?>)">Ver</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">No hay solicitudes de contado pendientes por pagar.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Detalle de solicitud contado -->
    <div id="detalle-contado" class="hidden"></div>
</div>

<!-- ================== Pago a crédito ================== -->
<div id="pago-credito" class="hidden p-6">
    <div class="flex justify-between items-center mb-4">
        <button onclick="regresarPagosMenu()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Pago a crédito</h2>
        <div></div>
    </div>

    <!-- Tabla de solicitudes a crédito -->
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
                            <button class="text-green-600 hover:underline" onclick="verDetalleCredito(<?= $sol['ID_Solicitud'] ?>)">Ver</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-500">No hay solicitudes a crédito pendientes por pagar.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Detalle de solicitud crédito -->
    <div id="detalle-credito" class="hidden"></div>
</div>
