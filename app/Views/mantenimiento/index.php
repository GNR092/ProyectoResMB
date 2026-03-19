<?php
$session = session(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento</title>
    <meta name="csrf-token-name" content="<?= csrf_token() ?>">
    <meta name="csrf-token-hash" content="<?= csrf_hash() ?>">
    <link rel="stylesheet" href="<?= base_url('css/choices.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Mantenimiento</h1>
        
        <!-- Contenido aquí -->
    </div>
    
    <script src="<?= base_url('js/alpine.min.js') ?>" defer></script>
    <script src="<?= base_url('js/choices.min.js') ?>"></script>
    <script src="<?= base_url('js/mbscript.js') ?>?v=<?= filemtime(
    FCPATH . 'js/mbscript.js',
) ?>"></script>
</body>
</html>
