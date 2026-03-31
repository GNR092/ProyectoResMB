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
</head>

<body class="h-screen flex">
<aside class="font-montserrat w-64 bg-carbon-700 text-white flex flex-col overflow-auto">
    <div class="p-4 border-b border-gray-600">
        <img src="<?= base_url(
            'images/logo.png',
        ) ?>" alt="Logo" class="mx-auto h-20 object-contain">
    </div>

    <nav id="sidebar-nav" class="flex-1 mt-4 px-4 space-y-2">
        <?php if (!empty($opcionesDinamicas)): ?>
            <?php foreach ($opcionesDinamicas as $key => $opcion): ?>
                <?php $isTitle =
                    (isset($opcion['is_title']) && $opcion['is_title']) ||
                    empty($opcion['icon']); ?>

                <?php if ($isTitle): ?>
                    <div class="text-gray-500 text-xs font-bold uppercase tracking-widest pt-6 pb-1 px-3 select-none">
                        <?= esc($opcion['label']) ?>
                    </div>
                <?php else: ?>
                    <a href="#" data-opcion="<?= $key ?>"
                       class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2 transition-colors"
                       onclick="abrirModal('<?= $key ?>', '<?= esc($opcion['label'], 'js') ?>')">
                        <?= $opcion['icon'] ?>
                        <span><?= esc($opcion['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-400 text-sm px-3">Sin opciones disponibles</p>
        <?php endif; ?>

        <hr class="border-gray-600 my-2">

        <?php if ($login_type === 'boss'): ?>
            <?php if (count($ajustes) > 0): ?>
                <a href="#" data-opcion="ajustes" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2 transition-colors"
                   onclick="abrirModal('ajustes')">
                    <svg class="size-6 shrink-0" fill="none" stroke-width="1.5" stroke="currentColor">
                        <use xlink:href="/icons/icons.svg#settings"></use>
                    </svg>
                    <span>Ajustes</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <a href="<?= base_url(
            'auth/logout',
        ) ?>" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2 transition-colors">
            <svg class="size-6 shrink-0" fill="none" stroke-width="1.5" stroke="currentColor">
                <use xlink:href="/icons/icons.svg#logout"></use>
            </svg>
            <span>Cerrar sesión</span>
        </a>
    </nav>
</aside>

<div class="flex-1 flex flex-col bg-gray-100">
    <header class="h-12 bg-white border-b border-gray-300 flex items-center justify-end px-6 text-sm text-gray-600 shadow-sm">
        <?= esc($nombre_usuario ?? 'Usuario') ?> | <?= esc(
     $modo_login . ' ' . ($departamento_usuario ?? 'Departamento'),
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

        <div id="modal-general" class="absolute inset-0 bg-black/20 backdrop-blur-sm z-30 hidden items-start justify-center pt-10 overflow-auto">
            <div class="bg-white bg-opacity-95 rounded-lg shadow-2xl max-w-4xl w-full mx-4 sm:mx-auto p-6 relative">
                <button onclick="cerrarModal()" class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-3xl font-bold">&times;</button>
                <h2 id="modal-title" class="text-xl font-semibold mb-4 text-gray-800"></h2>
                <div id="modal-contenido" class="text-gray-700 space-y-2">
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
    <?php
    $iconPath = FCPATH . 'icons/icons.svg';
    $iconVersion = file_exists($iconPath) ? filemtime($iconPath) : time();
    ?>
    const ICON_SVG_VERSION = "<?= $iconVersion ?>";
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
</body>

</html>