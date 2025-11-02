<?php
$nombreUsuario = session('nombre') ?? 'Nombre Apellido';
$emailUsuario = session('email') ?? 'correo@example.com';
$firmaUrl = session('firma_url') ?? null;
$nombreFirma = session('nombre_firma') ?? 'preview';
?>
<div id="micuenta" class="p-6 bg-white rounded-xl shadow-md">

    <div class="flex items-center mb-4">
        <button onclick="abrirModal('ajustes')" class="text-sm text-gray-600 hover:text-gray-900 transition">
            &larr; Regresar
        </button>
    </div>
    
    <!-- Sección de Información de Usuario -->
    <form id="form-update-user" action="<?= site_url('modales/actualizarUsuario') ?>" class="mb-8 p-6 border border-gray-200 rounded-lg shadow-sm">
        <?= csrf_field() ?>
        <h3 class="text-xl font-medium mb-4">Información de Usuario</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Usuario</label>
                <input type="text" id="username" name="username" value="<?= esc($nombreUsuario) ?>" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Correo</label>
                <input type="email" id="email" name="email" value="<?= esc($emailUsuario) ?>" readonly class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
            </div>
        </div>
        <div class="mt-6 text-right">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Guardar Cambios</button>
        </div>
    </form>

    <!-- Sección de Cambiar Contraseña -->
    <form id="form-change-password" action="<?= site_url('modales/cambiarPassword') ?>" class="mb-8 p-6 border border-gray-200 rounded-lg shadow-sm">
        <?= csrf_field() ?>
        <h3 class="text-xl font-medium mb-4">Cambiar Contraseña</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
            <div>
                <label for="old-password" class="block text-sm font-medium text-gray-700">Contraseña anterior</label>
                <input type="password" id="old-password" name="old_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
            </div>
            <div>
                <label for="new-password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                <input type="password" id="new-password" name="new_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2">
            </div>
        </div>
        <div class="mt-6 text-right">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Guardar Contraseña</button>
        </div>
    </form>

    <!-- Sección de Firma Electrónica -->
    <form id="form-upload-signature" action="<?= site_url('modales/subirFirma') ?>" enctype="multipart/form-data" class="p-6 border border-gray-200 rounded-lg shadow-sm">
        <?= csrf_field() ?>
        <h3 class="text-xl font-medium mb-4">Firma Electrónica</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
            <div>
                <label class="block text-sm font-medium text-gray-700">Previsualización</label>
                <div id="signature-preview" class="mt-1 flex justify-center items-center h-32 w-full border-2 border-gray-300 border-dashed rounded-md bg-gray-50">
                    <?php if ($firmaUrl): ?>
                        <img src="<?= esc($firmaUrl) ?>" alt="Firma" class="h-full w-full object-contain">
                    <?php else: ?>
                        <span class="text-gray-400"><?= esc($nombreFirma) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="signature-file" class="block text-sm font-medium text-gray-700">Archivo de Firma</label>
                <div class="mt-1">
                    <input type="file" id="signature-file" name="signature_file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"/>
                </div>
                 <div class="mt-6 text-right">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Subir y/o Guardar</button>
                </div>
            </div>
        </div>
    </form>
</div>