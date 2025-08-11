<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MLB Signature Properties</title>
    <link rel="stylesheet" href="<?= base_url('css/styless.css') ?>">
    <link rel="shortcut icon" type="image/png" href="/favicon.ico">

</head>

<body class="min-h-screen bg-gray-200 flex items-center justify-center p-4">

    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md font-montserrat">
        <!-- Logo -->
        <div class="text-center mb-8">
            <img src="<?= base_url(
                'images/logo.svg',
            ) ?>" alt="MLB Signature Properties" class="mx-auto h-20 w-auto">
        </div>
        <?php if (!function_exists('form_open')) {
            helper('form');
        } ?>
        <?= form_open('auth/login', ['class' => 'space-y-6']) ?>
        <?= csrf_field() ?>
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2 ">
                Correo *
            </label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>" required
                class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focusring-orange-400 focus:border-transparent">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña *</label>
            <input type="password" id="password" name="password" required
                class="w-full px-3 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent">
            <p class="text-red-500 text-sm mt-1"></p>
        </div>
        <div class="space-y-4 mb-6">
            <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-md border border-red-400">
                <?= esc($error) ?>
            </div>
            <?php endif; ?>
        </div>

        <button type="submit"
            class="w-full bg-gray-800 text-white py-3 px-4 rounded-md hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:ring-offset-2 transition duration-200">
            Iniciar sesión
        </button>
        <?= form_close() ?>
    </div>
</body>

</html>