<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pantalla principal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-screen flex">

<!-- Barra lateral -->
<aside class="w-64 bg-[#4A4A4A] text-white flex flex-col">
    <!-- Logo -->
    <div class="p-4 border-b border-gray-600">
        <img src="<?= base_url('images/logo.png') ?>" alt="Logo" class="mx-auto h-20 object-contain">
    </div>

    <!-- Menú dinámico -->
    <nav class="flex-1 mt-4 px-4 space-y-2">
        <?php if (!empty($opcionesDinamicas)) : ?>
            <?php foreach ($opcionesDinamicas as $key => $opcion) : ?>
                <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2" onclick="abrirModal('<?= $key ?>')">
                    <?= $opcion['icon'] ?>
                    <span><?= esc($opcion['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="text-gray-400 text-sm">Sin opciones disponibles</p>
        <?php endif; ?>
    </nav>

    <!-- Opciones fijas -->
    <nav class="px-4 py-4 border-t border-gray-600 space-y-2">

        <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.573-1.066z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Ajustes</span>
        </a>

        <a href="<?= base_url('logout') ?>" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3-3h12m0 0l-3.75-3.75M24 12l-3.75 3.75" />
            </svg>
            <span>Cerrar sesión</span>
        </a>
    </nav>
</aside>

<!-- Contenido principal -->
<div class="flex-1 flex flex-col bg-gray-100">
    <!-- Barra superior -->
    <header class="h-12 bg-white border-b border-gray-300 flex items-center justify-end px-6 text-sm text-gray-600 shadow-sm">
        <?= esc($nombre_usuario ?? 'Usuario') ?> | <?= esc($departamento_usuario ?? 'Departamento') ?>
    </header>

    <!-- Área de trabajo -->
    <main class="flex-1 relative p-6 overflow-auto bg-[#D9D9D9]">
        <!-- Logo transparente de fondo -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-15">
            <img src="<?= base_url('images/logo.png') ?>" alt="Logo" class="max-w-xs" />
        </div>

        <!-- Contenido dinámico -->
        <div class="relative z-10">
            <?= $this->renderSection('contenido') ?>
        </div>

        <!-- Modal (solo dentro del área de trabajo) -->
        <div id="modal-general" class="absolute inset-0 bg-black/20 backdrop-blur-sm z-30 hidden">
            <div class="bg-white bg-opacity-95 rounded-lg shadow-2xl max-w-4xl mx-auto mt-20 p-6 relative">
                <button onclick="cerrarModal()" class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
                <h2 id="modal-title" class="text-xl font-semibold mb-4 text-gray-800"></h2>
                <div id="modal-contenido" class="text-gray-700 space-y-2">
                    <!-- Aquí se carga el contenido dinámico -->
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    function abrirModal(opcion) {
        const modal = document.getElementById('modal-general');
        const titulo = document.getElementById('modal-title');
        const contenido = document.getElementById('modal-contenido');

        switch (opcion) {
            case 'solicitar_material':
                titulo.innerText = 'Solicitar Material';
                contenido.innerHTML = `<p>



</p>`;
                break;
            case 'ver_historial':
                titulo.innerText = 'Historial';
                contenido.innerHTML = `<p>Historial de solicitudes realizadas.</p>`;
                break;
            case 'revisar_solicitudes':
                titulo.innerText = 'Revisar Solicitudes';
                contenido.innerHTML = `<p>Listado de solicitudes pendientes de revisión.</p>`;
                break;
            case 'proveedores':
                titulo.innerText = 'Proveedores';
                contenido.innerHTML = `<p>Administración de proveedores registrados.</p>`;
                break;
            case 'ordenes_compra':
                titulo.innerText = 'Órdenes de Compra';
                contenido.innerHTML = `<p>Generación y control de órdenes de compra.</p>`;
                break;
            case 'enviar_revision':
                titulo.innerText = 'Enviar a Revisión';
                contenido.innerHTML = `<p>Enviar solicitudes a revisión.</p>`;
                break;
            case 'usuarios':
                titulo.innerText = 'Usuarios';
                contenido.innerHTML = `<p>Administración de usuarios del sistema.</p>`;
                break;
            case 'dictamen_solicitudes':
                titulo.innerText = 'Dictamen de Solicitudes';
                contenido.innerHTML = `<p>Emitir dictámenes sobre solicitudes.</p>`;
                break;
            case 'crud_proveedores':
                titulo.innerText = 'CRUD Proveedores';
                contenido.innerHTML = `<p>Crear, editar o eliminar proveedores.</p>`;
                break;
            case 'limpiar_almacenamiento':
                titulo.innerText = 'Limpiar Almacenamiento';
                contenido.innerHTML = `<p>Herramienta para liberar espacio o eliminar archivos antiguos.</p>`;
                break;
            case 'pagos_pendientes':
                titulo.innerText = 'Pagos Pendientes';
                contenido.innerHTML = `<p>Gestión de pagos que están pendientes.</p>`;
                break;
            default:
                titulo.innerText = 'Opción no reconocida';
                contenido.innerHTML = `<p>No hay contenido definido aún para esta opción.</p>`;
        }

        modal.classList.remove('hidden');
    }

    function cerrarModal() {
        document.getElementById('modal-general').classList.add('hidden');
    }
</script>

</body>
</html>
