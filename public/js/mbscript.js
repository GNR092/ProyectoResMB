/**
 * Funciones para manejar la apertura y cierre de modales,
 * y la inicialización de su contenido dinámico.
 */
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

    fetch(`${BASE_URL}modales/${opcion}`)
        .then(response => response.text())
        .then(html => {
            contenido.innerHTML = html;
            modal.classList.remove('hidden');

            // Llama a la función de inicialización correspondiente
            if (opcion === 'solicitar_material') {
                initSolicitarMaterial();
            } else if (opcion === 'ver_historial') {
                initPaginacionHistorial();
            } else if (opcion === 'usuarios') {
                initUsuarios();
            } else if (opcion === 'revisar_solicitudes') {
                initRevisarSolicitud();
            } else if (opcion === 'registrar_productos') {
                initRegistrarMaterial();
            } else if (opcion === 'enviar_revision') {
                initEnviarRevision();
            } else if (opcion === 'dictamen_solicitudes') {
                initDictamenSolicitudes();
            } else if (opcion === 'crud_productos') {
                initCrudProductos();
            } else if (opcion === 'ordenes_compra') {
                initOrdenesCompra();
            }

        })
        .catch(error => {
            console.error("Error al cargar modal:", error);
            contenido.innerHTML = '<p class="text-red-500">Error al cargar el contenido del modal.</p>';
            modal.classList.remove('hidden');
        });
}

function cerrarModal() {
    document.getElementById('modal-general').classList.add('hidden');
}


/**
 * Lógica para el modal "Solicitar Material"
 */
async function initSolicitarMaterial() {
    const tabla = document.getElementById('tabla-productos');
    const agregarBtn = document.getElementById('agregar-fila');
    const subtotalTd = document.getElementById('subtotal-costo');
    const totalTd = document.getElementById('total-costo');
    const chkIVA = document.getElementById('agregar-iva');

    if (!tabla) return;

    let productRowHtml = null;
    async function getProductRowHtml() {
        if (productRowHtml === null) {
            try {
                const response = await fetch(`${BASE_URL}modales/vistas/product_row`);
                if (!response.ok) throw new Error('Falló la carga de la fila de producto');
                productRowHtml = await response.text();
            } catch (error) {
                console.error(error);
                productRowHtml = '<tr><td colspan="7" class="text-red-500 p-2">Error al cargar fila.</td></tr>';
            }
        }
        return productRowHtml;
    }

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

    function actualizarTotal() {
        let suma = 0;
        tabla.querySelectorAll('tr').forEach(fila => {
            const costoTd = fila.querySelector('.costo');
            if (costoTd) {
                const valor = parseFloat(costoTd.textContent.replace(/[$,]/g, '')) || 0;
                suma += valor;
            }
        });

        if (subtotalTd) subtotalTd.textContent = '$' + suma.toFixed(2);

        let total = suma;
        if (chkIVA && chkIVA.checked) {
            total = suma * 1.16;
        }
        if (totalTd) totalTd.textContent = '$' + total.toFixed(2);
    }

    function asignarEventosFila(fila) {
        if (!fila) return;

        const cantidadInput = fila.querySelector('.cantidad');
        const importeInput = fila.querySelector('.importe');
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
        actualizarCosto();
    }

    tabla.querySelectorAll('tr').forEach(fila => asignarEventosFila(fila));
    actualizarBotonesEliminar();
    actualizarNumeros();
    actualizarTotal();

    if (chkIVA) {
        chkIVA.addEventListener('change', actualizarTotal);
    }

    if (agregarBtn) {
        agregarBtn.addEventListener('click', async () => {
            const rowHtml = await getProductRowHtml();
            const nuevaFila = tabla.insertRow();
            nuevaFila.innerHTML = rowHtml;
            asignarEventosFila(nuevaFila);
            actualizarNumeros();
            actualizarBotonesEliminar();
            actualizarTotal();
        });
    }

    loadRazonSocialProv();

    const formulario = document.getElementById('form-upload');
    if (formulario) {
        formulario.addEventListener('submit', SendData);
    }
}

function mostrarSolicitarMaterial() {
    document.getElementById('seleccion-opcion').classList.add('hidden');
    document.getElementById('solicitar-material-content').classList.remove('hidden');
    initSolicitarMaterial();
}

function mostrarSolicitarServicio() {
    document.getElementById('seleccion-opcion').classList.add('hidden');
    document.getElementById('solicitar-servicio-content').classList.remove('hidden');
}

function regresarSeleccionOpciones() {
    document.getElementById('solicitar-material-content').classList.add('hidden');
    document.getElementById('solicitar-servicio-content').classList.add('hidden');
    document.getElementById('seleccion-opcion').classList.remove('hidden');
}

/**
 * Lógica para el modal "Ver Historial"
 */
function initPaginacionHistorial() {
    const tabla = document.getElementById('tabla-historial');
    if (!tabla) return;
    const tbody = tabla.querySelector('tbody');
    const paginacionContenedor = document.getElementById('paginacion-historial');
    const filtroFecha = document.getElementById('filtro-fecha');
    const filtroEstado = document.getElementById('filtro-estado');

    let allData = [];
    const filasPorPagina = 10;
    let paginaActual = 1;

    async function fetchData() {
        try {
            const response = await fetch(`${BASE_URL}api/historic`);
            if (!response.ok) {
                throw new Error('Error al cargar el historial');
            }
            allData = await response.json();
            actualizarTabla();
        } catch (error) {
            console.error(error);
            if (tbody) tbody.innerHTML =
                `<tr><td colspan="6" class="text-center text-red-500 p-4">${error.message}</td></tr>`;
        }
    }

    function getStatusSVG(status) {
        if (!status) return '';
        const statusLower = status.toLowerCase();
        let svgClass = '';
        let iconId = '';

        switch (statusLower) {
            case 'aprobada':
                svgClass = 'text-green-600';
                iconId = 'aceptado';
                break;
            case 'en espera':
                svgClass = 'text-yellow-500';
                iconId = 'en_espera';
                break;
            case 'rechazada':
                svgClass = 'text-red-500';
                iconId = 'rechazado';
                break;
            default:
                return '';
        }
        return `<svg class="${svgClass} mx-auto size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="/icons/icons.svg#${iconId}"></use></svg>`;
    }

    function renderizarTabla(data) {
        if (!tbody) return;
        tbody.innerHTML = '';
        if (data.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="6" class="text-center p-4 text-gray-500">No se encontraron resultados.</td></tr>';
            return;
        }

        data.forEach(item => {
            const svg = getStatusSVG(item.Estado);
            const fila = `
            <tr class="text-center">
                <td class="border px-4 py-2">${item.ID_SolicitudProd}</td>
                <td class="border px-4 py-2 col-fecha">${item.Fecha}</td>
                <td class="border px-4 py-2">${item.DepartamentoNombre || 'N/A'}</td>
                <td class="border px-4 py-2">${item.No_Folio || 'N/A'}</td>
                <td class="border px-4 py-2 col-estado" data-estado="${item.Estado}">
                    ${svg}
                    <span class="hidden">${item.Estado}</span>
                </td>
                <td class="border px-4 py-2">
                    <a href="#" class="text-blue-600 hover:underline">ver</a>
                </td>
            </tr>
        `;
            tbody.insertAdjacentHTML('beforeend', fila);
        });
    }

    function aplicarFiltros() {
        const fechaFiltro = filtroFecha.value;
        const estadoFiltro = filtroEstado.value;

        return allData.filter(item => {
            const coincideFecha = !fechaFiltro || item.Fecha === fechaFiltro;
            const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro;
            return coincideFecha && coincideEstado;
        });
    }

    function mostrarPagina(pagina, filasFiltradas) {
        paginaActual = pagina;
        const inicio = (pagina - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;

        const datosPagina = filasFiltradas.slice(inicio, fin);
        renderizarTabla(datosPagina);
        renderizarControlesPaginacion(filasFiltradas.length);
    }

    function renderizarControlesPaginacion(totalFilas) {
        if (!paginacionContenedor) return;
        paginacionContenedor.innerHTML = '';

        const totalPaginas = Math.ceil(totalFilas / filasPorPagina);
        if (totalPaginas <= 1) return;

        for (let i = 1; i <= totalPaginas; i++) {
            const boton = document.createElement('button');
            boton.textContent = i;
            boton.className =
                `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
            boton.addEventListener('click', () => {
                mostrarPagina(i, aplicarFiltros());
            });
            paginacionContenedor.appendChild(boton);
        }
    }

    function actualizarTabla() {
        const filtradas = aplicarFiltros();
        mostrarPagina(1, filtradas);
    }

    filtroFecha?.addEventListener('input', actualizarTabla);
    filtroEstado?.addEventListener('change', actualizarTabla);

    fetchData();
}

/**
 * Lógica para el modal "Usuarios"
 */
function initUsuarios() {
    const modalContenido = document.getElementById('modal-contenido');
    if (!modalContenido) return;

    const form = modalContenido.querySelector('#form-register');
    const mensajeDiv = modalContenido.querySelector('#mensaje');

    loadDepartamentos();

    if (!form) {
        console.warn('initUsuarios: no se encontró #form-register dentro del modal');
        return;
    }

    if (form.dataset.init === '1') return;
    form.dataset.init = '1';

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

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
                if (mensajeDiv) mensajeDiv.innerHTML =
                    '<span class="text-red-600">Error: respuesta inesperada del servidor.</span>';
                return;
            }

            if (data.success) {
                if (mensajeDiv) {
                    mensajeDiv.innerHTML =
                        `<span class="text-green-600">${data.message || 'Registro correcto.'}</span>`;
                }
                form.reset();
                form.querySelector('input, select, textarea')?.focus();
            } else {
                if (mensajeDiv) {
                    mensajeDiv.innerHTML =
                        `<span class="text-red-600">${data.message || 'Error al registrar usuario.'}</span>`;
                }
            }

        } catch (err) {
            console.error('Error en la solicitud:', err);
            if (mensajeDiv) mensajeDiv.innerHTML =
                `<span class="text-red-600">Error en la solicitud: ${err.message}</span>`;
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = prevBtnHtml;
            }
        }
    });
}

/**
 * Lógica para el modal "Revisar Solicitudes"
 */
function initRevisarSolicitud() {
    const filas = document.querySelectorAll('#tablaRevisarSolicitud tr');
    const paginacion = document.getElementById('paginacion-enviar-revision');

    let paginaActual = 1;
    const filasPorPagina = 10;
    const totalFilas = filas.length;
    const totalPaginas = Math.ceil(totalFilas / filasPorPagina);

    function mostrarPagina(pagina) {
        paginaActual = pagina;
        filas.forEach((fila, index) => {
            fila.style.display = (index >= (pagina - 1) * filasPorPagina && index < pagina * filasPorPagina) ? '' : 'none';
        });
        renderPaginacion();
    }

    function renderPaginacion() {
        if (!paginacion) return;
        paginacion.innerHTML = '';
        for (let i = 1; i <= totalPaginas; i++) {
            const boton = document.createElement('button');
            boton.textContent = i;
            boton.className =
                `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
            boton.addEventListener('click', () => mostrarPagina(i));
            paginacion.appendChild(boton);
        }
    }

    if (totalFilas > 0) mostrarPagina(1);
}

function mostrarVer(idSolicitud) {
    document.getElementById('div-tabla').classList.add('hidden');
    document.getElementById('div-ver').classList.remove('hidden');
    console.log("VER solicitud ID:", idSolicitud);
}

function mostrarCotizar(idSolicitud) {
    document.getElementById('div-tabla').classList.add('hidden');
    document.getElementById('div-cotizar').classList.remove('hidden');
    console.log("COTIZAR solicitud ID:", idSolicitud);
}

function regresarTabla() {
    document.getElementById('div-ver').classList.add('hidden');
    document.getElementById('div-cotizar').classList.add('hidden');
    document.getElementById('div-tabla').classList.remove('hidden');
}

/**
 * Lógica para el modal "Registrar Material" (Almacén)
 */
function initRegistrarMaterial() {
    const form = document.getElementById('formRegistrarProducto');
    if (!form) {
        console.warn("No se encontró el formulario de registrar material");
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("Producto registrado correctamente");
                    form.reset();
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors).join('\n') : (data.message || 'Error desconocido');
                    alert("Error al registrar producto:\n" + errorMsg);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error al procesar la solicitud");
            });
    });
}

/**
 * Lógica para el modal "Enviar a Revisión"
 */
function initEnviarRevision() {
    const tabla = document.getElementById('tabla-enviar');
    if (!tabla) return;

    const filas = tabla.querySelectorAll('tbody tr');
    const paginacion = document.getElementById('paginacion-enviar-revision');

    let paginaActual = 1;
    const filasPorPagina = 10;
    const totalFilas = filas.length;
    const totalPaginas = Math.ceil(totalFilas / filasPorPagina);

    function mostrarPagina(pagina) {
        paginaActual = pagina;
        filas.forEach((fila, index) => {
            fila.style.display = (index >= (pagina - 1) * filasPorPagina && index < pagina * filasPorPagina) ? '' : 'none';
        });
        renderPaginacion();
    }

    function renderPaginacion() {
        if (!paginacion) return;
        paginacion.innerHTML = '';
        for (let i = 1; i <= totalPaginas; i++) {
            const boton = document.createElement('button');
            boton.textContent = i;
            boton.className =
                `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
            boton.addEventListener('click', () => mostrarPagina(i));
            paginacion.appendChild(boton);
        }
    }

    const botonesEnviar = tabla.querySelectorAll('.btn-enviar');
    botonesEnviar.forEach(btn => {
        btn.removeEventListener('click', enviarRevisionHandler);
        btn.addEventListener('click', enviarRevisionHandler);
    });

    if (totalFilas > 0) mostrarPagina(1);
}

function enviarRevisionHandler(event) {
    const fila = event.target.closest('tr');
    const idSolicitud = fila.dataset.id;
    console.log('Enviando a revisión la solicitud:', idSolicitud);
}

/**
 * Lógica para el modal "Dictamen de Solicitudes"
 */
function initDictamenSolicitudes() {
    const filas = document.querySelectorAll('#tablaDictamenSolicitudes tr');
    const paginacion = document.getElementById('paginacion-dictamen');

    let paginaActual = 1;
    const filasPorPagina = 10;
    const totalFilas = filas.length;
    const totalPaginas = Math.ceil(totalFilas / filasPorPagina);

    function mostrarPagina(pagina) {
        paginaActual = pagina;
        filas.forEach((fila, index) => {
            fila.style.display = (index >= (pagina - 1) * filasPorPagina && index < pagina * filasPorPagina) ? '' : 'none';
        });
        renderPaginacion();
    }

    function renderPaginacion() {
        if (!paginacion) return;
        paginacion.innerHTML = '';
        for (let i = 1; i <= totalPaginas; i++) {
            const boton = document.createElement('button');
            boton.textContent = i;
            boton.className =
                `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
            boton.addEventListener('click', () => mostrarPagina(i));
            paginacion.appendChild(boton);
        }
    }

    if (totalFilas > 0) mostrarPagina(1);
}

function mostrarVerDictamen(idSolicitud) {
    document.getElementById('div-tabla').classList.add('hidden');
    document.getElementById('div-ver-dictamen').classList.remove('hidden');
    console.log("VER solicitud dictamen ID:", idSolicitud);
    document.getElementById('detallesDictamen').innerHTML = `<p>Cargando detalles de la solicitud ${idSolicitud}...</p>`;
}

function regresarTablaDictamen() {
    document.getElementById('div-ver-dictamen').classList.add('hidden');
    document.getElementById('div-tabla').classList.remove('hidden');
}

/**
 * Lógica para el modal "CRUD Productos" (Existencias)
 */
function initCrudProductos() {
    const tbody = document.getElementById('tablaCrudProductos');
    if (!tbody) return;

    const paginacion = document.getElementById('paginacion-crud-productos');
    const inputBusqueda = document.getElementById('buscarProducto');
    const filasOriginales = Array.from(tbody.querySelectorAll('tr'));
    let paginaActual = 1;
    const filasPorPagina = 10;

    function aplicarFiltro() {
        const termino = (inputBusqueda?.value || '').trim().toLowerCase();
        if (!termino) return filasOriginales;
        return filasOriginales.filter(fila => {
            const codigo = (fila.cells[0]?.textContent || '').toLowerCase();
            const nombre = (fila.cells[1]?.textContent || '').toLowerCase();
            return codigo.includes(termino) || nombre.includes(termino);
        });
    }

    function mostrarPagina(pagina, filasFiltradas) {
        paginaActual = pagina;
        filasOriginales.forEach(f => (f.style.display = 'none'));
        const inicio = (pagina - 1) * filasPorPagina;
        const fin = inicio + filasPorPagina;
        filasFiltradas.slice(inicio, fin).forEach(f => (f.style.display = ''));
        renderPaginacion(filasFiltradas.length);
    }

    function renderPaginacion(totalFiltradas) {
        if (!paginacion) return;
        paginacion.innerHTML = '';
        const totalPaginas = Math.max(1, Math.ceil(totalFiltradas / filasPorPagina));
        if (totalPaginas <= 1) {
            paginacion.style.display = 'none';
            return;
        }
        paginacion.style.display = 'flex';
        for (let i = 1; i <= totalPaginas; i++) {
            const boton = document.createElement('button');
            boton.textContent = i;
            boton.className = `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
            boton.addEventListener('click', () => mostrarPagina(i, aplicarFiltro()));
            paginacion.appendChild(boton);
        }
    }

    function actualizar() {
        const filtradas = aplicarFiltro();
        mostrarPagina(1, filtradas);
    }

    actualizar();

    if (inputBusqueda && !inputBusqueda.dataset.bound) {
        inputBusqueda.addEventListener('input', actualizar);
        inputBusqueda.dataset.bound = '1';
    }
}

function eliminarProducto(idProducto) {
    if (!confirm("¿Estás seguro de que deseas eliminar este producto?")) return;

    fetch(`${BASE_URL}modales/eliminarProducto/${idProducto}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const fila = document.querySelector(`#tablaCrudProductos tr[data-id='${idProducto}']`);
                if (fila) fila.remove();
                alert(data.message);
                // Opcional: Re-inicializar paginación/filtros si es necesario
                initCrudProductos();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error("Error al eliminar el producto:", error);
            alert("Ocurrió un error al eliminar el producto.");
        });
}

function editarProducto(idProducto) {
    document.getElementById('div-tabla').classList.add('hidden');
    document.getElementById('div-busqueda').classList.add('hidden');
    document.getElementById('div-editar').classList.remove('hidden');

    const fila = document.querySelector(`#tablaCrudProductos tr[data-id='${idProducto}']`);
    if (fila) {
        document.getElementById('editarID_Producto').value = idProducto;
        document.getElementById('editarCodigo').value = fila.children[0].textContent.trim();
        document.getElementById('editarNombre').value = fila.children[1].textContent.trim();
        document.getElementById('editarExistencia').value = fila.children[2].textContent.trim();
    }
}

function regresarTablaProductos() {
    document.getElementById('div-tabla').classList.remove('hidden');
    document.getElementById('div-busqueda').classList.remove('hidden');
    document.getElementById('div-editar').classList.add('hidden');
}

function guardarEdicion() {
    const id = document.getElementById('editarID_Producto').value;
    const nombre = document.getElementById('editarNombre').value;
    const existenciaNueva = parseInt(document.getElementById('editarExistencia').value);

    const fila = document.querySelector(`#tablaCrudProductos tr[data-id='${id}']`);
    const existenciaActual = parseInt(fila.children[2].textContent.trim());

    if (existenciaNueva < existenciaActual) {
        alert("No se puede reducir la existencia. Solo se puede aumentar.");
        return;
    }

    fetch(`${BASE_URL}modales/editarProducto/${id}`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
            body: JSON.stringify({ Nombre: nombre, Existencia: existenciaNueva })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Producto actualizado correctamente");
                fila.children[1].textContent = nombre;
                fila.children[2].textContent = existenciaNueva;
                regresarTablaProductos();
            } else {
                const errorMsg = data.errors ? Object.values(data.errors).join('\n') : (data.message || 'Error desconocido');
                alert("Error al actualizar:\n" + errorMsg);
            }
        })
        .catch(err => console.error(err));
}

/**
 * Lógica para el modal "Órdenes de Compra"
 */
function initOrdenesCompra() {
    const filas = document.querySelectorAll('#tablaOrdenesCompra tr');
    const paginacion = document.getElementById('paginacion-ordenes-compra');
    let paginaActual = 1;
    const filasPorPagina = 10;
    const totalFilas = filas.length;
    const totalPaginas = Math.ceil(totalFilas / filasPorPagina);

    function mostrarPagina(pagina) {
        paginaActual = pagina;
        filas.forEach((fila, index) => {
            fila.style.display = (index >= (pagina - 1) * filasPorPagina && index < pagina * filasPorPagina) ? '' : 'none';
        });
        renderPaginacion();
    }

    function renderPaginacion() {
        if (!paginacion) return;
        paginacion.innerHTML = '';
        for (let i = 1; i <= totalPaginas; i++) {
            const boton = document.createElement('button');
            boton.textContent = i;
            boton.className = `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
            boton.addEventListener('click', () => mostrarPagina(i));
            paginacion.appendChild(boton);
        }
    }

    if (totalFilas > 0) mostrarPagina(1);
}

function mostrarVerOrdenCompra(idOrden) {
    document.getElementById('div-tabla-ordenes').classList.add('hidden');
    document.getElementById('div-ver-orden').classList.remove('hidden');
    console.log("VER orden de compra ID:", idOrden);
    document.getElementById('detallesOrdenCompra').innerHTML = `<p>Cargando detalles de la orden ${idOrden}...</p>`;
}

function regresarTablaOrdenCompra() {
    document.getElementById('div-ver-orden').classList.add('hidden');
    document.getElementById('div-tabla-ordenes').classList.remove('hidden');
}

/**
 * getData: Función para obtener datos de una API RESTful
 * Utiliza fetch para hacer solicitudes HTTP y maneja errores.
 * @param { * } endpoint - La ruta del endpoint (sin la parte base de la URL)
 * @param {*} option  - Opciones para la solicitud fetch (método, headers, body, etc.)
 * @param {*} api   - Indica si se debe usar la ruta de la API o no
 * @returns  - Los datos obtenidos de la API o un array vacío en caso de error
 */
async function getData(endpoint, option = {}, api = true) {
  let apiUrl = api ? `${BASE_URL}api/${endpoint}` : `${BASE_URL}${endpoint}`
  let response
  try {
    console.log(`Intentando cargar desde: ${apiUrl}`)
    response = option ? await fetch(apiUrl, option) : await fetch(apiUrl)

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`)
    }

    const data = await response.json()

    return data
  } catch (error) {
    console.error('Hubo un error al obtener los datos:', error)
    return []
  }
}
async function loadHistoric()
{
  try {
    const data = await getData('historic');
    if(data.length > 0)
    {

    }
    else{
      console.error('Los datos recibidos no son un array:', data);
    }
  } catch (error) {
    console.error('Hubo un error al obtener los departamentos:', error)
  }
}
/**
 * loadRazonSocialProv: Función para cargar las opciones de razón social desde la API
 * y agregarlas a un elemento <select> en el DOM.
 */
async function loadRazonSocialProv() {
  const razonSocialSelect = document.getElementById('razonSocialSelect')
  try {
    const data = await getData('providers/all')
    console.log('Datos recibidos:', data)
    if (data.length > 0) {
      razonSocialSelect.innerHTML = '<option value="">Seleccione una opción</option>'
      data.forEach((provider) => {
        let option = document.createElement('option')
        option.value = provider.ID_Proveedor
        option.textContent = provider.Nombre
        razonSocialSelect.appendChild(option)
      })
    } else {
      console.error('Los datos recibidos no son un array:', data)
    }
  } catch (error) {
    console.error('Hubo un error al obtener los departamentos:', error)
  }
}

async function loadDepartamentos() {
  const departamentosSelect = document.getElementById('departamento')
  try {
    const data = await getData('departments/all')
    console.log('Departamentos cargados: ', data)
    if (data.length > 0) {
      departamentosSelect.innerHTML = '<option value="">Seleccione un departamento</option>'
      data.forEach((departaments) => {
        let option = document.createElement('option')
        option.value = departaments.ID_Dpto
        option.textContent = departaments.Nombre + ' ' + departaments.Place
        departamentosSelect.appendChild(option)
      })
    } else {
      console.error('Los datos recibidos no son array: ', data)
    }
  } catch (error) {
    console.error(error)
  }
}

/**
 * SendData: Función para manejar el envío del formulario de manera asíncrona
 * @param {*} event - El evento de envío del formulario
 */
async function SendData(event) {
  event.preventDefault()

  const formulario = event.target
  const formData = new FormData(formulario)

  const messageContainer = document.getElementById('mensajes-form')
  const submitButton = document.getElementById('btn-enviar')

  if (submitButton) {
    submitButton.disabled = true
    const buttonTextSpan = submitButton.querySelector('span')
    if (buttonTextSpan) {
      buttonTextSpan.textContent = 'Enviando...'
    } else {
      submitButton.textContent = 'Enviando...'
    }
  }

  if (messageContainer) {
    messageContainer.innerHTML = ''
  }

  try {
    const data = await getData(
      'solicitudes/registrar',
      {
        method: 'POST',
        body: formData,
        headers: { Accept: 'application/json' },
      },
      false,
    )

    if (data.success) {
      if (messageContainer) {
        messageContainer.innerHTML = `<p class="text-green-600">${data.message}</p>`
      }
      formulario.reset()
    } else {
      let erroresHtml = ''
      if (data.errors) {
        for (const key in data.errors) {
          erroresHtml += `<li>${data.errors[key]}</li>`
        }
      } else {
        erroresHtml = `<li>${data.message || 'Ocurrió un error desconocido.'}</li>`
      }
      if (messageContainer) {
        messageContainer.innerHTML = `<ul class="list-disc list-inside text-red-600">${erroresHtml}</ul>`
      }
    }
  } catch (error) {
    console.error('Error en el envío del formulario:', error)
    if (messageContainer) {
      messageContainer.innerHTML = `<p class="text-red-600">Ocurrió un error de red. Por favor, intente de nuevo.</p>`
    }
  } finally {
    if (submitButton) {
      submitButton.disabled = false
      const buttonTextSpan = submitButton.querySelector('span')
      if (buttonTextSpan) {
        buttonTextSpan.textContent = 'Enviar'
      } else {
        submitButton.textContent = 'Enviar'
      }
    }
  }
}
