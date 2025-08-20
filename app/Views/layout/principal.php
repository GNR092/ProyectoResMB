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
                <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2" onclick="abrirModal('<?= $key ?>')">
                    <?= $opcion['icon'] ?>
                    <span><?= esc($opcion['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-400 text-sm">Sin opciones disponibles</p>
        <?php endif; ?>

        <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.573-1.066z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>Ajustes</span>
        </a>

        <a href="<?= base_url(
            'auth/logout',
        ) ?>" class="flex items-center px-3 py-2 rounded hover:bg-gray-700 space-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3-3h12m0 0l-3.75-3.75M24 12l-3.75 3.75" />
            </svg>
            <span>Cerrar sesión</span>
        </a>
    </nav>
</aside>

<!-- Contenido principal -->
<div class="flex-1 flex flex-col bg-gray-100">
    <header class="h-12 bg-white border-b border-gray-300 flex items-center justify-end px-6 text-sm text-gray-600 shadow-sm">
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
        <div id="modal-general" class="absolute inset-0 bg-black/20 backdrop-blur-sm z-30 hidden items-start justify-center pt-10 overflow-auto">
            <div class="bg-white bg-opacity-95 rounded-lg shadow-2xl max-w-4xl w-full mx-4 sm:mx-auto p-6 relative">
                <button onclick="cerrarModal()" class="absolute top-2 right-3 text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
                <h2 id="modal-title" class="text-xl font-semibold mb-4 text-gray-800"></h2>
                <div id="modal-contenido" class="text-gray-700 space-y-2">
                    <!-- Contenido cargado por AJAX -->
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    //Abrir-Cerrar modal
    function abrirModal(opcion) {
        const modal = document.getElementById('modal-general');
        const titulo = document.getElementById('modal-title');
        const contenido = document.getElementById('modal-contenido');

        let titulos = {
            'solicitar_material': 'Solicitar Material',
            'ver_historial': 'Historial',
            'revisar_solicitudes': 'Revisar Solicitudes',
            'proveedores': 'Proveedores',
            'ordenes_compra': 'Órdenes de Compra',
            'enviar_revision': 'Enviar a Revisión',
            'usuarios': 'Usuarios',
            'dictamen_solicitudes': 'Dictamen de Solicitudes',
            'crud_proveedores': 'CRUD Proveedores',
            'limpiar_almacenamiento': 'Limpiar Almacenamiento',
            'pagos_pendientes': 'Pagos Pendientes',
            'registrar_productos': 'Registrar Productos',
            'crud_productos': 'Existencias',
            'entrega_productos': 'Entrega de Productos'
        };

        titulo.innerText = titulos[opcion] ?? 'Opción';

        fetch(`<?= base_url('modales/') ?>${opcion}`)
            .then(response => response.text())
            .then(html => {
                contenido.innerHTML = html;
                modal.classList.remove('hidden');

                if (opcion === 'solicitar_material') {
                    initSolicitarMaterial();
                } else if (opcion === 'ver_historial') {
                    initPaginacionHistorial();
                } else if (opcion === 'usuarios') {
                    initUsuarios();
                } else if (opcion === 'revisar_solicitudes') {
                    initREvisarSolicitud();
                } else if (opcion === 'registrar_productos') {
                    initRegistrarMaterial();
                }else if (opcion === 'enviar_revision') {
                    initEnviarRevision();
                }

            })
            .catch(error => {
                contenido.innerHTML = '<p class="text-red-500">Error al cargar el contenido del modal.</p>';
                modal.classList.remove('hidden');
            });
    }
    function cerrarModal() {
        document.getElementById('modal-general').classList.add('hidden');
    }

    //SolicitarMaterial
    function initSolicitarMaterial() {
        const tabla = document.getElementById('tabla-productos'); // tbody
        const agregarBtn = document.getElementById('agregar-fila');

        // NUEVOS refs para subtotal/total/IVA
        const subtotalTd = document.getElementById('subtotal-costo'); // nuevo id en la vista
        const totalTd    = document.getElementById('total-costo');    // ahora muestra el total final
        const chkIVA     = document.getElementById('agregar-iva');    // checkbox "Agregar IVA"

        if (!tabla) return;

        function actualizarNumeros() {
            tabla.querySelectorAll('tr').forEach((fila, i) => {
                const celdaNumero = fila.querySelector('.numero-fila');
                if (celdaNumero) celdaNumero.textContent = i + 1;
            });
        }

        function actualizarBotonesEliminar() {
            const filas = tabla.querySelectorAll('tr');
            filas.forEach(fila => {
                const btnEliminar = fila.querySelector('.eliminar-fila');
                if (btnEliminar) {
                    btnEliminar.style.display = (filas.length === 1) ? 'none' : 'inline-block';
                }
            });
        }

        // === MODIFICADO: ahora calcula SUBTOTAL y TOTAL (con/ sin IVA) ===
        function actualizarTotal() {
            let suma = 0;
            tabla.querySelectorAll('tr').forEach(fila => {
                const costoTd = fila.querySelector('.costo');
                if (costoTd) {
                    const valor = parseFloat(costoTd.textContent.replace(/[$,]/g, '')) || 0;
                    suma += valor;
                }
            });

            // Subtotal
            if (subtotalTd) subtotalTd.textContent = '$' + suma.toFixed(2);

            // Total (aplica 16% si está marcado "Agregar IVA")
            let total = suma;
            if (chkIVA && chkIVA.checked) {
                total = suma * 1.16;
            }
            if (totalTd) totalTd.textContent = '$' + total.toFixed(2);
        }

        function asignarEventosFila(fila) {
            if (!fila) return;

            const cantidadInput = fila.querySelector('.cantidad');
            const importeInput  = fila.querySelector('.importe');
            const costoTd       = fila.querySelector('.costo');
            const eliminarBtn   = fila.querySelector('.eliminar-fila');

            function actualizarCosto() {
                const cantidad = parseFloat(cantidadInput?.value) || 0;
                const importe  = parseFloat(importeInput?.value)  || 0;
                const costo    = cantidad * importe;
                if (costoTd) costoTd.textContent = '$' + costo.toFixed(2);
                actualizarTotal(); // <- recalcula subtotal/total
            }

            if (cantidadInput) cantidadInput.addEventListener('input', actualizarCosto);
            if (importeInput)  importeInput.addEventListener('input',  actualizarCosto);

            if (eliminarBtn) {
                eliminarBtn.addEventListener('click', () => {
                    if (tabla.querySelectorAll('tr').length > 1) {
                        fila.remove();
                        actualizarNumeros();
                        actualizarBotonesEliminar();
                        actualizarTotal();
                    }
                });
            }

            // cálculo inicial
            actualizarCosto();
        }

        // Asignar eventos a filas existentes
        tabla.querySelectorAll('tr').forEach(fila => asignarEventosFila(fila));

        actualizarBotonesEliminar();
        actualizarNumeros();
        actualizarTotal();

        // Reaccionar cuando marquen/desmarquen "Agregar IVA"
        if (chkIVA) {
            chkIVA.addEventListener('change', actualizarTotal);
        }

        // Añadir nueva fila
        if (agregarBtn) {
            agregarBtn.addEventListener('click', () => {
                const nuevaFila = tabla.insertRow();
                nuevaFila.innerHTML = `
                <?= $this->include('layout/productTable') ?>
                <?= $this->renderSection('ProductTable') ?>
            `;
                asignarEventosFila(nuevaFila);
                actualizarNumeros();
                actualizarBotonesEliminar();
                actualizarTotal();
            });
        }

        // ------- tu lógica de envío se queda igual -------
        const formulario = document.getElementById('form-upload');
        if (!formulario) return;

        formulario.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(formulario);
            const url = "<?= base_url('archivo/subir') ?>";
            const MessageContainer = document.getElementById('mensajes-form');
            const submitButton = document.getElementById('btn-enviar');

            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Enviando...';
            if (MessageContainer) MessageContainer.innerHTML = '';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();

                if (data.success) {
                    MessageContainer.innerHTML = `<p class="text-green-600">${data.message}</p>`;
                } else {
                    let errores = '';
                    if (data.errors) {
                        for (const key in data.errors) {
                            errores += `<li>${data.errors[key]}</li>`;
                        }
                    } else {
                        errores = `<li>${data.message || 'Ocurrió un error desconocido.'}</li>`;
                    }
                    if (MessageContainer) MessageContainer.innerHTML = `<ul class="list-disc list-inside text-red-600">${errores}</ul>`;
                }
            } catch (error) {
                console.error('Error:', error);
                if (MessageContainer) MessageContainer.innerHTML = `<p class="text-red-600">Ocurrió un error de red. Por favor, intente de nuevo.</p>`;
            } finally {
                submitButton.disabled = false;
                submitButton.querySelector('span').textContent = 'Enviar';
            }
        });
    }

    //PaginacionHistorial
    function initPaginacionHistorial() {
        const tabla = document.getElementById('tabla-historial');
        const filasOriginales = Array.from(tabla.querySelectorAll('tbody tr'));
        const filasPorPagina = 10;
        let paginaActual = 1;

        const filtroFecha = document.getElementById('filtro-fecha');
        const filtroEstado = document.getElementById('filtro-estado');

        function aplicarFiltros() {
            const fechaFiltro = filtroFecha.value;
            const estadoFiltro = filtroEstado.value.toLowerCase();

            return filasOriginales.filter(fila => {
                const fecha = fila.querySelector('.col-fecha')?.textContent.trim();
                const estado = fila.querySelector('.col-estado')?.textContent.trim().toLowerCase();

                const coincideFecha = !fechaFiltro || fecha === fechaFiltro;
                const coincideEstado = !estadoFiltro || estado === estadoFiltro;

                return coincideFecha && coincideEstado;
            });
        }

        function mostrarPagina(pagina, filasFiltradas) {
            paginaActual = pagina;
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;

            filasOriginales.forEach(fila => fila.style.display = 'none');
            filasFiltradas.slice(inicio, fin).forEach(fila => fila.style.display = '');

            renderizarControlesPaginacion(filasFiltradas.length);
        }

        function renderizarControlesPaginacion(totalFilas) {
            let contenedor = document.getElementById('paginacion-historial');
            if (!contenedor) {
                contenedor = document.createElement('div');
                contenedor.id = 'paginacion-historial';
                contenedor.className = 'flex justify-center mt-4 space-x-2';
                tabla.parentElement.appendChild(contenedor);
            }
            contenedor.innerHTML = '';

            const totalPaginas = Math.ceil(totalFilas / filasPorPagina);
            if (totalPaginas <= 1) return;

            for (let i = 1; i <= totalPaginas; i++) {
                const boton = document.createElement('button');
                boton.textContent = i;
                boton.className = `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
                boton.addEventListener('click', () => {
                    mostrarPagina(i, aplicarFiltros());
                });
                contenedor.appendChild(boton);
            }
        }

        function actualizarTabla() {
            const filtradas = aplicarFiltros();
            mostrarPagina(1, filtradas);
        }

        filtroFecha?.addEventListener('input', actualizarTabla);
        filtroEstado?.addEventListener('change', actualizarTabla);

        actualizarTabla();
    }

    //Usuarios
    function initUsuarios() {
        const modalContenido = document.getElementById('modal-contenido');
        if (!modalContenido) return;

        const form = modalContenido.querySelector('#form-register');
        const mensajeDiv = modalContenido.querySelector('#mensaje');

        if (!form) {
            console.warn('initUsuarios: no se encontró #form-register dentro del modal');
            return;
        }

        // Evitar agregar listeners duplicados
        if (form.dataset.init === '1') return;
        form.dataset.init = '1';

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            // limpiar mensaje previo
            if (mensajeDiv) {
                mensajeDiv.textContent = '';
                mensajeDiv.classList.remove('text-green-600', 'text-red-600');
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const prevBtnHtml = submitBtn ? submitBtn.innerHTML : null;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Guardando...';
            }

            try {
                const formData = new FormData(form);

                const resp = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const text = await resp.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    console.error('Respuesta no JSON recibida al registrar usuario:', text);
                    if (mensajeDiv) mensajeDiv.innerHTML = '<span class="text-red-600">Error: respuesta inesperada del servidor.</span>';
                    return;
                }

                if (data.success) {
                    if (mensajeDiv) {
                        mensajeDiv.innerHTML = `<span class="text-green-600">${data.message || 'Registro correcto.'}</span>`;
                    }
                    form.reset(); // Solo limpia el formulario sin cerrar el modal
                    form.querySelector('input, select, textarea')?.focus(); // Opcional: vuelve al primer campo
                } else {
                    if (mensajeDiv) {
                        mensajeDiv.innerHTML = `<span class="text-red-600">${data.message || 'Error al registrar usuario.'}</span>`;
                    }
                }

            } catch (err) {
                console.error('Error en la solicitud:', err);
                if (mensajeDiv) mensajeDiv.innerHTML = `<span class="text-red-600">Error en la solicitud: ${err.message}</span>`;
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = prevBtnHtml;
                }
            }
        });
    }

    //Revisar solicitudes
    function initREvisarSolicitud() {
        const tabla = document.getElementById('tablaRevisarSolicitud').parentElement; // tbody -> div overflow
        const filas = document.querySelectorAll('#tablaRevisarSolicitud tr');
        const paginacion = document.getElementById('paginacion-enviar-revision');

        let paginaActual = 1;
        const filasPorPagina = 10;
        const totalFilas = filas.length;
        const totalPaginas = Math.ceil(totalFilas / filasPorPagina);

        function mostrarPagina(pagina) {
            paginaActual = pagina;
            filas.forEach((fila, index) => {
                // Ocultar fila de encabezado si la hubiera en tbody
                fila.style.display = (index >= (pagina - 1) * filasPorPagina && index < pagina * filasPorPagina) ? '' : 'none';
            });
            renderPaginacion();
        }

        function renderPaginacion() {
            paginacion.innerHTML = '';
            for (let i = 1; i <= totalPaginas; i++) {
                const boton = document.createElement('button');
                boton.textContent = i;
                boton.className = `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
                boton.addEventListener('click', () => mostrarPagina(i));
                paginacion.appendChild(boton);
            }
        }

        mostrarPagina(1); // Mostrar la primera página
    }
    function mostrarVer(idSolicitud) {
        document.getElementById('div-tabla').classList.add('hidden');
        document.getElementById('div-ver').classList.remove('hidden');
        console.log("VER solicitud ID:", idSolicitud); // Aquí puedes cargar los detalles
    }
    function mostrarCotizar(idSolicitud) {
        document.getElementById('div-tabla').classList.add('hidden');
        document.getElementById('div-cotizar').classList.remove('hidden');
        console.log("COTIZAR solicitud ID:", idSolicitud); // Aquí puedes cargar el formulario
    }
    function regresarTabla() {
        document.getElementById('div-ver').classList.add('hidden');
        document.getElementById('div-cotizar').classList.add('hidden');
        document.getElementById('div-tabla').classList.remove('hidden');
    }

    //Funciones para almacen
    function initRegistrarMaterial() {
        console.log("Inicializando función Registrar Material");

        const form = document.getElementById('formRegistrarProducto');
        if (!form) {
            console.warn("No se encontró el formulario de registrar material");
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Producto registrado correctamente");
                        form.reset(); // Solo limpia el formulario, no cierra el modal
                    } else {
                        alert("Error al registrar producto: " + (data.message || 'Desconocido'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Error al procesar la solicitud");
                });
        });
    }

    //Funcion para enviar a revision
    function initEnviarRevision() {
        const tabla = document.getElementById('tabla-enviar');
        if (!tabla) return; // evita errores si no existe

        // ejemplo: asignar eventos a botones de enviar
        const botonesEnviar = tabla.querySelectorAll('.btn-enviar');
        botonesEnviar.forEach(btn => {
            btn.removeEventListener('click', enviarRevisionHandler); // evitar duplicados
            btn.addEventListener('click', enviarRevisionHandler);
        });
    }

    function enviarRevisionHandler(event) {
        const fila = event.target.closest('tr');
        const idSolicitud = fila.dataset.id; // asumiendo que cada fila tiene data-id

        // aquí iría la lógica de enviar la solicitud a revisión
        console.log('Enviando a revisión la solicitud:', idSolicitud);
    }




    document.addEventListener('DOMContentLoaded', initPaginacionHistorial);

</script>
<script src="<?= base_url() ?>js/alpine@3.14.8.js" defer></script>
</body>
</html>
