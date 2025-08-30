<?php
$session = session(); ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pantalla principal</title>
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>">
</head>

<body class="h-screen flex">

    <!-- Barra lateral -->
    <aside class="font-montserrat w-64 bg-carbon text-white flex flex-col overflow-auto">
        <div class="p-4 border-b border-gray-600">
            <img src="<?= base_url(
                'images/logo.png',
            ) ?>" alt="Logo" class="mx-auto h-20 object-contain">
        </div>

        <nav class="flex-1 mt-4 px-4 space-y-2">
            <?php if (!empty($opcionesDinamicas)): ?>
            <?php foreach ($opcionesDinamicas as $key => $opcion): ?>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2"
                onclick="abrirModal('<?= $key ?>')">
                <?= $opcion['icon'] ?>
                <span><?= esc($opcion['label']) ?></span>
            </a>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-gray-400 text-sm">Sin opciones disponibles</p>
            <?php endif; ?>

            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2">
                <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
                    <use xlink:href="/icons/icons.svg#settings"></use>
                </svg>
                <span>Ajustes</span>
            </a>

            <a href="<?= base_url(
                'auth/logout',
            ) ?>" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2">
                <svg class="size-6" fill="none" stroke-width="1.5" stroke="currentColor">
                    <use xlink:href="/icons/icons.svg#logout"></use>
                </svg>
                <span>Cerrar sesión</span>
            </a>
        </nav>
    </aside>
    <!-- Contenido principal -->
    <div class="flex-1 flex flex-col bg-gray-100">
        <header
            class="h-12 bg-white border-b border-gray-300 flex items-center justify-end px-6 text-sm text-gray-600 shadow-sm">
            <?= esc($nombre_usuario ?? 'Usuario') ?> | <?= esc(
     $departamento_usuario ?? 'Departamento',
 ) ?>
        </header>

        <main class="flex-1 relative p-6 overflow-auto bg-[#D9D9D9]">
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-10">
                <img src="<?= base_url(
                    'images/logo.png',
                ) ?>" alt="Logo" class="max-w-xs filter invert" />
            </div>

            <div class="relative z-10">
                <?= $this->renderSection('contenido') ?>
            </div>

            <!-- Modal general -->
            <div id="modal-general"
                class="absolute inset-0 bg-black/20 backdrop-blur-sm z-30 hidden items-start justify-center pt-10 overflow-auto">
                <div class="bg-white bg-opacity-95 rounded-lg shadow-2xl max-w-4xl w-full mx-4 sm:mx-auto p-6 relative">
                    <button onclick="cerrarModal()"
                        class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
                    <h2 id="modal-title" class="text-xl font-semibold mb-4 text-gray-800"></h2>
                    <div id="modal-contenido" class="text-gray-700 space-y-2">
                        <!-- Contenido cargado por AJAX -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    const BASE_URL = "<?= base_url() ?>";
    </script>
    <script src="<?= base_url() ?>js/alpine@3.14.8.js" defer></script>
    <script src="<?= base_url() ?>js/mbscript.js" defer></script>
</body>

</html>