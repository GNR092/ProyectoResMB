
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
    <div class="p-4 border-b border-gray-600">
        <img src="<?= base_url('images/logo.png') ?>" alt="Logo" class="mx-auto h-20 object-contain">
    </div>

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
    <header class="h-12 bg-white border-b border-gray-300 flex items-center justify-end px-6 text-sm text-gray-600 shadow-sm">
        <?= esc($nombre_usuario ?? 'Usuario') ?> | <?= esc($departamento_usuario ?? 'Departamento') ?>
    </header>

    <main class="flex-1 relative p-6 overflow-auto bg-[#D9D9D9]">
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-10">
            <img src="<?= base_url('images/logo.png') ?>" alt="Logo" class="max-w-xs filter invert" />
        </div>

        <div class="relative z-10">
            <?= $this->renderSection('contenido') ?>
        </div>

        <!-- Modal general -->
        <div id="modal-general" class="absolute inset-0 bg-black/20 backdrop-blur-sm z-30 hidden flex items-start justify-center pt-10 overflow-auto">
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
            'pagos_pendientes': 'Pagos Pendientes'
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


    function initSolicitarMaterial() {
        const tabla = document.getElementById('tabla-productos'); // tbody
        const agregarBtn = document.getElementById('agregar-fila');
        const totalCostoTd = document.getElementById('total-costo');

        if (!tabla) return;

        function actualizarNumeros() {
            // numerar solo las filas actuales
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

        function actualizarTotal() {
            let suma = 0;
            tabla.querySelectorAll('tr').forEach(fila => {
                const costoTd = fila.querySelector('.costo');
                if (costoTd) {
                    const valor = parseFloat(costoTd.textContent.replace(/[$,]/g, '')) || 0;
                    suma += valor;
                }
            });
            totalCostoTd.textContent = '$' + suma.toFixed(2);
        }

        function asignarEventosFila(fila) {
            if (!fila) return;

            const cantidadInput = fila.querySelector('.cantidad');
            const importeInput = fila.querySelector('.importe');
            const codigoInput = fila.querySelector('.codigo'); // nuevo campo código (si lo necesitas para lógica adicional)
            const costoTd = fila.querySelector('.costo');
            const eliminarBtn = fila.querySelector('.eliminar-fila');

            function actualizarCosto() {
                const cantidad = parseFloat(cantidadInput?.value) || 0;
                const importe = parseFloat(importeInput?.value) || 0;
                const costo = cantidad * importe;
                if (costoTd) costoTd.textContent = '$' + costo.toFixed(2);
                actualizarTotal();
            }

            if (cantidadInput) cantidadInput.addEventListener('input', actualizarCosto);
            if (importeInput) importeInput.addEventListener('input', actualizarCosto);

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

            // Cálculo inicial para mostrar costo desde valores por defecto
            actualizarCosto();
        }

        // Asignar eventos a las filas existentes
        tabla.querySelectorAll('tr').forEach(fila => asignarEventosFila(fila));

        actualizarBotonesEliminar();
        actualizarNumeros();
        actualizarTotal();

        // Añadir nueva fila
        if (agregarBtn) {
            agregarBtn.addEventListener('click', () => {
                // Crear nueva fila y mantener exactamente la estructura de la plantilla
                const nuevaFila = tabla.insertRow();
                nuevaFila.innerHTML = `
                <td class="numero-fila border px-3 py-1 text-center"></td>
                <td class="border px-3 py-1">
                    <input type="text" name="codigo[]" class="w-full border rounded px-2 py-1 codigo" placeholder="Código">
                </td>
                <td class="border px-3 py-1">
                    <input type="text" name="producto[]" class="w-full border rounded px-2 py-1" placeholder="Producto">
                </td>
                <td class="border px-3 py-1">
                    <input type="number" name="cantidad[]" class="cantidad w-full border rounded px-2 py-1" min="1" step="1" value="1">
                </td>
                <td class="border px-3 py-1">
                    <input type="number" name="importe[]" class="importe w-full border rounded px-2 py-1" min="0" step="1" value="0">
                </td>
                <td class="costo border px-3 py-1 text-right">$0.00</td>
                <td class="border px-3 py-1 text-center">
                    <button type="button" class="eliminar-fila text-red-600 hover:text-red-800" title="Eliminar fila">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </button>
                </td>
            `;
                asignarEventosFila(nuevaFila);
                actualizarNumeros();
                actualizarBotonesEliminar();
                actualizarTotal();
            });
        }
    }
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
                        'X-Requested-With': 'XMLHttpRequest', // <- IMPORTANT (para que CI4 isAJAX() sea true)
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
                    
                    // Volver a solicitar la vista del modal usuarios para dejar contenido vacío actualizado.
                    const urlReload = `<?= base_url('modales/') ?>usuarios`;
                    const reloadResp = await fetch(urlReload);
                    const html = await reloadResp.text();
                    modalContenido.innerHTML = html;
                    // Re-inicializar la funcionalidad para la nueva vista
                    initUsuarios();

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




    document.addEventListener('DOMContentLoaded', initPaginacionHistorial);

</script>

</body>
</html>
