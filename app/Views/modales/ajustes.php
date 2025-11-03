<?php
$ajustes = session('ajustes');

$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

    <?php if (in_array('crud_usuarios', $ajustes)): ?>
    <!-- CRUD Usuarios -->
    <button onclick="abrirModal('crud_usuarios')"
        class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="<?= $iconUrl ?>#usuarios"></use>
        </svg>
        <span>Administrar Usuarios</span>
    </button>
    <?php endif; ?>

    <?php if (in_array('limpiar_almacenamiento', $ajustes)): ?>
    <!-- Limpiar Almacenamiento -->
    <button onclick="abrirModal('limpiar_almacenamiento')"
        class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="<?= $iconUrl ?>#limpiar_almacenamiento"></use>
        </svg>
        <span>Limpiar Almacenamiento</span>
    </button>
    <?php endif; ?>

    <?php if (in_array('crud_proveedores', $ajustes)): ?>
    <!-- CRUD Proveedores -->
    <button onclick="abrirModal('crud_proveedores')"
        class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
            <use xlink:href="<?= $iconUrl ?>#crud_proveedores"></use>
        </svg>
        <span>Proveedores</span>
    </button>
    <?php endif; ?>

    <?php if (in_array('reportes', $ajustes)): ?>
    <!-- Reportes/Auditorias -->
    <button onclick="abrirModal('reportes')"
        class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5">
            <use xlink:href="<?= $iconUrl ?>#reportes"></use>
        </svg>
        <span>Reportes/Auditorias</span>
    </button>
    <?php endif; ?>

    <?php if (in_array('razonsocial', $ajustes)): ?>
    <!-- Razon social -->
    <button onclick="abrirModal('razonsocial')"
        class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke-width="1.5">
            <use xlink:href="<?= $iconUrl ?>#razonsocial"></use>
        </svg>
        <span>Razón Social</span>
    </button>
    <?php endif; ?>

    <button onclick="abrirModal('micuenta')"
        class="p-4 bg-gray-100 hover:bg-gray-200 rounded-lg shadow transition flex items-center space-x-2">
        <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5">
            <use xlink:href="<?= $iconUrl ?>#settings"></use>
        </svg>
        <span>Mi cuenta</span>
    </button>

</div>