<?php
$session = session();
$stylessPath = FCPATH . 'css/styless.css';
$version = file_exists($stylessPath) ? filemtime($stylessPath) : time();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Pantalla principal</title>
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-hash" content="<?= csrf_hash() ?>">
    <link rel="stylesheet" href="<?= base_url('css/choices.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>?v=<?= $version ?>">
    <link rel="stylesheet" href="<?= base_url('css/modal-anim.css') ?>?v=<?= time() ?>">

    <script>
        window.BASE_URL = "<?= base_url() ?>";
        window.CURRENT_USER_ID = <?= session('id') ?? 'null' ?>;
        window.CURRENT_DEPTO_ID = <?= session('id_departamento_usuario') ?? 'null' ?>;
    </script>
</head>

<body class="h-screen flex">
<aside class="font-montserrat w-64 bg-carbon-800 flex flex-col overflow-auto transition-all duration-300">
    <div class="p-4 border-b border-carbon-700">
        <img src="<?= base_url(
            'images/logo.png',
        ) ?>" alt="Logo" class="mx-auto h-20 object-contain">
    </div>

    <nav id="sidebar-nav" class="flex-1 mt-4 px-3 space-y-1">
        <?php if (!empty($opcionesDinamicas)): ?>
            <?php foreach ($opcionesDinamicas as $key => $opcion): ?>
                <?php $isTitle =
                    (isset($opcion['is_title']) && $opcion['is_title']) ||
                    empty($opcion['icon']); ?>

                <?php if ($isTitle): ?>
                    <div class="text-carbon-400 text-[10px] font-bold uppercase tracking-widest pt-5 pb-2 px-3 select-none">
                        <?= esc($opcion['label']) ?>
                    </div>
                <?php else: ?>
                    <a href="#" data-opcion="<?= $key ?>"
                       class="flex items-center px-3 py-2 text-sm font-medium text-carbon-200 border-l-2 border-transparent hover:border-yellow-500 hover:bg-carbon-700 hover:text-white transition-all duration-150 ease-in-out group"
                       onclick="abrirModal('<?= $key ?>', '<?= esc($opcion['label'], 'js') ?>')">
                        <span class="shrink-0 group-hover:text-yellow-500 transition-colors duration-150">
                            <?= $opcion['icon'] ?>
                        </span>
                        <span class="ml-3"><?= esc($opcion['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-carbon-500 text-xs px-3">Sin opciones disponibles</p>
        <?php endif; ?>

        <hr class="border-carbon-700 my-4 mx-3">

        <?php if ($login_type === 'boss'): ?>
            <?php if (count($ajustes) > 0 && array_intersect(['razonsocial', 'crud_places', 'crud_departamento', 'crud_usuarios', 'crud_proveedores'], $ajustes)): ?>
                <a href="#" data-opcion="catalogos" class="flex items-center px-3 py-2 text-sm font-medium text-carbon-200 border-l-2 border-transparent hover:border-yellow-500 hover:bg-carbon-700 hover:text-white transition-all duration-150 ease-in-out group"
                   onclick="abrirModal('catalogos')">
                    <svg class="size-6 shrink-0 text-carbon-400 group-hover:text-yellow-500 transition-colors duration-150" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="/icons/icons.svg#Catalogos"></use>
                    </svg>
                    <span class="ml-3">Catálogos</span>
                </a>
            <?php endif; ?>

            <a href="#" data-opcion="ajustes" class="flex items-center px-3 py-2 text-sm font-medium text-carbon-200 border-l-2 border-transparent hover:border-yellow-500 hover:bg-carbon-700 hover:text-white transition-all duration-150 ease-in-out group"
               onclick="abrirModal('ajustes')">
                <svg class="size-6 shrink-0 text-carbon-400 group-hover:text-yellow-500 transition-colors duration-150" fill="none" stroke-width="1.5" stroke="currentColor">
                    <use xlink:href="/icons/icons.svg#settings"></use>
                </svg>
                <span class="ml-3">Ajustes</span>
            </a>
        <?php endif; ?>

        <a href="<?= base_url(
            'auth/logout',
        ) ?>" class="flex items-center px-3 py-2 text-sm font-medium text-carbon-200 border-l-2 border-transparent hover:border-red-500 hover:bg-carbon-700 hover:text-red-400 transition-all duration-150 ease-in-out group">
            <svg class="size-6 shrink-0 text-carbon-400 group-hover:text-red-400 transition-colors duration-150" fill="none" stroke-width="1.5" stroke="currentColor">
                <use xlink:href="/icons/icons.svg#logout"></use>
            </svg>
            <span class="ml-3">Cerrar sesión</span>
        </a>
    </nav>
</aside>

<div class="flex-1 flex flex-col bg-gray-100">
    <header class="h-12 bg-white border-b border-carbon-100 flex items-center justify-end px-6 text-sm text-carbon-600">
        <div class="flex items-center gap-2">
            <span class="size-2 bg-yellow-500 rounded-full animate-pulse"></span>
            <span class="font-semibold text-carbon-800"><?= esc($nombre_usuario ?? 'Usuario') ?></span>
            <span class="text-carbon-400 mx-1">|</span>
            <span><?= esc($modo_login . ' ' . ($departamento_usuario ?? 'Departamento')) ?></span>
        </div>
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

        <div id="modal-general" class="absolute inset-0 bg-carbon-950/20 backdrop-blur-[2px] z-30 hidden items-start justify-center pt-10 overflow-auto transition-all duration-300">
            <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 sm:mx-auto p-6 relative border border-carbon-100">
                <button onclick="cerrarModal()" class="absolute top-4 right-4 text-carbon-400 hover:text-red-500 transition-colors text-2xl line-height-0">&times;</button>
                <h2 id="modal-title" class="text-lg font-bold mb-6 text-carbon-900 border-l-4 border-yellow-500 pl-3"></h2>
                <div id="modal-contenido" class="text-carbon-700">
                </div>
            </div>
        </div>
    </main>

    <footer class="py-2 text-center text-xs text-gray-500 border-t border-gray-300 bg-white">
        Versión <?= getAppVersion() ?>
    </footer>
</div>

<script>
    const BASE_URL = "<?= base_url() ?>";
    const USER_DEPT_NAME = "<?= esc($departamento_usuario ?? '', 'js') ?>";
    const USER_DEPT_ID = "<?= esc($id_departamento_usuario ?? '', 'js') ?>";
    const CURRENT_USER_ID = "<?= session('id') ?>";
    <?php
    $iconPath = FCPATH . 'icons/icons.svg';
    $iconVersion = file_exists($iconPath) ? filemtime($iconPath) : time();
    ?>
    const ICON_SVG_VERSION = "<?= $iconVersion ?>";
</script>
<script>
    (function () {
        var aside = document.querySelector('aside');
        if (!aside) return;
        var storageKey = 'sidebar_scroll_' + (CURRENT_USER_ID || 'default');

        aside.addEventListener('scroll', function () {
            localStorage.setItem(storageKey, aside.scrollTop);
        });

        var saved = localStorage.getItem(storageKey);
        if (saved !== null) {
            requestAnimationFrame(function () {
                aside.scrollTop = Number(saved);
            });
        }
    })();
</script>
<script src="<?= base_url() ?>js/choices.min.js" defer></script>
<script src="<?= base_url() ?>js/alpine@3.14.8.js" defer></script>
<script src="<?= base_url() ?>js/utils.js?v=<?= filemtime(
    FCPATH . 'js/utils.js',
) ?>" defer></script>
<script src="<?= base_url(
    file_exists(FCPATH . 'js/mbscript.js') ? 'js/mbscript.js' : 'js/mbscript.min.js',
) ?>?v=<?= filemtime(FCPATH . 'js/mbscript.js') ?>" defer></script>
<script src="<?= base_url(
    file_exists(FCPATH . 'js/reportesScript.js')
        ? 'js/reportesScript.js'
        : 'js/reportesScript.min.js',
) ?>?v=<?= filemtime(FCPATH . 'js/reportesScript.js') ?>" defer></script>
<script src="<?= base_url() ?>js/presupuestos.js?v=<?= file_exists(FCPATH . 'js/presupuestos.js') ? filemtime(FCPATH . 'js/presupuestos.js') : time() ?>" defer></script>
<script src="<?= base_url() ?>js/reporte_presupuesto.js?v=<?= file_exists(FCPATH . 'js/reporte_presupuesto.js') ? filemtime(FCPATH . 'js/reporte_presupuesto.js') : time() ?>" defer></script>
<script src="<?= base_url() ?>js/revision.js?v=<?= filemtime(
    FCPATH . 'js/revision.js',
) ?>" defer></script>
<script src="<?= base_url() ?>js/almacen.js?v=<?= filemtime(
    FCPATH . 'js/almacen.js',
) ?>" defer></script>
<script src="<?= base_url() ?>js/user.js?v=<?= filemtime(FCPATH . 'js/user.js') ?>" defer></script>
<script src="<?= base_url() ?>js/pago.js?v=<?= filemtime(FCPATH . 'js/pago.js') ?>" defer></script>
<script src="<?= base_url() ?>js/correcciones.js?v=<?= filemtime(
    FCPATH . 'js/correcciones.js',
) ?>" defer></script>
<script src="<?= base_url() ?>js/gasto_manual.js?v=<?= filemtime(
    FCPATH . 'js/gasto_manual.js',
) ?>" defer></script>
<script src="<?= base_url() ?>js/bitacora.js?v=<?= file_exists(FCPATH . 'js/bitacora.js') ? filemtime(FCPATH . 'js/bitacora.js') : time() ?>" defer></script>
</body>

</html>