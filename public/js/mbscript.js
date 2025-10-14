/**
 * Funciones para manejar la apertura y cierre de modales,
 * y la inicialización de su contenido dinámico.
 */
function abrirModal(opcion) {
  const modal = document.getElementById('modal-general')
  const titulo = document.getElementById('modal-title')
  const contenido = document.getElementById('modal-contenido')

  let titulos = {
    solicitar_material: 'Requisiciones',
    ver_historial: 'Historial',
    revisar_solicitudes: 'Revisar requisiciones',
    ordenes_compra: 'Órdenes de Compra',
    enviar_revision: 'Enviar a Revisión',
    usuarios: 'Usuarios',
    dictamen_solicitudes: 'Dictamen de requisiciones',
    crud_proveedores: 'CRUD Proveedores',
    limpiar_almacenamiento: 'Limpiar Almacenamiento',
    crud_usuarios: 'Administrar Usuarios',
    pagos_pendientes: 'Facturas Pendientes',
    registrar_productos: 'Registrar Productos',
    crud_productos: 'Existencias',
    entrega_productos: 'Entrega de Material',
    ficha_pago: 'Fichas de pago',
  }
  // Título para la nueva opción
  titulos['aprobar_solicitudes'] = 'Aprobar Requisiciones de Empleados'

  titulo.innerText = titulos[opcion] ?? 'Opción'

  fetch(`${BASE_URL}modales/${opcion}`)
    .then((response) => response.text())
    .then((html) => {
      contenido.innerHTML = html
      modal.classList.remove('hidden')

      // Llama a la función de inicialización correspondiente
      const inicializadores = {
        ver_historial: initPaginacionHistorial,
        usuarios: initUsuarios,
        revisar_solicitudes: initRevisarSolicitud,
        registrar_productos: initRegistrarMaterial,
        enviar_revision: initEnviarRevision,
        dictamen_solicitudes: initDictamenSolicitudes,
        crud_productos: initCrudProductos,
        ordenes_compra: initOrdenesCompra,
        crud_proveedores: initCrudProveedores,
        entrega_productos: initEntregaMaterial,
        // Opciones con inicialización especial o sin ella se omiten
      };

      const inicializador = inicializadores[opcion];
      if (inicializador) {
        inicializador();
      }
    })
    .catch((error) => {
      console.error('Error al cargar modal:', error)
      contenido.innerHTML = '<p class="text-red-500">Error al cargar el contenido del modal.</p>'
      modal.classList.remove('hidden')
    })
}
function cerrarModal() {
  document.getElementById('modal-general').classList.add('hidden')
}

/**
 * Crea una tabla paginada y con filtros a partir de datos de una API.
 * @param {object} config
 * @param {string} config.tableSelector - Selector del tbody de la tabla.
 * @param {string} config.paginationSelector - Selector del contenedor de la paginación.
 * @param {string} config.endpoint - URL de la API para obtener los datos.
 * @param {function} config.renderRow - Función que recibe un item y devuelve el HTML de la fila (tr).
 * @param {number} [config.rowsPerPage=10] - Filas por página.
 * @param {string} [config.filterFormSelector] - Selector del formulario de filtros.
 * @param {function} [config.filterFunction] - Función que recibe (datos, formulario) y devuelve los datos filtrados.
 * @param {string} [config.loadingMessage='Cargando...'] - Mensaje de carga.
 * @param {string} [config.noResultsMessage='No se encontraron resultados.'] - Mensaje sin resultados.
 * @param {function} [config.onDataLoaded] - Callback que se ejecuta después de cargar y renderizar los datos.
 * @param {function} [config.processData] - Función para procesar los datos crudos de la API antes de usarlos.
 */
async function createPaginatedTable(config) {
  const {
    tableSelector,
    paginationSelector,
    endpoint,
    renderRow,
    rowsPerPage = 10,
    filterFormSelector,
    filterFunction,
    loadingMessage = 'Cargando...',
    noResultsMessage = 'No se encontraron resultados.',
    onDataLoaded,
    processData = (data) => data,
  } = config;

  const tbody = document.querySelector(tableSelector);
  const paginacion = document.getElementById(paginationSelector);
  const filterForm = filterFormSelector ? document.querySelector(filterFormSelector) : null;

  if (!tbody) {
    console.error(`Elemento no encontrado: ${tableSelector}`);
    return;
  }

  let allData = [];
  let currentPage = 1;

  async function fetchData() {
    tbody.innerHTML = `<tr><td colspan="100%" class="text-center p-4">${loadingMessage}</td></tr>`;
    try {
      const rawData = await getData(endpoint, {}, false); // getData handles base URL
      allData = processData(rawData);
      updateTable();
      if (onDataLoaded) {
        onDataLoaded(allData);
      }
    } catch (error) {
      console.error(error);
      tbody.innerHTML = `<tr><td colspan="100%" class="text-center text-red-500 p-4">${error.message}</td></tr>`;
    }
  }

  function renderTable(data) {
    tbody.innerHTML = '';
    if (data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="100%" class="text-center p-4 text-gray-500">${noResultsMessage}</td></tr>`;
      return;
    }
    data.forEach(item => {
      tbody.insertAdjacentHTML('beforeend', renderRow(item));
    });
  }

  function renderPagination(totalRows) {
    if (!paginacion) return;
    paginacion.innerHTML = '';
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const button = document.createElement('button');
      button.textContent = i;
      button.className = `px-3 py-1 border rounded ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
      button.addEventListener('click', () => {
        showPage(i, getFilteredData());
      });
      paginacion.appendChild(button);
    }
  }

  function showPage(page, filteredData) {
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageData = filteredData.slice(start, end);
    renderTable(pageData);
    renderPagination(filteredData.length);
  }
  
  function getFilteredData() {
      if (filterFunction && filterForm) {
          return filterFunction(allData, filterForm);
      }
      return allData;
  }

  function updateTable() {
    const filteredData = getFilteredData();
    showPage(1, filteredData);
  }

  if (filterForm) {
    filterForm.addEventListener('input', updateTable);
    filterForm.addEventListener('change', updateTable);
  }

  await fetchData();
}

/**
 * Configura paginación y filtros del lado del cliente para una tabla HTML estática.
 * @param {object} config
 * @param {string} config.rowsSelector - Selector para las filas a paginar/filtrar (ej. '#miTabla tbody tr').
 * @param {string} config.paginationSelector - Selector del contenedor para los botones de paginación.
 * @param {string} [config.filterFormSelector] - Selector del formulario o contenedor de los inputs de filtro.
 * @param {function} config.filterFunction - (row, form) => boolean. Devuelve true si la fila debe mostrarse.
 * @param {number} [config.rowsPerPage=10] - Filas por página.
 */
function setupClientSideTable(config) {
  const {
    rowsSelector,
    paginationSelector,
    filterFormSelector,
    filterFunction,
    rowsPerPage = 10,
  } = config;

  const allRows = Array.from(document.querySelectorAll(rowsSelector));
  const pagination = document.getElementById(paginationSelector);
  const filterForm = filterFormSelector ? document.querySelector(filterFormSelector) : null;

  if (!allRows.length) {
    if (pagination) pagination.innerHTML = '';
    return;
  }

  let currentPage = 1;
  let filteredRows = [...allRows];

  function applyFilters() {
    if (filterFunction && filterForm) {
      filteredRows = allRows.filter(row => filterFunction(row, filterForm));
    } else {
      filteredRows = [...allRows];
    }
    showPage(1);
  }

  function showPage(page) {
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    allRows.forEach(row => row.style.display = 'none');
    filteredRows.slice(start, end).forEach(row => {
        row.style.display = ''; 
    });
    
    renderPagination();
  }

  function renderPagination() {
    if (!pagination) return;
    pagination.innerHTML = '';
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    if (totalPages <= 1) {
        pagination.style.display = 'none';
        return;
    }
    
    pagination.style.display = 'flex';

    for (let i = 1; i <= totalPages; i++) {
      const button = document.createElement('button');
      button.textContent = i;
      button.className = `px-3 py-1 border rounded ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-white text-black'}`;
      button.addEventListener('click', (e) => {
          e.preventDefault();
          showPage(i)
      });
      pagination.appendChild(button);
    }
  }

  if (filterForm) {
    filterForm.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
        }
    });
    filterForm.addEventListener('input', applyFilters);
    filterForm.addEventListener('change', applyFilters);
  }

  applyFilters();
}


/**
 * Lógica para el modal "Solicitar Material"
 */
async function initSolicitarMaterial() {
  const tabla = document.getElementById('tabla-productos')
  const agregarBtn = document.getElementById('agregar-fila')
  const subtotalTd = document.getElementById('subtotal-costo')
  const totalTd = document.getElementById('total-costo')
  const chkIVA = document.getElementById('agregar-iva')

  if (!tabla) return

  let productRowHtml = null
  async function getProductRowHtml() {
    if (productRowHtml === null) {
      try {
        const response = await fetch(`${BASE_URL}modales/vistas/product_row`)
        if (!response.ok) throw new Error('Falló la carga de la fila de producto')
        productRowHtml = await response.text()
      } catch (error) {
        console.error(error)
        productRowHtml =
          '<tr><td colspan="7" class="text-red-500 p-2">Error al cargar fila.</td></tr>'
      }
    }
    return productRowHtml
  }

  function actualizarNumeros() {
    tabla.querySelectorAll('tr').forEach((fila, i) => {
      const celdaNumero = fila.querySelector('.numero-fila')
      if (celdaNumero) celdaNumero.textContent = i + 1
    })
  }

  function actualizarBotonesEliminar() {
    const filas = tabla.querySelectorAll('tr')
    filas.forEach((fila) => {
      const btnEliminar = fila.querySelector('.eliminar-fila')
      if (btnEliminar) {
        btnEliminar.style.display = filas.length === 1 ? 'none' : 'inline-block'
      }
    })
  }

  function actualizarTotal() {
    let suma = 0
    tabla.querySelectorAll('tr').forEach((fila) => {
      const costoTd = fila.querySelector('.costo')
      if (costoTd) {
        const valor = parseFloat(costoTd.textContent.replace(/[$,]/g, '')) || 0
        suma += valor
      }
    })

    if (subtotalTd) subtotalTd.textContent = '$' + suma.toFixed(2)

    let total = suma
    if (chkIVA && chkIVA.checked) {
      total = suma * 1.16
    }
    if (totalTd) totalTd.textContent = '$' + total.toFixed(2)
  }

  function asignarEventosFila(fila) {
    if (!fila) return

    const cantidadInput = fila.querySelector('.cantidad')
    const importeInput = fila.querySelector('.importe')
    const costoTd = fila.querySelector('.costo')
    const eliminarBtn = fila.querySelector('.eliminar-fila')

    function actualizarCosto() {
      const cantidad = parseFloat(cantidadInput?.value) || 0
      const importe = parseFloat(importeInput?.value) || 0
      const costo = cantidad * importe
      if (costoTd) costoTd.textContent = '$' + costo.toFixed(2)
      actualizarTotal()
    }

    if (cantidadInput) cantidadInput.addEventListener('input', actualizarCosto)
    if (importeInput) importeInput.addEventListener('input', actualizarCosto)

    if (eliminarBtn) {
      eliminarBtn.addEventListener('click', () => {
        if (tabla.querySelectorAll('tr').length > 1) {
          fila.remove()
          actualizarNumeros()
          actualizarBotonesEliminar()
          actualizarTotal()
        }
      })
    }
    actualizarCosto()
  }

  tabla.querySelectorAll('tr').forEach((fila) => asignarEventosFila(fila))
  actualizarBotonesEliminar()
  actualizarNumeros()
  actualizarTotal()

  if (chkIVA) {
    chkIVA.addEventListener('change', actualizarTotal)
  }

  if (agregarBtn) {
    const nuevoBtn = agregarBtn.cloneNode(true)
    agregarBtn.parentNode.replaceChild(nuevoBtn, agregarBtn)

    nuevoBtn.addEventListener('click', async () => {
      const rowHtml = await getProductRowHtml()
      const nuevaFila = tabla.insertRow()
      nuevaFila.innerHTML = rowHtml
      asignarEventosFila(nuevaFila)
      actualizarNumeros()
      actualizarBotonesEliminar()
      actualizarTotal()
    })
  }

  loadRazonSocialProv('ProvSelect')

  const formulario = document.getElementById('form-upload')
  if (formulario) {
    formulario.addEventListener('submit', function (e) {
      const importes = tabla.querySelectorAll('.importe')
      let valido = true

      importes.forEach((input) => {
        const valor = parseFloat(input.value)

        // Crear o reutilizar mensaje de error
        let errorMsg = input.parentNode.querySelector('.error-msg')
        if (!errorMsg) {
          errorMsg = document.createElement('p')
          errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1')
          input.parentNode.appendChild(errorMsg)
        }

        if (isNaN(valor) || valor <= 0) {
          valido = false
          input.classList.add('border-red-500')
          errorMsg.textContent = 'El importe debe ser mayor a 0'
        } else {
          input.classList.remove('border-red-500')
          errorMsg.textContent = ''
        }
      })

      if (!valido) {
        e.preventDefault()
      } else {
        SendData(e)
      }
    })
  }
}
async function initSolicitarMaterialSinCotizar() {
  const tabla = document.getElementById('tabla-productos-sin-cotizar')
  const agregarBtn = document.getElementById('agregar-fila-sin-cotizar')

  if (!tabla) return

  function actualizarNumeros() {
    tabla.querySelectorAll('tr').forEach((fila, i) => {
      const celdaNumero = fila.querySelector('.numero-fila')
      if (celdaNumero) celdaNumero.textContent = i + 1
    })
  }

  function actualizarBotonesEliminar() {
    const filas = tabla.querySelectorAll('tr')
    filas.forEach((fila) => {
      const btnEliminar = fila.querySelector('.eliminar-fila')
      if (btnEliminar) {
        btnEliminar.style.display = filas.length === 1 ? 'none' : 'inline-block'
      }
    })
  }

  function asignarEventosFila(fila) {
    if (!fila) return
    const eliminarBtn = fila.querySelector('.eliminar-fila')
    if (eliminarBtn) {
      eliminarBtn.addEventListener('click', () => {
        if (tabla.querySelectorAll('tr').length > 1) {
          fila.remove()
          actualizarNumeros()
          actualizarBotonesEliminar()
        }
      })
    }
  }

  tabla.querySelectorAll('tr').forEach((fila) => asignarEventosFila(fila))
  actualizarBotonesEliminar()
  actualizarNumeros()

  if (agregarBtn) {
    const nuevoBtn = agregarBtn.cloneNode(true)
    agregarBtn.parentNode.replaceChild(nuevoBtn, agregarBtn)

    nuevoBtn.addEventListener('click', () => {
      const nuevaFila = document.createElement('tr')
      nuevaFila.classList.add('fila-producto')
      nuevaFila.innerHTML = `
                <td class="numero-fila px-3 py-2 border text-center"></td>
                <td class="px-3 py-2 border">
                    <input type="text" name="producto[]" class="w-full px-2 py-1 border rounded" placeholder="Nombre del producto">
                </td>
                <td class="px-3 py-2 border">
                    <input type="number" name="cantidad[]" class="w-full px-2 py-1 border rounded cantidad" min="1" value="1">
                </td>
                <td class="px-3 py-2 border text-center">
                    <button type="button" class="eliminar-fila text-red-600 hover:text-red-800" title="Eliminar fila">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </button>
                </td>
            `
      tabla.appendChild(nuevaFila)
      asignarEventosFila(nuevaFila)
      actualizarNumeros()
      actualizarBotonesEliminar()
    })
  }

  loadRazonSocialProv('ProvSelectSinCotizar')

  const formulario = document.getElementById('form-upload-sin-cotizar')
  if (formulario) {
    formulario.addEventListener('submit', SendData)
  }
}
async function initSolicitarServicio() {
  const tabla = document.getElementById('tabla-servicios')
  const agregarBtn = document.getElementById('agregar-fila-servicio')
  const subtotalTd = document.getElementById('subtotal-servicio')
  const totalTd = document.getElementById('total-servicio')
  const chkIVA = document.getElementById('agregar-iva-servicio')

  if (!tabla) return

  let serviceRowHtml = null
  async function getServiceRowHtml() {
    if (serviceRowHtml === null) {
      try {
        const response = await fetch(`${BASE_URL}modales/vistas/service_row`)
        if (!response.ok) throw new Error('Falló la carga de la fila de servicio')
        serviceRowHtml = await response.text()
      } catch (error) {
        console.error(error)
        serviceRowHtml =
          '<tr><td colspan="4" class="text-red-500 p-2">Error al cargar fila.</td></tr>'
      }
    }
    return serviceRowHtml
  }

  function actualizarNumeros() {
    tabla.querySelectorAll('tr').forEach((fila, i) => {
      const celdaNumero = fila.querySelector('.numero-fila-servicio')
      if (celdaNumero) celdaNumero.textContent = i + 1
    })
  }

  function actualizarBotonesEliminar() {
    const filas = tabla.querySelectorAll('tr')
    filas.forEach((fila) => {
      const btnEliminar = fila.querySelector('.eliminar-fila-servicio')
      if (btnEliminar) {
        btnEliminar.style.display = filas.length === 1 ? 'none' : 'inline-block'
      }
    })
  }

  function actualizarTotal() {
    let suma = 0
    tabla.querySelectorAll('tr').forEach((fila) => {
      const costoInput = fila.querySelector('.costo-servicio')
      if (costoInput) {
        const valor = parseFloat(costoInput.value) || 0
        suma += valor
      }
    })

    if (subtotalTd) subtotalTd.textContent = '$' + suma.toFixed(2)

    let total = suma
    if (chkIVA && chkIVA.checked) total = suma * 1.16
    if (totalTd) totalTd.textContent = '$' + total.toFixed(2)
  }

  function asignarEventosFila(fila) {
    if (!fila) return

    const costoInput = fila.querySelector('.costo-servicio')
    const eliminarBtn = fila.querySelector('.eliminar-fila-servicio')

    if (costoInput) costoInput.addEventListener('input', actualizarTotal)

    if (eliminarBtn) {
      eliminarBtn.addEventListener('click', () => {
        if (tabla.querySelectorAll('tr').length > 1) {
          fila.remove()
          actualizarNumeros()
          actualizarBotonesEliminar()
          actualizarTotal()
        }
      })
    }

    actualizarTotal()
  }

  tabla.querySelectorAll('tr').forEach((fila) => asignarEventosFila(fila))
  actualizarBotonesEliminar()
  actualizarNumeros()
  actualizarTotal()

  if (chkIVA) chkIVA.addEventListener('change', actualizarTotal)

  if (agregarBtn) {
    const nuevoBtn = agregarBtn.cloneNode(true)
    agregarBtn.parentNode.replaceChild(nuevoBtn, agregarBtn)

    nuevoBtn.addEventListener('click', async () => {
      const rowHtml = await getServiceRowHtml()
      const nuevaFila = tabla.insertRow()
      nuevaFila.innerHTML = rowHtml
      asignarEventosFila(nuevaFila)
      actualizarNumeros()
      actualizarBotonesEliminar()
      actualizarTotal()
    })
  }

  loadRazonSocialProv('razonSocialServicioSelect')

  const formulario = document.getElementById('form-servicio-upload')
  if (formulario) {
    formulario.addEventListener('submit', function (e) {
      const costos = tabla.querySelectorAll('.costo-servicio')
      let valido = true

      costos.forEach((input) => {
        const valor = parseFloat(input.value)

        // Buscar si ya existe un mensaje de error debajo
        let errorMsg = input.parentNode.querySelector('.error-msg')
        if (!errorMsg) {
          errorMsg = document.createElement('p')
          errorMsg.classList.add('error-msg', 'text-red-500', 'text-sm', 'mt-1')
          input.parentNode.appendChild(errorMsg)
        }

        if (isNaN(valor) || valor <= 0) {
          valido = false
          input.classList.add('border-red-500')
          errorMsg.textContent = 'El costo debe ser mayor a 0'
        } else {
          input.classList.remove('border-red-500')
          errorMsg.textContent = ''
        }
      })

      if (!valido) {
        e.preventDefault()
        // No usamos alert(), el mensaje se muestra debajo de los inputs
      } else {
        SendData(e)
      }
    })
  }
}
function mostrarSubmenuMaterial() {
  document.getElementById('seleccion-opcion').classList.add('hidden')
  document.getElementById('submenu-material').classList.remove('hidden')
}
function mostrarSolicitarMaterialCotizado() {
  document.getElementById('submenu-material').classList.add('hidden')
  document.getElementById('solicitar-material-content').classList.remove('hidden')
  initSolicitarMaterial()
}
function mostrarSolicitarMaterialSinCotizar() {
  document.getElementById('submenu-material').classList.add('hidden')
  document.getElementById('solicitar-material-sin-cotizar').classList.remove('hidden')
  initSolicitarMaterialSinCotizar()
}
function mostrarSolicitarServicio() {
  document.getElementById('seleccion-opcion').classList.add('hidden')
  document.getElementById('solicitar-servicio-content').classList.remove('hidden')
  initSolicitarServicio()
}
function regresarSeleccionOpciones() {
  document.getElementById('submenu-material').classList.add('hidden')
  document.getElementById('solicitar-material-content').classList.add('hidden')
  document.getElementById('solicitar-material-sin-cotizar').classList.add('hidden')
  document.getElementById('solicitar-servicio-content').classList.add('hidden')
  document.getElementById('seleccion-opcion').classList.remove('hidden')
}
function regresarSubmenuMaterial() {
  document.getElementById('solicitar-material-content').classList.add('hidden')
  document.getElementById('solicitar-material-sin-cotizar').classList.add('hidden')
  document.getElementById('submenu-material').classList.remove('hidden')
}

/**
 * Lógica para el modal "Ver Historial"
 */
function initPaginacionHistorial() {
  const tabla = document.getElementById('tabla-historial');
  if (!tabla) return;

  // Mostrar la opción de filtro "Aprobacion Pendiente" solo si es un jefe
  const opcionPendiente = document.getElementById('filtro-pendiente-aprobacion');
  if (opcionPendiente && typeof USER_LOGIN_TYPE !== 'undefined' && USER_LOGIN_TYPE === 'boss') {
    opcionPendiente.classList.remove('hidden');
  }

  function getStatususSVG(statusus) {
    if (!statusus) return '';
    const statususLower = statusus.toLowerCase();
    const iconUrl = `/icons/icons.svg?v=${window.ICON_SVG_VERSION || new Date().getTime()}`;
    let svgClass = '';
    let iconId = '';

    switch (statususLower) {
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
      case 'cotizando':
        svgClass = 'text-blue-500';
        iconId = 'cotizacion';
        break;
      case 'en revision':
        svgClass = 'text-blue-500';
        iconId = 'revision';
        break;
      case 'aprobacion pendiente':
        svgClass = 'text-orange-500';
        iconId = 'pendiente';
        break;
      default:
        return '';
    }
    return `<svg class="${svgClass} mx-auto size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#${iconId}"></use></svg>`;
  }

  const exceptions = ['Compras', 'Administración'];
  let url = 'api/historic';
  if (
    typeof USER_DEPT_NAME !== 'undefined' &&
    typeof USER_DEPT_ID !== 'undefined' &&
    USER_DEPT_ID &&
    !exceptions.includes(USER_DEPT_NAME)
  ) {
    url = `api/historic/department/${USER_DEPT_ID}`;
  }

  createPaginatedTable({
    tableSelector: '#tabla-historial tbody',
    paginationSelector: 'paginacion-historial',
    endpoint: `${BASE_URL}${url}`,
    filterFormSelector: '#modal-contenido', // Container for filters
    renderRow: (item) => {
      const status = item.Estado == 'Dept_Rechazada' ? 'Rechazada' : item.Estado;
      const svg = getStatususSVG(status);
      return `
        <tr class="text-center">
            <td class="hidden border px-4 py-2">${item.ID_Solicitud}</td>
            <td class="border px-4 py-2">${item.No_Folio || 'N/A'}</td>
            <td class="border px-4 py-2 col-fecha">${item.Fecha}</td>
            <td class="border px-4 py-2">${item.DepartamentoNombre || 'N/A'}</td>
            <td class="border px-4 py-2 col-estado" data-estado="${status}" title="${status}">
                ${svg}
                <span >${status}</span>
            </td>
            <td class="border px-4 py-2">
                <a href="#" class="text-blue-600 hover:underline" onclick="mostrarVerHistorial(${item.ID_Solicitud}); return false;">ver</a>
            </td>
        </tr>
      `;
    },
    filterFunction: (allData, form) => {
      const fechaFiltro = form.querySelector('#filtro-fecha').value;
      const filtrarPorMes = form.querySelector('#filtrar-por-mes').checked;
      const estadoFiltro = form.querySelector('#filtro-estado').value;
      const departamentoFiltro = form.querySelector('#filtroDepartamento')?.value || '';

      return allData.filter((item) => {
        const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro;
        const coincideDepartamento = !departamentoFiltro || item.DepartamentoNombre === departamentoFiltro;

        if (!fechaFiltro) {
          return coincideEstado && coincideDepartamento;
        }

        const fechaItem = item.Fecha; // formato esperado: "2025-10-08"
        if (filtrarPorMes) {
          const mesFiltro = fechaFiltro.slice(0, 7);
          const mesItem = fechaItem.slice(0, 7);
          return mesItem === mesFiltro && coincideEstado && coincideDepartamento;
        } else {
          return fechaItem === fechaFiltro && coincideEstado && coincideDepartamento;
        }
      });
    }
  });
}


// Funciones para mostrar/ocultar la pantalla de ver historial
async function mostrarVerHistorial(idSolicitud) {
  const divHistorial = document.getElementById('div-historial')
  if (divHistorial) divHistorial.classList.add('hidden')

  const divVer = document.getElementById('div-ver-historial')
  if (divVer) divVer.classList.remove('hidden')

  const detallesContainer = document.getElementById('detalles-historial-solicitud')
  if (!detallesContainer) {
    console.error('El contenedor de detalles del historial no fue encontrado.')
    return
  }

  detallesContainer.innerHTML = '<p class="text-center text-gray-500">Cargando detalles...</p>'

  try {
    const response = await fetch(`${BASE_URL}api/solicitud/details/${idSolicitud}`)
    if (!response.ok) {
      throw new Error(`Error ${response.statusus}: ${response.statususText}`)
    }
    const data = await response.json()

    if (data.error) {
      throw new Error(data.error)
    }

    let estadoClass = getStatus(data.Estado)

    let html = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
                <div><strong>Fecha:</strong> ${data.Fecha}</div>
                <div><strong>Estado:</strong> <span class="font-semibold ${estadoClass}">${data.Estado === 'Dept_Rechazada' ? 'Rechazada' : data.Estado || 'N/A'}</span></div>
                <div><strong>Usuario:</strong> ${data.UsuarioNombre}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre + ' ' + data.ID_Place}</div>
                <div><strong>Complejo:</strong> ${data.Complejo}</div>
                <div><strong>Proveedor (Cotización):</strong> ${data.cotizacion?.ProveedorNombre || 'N/A'}</div>
                ${data.cotizacion?.Total ? `<div class="md:col-span-3"><strong>Monto (Cotización):</strong> <span class="font-bold text-lg">${parseFloat(data.cotizacion.Total).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</span></div>` : ''}
            </div>
        `

    if (data.ComentariosAdmin) {
      html += `
            <div class="mb-6 p-4 border rounded-lg bg-red-50 border-red-200">
                <h4 class="text-md font-bold text-red-700 mb-2">Comentarios / Motivo del Rechazo</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
            </div>`
    }
    html += `
            <h4 class="text-md font-bold mb-2">Productos Solicitados</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">Código</th>
                            <th class="py-2 px-4 text-left">Producto</th>
                            <th class="py-2 px-4 text-right">Cantidad</th>
                            <th class="py-2 px-4 text-right">Importe</th>
                            <th class="py-2 px-4 text-right">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `
    data.productos.forEach((p) => {
      const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
      html += `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t">${p.Codigo}</td>
                    <td class="py-2 px-4 border-t">${p.Nombre}</td>
                    <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                    <td class="py-2 px-4 border-t text-right">${parseFloat(p.Importe).toFixed(2)}</td>
                    <td class="py-2 px-4 border-t text-right">${costoTotal}</td>
                </tr>
            `
    })

    html += `
                    </tbody>
                </table>   
            </div>
        `
    if (data.ComentariosUser) {
      html += `
            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p>
            </div>`
    }
    if (data.Archivo) {
      const archivoUrl = `${BASE_URL}solicitudes/archivo/${idSolicitud}`
      html += `
                <div class="mt-6">
                    <h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                    <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.Archivo}</a>
                </div>
            `
    }

    detallesContainer.innerHTML = html
  } catch (error) {
    console.error('Error al cargar detalles del historial:', error)
    detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

function getStatus(status) {
  switch (status?.toLowerCase()) {
    case 'aprobada':
      return 'text-green-600'
    case 'dept_rechazada':
    case 'rechazada':
      return 'text-red-600'
    case 'en revision':
      return 'text-blue-600'
      break
    case 'cotizando':
      return 'text-purple-600'
    case 'aprobacion pendiente':
    case 'en espera':
      return 'text-yellow-600'
    default:
      return 'text-gray-600'
  }
}

function regresarHistorial() {
  const divVer = document.getElementById('div-ver-historial')
  if (divVer) divVer.classList.add('hidden')

  const divHistorial = document.getElementById('div-historial')
  if (divHistorial) divHistorial.classList.remove('hidden')

  console.log('Regresando a la tabla de historial')
}

/**
 * Lógica para el modal "Usuarios"
 */
function initUsuarios() {
  const modalContenido = document.getElementById('modal-contenido')
  if (!modalContenido) return

  const form = modalContenido.querySelector('#form-register')
  const mensajeDiv = modalContenido.querySelector('#mensaje')

  loadDepartamentos()

  if (!form) {
    console.warn('initUsuarios: no se encontró #form-register dentro del modal')
    return
  }

  if (form.dataset.init === '1') return
  form.dataset.init = '1'

  form.addEventListener('submit', async function (e) {
    e.preventDefault()

    if (mensajeDiv) {
      mensajeDiv.textContent = ''
      mensajeDiv.classList.remove('text-green-600', 'text-red-600')
    }

    const submitBtn = form.querySelector('button[type="submit"]')
    const prevBtnHtml = submitBtn ? submitBtn.innerHTML : null
    if (submitBtn) {
      submitBtn.disabled = true
      submitBtn.innerHTML = 'Guardando...'
    }

    try {
      const formData = new FormData(form)
      const resp = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      })

      const text = await resp.text()
      let data
      try {
        data = JSON.parse(text)
      } catch (err) {
        console.error('Respuesta no JSON recibida al registrar usuario:', text)
        if (mensajeDiv)
          mensajeDiv.innerHTML =
            '<span class="text-red-600">Error: respuesta inesperada del servidor.</span>'
        return
      }

      if (data.success) {
        if (mensajeDiv) {
          mensajeDiv.innerHTML = `<span class="text-green-600">${data.message || 'Registro correcto.'}</span>`
        }
        form.reset()
        form.querySelector('input, select, textarea')?.focus()
      } else {
        if (mensajeDiv) {
          mensajeDiv.innerHTML = `<span class="text-red-600">${data.message || 'Error al registrar usuario.'}</span>`
        }
      }
    } catch (err) {
      console.error('Error en la solicitud:', err)
      if (mensajeDiv)
        mensajeDiv.innerHTML = `<span class="text-red-600">Error en la solicitud: ${err.message}</span>`
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false
        submitBtn.innerHTML = prevBtnHtml
      }
    }
  })
}

/**
 * Lógica para el modal "Revisar Solicitudes"
 */
function initRevisarSolicitud() {
  setupClientSideTable({
    rowsSelector: '#tablaRevisarSolicitud tbody tr',
    paginationSelector: 'paginacion-enviar-revision',
    rowsPerPage: 10
  });
}

async function mostrarVer(idSolicitud) {
  const divTabla = document.getElementById('div-tabla')
  const divVer = document.getElementById('div-ver')
  const detallesContainer = document.getElementById('detalles-solicitud')

  if (!divTabla || !divVer || !detallesContainer) {
    console.error('Elementos del DOM no encontrados para mostrar detalles.')
    return
  }

  divTabla.classList.add('hidden')
  divVer.classList.remove('hidden')
  detallesContainer.innerHTML = '<p class="text-center text-gray-500">Cargando detalles...</p>'

  try {
    const response = await fetch(`${BASE_URL}api/solicitud/details/${idSolicitud}`)
    if (!response.ok) {
      throw new Error(`Error ${response.statusus}: ${response.statususText}`)
    }
    const data = await response.json()

    if (data.error) {
      throw new Error(data.error)
    }

    const iva = data.IVA === 't'

    let html = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
                <div><strong>Fecha:</strong> ${data.Fecha}</div>
                <div><strong>Estado:</strong> <span class="font-semibold ${getStatus(data.Estado)}">${data.Estado || 'N/A'}</span></div>
                <div><strong>Solicitante:</strong> ${data.UsuarioNombre}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre}</div>
                <div><strong>Complejo:</strong> ${data.Complejo}</div>
                <div><strong>Proveedor:</strong> ${data.RazonSocialNombre || 'N/A'}</div>
            </div>
            ${data.Tipo == 2 ? '<h4 class="text-md font-bold mb-2">Servicios Solicitados</h4>' : '<h4 class="text-md font-bold mb-2">Productos Solicitados</h4>'}
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">Código/SKU</th>
                             ${data.Tipo == 2 ? '<th class="py-2 px-4 text-left">Servicio</th>' : '<th class="py-2 px-4 text-left">Producto</th>'}
                            ${data.Tipo == 2 ? '' : '<th class="py-2 px-4 text-right">Cantidad</th>'}
                            <th class="py-2 px-4 text-right">Importe</th>
                            ${iva ? '<th class="py-2 px-4 text-right">IVA</th>' : ''}
                            ${data.Tipo == 2 ? '' : '<th class="py-2 px-4 text-right">Costo Total</th>'}
                        </tr>
                    </thead>
                    <tbody>
        `

    data.productos.forEach((p) => {
      const costoTotal = iva ? 1.16 * (p.Cantidad * p.Importe) : p.Cantidad * p.Importe
      html += `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'} </td>
                    <td class="py-2 px-4 border-t">${p.Nombre}</td>
                    ${data.Tipo == 2 ? '' : `<td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>`}
                    <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                    ${iva ? `<td class="py-2 px-4 border-t text-right">$${parseFloat(0.16 * p.Importe).toFixed(2)}</td>` : ''}
                    ${data.Tipo == 2 ? '' : `<td class="py-2 px-4 border-t text-right">$${parseFloat(costoTotal).toFixed(2)}</td>`}
                </tr>
            `
    })

    html += `
                    </tbody>
                </table>
            </div>
        `
    // poner comentario aqui
    if (data.ComentariosUser) {
      html += `
            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p>
            </div>`
    }

    if (data.Archivo) {
      // Usamos la nueva ruta segura que creamos para descargar el archivo
      const archivoUrl = `${BASE_URL}solicitudes/archivo/${idSolicitud}`
      html += `
                <div class="mt-6">
                    <h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                    <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.Archivo}</a>
                </div>
            `
    }

    html += `
              <div class="mt-6">
                <h4 class="text-md font-bold mb-2">Acciones</h4>
                <button onclick="mostrarVerPdf(${idSolicitud})" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Ver PDF</button>
            </div>
            `

    detallesContainer.innerHTML = html
  } catch (error) {
    console.error('Error al cargar detalles de la solicitud:', error)
    detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

async function mostrarCotizar(idSolicitud) {
  document.getElementById('div-tabla').classList.add('hidden')
  const divCotizar = document.getElementById('div-cotizar')
  divCotizar.classList.remove('hidden')

  const idSolicitudInput = document.getElementById('cotizar_id_solicitud')
  if (idSolicitudInput) {
    idSolicitudInput.value = idSolicitud
  }

  const tbody = divCotizar.querySelector('tbody')
  const paginacionDiv = divCotizar.querySelector('#paginacion-proveedores')
  const btnGenerar = document.getElementById('btn-generar-cotizacion')
  const inputBusqueda = document.getElementById('buscar-proveedor')

  if (btnGenerar) btnGenerar.disabled = true

  tbody.innerHTML =
    '<tr><td colspan="4" class="text-center text-gray-500">Cargando proveedores...</td></tr>'

  try {
    const response = await getData('providers/all')
    const response2 = await getData(`solicitud/details/${idSolicitud}`)

    let todosLosProveedores = response

    if (!todosLosProveedores.length) {
      tbody.innerHTML =
        '<tr><td colspan="4" class="text-center text-gray-500">No hay proveedores registrados.</td></tr>'
      return
    }

    let proveedoresFiltrados = [...todosLosProveedores]

    const filasPorPagina = 10
    let paginaActual = 1

    function renderizarTabla() {
      const totalPaginas = Math.ceil(proveedoresFiltrados.length / filasPorPagina) || 1
      paginaActual = Math.min(paginaActual, totalPaginas)

      const start = (paginaActual - 1) * filasPorPagina
      const end = start + filasPorPagina

      tbody.innerHTML = proveedoresFiltrados
        .slice(start, end)
        .map(
          (p) => `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t text-center">
                        <input type="radio" name="proveedor_seleccionado" value="${p.ID_Proveedor}" class="radio-proveedor accent-blue-600">
                    </td>
                    <td class="py-2 px-4 border-t">${p.RazonSocial}</td>
                    <td class="py-2 px-4 border-t">${p.Tel_Contacto}</td>
                    <td class="py-2 px-4 border-t">${p.RFC}</td>
                </tr>
            `,
        )
        .join('')

      tbody.querySelectorAll('.radio-proveedor').forEach((radio) => {
        radio.addEventListener('change', () => {
          if (btnGenerar) btnGenerar.disabled = false
        })
      })

      renderizarPaginacion()
    }

    function renderizarPaginacion() {
      if (!paginacionDiv) return
      paginacionDiv.innerHTML = ''
      const totalPaginas = Math.ceil(proveedoresFiltrados.length / filasPorPagina)
      if (totalPaginas <= 1) return

      for (let i = 1; i <= totalPaginas; i++) {
        const boton = document.createElement('button')
        boton.textContent = i
        boton.className = `px-3 py-1 border rounded ${i === paginaActual ? 'bg-blue-500 text-white' : 'bg-white text-black'}`
        boton.addEventListener('click', () => {
          paginaActual = i
          renderizarTabla()
        })
        paginacionDiv.appendChild(boton)
      }
    }

    function filtrarProveedores() {
      const termino = inputBusqueda.value.toLowerCase()
      proveedoresFiltrados = todosLosProveedores.filter((p) =>
        p.RazonSocial.toLowerCase().includes(termino),
      )
      paginaActual = 1
      renderizarTabla()
    }

    inputBusqueda.addEventListener('input', filtrarProveedores)
    if (response2.RazonSocialNombre) {
      inputBusqueda.value = response2.RazonSocialNombre
      filtrarProveedores()
    }

    renderizarTabla()
  } catch (error) {
    console.error('Error al cargar proveedores:', error)
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500">Error al cargar proveedores</td></tr>`
  }

  if (btnGenerar && !btnGenerar.dataset.listenerAttached) {
    btnGenerar.addEventListener('click', handleGenerarCotizacion)
    btnGenerar.dataset.listenerAttached = 'true'
  }

  console.log('COTIZAR solicitud ID:', idSolicitud)
}

async function handleGenerarCotizacion() {
  const idSolicitud = document.getElementById('cotizar_id_solicitud').value
  const selectedProviderRadio = document.querySelector(
    'input[name="proveedor_seleccionado"]:checked',
  )

  if (!selectedProviderRadio) {
    alert('Por favor, seleccione un proveedor.')
    return
  }

  const idProveedor = selectedProviderRadio.value

  if (
    !confirm('¿Está seguro de que desea generar la solicitud de cotización para este proveedor?')
  ) {
    return
  }

  const btn = document.getElementById('btn-generar-cotizacion')
  btn.disabled = true
  btn.textContent = 'Generando...'

  try {
    const response = await fetch(`${BASE_URL}api/cotizacion/crear`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        ID_Solicitud: idSolicitud,
        ID_Proveedor: idProveedor,
      }),
    })

    const result = await response.json()

    if (result.success) {
      alert('Solicitud de cotización generada y estado de la solicitud actualizado.')
      // Refresh the modal content to see the updated list of pending requests
      abrirModal('revisar_solicitudes')
    } else {
      alert('Error: ' + (result.message || 'No se pudo generar la cotización.'))
      btn.disabled = false
      btn.textContent = 'Generar Solicitud de Cotización'
    }
  } catch (error) {
    console.error('Error al generar cotización:', error)
    alert('Ocurrió un error de red al generar la cotización.')
    btn.disabled = false
    btn.textContent = 'Generar Solicitud de Cotización'
  }
}

function regresarTabla() {
  document.getElementById('div-ver').classList.add('hidden')
  document.getElementById('div-cotizar').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
}

/**
 * Lógica para el modal "Registrar Material" (Almacén)
 */
function initRegistrarMaterial() {
  const form = document.getElementById('formRegistrarProducto')
  if (!form) {
    console.warn('No se encontró el formulario de registrar material')
    return
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault()
    const formData = new FormData(form)

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          alert('Producto registrado correctamente')
          form.reset()
        } else {
          const errorMsg = data.errors
            ? Object.values(data.errors).join('\n')
            : data.message || 'Error desconocido'
          alert('Error al registrar producto:\n' + errorMsg)
        }
      })
      .catch((err) => {
        console.error(err)
        alert('Error al procesar la solicitud')
      })
  })
}

/**
 * Lógica para el modal "Enviar a Revisión"
 */
function initEnviarRevision() {
  if (!document.getElementById('tabla-enviar')) return;

  createPaginatedTable({
    tableSelector: '#tabla-enviar tbody',
    paginationSelector: 'paginacion-enviar-revision',
    endpoint: `${BASE_URL}api/solicitudes/cotizadas`,
    processData: (data) => data.filter((s) => s.Estado !== 'En revision'),
    noResultsMessage: 'No hay solicitudes cotizadas para mostrar.',
    renderRow: (s) => {
      const monto = parseFloat(s.Monto || 0).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN',
      });
      return `
        <tr class="hover:bg-gray-50" data-id="${s.ID_Solicitud}">
            <td class="py-3 px-6 text-left">${s.Folio}</td>
            <td class="py-3 px-6 text-left">${s.Usuario || 'N/A'}</td>
            <td class="py-3 px-6 text-left">${s.Departamento || 'N/A'}</td>
            <td class="py-3 px-6 text-left">${s.Proveedor || 'N/A'}</td>
            <td class="py-3 px-6 text-left">${monto}</td>
            <td class="py-3 px-6 text-left">${s.Estado}</td>
            <td class="py-3 px-6 text-left">
                <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded btn-enviar" onclick="enviarRevisionHandler(event)">
                    Ver
                </button>
            </td>
        </tr>
      `;
    }
  });
}


async function enviarRevisionHandler(event) {
  const fila = event.target.closest('tr')
  const idSolicitud = fila.dataset.id

  const divTabla = document.getElementById('div-tabla-enviar')
  const divRevision = document.getElementById('div-enviar-revision')
  const detallesContainer = document.getElementById('detalles-para-revision')
  const form = document.getElementById('form-enviar-revision')
  const btnConfirmar = document.getElementById('btn-confirmar-revision')

  // Mostrar div revision
  divTabla.classList.add('hidden')
  divRevision.classList.remove('hidden')
  detallesContainer.innerHTML = '<p class="text-center">Cargando detalles...</p>'

  try {
    const response = await fetch(`${BASE_URL}api/solicitud/details/${idSolicitud}`)
    if (!response.ok) throw new Error('No se pudieron cargar los detalles.')
    const data = await response.json()
    let estadoClass = getStatus(data.Estado)
    const monto = parseFloat(data.cotizacion?.Total || 0).toLocaleString('es-MX', {
      style: 'currency',
      currency: 'MXN',
    })
    let html = `
       <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
                <div><strong>Fecha:</strong> ${data.Fecha}</div>
                <div><strong>Estado:</strong> <span class="font-semibold ${estadoClass}">${data.Estado === 'Dept_Rechazada' ? 'Rechazada' : data.Estado || 'N/A'}</span></div>
                <div><strong>Usuario:</strong> ${data.UsuarioNombre}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre}</div>
                <div><strong>Complejo:</strong> ${data.Complejo}</div>
                <div><strong>Proveedor (Cotización):</strong> ${data.cotizacion?.ProveedorNombre || 'N/A'}</div>
                ${data.cotizacion?.Total ? `<div class="md:col-span-3"><strong>Monto (Cotización):</strong> <span class="font-bold text-lg">${parseFloat(data.cotizacion.Total).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })}</span></div>` : ''}
            </div>
    `
    if (data.ComentariosAdmin) {
      html += `
            <div class="mb-6 p-4 border rounded-lg bg-red-50 border-red-200">
                <h4 class="text-md font-bold text-red-700 mb-2">Comentarios / Motivo del Rechazo</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
            </div>`
    }
    html += `
            <h4 class="text-md font-bold mb-2">Productos Solicitados</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">Código</th>
                            <th class="py-2 px-4 text-left">Producto</th>
                            <th class="py-2 px-4 text-right">Cantidad</th>
                            <th class="py-2 px-4 text-right">Importe</th>
                            <th class="py-2 px-4 text-right">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `
    data.productos.forEach((p) => {
      const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
      html += `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t">${p.Codigo}</td>
                    <td class="py-2 px-4 border-t">${p.Nombre}</td>
                    <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                    <td class="py-2 px-4 border-t text-right">${parseFloat(p.Importe).toFixed(2)}</td>
                    <td class="py-2 px-4 border-t text-right">${costoTotal}</td>
                </tr>
            `
    })

    html += `
                    </tbody>
                </table>   
            </div>
        `
    if (data.ComentariosUser) {
      html += `
            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p>
            </div>`
    }
    console.log(data.Archivo)
    if (data.Archivo) {
      const archivoUrl = `${BASE_URL}solicitudes/archivo/${idSolicitud}`
      html += `
                <div class="mt-6">
                    <h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                    <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.Archivo}</a>
                </div>
            `
    }
    html += `
              <div class="mt-6">
                <button onclick="mostrarVerPdf(${idSolicitud})" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Ver PDF</button>
            </div>
            `

    detallesContainer.innerHTML = html
  } catch (error) {
    detallesContainer.innerHTML = `<p class="text-red-500 text-center">${error.message}</p>`
  }

  form.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData()
    formData.append('ID_Solicitud', idSolicitud)

    const archivos = document.getElementById('archivos-revision').files
    for (let i = 0; i < archivos.length; i++) {
      formData.append('archivos[]', archivos[i])
    }

    const tipoPago = document.querySelector('input[name="tipo_pago"]:checked');
    if (!tipoPago) {
      mostrarNotificacion('Por favor, seleccione un tipo de pago.', 'error');
      return;
    }
    formData.append('tipo_pago', tipoPago.value);

    btnConfirmar.disabled = true
    btnConfirmar.textContent = 'Enviando...'

    try {
      const response = await fetch(`${BASE_URL}api/solicitud/enviar-revision`, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })

      const result = await response.json()

      if (result.success) {
        mostrarNotificacion(result.message || 'Solicitud enviada a revisión.', 'success')
        regresarEnviarRevision()
        initEnviarRevision() // refrescar tabla
      } else {
        mostrarNotificacion(result.message || 'Error al enviar a revisión.', 'error')
      }
    } catch (error) {
      console.error('Error:', error)
      mostrarNotificacion('Error de red al enviar a revisión.', 'error')
    } finally {
      btnConfirmar.disabled = false
      btnConfirmar.textContent = 'Confirmar y Enviar'
    }
  }
}

function regresarEnviarRevision() {
  document.getElementById('div-tabla-enviar').classList.remove('hidden')
  document.getElementById('div-enviar-revision').classList.add('hidden')
}

/**
 * Lógica para el modal "Dictamen de Solicitudes"
 */
async function initDictamenSolicitudes() {
  if (!document.getElementById('tablaDictamenSolicitudes')) return;

  createPaginatedTable({
    tableSelector: '#tablaDictamenSolicitudes',
    paginationSelector: 'paginacion-dictamen',
    endpoint: `${BASE_URL}api/solicitudes/en-revision`,
    noResultsMessage: 'No hay solicitudes en dictamen para mostrar.',
    renderRow: (s) => `
      <tr class="hover:bg-gray-50" data-id="${s.ID}">
          <td class="py-3 px-6 text-left">${s.Folio || 'N/A'}</td>
          <td class="py-3 px-6 text-left">${s.Usuario || 'N/A'}</td>
          <td class="py-3 px-6 text-left">${s.Departamento || 'N/A'}</td>
          <td class="py-3 px-6 text-left">${s.Fecha}</td>
          <td class="py-3 px-6 text-left">${s.Estado}</td>
          <td class="py-3 px-6 text-left text-blue-600 cursor-pointer" onclick="mostrarVerDictamen(${s.ID})">VER</td>
      </tr>
    `
  });
}


/**
 * Genera el HTML para mostrar los detalles de una solicitud.
 * @param {object} data - Objeto con los datos de la solicitud.
 * @returns {string} - Cadena de texto con el HTML.
 */
function generarDetallesSolicitudHTML(data) {
  const montoFormateado = parseFloat(data.cotizacion?.Total || 0).toLocaleString('es-MX', {
    style: 'currency',
    currency: 'MXN',
  })
  const metodoPago = data.MetodoPago == 0 ? 'Efectivo' : 'Crédito'

  return `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
            <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
            <div><strong>Fecha:</strong> ${data.Fecha}</div>
            <div><strong>Estado:</strong> <span class="font-semibold text-blue-600">${data.Estado}</span></div>
            <div><strong>Usuario:</strong> ${data.UsuarioNombre}</div>
            <div><strong>Departamento:</strong> ${data.DepartamentoNombre + ' - ' + data.ID_Place}</div>
            <div><strong>Complejo:</strong> ${data.Complejo}</div>
            <div><strong>Proveedor:</strong> ${data.cotizacion?.ProveedorNombre || 'N/A'}</div>
            <div><strong>Metodo de Pago:</strong> ${metodoPago}</div>
            <div class="md:col-span-3"><strong>Monto Total (Cotización):</strong> <span class="font-bold text-lg">${montoFormateado}</span></div>
        </div>
    `
}

async function mostrarVerDictamen(idSolicitud) {
  document.getElementById('div-tabla').classList.add('hidden')
  const divVer = document.getElementById('div-ver-dictamen')
  divVer.classList.remove('hidden')

  const detallesContainer = document.getElementById('detallesDictamen')
  detallesContainer.innerHTML = `<p class="text-center text-gray-500">Cargando detalles de la solicitud ${idSolicitud}...</p>`

  try {
    const response = await fetch(`${BASE_URL}api/cotizacion/details/${idSolicitud}`)
    if (!response.ok) throw new Error(`Error ${response.statusus}: ${response.statususText}`)

    const data = await response.json()
    if (data.error) throw new Error(data.error)

    let html = generarDetallesSolicitudHTML(data)

    // Mostrar comentarios si existen (especialmente para rechazos)
    if (data.ComentariosAdmin) {
      html += `
            <div class="mt-6 p-4 border rounded-lg bg-red-50 border-red-200">
                <h4 class="text-md font-bold text-red-700 mb-2">Motivo del Rechazo</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
            </div>`
    }

    html += `
            <h4 class="text-md font-bold mb-2">Productos Solicitados</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">Código</th>
                            <th class="py-2 px-4 text-left">Producto</th>
                            <th class="py-2 px-4 text-right">Cantidad</th>
                            <th class="py-2 px-4 text-right">Importe</th>
                            <th class="py-2 px-4 text-right">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `

    data.productos.forEach((p) => {
      const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
      html += `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t">${p.Codigo}</td>
                    <td class="py-2 px-4 border-t">${p.Nombre}</td>
                    <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                    <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                    <td class="py-2 px-4 border-t text-right">$${costoTotal}</td>
                </tr>
            `
    })

    html += `
                    </tbody>
                </table>
            </div>
        `

    if (data.Archivo) {
      const archivoUrl = `${BASE_URL}solicitudes/archivo/${idSolicitud}`
      html += `
                <div class="mt-6">
                    <h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                    <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.Archivo}</a>
                </div>
            `
    }

    if (data.cotizacion && data.cotizacion.Cotizacion_Files) {
      const listaDeArchivos = data.cotizacion.Cotizacion_Files.split(',')
      html += `
        <div class="mt-6">
            <h4 class="text-md font-bold mb-2">Cotizaciones adjuntas</h4>
    `
      listaDeArchivos.forEach((nombreDeArchivo) => {
        const filec = nombreDeArchivo.trim()

        if (filec) {
          const archivoUrl = `${BASE_URL}cotizaciones/archivo/${idSolicitud}/${filec}`
          html += `
                <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline block mb-1">${filec}</a>
            `
        }
      })
      html += `
        </div>
    `
    }

    // Solo mostrar botones de acción si la solicitud está 'En revision'
    if (data.Estado === 'En revision') {
      html += `
                <div class="mt-8 flex justify-end space-x-4">
                    <button onclick="mostrarVerPdf(${idSolicitud}, 1)" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Ver PDF
                    </button>
                    <button onclick="dictaminarSolicitud(${idSolicitud}, 'Rechazada')" class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition">
                        Rechazar
                    </button>
                    <button onclick="dictaminarSolicitud(${idSolicitud}, 'Aprobada')" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                        Aprobar
                    </button>
                </div>
            `
    }

    detallesContainer.innerHTML = html
  } catch (error) {
    console.error('Error al cargar detalles para dictamen:', error)
    detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

function regresarTablaDictamen() {
  document.getElementById('div-ver-dictamen').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
}

async function dictaminarSolicitud(idSolicitud, nuevoEstado) {
  let comentarios = null
  const accion = nuevoEstado === 'Aprobada' ? 'aprobar' : 'rechazar'

  if (nuevoEstado === 'Rechazada') {
    comentarios = prompt('Por favor, ingrese el motivo del rechazo:')
    if (comentarios === null) {
      // Usuario presionó 'Cancelar'
      return
    }
    if (!comentarios.trim()) {
      mostrarNotificacion('El motivo del rechazo es obligatorio.', 'error')
      return
    }
  } else {
    // Para 'Aprobada'
    if (!confirm(`¿Está seguro de que desea ${accion} esta solicitud?`)) {
      return
    }
  }

  const payload = {
    ID_Solicitud: idSolicitud,
    Estado: nuevoEstado,
    ComentariosAdmin: comentarios,
  }

  try {
    const response = await fetch(`${BASE_URL}api/solicitud/dictaminar`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(payload),
    })

    const result = await response.json()

    if (response.ok && result.success) {
      mostrarNotificacion(
        result.message || `Solicitud ${nuevoEstado.toLowerCase()} con éxito.`, 'success',
      )
      regresarTablaDictamen()
      initDictamenSolicitudes()
    } else {
      mostrarNotificacion(result.message || `Error al ${accion} la solicitud.`, 'error')
    }
  } catch (error) {
    console.error(`Error al ${accion} la solicitud:`, error)
    mostrarNotificacion(`Error de red al intentar ${accion} la solicitud.`, 'error')
  }
}

/**
 * Lógica para el modal "CRUD Productos" (Existencias)
 */
function initCrudProductos() {
  if (!document.getElementById('tablaCrudProductos')) return;

  setupClientSideTable({
    rowsSelector: '#tablaCrudProductos tr[data-id]',
    paginationSelector: 'paginacion-crud-productos',
    filterFormSelector: '#div-busqueda',
    filterFunction: (row, form) => {
      const inputBusqueda = form.querySelector('#buscarProducto');
      const termino = (inputBusqueda?.value || '').trim().toLowerCase();
      if (!termino) return true;
      
      const codigo = (row.cells[0]?.textContent || '').toLowerCase();
      const nombre = (row.cells[1]?.textContent || '').toLowerCase();
      return codigo.includes(termino) || nombre.includes(termino);
    },
    rowsPerPage: 10
  });
}


function eliminarProducto(idProducto) {
  if (!confirm('¿Estás seguro de que deseas eliminar este producto?')) return

  fetch(`${BASE_URL}modales/eliminarProducto/${idProducto}`, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json',
    },
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const fila = document.querySelector(`#tablaCrudProductos tr[data-id='${idProducto}']`)
        if (fila) fila.remove()
        alert(data.message)
        initCrudProductos() // reiniciar filtros/paginación
      } else {
        alert(data.message)
      }
    })
    .catch((error) => {
      console.error('Error al eliminar el producto:', error)
      alert('Ocurrió un error al eliminar el producto.')
    })
}

function editarProducto(idProducto) {
  // Ocultar tabla y búsqueda
  document.getElementById('div-tabla').classList.add('hidden')
  document.getElementById('div-busqueda').classList.add('hidden')
  document.getElementById('div-editar').classList.remove('hidden')

  // Obtener fila seleccionada
  const fila = document.querySelector(`#tablaCrudProductos tr[data-id='${idProducto}']`)
  if (!fila) return

  const codigo = fila.children[0].textContent.trim()
  const nombre = fila.children[1].textContent.trim()
  const existencia = fila.children[2].textContent.trim()

  // ID oculto
  document.getElementById('editarID_Producto').value = idProducto

  // Campos NO editables
  document.getElementById('mostrarCodigo').value = codigo
  document.getElementById('mostrarNombre').value = nombre
  document.getElementById('mostrarExistencia').value = existencia

  // Campos editables
  document.getElementById('editarCodigo').value = codigo // si no se puede cambiar
  document.getElementById('editarNombre').value = nombre
  document.getElementById('editarExistencia').value = existencia
}

function regresarTablaProductos() {
  document.getElementById('div-tabla').classList.remove('hidden')
  document.getElementById('div-busqueda').classList.remove('hidden')
  document.getElementById('div-editar').classList.add('hidden')
}

function guardarEdicion() {
  const idProducto = document.getElementById('editarID_Producto').value
  const codigoAnt = document.getElementById('mostrarCodigo').value
  const nombreAnt = document.getElementById('mostrarNombre').value
  const existenciaAnt = document.getElementById('mostrarExistencia').value

  const codigoNew = document.getElementById('editarCodigo').value
  const nombreNew = document.getElementById('editarNombre').value
  const existenciaNew = document.getElementById('editarExistencia').value
  const razon = document.getElementById('editarComentarios').value

  if (!nombreNew || !existenciaNew) {
    alert('Completa los campos requeridos')
    return
  }

  // 1️⃣ Actualizar Producto
  fetch(`${BASE_URL}modales/actualizarProducto/${idProducto}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({
      Nombre: nombreNew,
      Existencia: existenciaNew,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        // 2️⃣ Insertar en HistorialProductos
        fetch(`${BASE_URL}modales/insertarHistorialProducto`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            ID_Producto: idProducto,
            CodigoAnt: codigoAnt,
            NombreAnt: nombreAnt,
            ExistenciaAnt: existenciaAnt,
            CodigoNew: codigoNew,
            NombreNew: nombreNew,
            ExistenciaNew: existenciaNew,
            Razon: razon,
          }),
        })
          .then((res) => res.json())
          .then((histData) => {
            if (histData.success) {
              alert('Producto actualizado y registrado en historial correctamente.')
              location.reload() // o refrescar tabla dinámicamente
            } else {
              alert('Producto actualizado, pero no se pudo registrar en historial.')
            }
          })
      } else {
        alert('No se pudo actualizar el producto: ' + data.message)
      }
    })
    .catch((err) => {
      console.error(err)
      alert('Ocurrió un error al guardar los cambios.')
    })
}

/**
 * Lógica para el modal "Órdenes de Compra"
 */
function initOrdenesCompra() {
  setupClientSideTable({
    rowsSelector: '#tablaOrdenesCompra tbody tr',
    paginationSelector: 'paginacion-ordenes-compra',
    rowsPerPage: 10
  });
}

async function mostrarVerOrdenCompra(idOrden) {
  document.getElementById('div-tabla-ordenes').classList.add('hidden')
  document.getElementById('div-ver-orden').classList.remove('hidden')
  const detallesContainer = document.getElementById('detallesOrdenCompra')
  detallesContainer.innerHTML = `<p>Cargando detalles de la orden ${idOrden}...</p>`
  try {
    const response = await fetch(`${BASE_URL}api/cotizacion/details/${idOrden}`)
    if (!response.ok) throw new Error(`Error ${response.statusus}: ${response.statususText}`)

    const data = await response.json()
    if (data.error) throw new Error(data.error)

    let html = generarDetallesSolicitudHTML(data)

    // Mostrar comentarios si existen (especialmente para rechazos)
    if (data.ComentariosAdmin) {
      html += `
            <div class="mt-6 p-4 border rounded-lg bg-red-50 border-red-200">
                <h4 class="text-md font-bold text-red-700 mb-2">Motivo del Rechazo</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
            </div>`
    }

    html += `
            <h4 class="text-md font-bold mb-2">Productos Solicitados</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left">Código</th>
                            <th class="py-2 px-4 text-left">Producto</th>
                            <th class="py-2 px-4 text-right">Cantidad</th>
                            <th class="py-2 px-4 text-right">Importe</th>
                            <th class="py-2 px-4 text-right">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `

    data.productos.forEach((p) => {
      const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
      html += `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t">${p.Codigo}</td>
                    <td class="py-2 px-4 border-t">${p.Nombre}</td>
                    <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                    <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                    <td class="py-2 px-4 border-t text-right">$${costoTotal}</td>
                </tr>
            `
    })

    html += `
                    </tbody>
                </table>
            </div>
        `

    if (data.Archivo) {
      const archivoUrl = `${BASE_URL}solicitudes/archivo/${idOrden}`
      html += `
                <div class="mt-6">
                    <h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                    <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.Archivo}</a>
                </div>
            `
    }

    if (data.cotizacion && data.cotizacion.Cotizacion_Files) {
      const listaDeArchivos = data.cotizacion.Cotizacion_Files.split(',')
      html += `
        <div class="mt-6">
            <h4 class="text-md font-bold mb-2">Cotizaciones adjuntas</h4>
    `
      listaDeArchivos.forEach((nombreDeArchivo) => {
        const filec = nombreDeArchivo.trim()

        if (filec) {
          const archivoUrl = `${BASE_URL}cotizaciones/archivo/${idOrden}/${filec}`
          html += `
                <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline block mb-1">${filec}</a>
            `
        }
      })
      html += `
        </div>
    `
    }

    // Solo mostrar botones de acción si la solicitud está 'En revision'
    if (data.Estado === 'Aprobada') {
      html += `
                <div class="mt-8 flex justify-end space-x-4">
                    <button onclick="mostrarOrdenPdf(${idOrden}, 1)" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Ver Orden
                    </button>
                    
                    <!-- Aqui se necesitaria que el boton envie la orden por pdf al proveedor y que cambie de estado a "Por Pagar" -->
                    <button onclick="#" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                        Enviar orden de compra
                    </button>
                </div>
            `
    }
    detallesContainer.innerHTML = html
  } catch (error) {
    detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

function regresarTablaOrdenCompra() {
  document.getElementById('div-ver-orden').classList.add('hidden')
  document.getElementById('div-tabla-ordenes').classList.remove('hidden')
}

/**
 * Lógica para el CRUD de proveedores
 */
function initCrudProveedores() {
  const tabla = document.getElementById('tabla-proveedores');
  if (!tabla) return;

  initProveedorTabla();
  initProveedorPantallas();
  initProveedorForm();
  initProveedorEditarForm();
  initProveedorActions(tabla);
}

function initProveedorTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-proveedores tr[data-id]',
    paginationSelector: 'paginacion-proveedores', // Assuming a new ID for pagination container
    filterFormSelector: '#form-filtros-proveedores', // Assuming a form for filters
    filterFunction: (row, form) => {
      const nombreFiltro = (form.querySelector('#buscar-nombre')?.value || '').toLowerCase();
      const servicioFiltro = (form.querySelector('#buscar-servicio')?.value || '').toLowerCase();
      
      const razonsocial = row.querySelector('.razonsocial')?.textContent.toLowerCase() || '';
      const servicio = row.querySelector('.servicio')?.textContent.toLowerCase() || '';
      
      return razonsocial.includes(nombreFiltro) && servicio.includes(servicioFiltro);
    },
    rowsPerPage: 10
  });
}


// --- Cambio de pantallas ---
function initProveedorPantallas() {
  const pantallaAgregar = document.getElementById('pantalla-agregar-proveedor')
  const pantallaEditar = document.getElementById('pantalla-editar-proveedor')
  const pantallaLista = document.getElementById('pantalla-lista-proveedores')

  const btnAgregar = document.getElementById('btn-agregar-proveedor')
  const btnRegresarAgregar = document.getElementById('btn-regresar-lista')
  const btnRegresarEditar = document.getElementById('btn-regresar-lista-editar')

  if (btnAgregar) 
    btnAgregar.onclick = (e) => {
      e.preventDefault()
      pantallaLista?.classList.add('hidden')
      pantallaAgregar?.classList.remove('hidden')
    }

  if (btnRegresarAgregar)
    btnRegresarAgregar.onclick = (e) => {
      e.preventDefault()
      pantallaAgregar?.classList.add('hidden')
      pantallaLista?.classList.remove('hidden')
    }

  if (btnRegresarEditar)
    btnRegresarEditar.onclick = (e) => {
      e.preventDefault()
      pantallaEditar?.classList.add('hidden')
      pantallaLista?.classList.remove('hidden')
    }
}

// --- Formulario agregar ---
function initProveedorForm() {
  const formProveedor = document.getElementById('form-agregar-proveedor')
  const pantallaAgregar = document.getElementById('pantalla-agregar-proveedor')
  const pantallaLista = document.getElementById('pantalla-lista-proveedores')

  if (!formProveedor) return

  formProveedor.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formProveedor)

    try {
      const response = await fetch('/proveedores/insertar', {
        method: 'POST',
        body: formData,
      })
      const result = await response.json()

      if (result.success) {
        mostrarNotificacion('Proveedor agregado correctamente ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formProveedor.reset()
        location.reload()
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

// --- Formulario editar ---
function initProveedorEditarForm() {
  const formEditar = document.getElementById('form-editar-proveedor')
  const pantallaEditar = document.getElementById('pantalla-editar-proveedor')
  const pantallaLista = document.getElementById('pantalla-lista-proveedores')
  const tabla = document.getElementById('tabla-proveedores')

  if (!formEditar) return

  formEditar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formEditar)

    try {
      const id = formData.get('ID_Proveedor')
      const response = await fetch(`/proveedores/editar/${id}`, {
        method: 'POST',
        body: formData,
      })
      const result = await response.json()

      if (result.success) {
        mostrarNotificacion('Proveedor actualizado ✅', 'success')

        // Actualizar la fila correspondiente en la tabla
        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          fila.querySelector('.razonsocial').textContent = formData.get('RazonSocial')
          fila.querySelector('.servicio').textContent = formData.get('Servicio')

          // actualizar los data-* de la fila
          fila.dataset.rfc = formData.get('RFC')
          fila.dataset.banco = formData.get('Banco')
          fila.dataset.cuenta = formData.get('Cuenta')
          fila.dataset.clabe = formData.get('Clabe')
          fila.dataset.telContacto = formData.get('Tel_Contacto')
          fila.dataset.nombreContacto = formData.get('Nombre_Contacto')
          fila.dataset.correo = formData.get('correo')
        }

        // Cerrar pantalla de edición y mostrar lista
        pantallaEditar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
      } else {
        mostrarNotificacion(result.message || 'Error al actualizar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

// --- Botones editar/eliminar ---
function initProveedorActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', (e) => {
    // --- ELIMINAR ---
    const svgEliminar = e.target.closest('svg')
    if (svgEliminar) {
      const btnEliminar = svgEliminar.closest("[id^='btn-eliminar-proveedor-']")
      if (btnEliminar) {
        e.preventDefault()
        const id = btnEliminar.dataset.id
        if (!confirm('¿Seguro que deseas eliminar este proveedor?')) return

        fetch(`/proveedores/eliminarProveedor/${id}`, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
          .then((res) => res.json())
          .then((result) => {
            if (result.success) {
              mostrarNotificacion('Proveedor eliminado ✅', 'success')
              btnEliminar.closest('tr')?.remove()
            } else {
              mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
            }
          })
          .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
        return
      }
    }

    // --- EDITAR ---
    const btnEditar = e.target.closest("[id^='btn-editar-proveedor-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return
    const credito = fila.dataset.diasCredito > 0 

    // Cargar datos desde data-* de la fila
    document.getElementById('editar-ID_Proveedor').value = fila.dataset.id
    document.getElementById('editar-RazonSocial').value =
      fila.querySelector('.razonsocial').textContent
    document.getElementById('editar-correo').value = fila.dataset.correo
    document.getElementById('editar-RFC').value = fila.dataset.rfc
    document.getElementById('editar-Banco').value = fila.dataset.banco
    document.getElementById('editar-Cuenta').value = fila.dataset.cuenta
    document.getElementById('editar-Clabe').value = fila.dataset.clabe
    document.getElementById('editar-Tel_Contacto').value = fila.dataset.telContacto
    document.getElementById('editar-Nombre_Contacto').value = fila.dataset.nombreContacto
    document.getElementById('editar-Servicio').value = fila.querySelector('.servicio').textContent
    document.getElementById('editar-tiene_credito').checked = credito
    const dias_credito = document.getElementById('editar-dias_credito')
    const monto_credito = document.getElementById('editar-monto_credito')
    dias_credito.value = credito ? fila.dataset.diasCredito : 0
    monto_credito.value = credito ? fila.dataset.montoCredito : 0
    
    document.getElementById('pantalla-lista-proveedores').classList.add('hidden')
    document.getElementById('pantalla-editar-proveedor').classList.remove('hidden')
  })
}

/**
 * Lógica para Entrega de Material
 */
function initEntregaMaterial() {
  const tbodyBuscar = document.getElementById('tablaBuscarMateriales');
  const tbodyEntrega = document.getElementById('tablaEntregaMateriales');
  const btnAgregarSeleccionados = document.getElementById('btn-agregar-seleccionados');
  if (!tbodyBuscar || !tbodyEntrega) return;

  let productosSeleccionados = [];

  setupClientSideTable({
    rowsSelector: '#tablaBuscarMateriales tr[data-id]',
    paginationSelector: 'paginacion-buscar-materiales',
    filterFormSelector: '#buscar-materiales-content',
    filterFunction: (row, form) => {
      const inputBuscar = form.querySelector('#buscarMaterial');
      const termino = (inputBuscar?.value || '').trim().toLowerCase();
      if (!termino) return true;
      const codigo = (row.cells[0]?.textContent || '').toLowerCase();
      const nombre = (row.cells[1]?.textContent || '').toLowerCase();
      return codigo.includes(termino) || nombre.includes(termino);
    },
    rowsPerPage: 10
  });

  // ---------- SELECCIÓN DE PRODUCTOS ----------
  window.toggleSeleccionProducto = function (id) {
    const index = productosSeleccionados.indexOf(id);
    const fila = document.getElementById('fila-producto-' + id);
    if (index === -1) {
      productosSeleccionados.push(id);
      if(fila) fila.classList.add('bg-green-100');
    } else {
      productosSeleccionados.splice(index, 1);
      if(fila) fila.classList.remove('bg-green-100');
    }
    actualizarBotonAgregar();
  }

  function actualizarBotonAgregar() {
    const total = productosSeleccionados.length;
    btnAgregarSeleccionados.textContent =
      total > 0 ? `Agregar ${total} productos` : 'Agregar 0 productos';
    btnAgregarSeleccionados.disabled = total === 0;
  }

  // ---------- AGREGAR PRODUCTOS A TABLA ENTREGA ----------
  window.agregarProductosSeleccionados = function () {
    if (productosSeleccionados.length === 0) return;

    const filaVacia = tbodyEntrega.querySelector('tr td[colspan="5"]');
    if (filaVacia) filaVacia.parentElement.remove();

    productosSeleccionados.forEach((id) => {
      const filaBuscar = document.getElementById('fila-producto-' + id);
      if (!filaBuscar || tbodyEntrega.querySelector(`#entrega-${id}`)) return;

      const codigo = filaBuscar.cells[0]?.textContent || '';
      const nombre = filaBuscar.cells[1]?.textContent || '';
      const existencia = filaBuscar.cells[2]?.textContent || '0';

      const nuevaFila = document.createElement('tr');
      nuevaFila.id = `entrega-${id}`;
      nuevaFila.innerHTML = `
        <td class="py-2 px-4">${codigo}</td>
        <td class="py-2 px-4">${nombre}</td>
        <td class="py-2 px-4">
          <input type="number" class="w-full px-2 py-1 border rounded" min="1" max="${existencia}" value="1">
        </td>
        <td class="py-2 px-4">${existencia}</td>
        <td class="py-2 px-4 text-center">
          <button type="button" onclick="eliminarFilaEntrega('${id}')" class="text-red-600 hover:text-red-800">
            <svg fill="none" stroke-width="1.5" stroke="currentColor" class="size-6 inline">
              <use xlink:href="/icons/icons.svg#eliminar-fila"></use>
            </svg>
          </button>
        </td>
      `;
      tbodyEntrega.appendChild(nuevaFila);
    });

    productosSeleccionados = [];
    actualizarBotonAgregar();
    regresarBuscarMateriales();
  }

  // ---------- ELIMINAR FILA ----------
  window.eliminarFilaEntrega = function (id) {
    const fila = document.getElementById(`entrega-${id}`);
    if (fila) fila.remove();

    if (tbodyEntrega.querySelectorAll('tr').length === 0) {
      const filaVacia = document.createElement('tr');
      filaVacia.innerHTML = `<td colspan="5" class="py-2 px-4 text-center text-gray-500">No hay materiales seleccionados.</td>`;
      tbodyEntrega.appendChild(filaVacia);
    }
  }

  // ---------- MOSTRAR / OCULTAR PANTALLAS ----------
  window.mostrarBuscarMateriales = function () {
    productosSeleccionados = [];
    document.querySelectorAll('#tablaBuscarMateriales tr').forEach((fila) => fila.classList.remove('bg-green-100'));
    actualizarBotonAgregar();
    document.getElementById('entrega-material-content').classList.add('hidden');
    document.getElementById('buscar-materiales-content').classList.remove('hidden');
  }

  window.regresarBuscarMateriales = function () {
    document.getElementById('buscar-materiales-content').classList.add('hidden');
    document.getElementById('entrega-material-content').classList.remove('hidden');
  }
}

/**
 * Lógica para el modal "CRUD Usuarios" con Alpine.js
 */
function crudUsuarios() {
  return {
    // Función para filtrar usuarios en la tabla
    filtrarUsuarios() {
      const termino = document.getElementById('buscarUsuario').value.toLowerCase()
      const filas = document.querySelectorAll('#tablaCrudUsuarios .usuario-row')

      filas.forEach((fila) => {
        const nombre = fila.querySelector('.nombre').textContent.toLowerCase()
        const correo = fila.querySelector('.correo').textContent.toLowerCase()
        const visible = nombre.includes(termino) || correo.includes(termino)
        fila.style.display = visible ? '' : 'none'
      })
    },

    // Muestra el formulario de edición con los datos del usuario
    editarUsuario(id) {
      const fila = document.querySelector(`#tablaCrudUsuarios tr[data-id='${id}']`)
      if (!fila) return

      document.getElementById('editar-ID_Usuario').value = id
      document.getElementById('editar-Nombre').value = fila.querySelector('.nombre').textContent
      document.getElementById('editar-Correo').value = fila.querySelector('.correo').textContent
      document.getElementById('editar-ID_Dpto').value =
        fila.querySelector('.departamento').dataset.idDpto
      document.getElementById('editar-ID_RazonSocial').value = fila.dataset.idRazonsocial
      document.getElementById('editar-Numero').value = fila.dataset.numero || ''
      document.getElementById('editar-ContrasenaP').value = '' // Limpiar campo de contraseña
      document.getElementById('editar-ContrasenaG').value = '' // Limpiar campo de contraseña
      document.getElementById('editar-ContrasenaP_confirm').value = ''
      document.getElementById('editar-ContrasenaG_confirm').value = ''

      document.getElementById('div-lista-usuarios').classList.add('hidden')
      document.getElementById('div-editar-usuario').classList.remove('hidden')
    },

    // Muestra el formulario de creación de usuario
    mostrarFormularioCrear() {
      document.getElementById('form-crear-usuario').reset()
      document.getElementById('div-lista-usuarios').classList.add('hidden')
      document.getElementById('div-crear-usuario').classList.remove('hidden')
    },

    // Regresa a la vista de la lista de usuarios
    regresarALista() {
      document.getElementById('div-editar-usuario').classList.add('hidden')
      document.getElementById('div-crear-usuario').classList.add('hidden')
      document.getElementById('div-lista-usuarios').classList.remove('hidden')
    },

    // Guarda los cambios del formulario de edición
    async guardarCambiosUsuario() {
      const id = document.getElementById('editar-ID_Usuario').value
      const nombre = document.getElementById('editar-Nombre').value
      const correo = document.getElementById('editar-Correo').value
      const idDpto = document.getElementById('editar-ID_Dpto').value
      const idRazonSocial = document.getElementById('editar-ID_RazonSocial').value
      const numero = document.getElementById('editar-Numero').value
      const contrasena = document.getElementById('editar-ContrasenaP').value
      const contrasenaG = document.getElementById('editar-ContrasenaG').value
      const contrasenaConfirm = document.getElementById('editar-ContrasenaP_confirm').value
      const contrasenaGConfirm = document.getElementById('editar-ContrasenaG_confirm').value

      const data = {
        Nombre: nombre,
        Correo: correo,
        ID_Dpto: idDpto,
        Numero: numero,
        ID_RazonSocial: idRazonSocial,
      }

      if (contrasena) {
        if (contrasena !== contrasenaConfirm) {
          mostrarNotificacion('Las contraseñas de Jefe no coinciden.', 'error')
          return
        }
        data.ContrasenaP = contrasena
      }

      if (contrasenaG) {
        if (contrasenaG !== contrasenaGConfirm) {
          mostrarNotificacion('Las contraseñas de Empleado no coinciden.', 'error')
          return
        }
        data.ContrasenaG = contrasenaG
      }

      try {
        const response = await fetch(`${BASE_URL}modales/actualizarUsuario/${id}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(data),
        })

        const result = await response.json()

        if (result.success) {
          mostrarNotificacion(result.message, 'success')
          // Actualizar la fila en la tabla
          const fila = document.querySelector(`#tablaCrudUsuarios tr[data-id='${id}']`)
          if (fila) {
            fila.querySelector('.nombre').textContent = nombre
            fila.querySelector('.correo').textContent = correo
            const select = document.getElementById('editar-ID_Dpto') // Aquí se obtiene el texto completo (Depto + Lugar)
            const deptoText = select.options[select.selectedIndex].text
            fila.querySelector('.departamento').textContent = deptoText
            fila.dataset.numero = numero
            fila.querySelector('.departamento').dataset.idDpto = idDpto
          }
          this.regresarALista()
        } else {
          const errorMsg = result.errors ? Object.values(result.errors).join('\n') : result.message
          mostrarNotificacion(errorMsg, 'error')
        }
      } catch (error) {
        console.error('Error al actualizar usuario:', error)
        mostrarNotificacion('Error de conexión al actualizar.', 'error')
      }
    },

    // Guarda un nuevo usuario
    async guardarNuevoUsuario() {
      const nombre = document.getElementById('crear-Nombre').value
      const correo = document.getElementById('crear-Correo').value
      const idDpto = document.getElementById('crear-ID_Dpto').value
      const idRazonSocial = document.getElementById('crear-ID_RazonSocial').value
      const numero = document.getElementById('crear-Numero').value
      const contrasena = document.getElementById('crear-ContrasenaP').value
      const contrasenaG = document.getElementById('crear-ContrasenaG').value
      const contrasenaConfirm = document.getElementById('crear-ContrasenaP_confirm').value
      const contrasenaGConfirm = document.getElementById('crear-ContrasenaG_confirm').value

      if (contrasena.length < 8) {
        mostrarNotificacion('La contraseña de Jefe debe tener al menos 8 caracteres.', 'error')
        return
      }

      if (contrasena !== contrasenaConfirm) {
        mostrarNotificacion('Las contraseñas de Jefe no coinciden.', 'error')
        return
      }

      // Validar ContraseñaG solo si se ha introducido
      if (contrasenaG) {
        if (contrasenaG.length < 8) {
          mostrarNotificacion(
            'La contraseña de Empleado debe tener al menos 8 caracteres.',
            'error',
          )
          return
        }
        if (contrasenaG !== contrasenaGConfirm) {
          mostrarNotificacion('Las contraseñas de Empleado no coinciden.', 'error')
          return
        }
      }

      const data = {
        Nombre: nombre,
        Correo: correo,
        ID_Dpto: idDpto,
        Numero: numero,
        ID_RazonSocial: idRazonSocial,
        ContrasenaP: contrasena,
        ContrasenaG: contrasenaG,
      }

      try {
        const response = await fetch(`${BASE_URL}modales/registrarUsuario`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(data),
        })

        const result = await response.json()

        if (result.success) {
          mostrarNotificacion(result.message, 'success')

          // Añadir dinámicamente la nueva fila a la tabla
          const tablaBody = document.getElementById('tablaCrudUsuarios')
          const selectDepto = document.getElementById('crear-ID_Dpto') // Aquí se obtiene el texto completo (Depto + Lugar)
          const deptoText = selectDepto.options[selectDepto.selectedIndex].text
          const iconUrl = `/icons/icons.svg?v=${window.ICON_SVG_VERSION || new Date().getTime()}`

          const nuevaFila = `
            <tr data-id="${result.user.ID_Usuario}" class="usuario-row" data-numero="${numero}" data-id-razonsocial="${idRazonSocial}">
              <td class="py-2 px-4 border-b nombre">${result.user.Nombre}</td>
              <td class="py-2 px-4 border-b correo">${result.user.Correo}</td>
              <td class="py-2 px-4 border-b departamento" data-id-dpto="${result.user.ID_Dpto}">${deptoText}</td>
              <td class="py-2 px-4 border-b text-center">
                <button @click="editarUsuario(${result.user.ID_Usuario})" class="text-blue-600 hover:text-blue-800" title="Editar">
                  <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#editar"></use></svg>
                </button>
                <button @click="eliminarUsuario(${result.user.ID_Usuario})" class="text-red-600 hover:text-red-800 ml-2" title="Eliminar">
                  <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#eliminar-fila"></use></svg>
                </button>
              </td>
            </tr>`

          tablaBody.insertAdjacentHTML('beforeend', nuevaFila)
          this.regresarALista()
        } else {
          const errorMsg = result.errors ? Object.values(result.errors).join('\n') : result.message
          mostrarNotificacion(errorMsg, 'error')
        }
      } catch (error) {
        console.error('Error al registrar usuario:', error)
        mostrarNotificacion('Error de conexión al registrar.', 'error')
      }
    },

    // Elimina un usuario
    async eliminarUsuario(id) {
      if (
        !confirm(
          '¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.',
        )
      ) {
        return
      }

      try {
        const response = await fetch(`${BASE_URL}modales/eliminarUsuario/${id}`, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        const result = await response.json()
        if (result.success) {
          mostrarNotificacion(result.message, 'success')
          document.querySelector(`#tablaCrudUsuarios tr[data-id='${id}']`)?.remove()
        } else {
          mostrarNotificacion(result.message, 'error')
        }
      } catch (error) {
        console.error('Error al eliminar usuario:', error)
        mostrarNotificacion('Error de conexión al eliminar.', 'error')
      }
    },
  }
}

/**
 * Lógica para el modal "Aprobar Solicitudes" (Jefes de Depto) con Alpine.js
 */
function aprobarSolicitudes() {
  return {
    verDetalle: async function (idSolicitud) {
      document.getElementById('div-tabla-aprobacion').classList.add('hidden')
      const divVer = document.getElementById('div-ver-aprobacion')
      divVer.classList.remove('hidden')

      const detallesContainer = document.getElementById('detalles-aprobacion-solicitud')
      detallesContainer.innerHTML = '<p class="text-center text-gray-500">Cargando detalles...</p>'

      try {
        const data = await getData(`solicitud/details/${idSolicitud}`, {}, true)
        if (data.error) throw new Error(data.error)

        let html = `
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
              <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
              <div><strong>Fecha:</strong> ${data.Fecha}</div>
              <div><strong>Estado:</strong> <span class="font-semibold text-yellow-600">${data.Estado}</span></div>
              <div><strong>Solicitante:</strong> ${data.UsuarioNombre}</div>
              <div class="md:col-span-2"><strong>Departamento:</strong> ${data.DepartamentoNombre}</div>
          </div>
          <h4 class="text-md font-bold mb-2">Productos/Servicios Solicitados</h4>
          <div class="overflow-x-auto">
              <table class="min-w-full border border-gray-300">
                  <thead class="bg-gray-100">
                      <tr>
                          ${data.Tipo != 2 ? '<th class="py-2 px-4 text-left">Código</th>' : ''}
                          <th class="py-2 px-4 text-left">Descripción</th>
                          ${data.Tipo != 2 ? '<th class="py-2 px-4 text-right">Cantidad</th>' : ''}
                          ${data.Tipo != 1 ? '<th class="py-2 px-4 text-right">Importe</th>' : ''}
                          ${data.Tipo == 1 ? '<th class="py-2 px-4 text-right">Costo Total</th>' : ''}
                      </tr>
                  </thead>
                  <tbody>
      `
        data.productos.forEach((p) => {
          const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
          html += `
              <tr class="hover:bg-gray-50">
                  ${data.Tipo != 2 ? `<td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>` : ''}
                  <td class="py-2 px-4 border-t">${p.Nombre}</td>
                  ${data.Tipo != 2 ? `<td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>` : ''}
                  ${data.Tipo != 1 ? `<td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>` : ''}
                  ${data.Tipo == 1 ? `<td class="py-2 px-4 border-t text-right">$${costoTotal}</td>` : ''}
              </tr>
          `
        })
        html += `</tbody></table></div>`

        if (data.Archivo) {
          html += `<div class="mt-6"><h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                     <a href="${BASE_URL}solicitudes/archivo/${idSolicitud}" target="_blank" class="text-blue-600 hover:underline">${data.Archivo}</a></div>`
        }

        // Botones de acción
        html += `
          <div class="mt-8 flex justify-end space-x-4">
              <button @click="dictaminar(${idSolicitud}, 'rechazar')" class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700">Rechazar</button>
              <button @click="dictaminar(${idSolicitud}, 'aprobar')" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">Aprobar y Enviar a Compras</button>
          </div>`

        detallesContainer.innerHTML = html
      } catch (error) {
        detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
      }
    },

    regresarATabla: function () {
      document.getElementById('div-ver-aprobacion').classList.add('hidden')
      document.getElementById('div-tabla-aprobacion').classList.remove('hidden')
    },

    dictaminar: async function (idSolicitud, accion) {
      const esRechazo = accion === 'rechazar'
      const titulo = esRechazo ? 'Rechazar Solicitud' : 'Aprobar Solicitud'
      const mensaje = `¿Está seguro de que desea ${accion} esta solicitud?`
      const botonTexto = esRechazo ? 'Sí, Rechazar' : 'Sí, Aprobar'
      const botonClase = esRechazo
        ? 'bg-red-600 hover:bg-red-700'
        : 'bg-green-600 hover:bg-green-700'

      // Crear el modal de confirmación
      const modalOverlay = document.createElement('div')
      modalOverlay.className = 'fixed inset-0 flex items-center justify-center z-50'
      modalOverlay.style.zIndex = '2147483647'

      let modalHtml = `
        <div class="bg-gray-300 rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
          <h3 class="text-lg font-bold mb-4">${titulo}</h3>
          <p class="mb-4">${mensaje}</p>
      `

      if (esRechazo) {
        modalHtml += `
          <label for="motivoRechazo" class="block text-sm font-medium text-gray-700 mb-1">Motivo del rechazo (obligatorio):</label>
          <textarea id="motivoRechazo" rows="3" class="w-full border-gray-300 border-2 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
        `
      }

      modalHtml += `
          <div class="mt-6 flex justify-end space-x-4">
            <button id="cancelarBtn" class="px-4 py-2 bg-gray-200 border-2 border-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancelar</button>
            <button id="confirmarBtn" class="px-4 py-2 text-white rounded-md ${botonClase}">${botonTexto}</button>
          </div>
        </div>
      `

      modalOverlay.innerHTML = modalHtml
      document.body.appendChild(modalOverlay)

      const cerrarModal = () => modalOverlay.remove()

      document.getElementById('cancelarBtn').addEventListener('click', cerrarModal)

      document.getElementById('confirmarBtn').addEventListener('click', async () => {
        let comentarios = null
        if (esRechazo) {
          const motivoInput = document.getElementById('motivoRechazo')
          comentarios = motivoInput.value.trim()
          if (!comentarios) {
            mostrarNotificacion('El motivo del rechazo es obligatorio.', 'error')
            motivoInput.focus()
            motivoInput.classList.add('border-red-500')
            return
          }
        }

        const payload = {
          ID_Solicitud: idSolicitud,
          accion: accion,
        }

        if (comentarios) {
          payload.comentarios = comentarios
        }

        const btnConfirmar = document.getElementById('confirmarBtn')
        btnConfirmar.disabled = true
        btnConfirmar.textContent = 'Procesando...'

        try {
          const response = await fetch(`${BASE_URL}api/solicitud/dictaminar-jefe`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
          })

          const result = await response.json()

          if (response.ok && result.success) {
            mostrarNotificacion(result.message, 'success')
            cerrarModal()
            abrirModal('aprobar_solicitudes')
          } else {
            cerrarModal()
            abrirModal('aprobar_solicitudes')
            mostrarNotificacion(result.message || `Error al ${accion} la solicitud.`, 'error')
            btnConfirmar.disabled = false
            btnConfirmar.textContent = botonTexto
          }
        } catch (error) {
          mostrarNotificacion(`Error de red al intentar ${accion} la solicitud.`, 'error')
          btnConfirmar.disabled = false
          btnConfirmar.textContent = botonTexto
        }
      })

      if (esRechazo) {
        document.getElementById('motivoRechazo').focus()
      }
    },
  }
}

function regresarBuscarMateriales() {
  document.getElementById('buscar-materiales-content').classList.add('hidden')
  document.getElementById('entrega-material-content').classList.remove('hidden')
}

/**
 * Lógica para pagos pendientes
 */
function mostrarPagoContado() {
  document.getElementById('pagos-menu').classList.add('hidden')
  document.getElementById('pago-contado').classList.remove('hidden')
}

function mostrarPagoCredito() {
  document.getElementById('pagos-menu').classList.add('hidden')
  document.getElementById('pago-credito').classList.remove('hidden')
}

function regresarPagosMenu() {
  document.getElementById('pago-contado').classList.add('hidden')
  document.getElementById('pago-credito').classList.add('hidden')
  document.getElementById('pagos-menu').classList.remove('hidden')
}

// ================== PAGO CONTADO ==================
function verDetalleContado(id) {
  const detalle = document.getElementById('detalle-contado')
  const tabla = document.getElementById('tabla-contado')
  const botonRegresarPrincipal = document.querySelector('#pago-contado .flex.justify-between button')

  tabla.classList.add('hidden')
  if (botonRegresarPrincipal) botonRegresarPrincipal.classList.add('hidden')
  detalle.classList.remove('hidden')

  detalle.innerHTML = `

        <h1>Nota</h1>
        <h2>En este apartado se debe cargar la solicitud, haber el espacio para cargar archivos por si se necesita y debe cargar tambien todos los datos del proveedor que esta seleccionado en la orden para enviarlo a tesoreria el cual posteriormente devolvera esta requisición a compras para poder adjuntar la factura</h2>
    
    <div class="flex justify-between items-center mb-4">
      <button onclick="regresarTablaContado()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
      
      <h2 class="text-lg font-semibold">Detalle de la solicitud ${id}</h2>
      <div></div>
    </div>

    <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 shadow-sm">
      <p class="text-gray-700 mb-4">Información detallada de la solicitud <strong>${id}</strong> (contenido dinámico aquí).</p>

      <!-- Botón de acción -->
      <div class="flex justify-end mt-4">
        <button
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
          Enviar a tesorería para pago
        </button>
      </div>
    </div>
  `
}

function regresarTablaContado() {
  const detalle = document.getElementById('detalle-contado')
  const tabla = document.getElementById('tabla-contado')
  const botonRegresarPrincipal = document.querySelector('#pago-contado .flex.justify-between button')

  detalle.classList.add('hidden')
  tabla.classList.remove('hidden')
  if (botonRegresarPrincipal) botonRegresarPrincipal.classList.remove('hidden')
}

// ================== PAGO CRÉDITO ==================
function verDetalleCredito(id) {
  const detalle = document.getElementById('detalle-credito')
  const tabla = document.getElementById('tabla-credito')
  const botonRegresarPrincipal = document.querySelector('#pago-credito .flex.justify-between button')

  tabla.classList.add('hidden')
  if (botonRegresarPrincipal) botonRegresarPrincipal.classList.add('hidden')
  detalle.classList.remove('hidden')

  detalle.innerHTML = `
    <h1>Nota</h1>
        <h2>En este apartado se debe cargar la solicitud, haber el espacio para cargar archivos de la factura y debe cargar tambien todos los datos del proveedor que esta seleccionado en la orden para enviarlo a tesoreria</h2>
   
    <div class="flex justify-between items-center mb-4">
      <button onclick="regresarTablaCredito()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
      <h2 class="text-lg font-semibold">Detalle de la solicitud ${id}</h2>
      <div></div>
    </div>

    <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 shadow-sm">
      <p class="text-gray-700 mb-4">Información detallada de la solicitud <strong>${id}</strong> (contenido dinámico aquí).</p>

      <!-- Botón de acción -->
      <div class="flex justify-end mt-4">
        <button
          class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
          Enviar a tesorería
        </button>
      </div>
    </div>
  `
}

function regresarTablaCredito() {
  const detalle = document.getElementById('detalle-credito')
  const tabla = document.getElementById('tabla-credito')
  const botonRegresarPrincipal = document.querySelector('#pago-credito .flex.justify-between button')

  detalle.classList.add('hidden')
  tabla.classList.remove('hidden')
  if (botonRegresarPrincipal) botonRegresarPrincipal.classList.remove('hidden')
}


/**
 * Lógica para fichas de pago
 */
function mostrarFichaContado() {
  document.getElementById('ficha-menu').classList.add('hidden')
  document.getElementById('ficha-contado').classList.remove('hidden')
}

function mostrarFichaCredito() {
  document.getElementById('ficha-menu').classList.add('hidden')
  document.getElementById('ficha-credito').classList.remove('hidden')
}

function regresarFichaMenu() {
  document.getElementById('ficha-contado').classList.add('hidden')
  document.getElementById('ficha-credito').classList.add('hidden')
  document.getElementById('ficha-menu').classList.remove('hidden')
}

// ================== FICHA CONTADO ==================
function verFichaContado(id) {
  const detalle = document.getElementById('detalle-contado')
  const tabla = document.getElementById('tabla-contado')
  const botonRegresar = document.querySelector('#ficha-contado .flex.justify-between button')

  tabla.classList.add('hidden')
  if (botonRegresar) botonRegresar.classList.add('hidden')
  detalle.classList.remove('hidden')

  detalle.innerHTML = `
    <div class="flex justify-between items-center mb-4">
      <button onclick="regresarFichaContado()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
      <h2 class="text-lg font-semibold">Detalle de la ficha ${id}</h2>
      <div></div>
    </div>

    <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 shadow-sm">
      <p class="text-gray-700 mb-4">Detalle de la ficha de pago <strong>${id}</strong> (contenido dinámico aquí).</p>

      <div class="flex justify-end mt-4">
        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
          Confirmar envío a tesorería
        </button>
      </div>
    </div>
  `
}

function regresarFichaContado() {
  const detalle = document.getElementById('detalle-contado')
  const tabla = document.getElementById('tabla-contado')
  const botonRegresar = document.querySelector('#ficha-contado .flex.justify-between button')

  detalle.classList.add('hidden')
  tabla.classList.remove('hidden')
  if (botonRegresar) botonRegresar.classList.remove('hidden')
}

// ================== FICHA CRÉDITO ==================
function verFichaCredito(id) {
  const detalle = document.getElementById('detalle-credito')
  const tabla = document.getElementById('tabla-credito')
  const botonRegresar = document.querySelector('#ficha-credito .flex.justify-between button')

  tabla.classList.add('hidden')
  if (botonRegresar) botonRegresar.classList.add('hidden')
  detalle.classList.remove('hidden')

  detalle.innerHTML = `
    <div class="flex justify-between items-center mb-4">
      <button onclick="regresarFichaCredito()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
      <h2 class="text-lg font-semibold">Detalle de la ficha ${id}</h2>
      <div></div>
    </div>

    <div class="bg-gray-50 border border-gray-300 rounded-lg p-4 shadow-sm">
      <p class="text-gray-700 mb-4">Detalle de la ficha de pago <strong>${id}</strong> (contenido dinámico aquí).</p>

      <div class="flex justify-end mt-4">
        <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
          Confirmar recepción en tesorería
        </button>
      </div>
    </div>
  `
}

function regresarFichaCredito() {
  const detalle = document.getElementById('detalle-credito')
  const tabla = document.getElementById('tabla-credito')
  const botonRegresar = document.querySelector('#ficha-credito .flex.justify-between button')

  detalle.classList.add('hidden')
  tabla.classList.remove('hidden')
  if (botonRegresar) botonRegresar.classList.remove('hidden')
}


/**
 * Varios
 */
// Inicializar
document.addEventListener('DOMContentLoaded', initCrudProveedores)

function mostrarNotificacion(mensaje, tipo = 'success', duracion = 3000) {
  const CT_ID = '__app_toast_container'
  let container = document.getElementById(CT_ID)

  // Crear contenedor si no existe
  if (!container) {
    container = document.createElement('div')
    container.id = CT_ID
    Object.assign(container.style, {
      position: 'fixed',
      top: '1rem',
      right: '1rem',
      zIndex: 2147483647, // muy alto
      display: 'flex',
      flexDirection: 'column',
      gap: '0.5rem',
      alignItems: 'flex-end',
      pointerEvents: 'none', // permite clicks pasar por debajo excepto en cada toast
    })
    document.body.appendChild(container)
  }

  // Crear toast
  const toast = document.createElement('div')
  toast.setAttribute('role', 'statusus')
  toast.setAttribute('aria-live', 'polite')
  Object.assign(toast.style, {
    pointerEvents: 'auto', // permitir interacción con el toast
    display: 'flex',
    alignItems: 'center',
    gap: '0.6rem',
    minWidth: '180px',
    maxWidth: '340px',
    padding: '0.55rem 0.85rem',
    borderRadius: '0.5rem',
    boxShadow: '0 8px 24px rgba(0,0,0,0.12)',
    color: '#fff',
    fontSize: '0.95rem',
    transform: 'translateX(120%)',
    opacity: '0',
    transition: 'transform 320ms cubic-bezier(.2,.8,.2,1), opacity 320ms ease',
  })

  // Color por tipo
  if (tipo === 'success') {
    toast.style.backgroundColor = '#16a34a' // verde
  } else if (tipo === 'error') {
    toast.style.backgroundColor = '#dc2626' // rojo
  } else {
    toast.style.backgroundColor = '#0369a1' // azul/info
  }

  // Icono simple (puedes cambiar por SVG si prefieres)
  const icon = document.createElement('span')
  icon.style.fontWeight = '700'
  icon.style.flex = '0 0 auto'
  icon.style.lineHeight = '1'
  icon.style.fontSize = '1.05rem'
  icon.style.display = 'inline-block'
  icon.style.width = '1.2rem'
  icon.style.textAlign = 'center'
  icon.style.opacity = '0.98'
  icon.textContent = tipo === 'success' ? '✓' : tipo === 'error' ? '✕' : 'ℹ'
  toast.appendChild(icon)

  // Texto
  const text = document.createElement('div')
  text.style.whiteSpace = 'nowrap'
  text.style.overflow = 'hidden'
  text.style.textOverflow = 'ellipsis'
  text.style.flex = '1 1 auto'
  text.textContent = mensaje
  toast.appendChild(text)

  // Insertar en el contenedor (apilar hacia abajo)
  container.appendChild(toast)

  // Forzar frame para activar la animación de entrada
  requestAnimationFrame(() => {
    toast.style.transform = 'translateX(0)'
    toast.style.opacity = '1'
  })

  // Auto-cerrar con pausa en hover
  let timeoutId = setTimeout(hide, duracion)

  function hide() {
    clearTimeout(timeoutId)
    // animación de salida
    toast.style.transform = 'translateX(120%)'
    toast.style.opacity = '0'
    setTimeout(() => {
      toast.remove()
      // si no hay más toasts, eliminar el contenedor
      if (container && container.childElementCount === 0) {
        container.remove()
      }
    }, 360)
  }

  toast.addEventListener('click', hide)
  toast.addEventListener('mouseenter', () => {
    clearTimeout(timeoutId)
  })
  toast.addEventListener('mouseleave', () => {
    timeoutId = setTimeout(hide, duracion)
  })

  return toast // por si quieres manipularlo luego
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
      throw new Error(`Error HTTP: ${response.statusus} - ${response.statususText}`)
    }

    const data = await response.json()

    return data
  } catch (error) {
    console.error('Hubo un error al obtener los datos:', error)
    return []
  }
}
/**
 * loadRazonSocialProv: Función para cargar las opciones de razón social desde la API
 * y agregarlas a un elemento <select> en el DOM.
 */
async function loadRazonSocialProv(selectId) {
  const ProvSelect = document.getElementById(selectId)
  if (!ProvSelect) return

  try {
    const data = await getData('providers/all')
    console.log('Datos recibidos:', data)
    if (Array.isArray(data) && data.length > 0) {
      ProvSelect.innerHTML = '<option value="">Seleccione una opción</option>'
      data.forEach((provider) => {
        let option = document.createElement('option')
        option.value = provider.ID_Proveedor
        option.textContent = provider.RazonSocial
        ProvSelect.appendChild(option)
      })
    } else {
      console.error('Los datos recibidos no son un array válido:', data)
    }
  } catch (error) {
    console.error('Hubo un error al obtener los proveedores:', error)
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

  const messageContainer = formulario.querySelector('.form-message-container')
  const submitButton = formulario.querySelector('button[type="submit"]')

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

      // Reiniciar subtotal y total
      const subtotalTd = formulario.querySelector('#subtotal-costo, #subtotal-servicio')
      const totalTd = formulario.querySelector('#total-costo, #total-servicio')
      if (subtotalTd) subtotalTd.textContent = '$0.00'
      if (totalTd) totalTd.textContent = '$0.00'

      // Resetear formulario
      formulario.reset()

      // Reiniciar filas de la tabla dejando la primera fila limpia
      const tabla = formulario.querySelector('tbody')
      if (tabla) {
        const filas = Array.from(tabla.querySelectorAll('tr'))
        filas.forEach((fila, i) => {
          if (i > 0) {
            fila.remove()
          } else {
            // Limpiar valores de la primera fila
            const cantidad = fila.querySelector('.cantidad')
            const importe = fila.querySelector('.importe')
            const costo = fila.querySelector('.costo')
            const costoServicio = fila.querySelector('.costo-servicio')
            if (cantidad) cantidad.value = 1
            if (importe) importe.value = ''
            if (costo) costo.textContent = '$0.00'
            if (costoServicio) costoServicio.value = ''
          }
        })
      }
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

function mostrarVerPdf(idSolicitud, tipo = 0) {
  const url =
    tipo === 1
      ? `${BASE_URL}api/solicitud/pdf/${idSolicitud}/${tipo}`
      : `${BASE_URL}api/solicitud/pdf/${idSolicitud}`
  window.open(url, '_blank')
}

function mostrarOrdenPdf(id) {
  const url = `${BASE_URL}api/orden/pdf/${id}`
  window.open(url, '_blank')
}