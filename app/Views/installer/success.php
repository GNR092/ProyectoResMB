<!DOCTYPE html>
<html>

<head>
    <title>Instalación Completa</title>
    <link rel="stylesheet" type="text/css" href="<?= base_url(); ?>css/styless.css">
</head>

<body class="bg-gray-100 font-montserrat flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 md:p-12 rounded-lg shadow-xl w-full max-w-md text-center">
        <div class="flex justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-24 text-green-500">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </div>
        <h1 class="text-3xl font-bold font-doulos text-center mb-4 text-gray-800">¡Instalación Exitosa!</h1>
        <p class="text-gray-600 mb-4">La base de datos se ha creado, el usuario se ha configurado y el archivo de
            entorno(.env) se
            ha actualizado correctamente.</p>
        <p class="text-gray-600 mb-4">Credenciales para inicar sesión:</p>
        <div class="text-left ml-14">
            <p class="text-gray-600 mb-1">Correo: <span class="font-extrabold">admin@example.com</span></p>
            <p class="text-gray-600 mb-4">Contraseña: <span class="font-extrabold">admin</span></p>
        </div>
        <p class="text-gray-600 mb-6">El asistente de instalación ha sido deshabilitado para futuras ejecuciones.</p>

        <a href="/auth"
            class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition-colors duration-200">Ir
            a la página principal ahora</a>
    </div>
</body>
<script src="<?= base_url(); ?>js/alpine@3.14.8.js" defer></script>

</html>