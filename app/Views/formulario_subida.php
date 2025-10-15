<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir archivo</title>
</head>
<body class="p-6 font-sans">
<h1 class="text-2xl font-bold mb-4">Subir un archivo</h1>

<?php if (session()->getFlashdata('mensaje')): ?>
    <div class="text-green-600 mb-2"><?= session()->getFlashdata('mensaje') ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="text-red-600 mb-2"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form action="<?= base_url('solicitudes/registrar') ?>" method="post" enctype="multipart/form-data">
    <label for="archivo_input" class="block text-gray-700 text-sm font-bold mb-2">Seleccionar archivo:</label>
    <input type="file" id="archivo_input" name="archivo" required class="block mb-4">
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Subir</button>
</form>
</body>
</html>
