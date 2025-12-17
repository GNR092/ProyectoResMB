/**
 * Funciones para manejar la apertura y cierre de modales,
 * y la inicialización de su contenido dinámico.
 */
function abrirModal(opcion) {
  const parentModals = {
    registrar_productos: 'almacen',
    crud_productos: 'almacen',
    entrega_productos: 'almacen',
    reporte_almacen: 'almacen',
    crud_usuarios: 'ajustes',
    limpiar_almacenamiento: 'ajustes',
    crud_proveedores: 'ajustes',
    reportes: 'ajustes',
    razonsocial: 'ajustes',
    micuenta: 'ajustes',
    programar_pagos: 'programar_pagos',
  }

  const highlightOpcion = parentModals[opcion] || opcion

  const activeClasses = ['bg-indigo-600', 'text-white']
  const inactiveClasses = ['text-gray-300', 'hover:bg-gray-700']

  document.querySelectorAll('#sidebar-nav a[data-opcion]').forEach((link) => {
    link.classList.remove(...activeClasses)
    link.classList.add(...inactiveClasses)
  })

  const activeLink = document.querySelector(`#sidebar-nav a[data-opcion='${highlightOpcion}']`)
  if (activeLink) {
    activeLink.classList.remove(...inactiveClasses)
    activeLink.classList.add(...activeClasses)
  }

  const modal = document.getElementById('modal-general')
  const titulo = document.getElementById('modal-title')
  const contenido = document.getElementById('modal-contenido')
  const modalBox = titulo.parentElement

  const modalesAnchos = ['reportes', 'ver_historial']
  if (modalesAnchos.includes(opcion)) {
    modal.classList.remove('justify-center')
    modalBox.classList.remove('max-w-4xl', 'mx-4', 'sm:mx-auto')
  } else {
    modal.classList.add('justify-center')
    modalBox.classList.add('max-w-4xl', 'mx-4', 'sm:mx-auto')
  }

  let titulos = {
    solicitar_material: 'Requisiciones',
    ver_historial: 'Historial',
    revisar_solicitudes: 'Revisar requisiciones',
    ordenes_compra: 'Órdenes de Compra',
    enviar_revision: 'Enviar a Revisión',
    usuarios: 'Usuarios',
    dictamen_solicitudes: 'Dictamen de requisiciones',
    crud_proveedores: 'Proveedores',
    limpiar_almacenamiento: 'Limpiar Almacenamiento',
    crud_usuarios: 'Administrar Usuarios',
    pagos_pendientes: 'Facturas Pendientes',
    registrar_productos: 'Registrar Productos',
    crud_productos: 'Existencias',
    entrega_productos: 'Entrega de Material',
    ficha_pago: 'Fichas de pago',
    ajustes: 'Ajustes',
    almacen: 'Almacén',
    reportes: 'Reportes/Auditoria',
    razonsocial: 'Razón social',
    reporte_almacen: 'Reportes/Historial',
    micuenta: 'Mi cuenta',
    programar_pagos: 'Programar pagos',
    recepcion_material: 'Recepción de Material',
    bajas_destruccion: 'Bajas por Destrucción',
  }
  titulos['aprobar_solicitudes'] = 'Aprobar Requisiciones de Empleados'

  titulo.innerText = titulos[opcion] ?? 'Opción'

  SendDataEnd(`modales/${opcion}`, { responseType: 'text' })
    .then((html) => {
      contenido.innerHTML = html
      modal.classList.remove('hidden')

      const inicializadores = {
        ver_historial: initPaginacionHistorial,
        usuarios: initUsuarios,
        revisar_solicitudes: initRevisarSolicitud,
        dictamen_solicitudes: initDictamenSolicitudes,
        ordenes_compra: initOrdenesCompra,
        crud_proveedores: initCrudProveedores,
        pagos_pendientes: initPagosPendientes,
        ficha_pago: initFichasPago,
        razonsocial: initCrudRazonSocial,
        limpiar_almacenamiento: initLimpiarAlmacenamiento,
        recepcion_material: initRecepcionMaterial,
        bajas_destruccion: initBajasDestruccion,
      }

      const inicializador = inicializadores[opcion]
      if (inicializador) {
        inicializador()
      }
    })
    .catch((error) => {
      console.error('Error al cargar modal:', error)
      contenido.innerHTML = '<p class="text-red-500">Error al cargar el contenido del modal.</p>'
      modal.classList.remove('hidden')
    })
}
function cerrarModal() {
  //  Ocultar el modal
  document.getElementById('modal-general').classList.add('hidden');

  // Limpiar la selección de la barra lateral
  const activeClasses = ['bg-indigo-600', 'text-white'];
  const inactiveClasses = ['text-gray-300', 'hover:bg-gray-700'];

  document.querySelectorAll('#sidebar-nav a[data-opcion]').forEach((link) => {
    link.classList.remove(...activeClasses);
    link.classList.add(...inactiveClasses);
  });
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
        const productRowHtmlContent = await SendDataEnd('modales/vistas/product_row', { responseType: 'text' })
        productRowHtml = productRowHtmlContent
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
        const serviceRowHtmlContent = await SendDataEnd('modales/vistas/service_row', { responseType: 'text' })
        serviceRowHtml = serviceRowHtmlContent
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
  const tabla = document.getElementById('tabla-historial')
  if (!tabla) return

  //Filtro para la casilla de departamentos
  function validarFiltroDepartamento() {
    const filtro = document.getElementById('filtroDepartamento')
    if (!filtro) return
    const deptosPermitidos = ['Administración', 'Compras', 'Dirección', 'Tesorería']

    const miDepto = (typeof USER_DEPT_NAME !== 'undefined') ? USER_DEPT_NAME : ''

    if (!deptosPermitidos.includes(miDepto)) {
      filtro.classList.add('hidden') // Clase de Tailwind para ocultar
      filtro.disabled = true // Deshabilitar para evitar envíos accidentales
    } else {
      filtro.classList.remove('hidden')
    }
  }

  // Ejecutar la validación inmediatamente
  validarFiltroDepartamento()

  // Mostrar la opción de filtro "Aprobacion Pendiente" solo si es un jefe
  const opcionPendiente = document.getElementById('filtro-pendiente-aprobacion')
  if (opcionPendiente && typeof USER_LOGIN_TYPE !== 'undefined' && USER_LOGIN_TYPE === 'boss') {
    opcionPendiente.classList.remove('hidden')
  }

  function getStatususSVG(statusus) {
    if (!statusus) return ''
    const statususLower = statusus.toLowerCase()
    const iconUrl = `icons/icons.svg?v=${window.ICON_SVG_VERSION || '1.0'}`
    let svgClass = ''
    let iconId = ''

    switch (statususLower) {
      case 'aprobada':
      case 'pagada':
        svgClass = 'text-green-600'
        iconId = 'aceptado'
        break
      case 'en espera':
        svgClass = 'text-yellow-500'
        iconId = 'en_espera'
        break
      case 'rechazada':
      case 'cancelada':
        svgClass = 'text-red-500'
        iconId = 'rechazado'
        break
      case 'cotizando':
        svgClass = 'text-blue-500'
        iconId = 'cotizacion'
        break
      case 'en revision':
        svgClass = 'text-blue-500'
        iconId = 'revision'
        break
      case 'aprobacion pendiente':
        svgClass = 'text-orange-500'
        iconId = 'pendiente'
        break
      case 'en proceso de pago':
        svgClass = 'text-yellow-500'
        iconId = 'procesopago'
        break
      case 'por pagar':
        svgClass = 'text-yellow-500'
        iconId = 'porpagar'
        break
      default:
        return ''
    }
    return `<svg class="${svgClass} mx-auto size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#${iconId}"></use></svg>`
  }

  const exceptions = ['Compras', 'Administración']
  let url = 'api/historic'
  if (
    typeof USER_DEPT_NAME !== 'undefined' &&
    typeof USER_DEPT_ID !== 'undefined' &&
    USER_DEPT_ID &&
    !exceptions.includes(USER_DEPT_NAME)
  ) {
    url = `api/historic/department/${USER_DEPT_ID}`
  }

  createPaginatedTable({
    tableSelector: '#tabla-historial tbody',
    paginationSelector: 'paginacion-historial',
    endpoint: url,
    filterFormSelector: '#modal-contenido', // Container for filters, used to attach events
    renderRow: (item) => {
      const status = getStatusText(item.Estado)
      const svg = getStatusSVG(item.Estado)
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
      `
    },
    filterFunction: (allData, form) => {
      const fechaFiltro = document.getElementById('filtro-fecha').value
      const filtrarPorMes = document.getElementById('filtrar-por-mes').checked
      const estadoFiltro = document.getElementById('filtro-estado').value
      const departamentoFiltro = document.getElementById('filtroDepartamento')?.value || ''

      return allData.filter((item) => {
        const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro
        const coincideDepartamento =
          !departamentoFiltro || item.DepartamentoNombre === departamentoFiltro

        if (!fechaFiltro) {
          return coincideEstado && coincideDepartamento
        }

        const fechaItem = item.Fecha // formato esperado: "2025-10-08"
        if (filtrarPorMes) {
          const mesFiltro = fechaFiltro.slice(0, 7)
          const mesItem = fechaItem.slice(0, 7)
          return mesItem === mesFiltro && coincideEstado && coincideDepartamento
        } else {
          return fechaItem === fechaFiltro && coincideEstado && coincideDepartamento
        }
      })
    },
  })
}

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
    const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)

    if (data.error) {
      throw new Error(data.error)
    }

    let html = generarDetallesSolicitudHTML(data)

    if (data.ComentariosAdmin) {
        if (data.TipoComentarioAdmin === 'Rechazo') {
            html += `
                <div class="mb-6 p-4 border rounded-lg bg-red-50 border-red-200">
                    <h4 class="text-md font-bold text-red-700 mb-2">Comentarios / Motivo del Rechazo</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`;
        } else if (data.TipoComentarioAdmin === 'Observacion') {
            html += `
                <div class="mb-6 p-4 border rounded-lg bg-yellow-50 border-yellow-200">
                    <h4 class="text-md font-bold text-yellow-700 mb-2">Observación</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`;
        } else {
            html += `
                <div class="mb-6 p-4 border rounded-lg bg-gray-100 border-gray-300">
                    <h4 class="text-md font-bold text-gray-700 mb-2">Comentario del Administrador</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`;
        }
    }
    html += generarProductosServiciosHTML(data)

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

function regresarHistorial() {
  const divVer = document.getElementById('div-ver-historial')
  if (divVer) divVer.classList.add('hidden')

  const divHistorial = document.getElementById('div-historial')
  if (divHistorial) divHistorial.classList.remove('hidden')

  console.log('Regresando a la tabla de historial')
}

/**
 * Lógica para el modal "Revisar Solicitudes"
 */
function initRevisarSolicitud() {
  setupClientSideTable({
    rowsSelector: '#tablaRevisarSolicitud tbody tr',
    paginationSelector: 'paginacion-enviar-revision',
    rowsPerPage: 10,
  })
}

async function initListaPagos() {
  createPaginatedTable({
    tableSelector: '#tablaListaPagos',
    paginationSelector: 'paginacion-lista-pagos',
    endpoint: 'api/pagos/all',
    renderRow: (p) => `
      <tr class="hover:bg-gray-50">
          <td class="py-3 px-6 text-left">${p.Folio || 'N/A'}</td>
          <td class="py-3 px-6 text-left">${p.Proveedor || 'N/A'}</td>
          <td class="py-3 px-6 text-right">$${parseFloat(p.Total).toFixed(2)}</td>
          <td class="py-3 px-6 text-left">${p.Estado || 'N/A'}</td>
          <td class="py-3 px-6 text-center">
              <button onclick="verDetallePago(${p.ID_Pago})" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">Ver</button>
          </td>
      </tr>
    `,
    noResultsMessage: 'No hay pagos registrados.',
  })
}

async function initRecepcionMaterial() {
    const ordenCompraSelect = document.getElementById('ordenCompraSelect');
    const solicitudFolioInput = document.getElementById('solicitudFolio');
    const proveedorNombreInput = document.getElementById('proveedorNombre');
    const productosRecepcionTable = document.getElementById('productosRecepcionTable');
    const formRecepcionMaterial = document.getElementById('form-recepcion-material');

    if (!ordenCompraSelect || !formRecepcionMaterial) return;

    let allOrdenesCompra = [];

    // Cargar órdenes de compra pendientes
    const loadOrdenesCompra = async () => {
        try {
            const ordenes = await SendDataEnd('api/ordenes-compra/pendientes-recepcion');
            allOrdenesCompra = ordenes; // Guardar todas las órdenes para referencia
            ordenCompraSelect.innerHTML = '<option value="">-- Seleccionar Orden de Compra --</option>';
            if (ordenes.length > 0) {
                ordenes.forEach(orden => {
                    const option = document.createElement('option');
                    option.value = orden.ID_Solicitud; // Usar ID_Solicitud para obtener detalles
                    option.textContent = `${orden.No_Folio} - ${orden.ProveedorNombre} (Total: $${parseFloat(orden.Total).toFixed(2)})`;
                    ordenCompraSelect.appendChild(option);
                });
            } else {
                ordenCompraSelect.innerHTML = '<option value="">No hay órdenes pendientes de recepción</option>';
            }
        } catch (error) {
            console.error('Error cargando órdenes de compra:', error);
            ordenCompraSelect.innerHTML = '<option value="">Error al cargar órdenes de compra</option>';
        }
    };

    // Manejar selección de Orden de Compra
    ordenCompraSelect.addEventListener('change', async () => {
        const idSolicitud = ordenCompraSelect.value;
        if (!idSolicitud) {
            solicitudFolioInput.value = '';
            proveedorNombreInput.value = '';
            productosRecepcionTable.innerHTML = '<tr><td colspan="3" class="text-center py-2">Seleccione una Orden de Compra para ver los productos.</td></tr>';
            return;
        }

        try {
            const data = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`);
            
            // Llenar campos de solo lectura
            solicitudFolioInput.value = data.No_Folio || '';
            proveedorNombreInput.value = data.proveedor?.RazonSocial || '';

            // Llenar tabla de productos
            productosRecepcionTable.innerHTML = '';
            if (data.productos && data.productos.length > 0) {
                data.productos.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="py-2 px-4 border-b">${p.Nombre}</td>
                        <td class="py-2 px-4 border-b text-right">${p.Cantidad}</td>
                        <td class="py-2 px-4 border-b">
                            <input type="number" name="productos[${p.ID_SolicitudProd}][cantidad_recibida]" 
                                class="w-full border-gray-300 rounded-md shadow-sm text-right cantidad-recibida" 
                                value="${p.Cantidad}" min="0" max="${p.Cantidad}">
                            <input type="hidden" name="productos[${p.ID_SolicitudProd}][id_producto]" value="${p.ID_Producto}">
                            <input type="hidden" name="productos[${p.ID_SolicitudProd}][id_solicitud_prod]" value="${p.ID_SolicitudProd}">
                        </td>
                    `;
                    productosRecepcionTable.appendChild(tr);
                });
            } else {
                productosRecepcionTable.innerHTML = '<tr><td colspan="3" class="text-center py-2">No hay productos en esta Orden de Compra.</td></tr>';
            }
        } catch (error) {
            console.error('Error cargando detalles de la orden de compra:', error);
            solicitudFolioInput.value = '';
            proveedorNombreInput.value = '';
            productosRecepcionTable.innerHTML = '<tr><td colspan="3" class="text-center py-2 text-red-500">Error al cargar detalles de la orden.</td></tr>';
        }
    });

    // Manejar envío del formulario
    formRecepcionMaterial.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idOrdenCompra = ordenCompraSelect.value;
        if (!idOrdenCompra) {
            mostrarNotificacion('Debe seleccionar una Orden de Compra.', 'error');
            return;
        }

        const formData = new FormData(formRecepcionMaterial);
        const productosRecibidos = [];
        productosRecepcionTable.querySelectorAll('tr').forEach(row => {
            const cantidadInput = row.querySelector('.cantidad-recibida');
            if (cantidadInput) {
                const idSolicitudProd = cantidadInput.name.match(/\[(\d+)\]/)[1]; // Extraer el ID
                const idProductoInput = row.querySelector(`input[name="productos[${idSolicitudProd}][id_producto]"]`);
                
                productosRecibidos.push({
                    id_solicitud_prod: idSolicitudProd,
                    id_producto: idProductoInput ? idProductoInput.value : null,
                    cantidad_recibida: parseInt(cantidadInput.value),
                    cantidad_pedida: parseInt(cantidadInput.max),
                });
            }
        });
        
        // Obtener archivo de remisión
        const remisionFile = document.getElementById('remisionArchivo').files[0];
        if(remisionFile) {
            formData.append('remision_file', remisionFile);
        }

        // Obtener archivo de factura de entrada
        const facturaEntradaFile = document.getElementById('facturaEntradaArchivo').files[0];
        if(facturaEntradaFile) {
            formData.append('factura_entrada_file', facturaEntradaFile);
        }

        formData.append('id_orden_compra', idOrdenCompra);
        formData.append('productos_recibidos', JSON.stringify(productosRecibidos)); // Enviar como JSON string

        const procesandoNotif = mostrarNotificacion('Confirmando recepción...', 'info', 999999);

        try {
            const result = await SendDataEnd('api/recepcion/confirmar', {
                method: 'POST',
                body: formData, // FormData con el archivo y otros campos
            });

            procesandoNotif.click();

            if (result.success) {
                mostrarNotificacion(result.message, 'success');
                formRecepcionMaterial.reset();
                productosRecepcionTable.innerHTML = '<tr><td colspan="3" class="text-center py-2">Seleccione una Orden de Compra para ver los productos.</td></tr>';
                loadOrdenesCompra(); // Recargar la lista de órdenes pendientes
            } else {
                mostrarNotificacion(result.message || 'Error al confirmar la recepción.', 'error');
            }
        } catch (error) {
            procesandoNotif.click();
            console.error('Error en la recepción de material:', error);
            mostrarNotificacion('Error de red al confirmar la recepción.', 'error');
        }
    });

    loadOrdenesCompra(); // Cargar órdenes al inicializar el modal
}

async function initBajasDestruccion() {
    const productoSelect = document.getElementById('productoSelect');
    const existenciaActualInput = document.getElementById('existenciaActual');
    const cantidadBajaInput = document.getElementById('cantidadBaja');
    const formBajasDestruccion = document.getElementById('form-bajas-destruccion');

    if (!productoSelect || !formBajasDestruccion) return;

    let allProducts = []; // Para almacenar todos los productos cargados

    // Cargar productos
    const loadProducts = async () => {
        try {
            const products = await SendDataEnd('api/product/all', { method: 'GET' });
            allProducts = products;
            productoSelect.innerHTML = '<option value="">-- Seleccionar Producto --</option>';
            if (products.length > 0) {
                products.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.ID_Producto;
                    option.textContent = `${p.Nombre} (Código: ${p.Codigo})`;
                    productoSelect.appendChild(option);
                });
            } else {
                productoSelect.innerHTML = '<option value="">No hay productos disponibles</option>';
            }
        } catch (error) {
            console.error('Error cargando productos:', error);
            productoSelect.innerHTML = '<option value="">Error al cargar productos</option>';
        }
    };

    // Manejar selección de producto
    productoSelect.addEventListener('change', () => {
        const idProducto = productoSelect.value;
        if (idProducto) {
            const selectedProduct = allProducts.find(p => String(p.ID_Producto) === idProducto);
            if (selectedProduct) {
                existenciaActualInput.value = selectedProduct.Existencia;
                cantidadBajaInput.max = selectedProduct.Existencia; // Establecer máximo para la cantidad a dar de baja
                cantidadBajaInput.value = ''; // Limpiar campo de cantidad
            }
        } else {
            existenciaActualInput.value = '';
            cantidadBajaInput.max = '';
            cantidadBajaInput.value = '';
        }
    });

    // Manejar envío del formulario
    formBajasDestruccion.addEventListener('submit', async (e) => {
        e.preventDefault();

        const idProducto = productoSelect.value;
        const cantidadBaja = parseInt(cantidadBajaInput.value);
        const existenciaActual = parseInt(existenciaActualInput.value);
        const motivoBaja = document.getElementById('motivoBaja').value;
        const fechaBaja = document.getElementById('fechaBaja').value;

        if (!idProducto || !cantidadBaja || !motivoBaja || !fechaBaja) {
            mostrarNotificacion('Por favor, complete todos los campos.', 'error');
            return;
        }

        if (cantidadBaja <= 0 || cantidadBaja > existenciaActual) {
            mostrarNotificacion('La cantidad a dar de baja debe ser mayor a 0 y no puede exceder la existencia actual.', 'error');
            return;
        }

        const payload = {
            id_producto: idProducto,
            cantidad_baja: cantidadBaja,
            motivo_baja: motivoBaja,
            fecha_baja: fechaBaja,
        };

        const procesandoNotif = mostrarNotificacion('Confirmando baja...', 'info', 999999);

        try {
            const result = await SendDataEnd('api/bajas/destruccion/registrar', {
                method: 'POST',
                body: payload,
            });

            procesandoNotif.click();

            if (result.success) {
                mostrarNotificacion(result.message, 'success');
                formBajasDestruccion.reset();
                loadProducts(); // Recargar productos para actualizar existencias
            } else {
                mostrarNotificacion(result.message || 'Error al confirmar la baja.', 'error');
            }
        } catch (error) {
            procesandoNotif.click();
            console.error('Error en baja por destrucción:', error);
            mostrarNotificacion('Error de red al confirmar la baja.', 'error');
        }
    });

    loadProducts(); // Cargar productos al inicializar el modal
}

function exportarRequisicionesExcel() {
    window.location.href = `${BASE_URL}api/exportar-requisiciones`;
}

function exportarHistorialExcel() {
    const fecha = document.getElementById('filtro-fecha').value;
    const porMes = document.getElementById('filtrar-por-mes').checked;
    const estado = document.getElementById('filtro-estado').value;
    const dpto = document.getElementById('filtroDepartamento')?.value || '';

    const params = new URLSearchParams();
    if (fecha) {
        params.append('fecha', fecha);
    }
    if (porMes) {
        params.append('por_mes', '1');
    }
    if (estado) {
        params.append('estado', estado);
    }
    if (dpto) {
        params.append('dpto', dpto);
    }

    const queryString = params.toString();
    window.location.href = `${BASE_URL}api/historial/exportar${queryString ? '?' + queryString : ''}`;
}

async function mostrarVer(idSolicitud) {
  document.getElementById('btn-exportar-requisiciones').classList.add('hidden') // Ocultar el botón de exportar
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
    const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)

    if (data.error) {
      throw new Error(data.error)
    }

    let html = generarDetallesSolicitudHTML(data)
    html += generarProductosServiciosHTML(data)

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

  const selectedProviderIds = new Set()

  if (btnGenerar) btnGenerar.disabled = true

  tbody.innerHTML =
    '<tr><td colspan="4" class="text-center text-gray-500">Cargando proveedores...</td></tr>'

  try {
    const response = await SendDataEnd('api/providers/all')
    const response2 = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)

    let todosLosProveedores = response

    if (!todosLosProveedores || !todosLosProveedores.length) {
      tbody.innerHTML =
        '<tr><td colspan="4" class="text-center text-gray-500">No hay proveedores registrados.</td></tr>'
      return
    }

    let proveedoresFiltrados = [...todosLosProveedores]
    const filasPorPagina = 10
    let paginaActual = 1

    function checkSeleccion() {
      if (btnGenerar) {
        btnGenerar.disabled = selectedProviderIds.size === 0
      }
    }

    function handleCheckboxChange(event) {
      const checkbox = event.target
      const providerId = checkbox.value

      if (checkbox.checked) {
        selectedProviderIds.add(providerId)
      } else {
        selectedProviderIds.delete(providerId)
      }
      checkSeleccion()
    }

    function renderizarTabla() {
      const totalPaginas = Math.ceil(proveedoresFiltrados.length / filasPorPagina) || 1
      paginaActual = Math.min(paginaActual, totalPaginas)

      const start = (paginaActual - 1) * filasPorPagina
      const end = start + filasPorPagina

      tbody.innerHTML = proveedoresFiltrados
        .slice(start, end)
        .map((p) => {
          // Comprueba si el ID está en el Set para mantenerlo marcado
          const isChecked = selectedProviderIds.has(String(p.ID_Proveedor))

          return `
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-t text-center">
                        <input type="checkbox"
                               name="proveedor_seleccionado[]"
                               value="${p.ID_Proveedor}"
                               class="check-proveedor accent-blue-600 h-4 w-4"
                               ${isChecked ? 'checked' : ''}>
                    </td>
                    <td class="py-2 px-4 border-t">${p.RazonSocial}</td>
                    <td class="py-2 px-4 border-t">${p.Tel_Contacto || '-'}</td>
                    <td class="py-2 px-4 border-t">${p.RFC || '-'}</td>
                </tr>
            `
        })
        .join('')

      tbody.querySelectorAll('.check-proveedor').forEach((check) => {
        check.addEventListener('change', handleCheckboxChange)
      })

      checkSeleccion()
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
    } else {
      renderizarTabla()
    }
  } catch (error) {
    console.error('Error al cargar proveedores:', error)
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-red-500">Error al cargar proveedores</td></tr>`
  }

  async function handleGenerarCotizacion() {
    const idSolicitud = document.getElementById('cotizar_id_solicitud').value

    if (selectedProviderIds.size === 0) {
      mostrarNotificacion('Por favor, seleccione al menos un proveedor.', 'alert')
      return
    }

    if (
      !(await Confirmar(
        'Generar solicitud de cotización',
        `¿Está seguro de que desea generar la solicitud de cotización para los ${selectedProviderIds.size} proveedor(es) seleccionado(s)?`,
      ))
    ) {
      return
    }

    const btn = document.getElementById('btn-generar-cotizacion')
    btn.disabled = true
    btn.textContent = 'Generando...'

    const providerIds = Array.from(selectedProviderIds)

    try {
      const result = await SendDataEnd('api/cotizacion/crear', {
        method: 'POST',
        body: {
          ID_Solicitud: idSolicitud,
          ID_Proveedores: providerIds,
        },
      })

      if (result.success) {
        alert(
          result.message ||
            'Solicitudes de cotización generadas y estado de la solicitud actualizado.',
        )
        abrirModal('revisar_solicitudes')
      } else {
        alert('Error: ' + (result.message || 'No se pudieron generar las cotizaciones.'))
        // Re-habilitar el botón si falla
        btn.disabled = false
        btn.textContent = 'Generar requisicion de Cotización'
      }
    } catch (error) {
      console.error('Error al generar cotización:', error)
      alert('Ocurrió un error de red al generar las cotizaciones.')
      btn.disabled = false
      btn.textContent = 'Generar requisicion de Cotización'
    }
  }

  if (btnGenerar && !btnGenerar.dataset.listenerAttached) {
    btnGenerar.addEventListener('click', handleGenerarCotizacion)
    btnGenerar.dataset.listenerAttached = 'true'
  }
}

function regresarTabla() {
  document.getElementById('div-ver').classList.add('hidden')
  document.getElementById('div-cotizar').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
  document.getElementById('btn-exportar-requisiciones').classList.remove('hidden') // Mostrar el botón de exportar
}

/**
 * Lógica para el modal "Dictamen de Solicitudes"
 */
async function initDictamenSolicitudes() {
  if (!document.getElementById('tablaDictamenSolicitudes')) return

  createPaginatedTable({
    tableSelector: '#tablaDictamenSolicitudes',
    paginationSelector: 'paginacion-dictamen',
    endpoint: 'api/solicitudes/en-revision',
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
    `,
  })
}

async function mostrarVerDictamen(idSolicitud) {
  document.getElementById('div-tabla').classList.add('hidden')
  const divVer = document.getElementById('div-ver-dictamen')
  divVer.classList.remove('hidden')

  const detallesContainer = document.getElementById('detallesDictamen')
  detallesContainer.innerHTML = `<p class="text-center text-gray-500">Cargando detalles de la solicitud ${idSolicitud}...</p>`

  try {
    const data = await SendDataEnd(`api/cotizacion/details/${idSolicitud}`)
    if (data.error) throw new Error(data.error)

    let html = generarDetallesSolicitudHTML(data)

    // Mostrar comentarios si existen (especialmente para rechazos)
    if (data.ComentariosAdmin) {
        if (data.TipoComentarioAdmin === 'Rechazo') {
            html += `
                <div class="mt-6 p-4 border rounded-lg bg-red-50 border-red-200">
                    <h4 class="text-md font-bold text-red-700 mb-2">Motivo del Rechazo</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`;
        } else if (data.TipoComentarioAdmin === 'Observacion') {
            html += `
                <div class="mt-6 p-4 border rounded-lg bg-yellow-50 border-yellow-200">
                    <h4 class="text-md font-bold text-yellow-700 mb-2">Observación</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`;
        } else { 
            html += `
                <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-300">
                    <h4 class="text-md font-bold text-gray-700 mb-2">Comentario del Administrador</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`;
        }
    }

    html += generarProductosServiciosHTML(data)

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
    const esAprobacion = nuevoEstado === 'Aprobada';
    const title = esAprobacion ? 'Aprobar Solicitud' : 'Rechazar Solicitud';
    const message = esAprobacion 
        ? 'Puede agregar observaciones (opcional):' 
        : 'Por favor, ingrese el motivo del rechazo (obligatorio):';
    const isRequired = !esAprobacion; // El comentario es obligatorio solo para rechazos

    const comentarios = await InputPrompt(title, message, isRequired);

    // Si el usuario cancela el modal, comentarios será null.
    if (comentarios === null) {
        return; 
    }

    const payload = {
        ID_Solicitud: idSolicitud,
        Estado: nuevoEstado,
        ComentariosAdmin: comentarios,
    };

    const procesandoNotif = mostrarNotificacion('Procesando dictamen...', 'info', 999999);

    try {
        const result = await SendDataEnd('api/solicitud/dictaminar', {
            method: 'POST',
            body: payload,
        });

        procesandoNotif.click();

        if (result.success) {
            mostrarNotificacion(result.message, 'success');
            abrirModal('dictamen_solicitudes'); 
        } else {
            mostrarNotificacion(result.message || 'Error al procesar el dictamen.', 'error');
        }
    } catch (error) {
        procesandoNotif.click();
        mostrarNotificacion('Error de red al procesar el dictamen.', 'error');
    }
}



/**
 * Lógica para el modal "Órdenes de Compra"
 */
function initOrdenesCompra() {
  setupClientSideTable({
    rowsSelector: '#tablaOrdenesCompra tbody tr',
    paginationSelector: 'paginacion-ordenes-compra',
    rowsPerPage: 10,
  })
}

async function mostrarVerOrdenCompra(idOrden, $idsession) {
  document.getElementById('div-tabla-ordenes').classList.add('hidden')
  document.getElementById('div-ver-orden').classList.remove('hidden')
  const detallesContainer = document.getElementById('detallesOrdenCompra')
  const iduser = $idsession

  detallesContainer.innerHTML = `<p>Cargando detalles de la orden ${idOrden}...</p>`
  try {
    const data = await SendDataEnd(`api/cotizacion/details/${idOrden}`)
    if (data.error) throw new Error(data.error)

    let html = generarDetallesSolicitudHTML(data)

    // Mostrar comentarios si existen (especialmente para rechazos)
    if (data.ComentariosAdmin) {
      if (data.TipoComentarioAdmin === 'Rechazo') {
        html += `
              <div class="mt-6 p-4 border rounded-lg bg-red-50 border-red-200">
                  <h4 class="text-md font-bold text-red-700 mb-2">Motivo del Rechazo</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
              </div>`;
      } else if (data.TipoComentarioAdmin === 'Observacion') {
        html += `
              <div class="mt-6 p-4 border rounded-lg bg-yellow-50 border-yellow-200">
                  <h4 class="text-md font-bold text-yellow-700 mb-2">Observación</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
              </div>`;
      } else { 
        html += `
              <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-300">
                  <h4 class="text-md font-bold text-gray-700 mb-2">Comentario del Administrador</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
              </div>`;
      }
    }

    html += generarProductosServiciosHTML(data)

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

    // Solo mostrar botones de acción si la solicitud está 'Aprobada'
    if (data.Estado === 'Aprobada') {
      html += `
                <div class="mt-8 flex justify-end space-x-4">
                    <button onclick="mostrarOrdenPdf(${idOrden}, 1)" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Ver Orden
                    </button>
                    
                    <!-- Aqui se necesitaria que el boton envie la orden por pdf al proveedor y que cambie de estado a "Por Pagar" -->
                    <button onclick="enviarOrdenCompra(${idOrden}, ${iduser}, this)" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
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

async function enviarOrdenCompra(idSolicitud, iduser, boton) {
  if (!(await Confirmar('Aviso', '¿Deseas enviar esta orden de compra a programación?'))) return

  const originalHtml = boton.innerHTML

  boton.disabled = true
  boton.textContent = `
    Enviando...
  `
  try {
    const data = await SendDataEnd(`api/orden/enviar-proveedor/${idSolicitud}/${iduser}`, {
      method: 'POST',
    })

    if (!response.ok || !data.success) {
      mostrarNotificacion(data.message || 'Error desconocido al enviar la orden.', 'error')

      boton.disabled = false
      boton.innerHTML = originalHtml
      return
    }

    mostrarNotificacion('✅ La orden fue enviada al proveedor y en espera de programación', 'success')

    // Si la operación es exitosa, recargamos el modal.
    abrirModal('ordenes_compra')
  } catch (error) {
    console.error('Error al enviar orden:', error)
    mostrarNotificacion('❌ Ocurrió un error al intentar enviar la orden.', 'error')

    boton.disabled = false
    boton.innerHTML = originalHtml
  }
}

/**
 * Lógica para el CRUD de proveedores
 */
function initCrudProveedores() {
  const tabla = document.getElementById('tabla-proveedores')
  if (!tabla) return

  initProveedorTabla()
  initProveedorPantallas()
  initProveedorForm()
  initProveedorEditarForm()
  initProveedorActions(tabla)
}

function initProveedorTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-proveedores tr[data-id]',
    paginationSelector: 'paginacion-proveedores',
    filterFormSelector: '#form-filtros-proveedores',
    filterFunction: (row, form) => {
      const nombreFiltro = (form.querySelector('#buscar-nombre')?.value || '').toLowerCase()
      const servicioFiltro = (form.querySelector('#buscar-servicio')?.value || '').toLowerCase()

      const razonsocial = row.querySelector('.razonsocial')?.textContent.toLowerCase() || ''
      const servicio = row.querySelector('.servicio')?.textContent.toLowerCase() || ''

      return razonsocial.includes(nombreFiltro) && servicio.includes(servicioFiltro)
    },
    rowsPerPage: 10,
  })
}

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

function initProveedorForm() {
  const formProveedor = document.getElementById('form-agregar-proveedor')
  const pantallaAgregar = document.getElementById('pantalla-agregar-proveedor')
  const pantallaLista = document.getElementById('pantalla-lista-proveedores')

  if (!formProveedor) return

  formProveedor.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formProveedor)

    try {
      const result = await SendDataEnd('proveedores/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Proveedor agregado correctamente ✅', 'success')
        formProveedor.reset()
        abrirModal('crud_proveedores')
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

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
      const result = await SendDataEnd(`proveedores/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Proveedor actualizado ✅', 'success')

        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          fila.querySelector('.razonsocial').textContent = formData.get('RazonSocial')
          fila.querySelector('.servicio').textContent = formData.get('Servicio')

          fila.dataset.rfc = formData.get('RFC')
          fila.dataset.banco = formData.get('Banco')
          fila.dataset.cuenta = formData.get('Cuenta')
          fila.dataset.clabe = formData.get('Clabe')
          fila.dataset.telContacto = formData.get('Tel_Contacto')
          fila.dataset.nombreContacto = formData.get('Nombre_Contacto')
          fila.dataset.correo = formData.get('correo')
        }
        abrirModal('crud_proveedores')
      } else {
        mostrarNotificacion(result.message || 'Error al actualizar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

function initProveedorActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    const svgEliminar = e.target.closest('svg')
    if (svgEliminar) {
      const btnEliminar = svgEliminar.closest("[id^='btn-eliminar-proveedor-']")
      if (btnEliminar) {
        e.preventDefault()
        const id = btnEliminar.dataset.id
        if (!(await Confirmar('Eliminar Proveedor', '¿Seguro que deseas eliminar este proveedor?')))
          return

        SendDataEnd(`proveedores/eliminarProveedor/${id}`, {
          method: 'POST',
        })
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

    const btnEditar = e.target.closest("[id^='btn-editar-proveedor-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return
    const credito = fila.dataset.diasCredito > 0

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

    const checkboxCredito = document.getElementById('editar-tiene_credito')
    checkboxCredito.checked = credito

    checkboxCredito.dispatchEvent(new Event('input', { bubbles: true }))

    const dias_credito = document.getElementById('editar-dias_credito')
    const monto_credito = document.getElementById('editar-monto_credito')
    dias_credito.value = credito ? fila.dataset.diasCredito : 0
    monto_credito.value = credito ? fila.dataset.montoCredito : 0

    document.getElementById('pantalla-lista-proveedores').classList.add('hidden')
    document.getElementById('pantalla-editar-proveedor').classList.remove('hidden')
  })
}

/**
 * Lógica para el CRUD de usuarios con Alpine.js
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
      const endpoint = form.action.replace(BASE_URL, '');
      const text = await SendDataEnd(endpoint, {
        method: 'POST',
        body: formData,
        responseType: 'text'
      })
      
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
 * Lógica para el modal "Aprobar Solicitudes" (Jefes de Depto) con Alpine.js
 */
function aprobarSolicitudes() {
  return {
    providers: [],
    init() {
      setupClientSideTable({
        rowsSelector: '#tablaAprobarSolicitudes tr.solicitud-row',
        paginationSelector: 'paginacion-aprobar-solicitudes',
        rowsPerPage: 10,
      })
    },

    verDetalle: async function (idSolicitud) {
      document.getElementById('div-tabla-aprobacion').classList.add('hidden')
      const divVer = document.getElementById('div-ver-aprobacion')
      divVer.classList.remove('hidden')

      const detallesContainer = document.getElementById('detalles-aprobacion-solicitud')
      detallesContainer.innerHTML = '<p class="text-center text-gray-500">Cargando detalles...</p>'

      try {
        const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`, {})
        if (data.error) throw new Error(data.error)
        
        // Cargar proveedores
        this.providers = await SendDataEnd('api/providers/all');

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

        if (data.ComentariosUser) {
            html += `
                <div class="mt-6">
                    <h4 class="text-md font-bold mb-2">Comentarios del Solicitante</h4>
                    <p class="p-4 border rounded-lg bg-gray-50 whitespace-pre-wrap">${data.ComentariosUser}</p>
                </div>
            `
        }
        
        // Botones de acción
        // html += `
        //   <div id="botones-accion-aprobar" class="mt-8 flex justify-end space-x-4">
        //       <button @click="dictaminar(${idSolicitud}, 'rechazar')" class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700">Rechazar</button>
        //       <button @click="mostrarSeleccionProveedores()" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">Aprobar y Cotizar</button>
        //       <button @click="dictaminar(${idSolicitud}, 'aprobar')" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700">Aprobar y Enviar a Compras</button>
        //   </div>`
          
        // Sección oculta para seleccionar proveedores
        // html += `
        //     <div id="seccion-cotizar" class="hidden mt-8 border-t pt-6">
        //         <h3 class="text-lg font-bold mb-4">Selecciona Proveedores para Cotizar</h3>
        //         <div class="overflow-y-auto max-h-64 border rounded-md mb-4">
        //             <table class="min-w-full">
        //                 <thead class="bg-gray-100 sticky top-0">
        //                     <tr>
        //                         <th class="py-2 px-4 text-center"><input type="checkbox" @click="seleccionarTodosProveedores($event)"></th>
        //                         <th class="py-2 px-4 text-left">Proveedor</th>
        //                     </tr>
        //                 </thead>
        //                 <tbody id="tabla-proveedores-aprobar">
        // `
        // this.providers.forEach(provider => {
        //     html += `
        //         <tr>
        //             <td class="py-2 px-4 text-center border-t">
        //                 <input type="checkbox" class="proveedor-checkbox" value="${provider.ID_Proveedor}">
        //             </td>
        //             <td class="py-2 px-4 border-t">${provider.RazonSocial}</td>
        //         </tr>
        //     `
        // });
        html += `
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end space-x-4">
                    <button @click="enviarAprobacionYCotizacion(${idSolicitud})" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700"> Aprobar y Enviar a Cotizar</button>
                </div>
            </div>
        `

        detallesContainer.innerHTML = html
      } catch (error) {
        detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
      }
    },

    regresarATabla: function () {
      document.getElementById('div-ver-aprobacion').classList.add('hidden')
      document.getElementById('div-tabla-aprobacion').classList.remove('hidden')
    },
    
    mostrarSeleccionProveedores: function() {
        document.getElementById('botones-accion-aprobar').classList.add('hidden');
        document.getElementById('seccion-cotizar').classList.remove('hidden');
    },

    ocultarSeleccionProveedores: function() {
        document.getElementById('seccion-cotizar').classList.add('hidden');
        document.getElementById('botones-accion-aprobar').classList.remove('hidden');
    },

    seleccionarTodosProveedores: function(event) {
        document.querySelectorAll('#tabla-proveedores-aprobar .proveedor-checkbox').forEach(checkbox => {
            checkbox.checked = event.target.checked;
        });
    },

    enviarAprobacionYCotizacion: async function(idSolicitud) {
        const payload = {
            ID_Solicitud: idSolicitud,
        };

        const procesandoNotif = mostrarNotificacion('Procesando...', 'info', 999999);

        try {
            const result = await SendDataEnd('api/solicitud/aprobar-y-cotizar', {
                method: 'POST',
                body: payload
            });
            
            procesandoNotif.click();

            if (result.success) {
                mostrarNotificacion(result.message, 'success');
                abrirModal('aprobar_solicitudes');
            } else {
                mostrarNotificacion(result.message || 'Error al procesar la solicitud.', 'error');
            }
        } catch (error) {
            procesandoNotif.click();
            mostrarNotificacion('Error de red al procesar la solicitud.', 'error');
        }
    },

    dictaminar: async function (idSolicitud, accion) {
        const esRechazo = accion === 'rechazar';
        let comentarios = null;

        if (esRechazo) {
            comentarios = await InputPrompt(
                'Rechazar Solicitud',
                'Por favor, ingrese el motivo del rechazo (obligatorio):',
                true
            );
            if (comentarios === null) {
                return; // El usuario canceló el modal
            }
        } else {
            const confirmado = await Confirmar(
                'Aprobar Solicitud',
                '¿Está seguro de que desea aprobar esta solicitud y enviarla a Revisión de Compras?'
            );
            if (!confirmado) {
                return; // El usuario canceló
            }
        }

        const payload = {
            ID_Solicitud: idSolicitud,
            accion: accion,
        };

        if (comentarios) {
            payload.comentarios = comentarios;
        }

        // Muestra una notificación de "procesando" que no se cierra automáticamente
        const procesandoNotif = mostrarNotificacion('Procesando...', 'info', 999999);

        try {
            const result = await SendDataEnd('api/solicitud/dictaminar-jefe', {
                method: 'POST',
                body: payload,
            });

            // Cierra la notificación de "procesando"
            procesandoNotif.click();

            if (result.success) {
                mostrarNotificacion(result.message, 'success');
                // Recargar el modal principal para refrescar la lista
                abrirModal('aprobar_solicitudes');
            } else {
                mostrarNotificacion(result.message || `Error al ${accion} la solicitud.`, 'error');
            }
        } catch (error) {
            // Cierra la notificación de "procesando" en caso de error
            procesandoNotif.click();
            mostrarNotificacion(`Error de red al intentar ${accion} la solicitud.`, 'error');
        }
    },
  }
}

/**
 * Lógica para pagos pendientes (facturas)
 */
async function initPagosPendientes() {
  const tbodyContado = document.getElementById('body-contado')
  const tbodyCredito = document.getElementById('body-credito')
  const detalleContado = document.getElementById('detalle-contado')
  const detalleCredito = document.getElementById('detalle-credito')

  tbodyContado.innerHTML = `<tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>`
  tbodyCredito.innerHTML = tbodyContado.innerHTML

  function calcularFechaVencimiento(fechaStr, diasStr) {
    if (!fechaStr) {
      return new Date('2999-12-31')
    }
    const fechaSimple = fechaStr.split(' ')[0]
    const partes = fechaSimple.split('-')
    if (partes.length !== 3) return new Date('2999-12-31')
    const anio = parseInt(partes[0])
    const mes = parseInt(partes[1]) - 1
    const dia = parseInt(partes[2])
    const fechaAprobacion = new Date(anio, mes, dia)
    const diasCredito = parseInt(diasStr) || 0
    fechaAprobacion.setDate(fechaAprobacion.getDate() + diasCredito)
    return fechaAprobacion
  }

  /**
   * Determina la clase CSS para la fila de la tabla según la fecha de vencimiento.
   * @param {Date} fechaVencimiento - La fecha de vencimiento calculada.
   * @param {Date} hoyNormalizado - La fecha de hoy (normalizada a medianoche).
   * @returns {string} - Las clases de Tailwind CSS correspondientes.
   */
  function getClaseSemaforo(fechaVencimiento, hoyNormalizado) {
    const diffMs = fechaVencimiento.getTime() - hoyNormalizado.getTime()

    const diasDiferencia = Math.floor(diffMs / 86400000)

    if (diasDiferencia < 0) {
      // Usamos gris oscuro (Tailwind) para "negro", y texto blanco
      return 'bg-gray-900 text-white hover:bg-gray-800'
    }

    if (diasDiferencia < 5) {
      return 'bg-red-100 text-red-800 hover:bg-red-200'
    }

    if (diasDiferencia < 15) {
      return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'
    }

    return 'hover:bg-gray-50'
  }

  try {
    const ordenes = await SendDataEnd('api/orden-compra/alldata')

    if (!ordenes || ordenes.length === 0) {
      tbodyContado.innerHTML = `<tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">No hay registros disponibles.</td></tr>`
      tbodyCredito.innerHTML = tbodyContado.innerHTML
      return
    }

    const detallesPromises = ordenes.map((o) =>
      SendDataEnd(`api/orden-compra/details/${o.ID_Solicitud}`),
    )
    const detalles = await Promise.all(detallesPromises)

    let ordenesContado = []
    let ordenesCredito = []

    detalles.forEach((det) => {
      if (det.EstadoOrden !== 'Por Pagar') return
      if (det.MetodoPago == '0') {
        ordenesContado.push(det)
      } else if (det.MetodoPago == '1') {
        ordenesCredito.push(det)
      }
    })

    ordenesCredito.sort((a, b) => {
      const fechaVencimientoA = calcularFechaVencimiento(
        a.Fecha_Aprobacion,
        a.proveedor?.Dias_Credito,
      )
      const fechaVencimientoB = calcularFechaVencimiento(
        b.Fecha_Aprobacion,
        b.proveedor?.Dias_Credito,
      )
      return fechaVencimientoA - fechaVencimientoB
    })

    tbodyContado.innerHTML = ''
    tbodyCredito.innerHTML = ''

    if (ordenesContado.length === 0) {
      tbodyContado.innerHTML = `<tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">No hay registros de contado.</td></tr>`
    } else {
      ordenesContado.forEach((det) => {
        const fila = `
              <tr class="hover:bg-gray-50 transition">
               <td class="px-4 py-2 border-b">${det.DepartamentoNombre || '-'}</td>
               <td class="px-4 py-2 border-b">${det.Complejo || '-'}</td>
               <td class="px-4 py-2 border-b">${det.No_Folio || '-'}</td>
               <td class="px-4 py-2 border-b">${det.proveedor?.RazonSocial || '-'}</td>
               <td class="px-4 py-2 border-b">${det.proveedor?.Banco || '-'}</td>
               <td class="px-4 py-2 border-b">${det.cotizacion?.Total ? '$' + det.cotizacion.Total : '-'}</td>
               <td class="px-4 py-2 border-b"></td>
               <td class="px-4 py-2 border-b text-center">
                  <button onclick="mostrarDetalleOrden(${det.ID_Solicitud}, '${det.MetodoPago}')" 
                          class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                    VER
                  </button>
                </td>
              </tr>
            `
        tbodyContado.insertAdjacentHTML('beforeend', fila)
      })
    }

    const hoy = new Date()
    hoy.setHours(0, 0, 0, 0)

    if (ordenesCredito.length === 0) {
      tbodyCredito.innerHTML = `<tr><td colspan="6" class="px-4 py-3 text-center text-gray-500">No hay registros a crédito.</td></tr>`
    } else {
      ordenesCredito.forEach((det) => {
        const fechaVencimiento = calcularFechaVencimiento(
          det.Fecha_Aprobacion,
          det.proveedor?.Dias_Credito,
        )

        const claseFila = getClaseSemaforo(fechaVencimiento, hoy)
        
        const diffTime = fechaVencimiento - hoy;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        let diasRestantesHtml = ``;

        if (!det.proveedor?.Dias_Credito) {
            diasRestantesHtml = 'N/A';
        } else if (diffDays < 0) {
            diasRestantesHtml = `<span class="font-semibold text-red-600">Vencido por ${Math.abs(diffDays)} día(s)</span>`;
        } else if (diffDays === 0) {
            diasRestantesHtml = '<span class="font-semibold text-yellow-600">Vence hoy</span>';
        } else {
            diasRestantesHtml = `${diffDays} día(s) restante(s)`;
        }

        const fila = `
              <tr class="${claseFila} transition">
               <td class="px-4 py-2 border-b">${det.DepartamentoNombre || '-'}</td>
               <td class="px-4 py-2 border-b">${det.Complejo || '-'}</td>
               <td class="px-4 py-2 border-b">${det.No_Folio || '-'}</td>
               <td class="px-4 py-2 border-b">${det.proveedor?.RazonSocial || '-'}</td>
               <td class="px-4 py-2 border-b">${det.proveedor?.Banco || '-'}</td>
               <td class="px-4 py-2 border-b">${det.cotizacion?.Total ? '$' + det.cotizacion.Total : '-'}</td>
               <td class="px-4 py-2 border-b text-center">${diasRestantesHtml}</td>
               <td class="px-4 py-2 border-b text-center">
                  <button onclick="mostrarDetalleOrden(${det.ID_Solicitud}, '${det.MetodoPago}')" 
                          class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                    VER
                  </button>
                </td>
              </tr>
            `
        tbodyCredito.insertAdjacentHTML('beforeend', fila)
      })
    }
  } catch (error) {
    console.error('Error al cargar las órdenes:', error)
    tbodyContado.innerHTML = `<tr><td colspan="6" class="px-4 py-3 text-center text-red-500">Error al cargar datos.</td></tr>`
    tbodyCredito.innerHTML = tbodyContado.innerHTML
  }
}
// --- Función de navegación para VER detalles ---
async function mostrarDetalleOrden(id, metodoPago) {
  const detalleDiv =
    metodoPago == '0'
      ? document.getElementById('detalle-contado')
      : document.getElementById('detalle-credito')
  const tablaDiv =
    metodoPago == '0'
      ? document.getElementById('tabla-contado')
      : document.getElementById('tabla-credito')

  tablaDiv.classList.add('hidden')
  detalleDiv.classList.remove('hidden')

  detalleDiv.innerHTML = `<p class="text-center text-gray-500">Cargando detalles de la orden #${id}...</p>`

  try {
    const data = await SendDataEnd(`api/orden-compra/details/${id}`)

    const prov = data.proveedor || {}
    const totalFormateado = parseFloat(data.cotizacion?.Total || 0).toLocaleString('es-MX', {
      style: 'currency',
      currency: 'MXN',
    })

    const metodoPagoTexto =
      data.MetodoPago == 0
        ? 'Efectivo'
        : data.MetodoPago == 1
          ? 'Crédito'
          : data.MetodoPago == 9
            ? 'En Espera'
            : 'N/A'

    let html = `
      <div class="flex justify-between items-center mb-4">
        <button onclick="volverATabla('${metodoPago}')" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Detalle Orden #${data.No_Folio || id}</h2>
        <div></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
        <div><strong>Fecha de solicitud:</strong> ${data.Fecha || 'N/A'}</div>
        <div><strong>Departamento:</strong> ${data.DepartamentoNombre || 'N/A'}</div>
        <div><strong>Proyecto:</strong> ${data.Complejo || 'N/A'}</div>
        <div><strong>Importe total:</strong> <span class="font-bold">${totalFormateado}</span></div>
        <div><strong>Método de pago:</strong> ${metodoPagoTexto}</div> </div>

      <h3 class="text-md font-semibold mb-3 text-gray-700">INFORMACIÓN DEL PROVEEDOR</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
        <div><strong>Razón social:</strong> ${prov.RazonSocial || 'N/A'}</div>
        <div><strong>RFC:</strong> ${prov.RFC || 'N/A'}</div>
        <div><strong>Banco del proveedor:</strong> ${prov.Banco || 'N/A'}</div>
        <div><strong>Cuenta del proveedor:</strong> ${prov.Cuenta || 'N/A'}</div>
        <div><strong>Clabe interbancaria:</strong> ${prov.Clabe || 'N/A'}</div>
        <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
        <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${ 
          prov.Monto_Credito
            ? parseFloat(prov.Monto_Credito).toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN',
              })
            : 'N/A'
        }</div>
      </div>

      <h3 class="text-md font-semibold mb-3 text-gray-700">PRODUCTOS DE LA ORDEN</h3>
      
      <div class="overflow-x-auto mb-6">
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

    if (data.productos && data.productos.length > 0) {
      data.productos.forEach((p) => {
        const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
        html += `
            <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>
                <td class="py-2 px-4 border-t">${p.Nombre}</td>
                <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                <td class="py-2 px-4 border-t text-right">$${costoTotal}</td>
            </tr>
        `
      })
    } else {
      html += `<tr><td colspan="5" class="text-center py-3">No hay productos en esta orden.</td></tr>`
    }

    html += `
            </tbody>
        </table>
      </div>
      `
    html += GetFiles(data)

    html += `
      
<div class="block mb-6 p-4 border rounded-lg">
     <label for="archivo-factura" class="block text-sm font-medium text-black-500 ">Adjuntar factura (Imágene o PDF)</label>
     
     <input type="file" id="archivo-factura" name="factura" accept="image/*,.pdf" class="mt-1 p-1 block w-full text-sm text-black-300 border-gray-700 rounded cursor-pointer bg-gray-200 focus:outline-none border-2">
     
     <p class="mt-1 text-sm text-gray-500">Solo se permite un archivo.</p>
</div>
      
    `

    html += `
      <div class="flex justify-between mt-6 pt-4  gap-4">
        <button onclick="CerrarOrden(${id}, '${metodoPago}', volverATabla, initPagosPendientes)" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded-lg transition w-1/2">
          Cerrar requisición
        </button>
        
        <button onclick="enviarATesoreria(${id}, '${metodoPago}')" class="bg-green-500 hover:bg-green-700 text-white font-semibold py-1 px-4 rounded-lg transition w-1/2">
          Enviar a tesorería para ficha
        </button>
        
      </div>
      
      <div class="flex flex-col gap-4 mt-4">
      <button onclick="CancelarOrdenFactura(${id}, '${metodoPago}', volverATabla, initPagosPendientes)" class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-4 rounded-lg ">
          Cancelar requisición
        </button>
      </div>
    `

    detalleDiv.innerHTML = html
  } catch (error) {
    console.error('Error al cargar detalle de orden:', error)
    detalleDiv.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}
// --- Volver a la tabla desde detalle ---
function volverATabla(metodoPago) {
  const detalleDiv =
    metodoPago == '0'
      ? document.getElementById('detalle-contado')
      : document.getElementById('detalle-credito')
  const tablaDiv =
    metodoPago == '0'
      ? document.getElementById('tabla-contado')
      : document.getElementById('tabla-credito')

  detalleDiv.classList.add('hidden')
  tablaDiv.classList.remove('hidden')
}
// --- Funciones de navegación principales ---

//Funcion para cambio de estado
async function enviarATesoreria(idSolicitud, metodoPago) {
  const facturaElement = document.getElementById('archivo-factura')
  const facturaFile = facturaElement.files[0]

  try {
    // if (!facturaFile) {
    //   mostrarNotificacion('Por favor, selecciona un archivo primero.', 'error', 5000)
    //   return
    // }
    const formData = new FormData()
    formData.append('factura', facturaFile)
    formData.append('nuevoEstado', 'En Proceso de Pago')
    const data = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
        method: 'POST',
        body: formData,
    })

    if (data.success) {
      alert('Enviado a tesoreria')
      volverATabla(metodoPago)
      initPagosPendientes() // refrescar tabla
    } else {
      alert(data.messages['error'])
    }
  } catch (error) {
    console.error('Error al actualizar estado:', error)
    alert('Ocurrió un error al actualizar el estado')
  }
}

/**
 * Lógica para fichas de pago
 */
async function initFichasPago() {
  const tbodyContado = document.getElementById('body-contado')
  const tbodyCredito = document.getElementById('body-credito')

  tbodyContado.innerHTML = `<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>`
  tbodyCredito.innerHTML = tbodyContado.innerHTML

  function calcularFechaVencimiento(fechaStr, diasStr) {
    if (!fechaStr) {
      return new Date('2999-12-31')
    }
    const fechaSimple = fechaStr.split(' ')[0]
    const partes = fechaSimple.split('-')
    if (partes.length !== 3) return new Date('2999-12-31')
    const anio = parseInt(partes[0])
    const mes = parseInt(partes[1]) - 1
    const dia = parseInt(partes[2])
    const fechaAprobacion = new Date(anio, mes, dia)
    const diasCredito = parseInt(diasStr) || 0
    fechaAprobacion.setDate(fechaAprobacion.getDate() + diasCredito)
    return fechaAprobacion
  }

  function getClaseSemaforo(fechaVencimiento, hoyNormalizado) {
    const diffMs = fechaVencimiento.getTime() - hoyNormalizado.getTime()

    // Convertimos a días
    const diasDiferencia = Math.floor(diffMs / 86400000)

    //  Vencido (Negro)
    if (diasDiferencia < 0) {
      return 'bg-gray-900 text-white hover:bg-gray-800'
    }
    //  Vence hoy o en menos de 5 días (Rojo)
    if (diasDiferencia < 5) {
      return 'bg-red-100 text-red-800 hover:bg-red-200'
    }
    //  Vence en menos de 15 días (Amarillo)
    if (diasDiferencia < 15) {
      return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'
    }
    //  Más de 15 días (Blanco/Default)
    return 'hover:bg-gray-50'
  }

  try {
    const ordenes = await SendDataEnd('api/orden-compra/alldata')

    if (!ordenes || ordenes.length === 0) {
      tbodyContado.innerHTML = `<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No hay registros disponibles.</td></tr>`
      tbodyCredito.innerHTML = tbodyContado.innerHTML
      return
    }

    const detallesPromises = ordenes.map((o) =>
      SendDataEnd(`api/orden-compra/details/${o.ID_Solicitud}`),
    )
    const detalles = await Promise.all(detallesPromises)

    let ordenesContado = []
    let ordenesCredito = []

    // Filtrar y separar
    detalles.forEach((det) => {
      if (det.EstadoOrden !== 'En Proceso de Pago') return

      if (det.MetodoPago == '0') {
        ordenesContado.push(det)
      } else if (det.MetodoPago == '1') {
        ordenesCredito.push(det)
      }
    })

    // Ordenar el array de crédito
    ordenesCredito.sort((a, b) => {
      const fechaVencimientoA = calcularFechaVencimiento(
        a.Fecha_Aprobacion,
        a.proveedor?.Dias_Credito,
      )
      const fechaVencimientoB = calcularFechaVencimiento(
        b.Fecha_Aprobacion,
        b.proveedor?.Dias_Credito,
      )
      return fechaVencimientoA - fechaVencimientoB
    })

    tbodyContado.innerHTML = ''
    tbodyCredito.innerHTML = ''

    // Renderizar tabla de Contado
    if (ordenesContado.length === 0) {
      tbodyContado.innerHTML = `<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No hay registros de contado.</td></tr>`
    } else {
      ordenesContado.forEach((det) => {
        const fila = `
          <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-2 border-b">${det.No_Folio || '-'}</td>
            <td class="px-4 py-2 border-b">${det.DepartamentoNombre || '-'}</td>
            <td class="px-4 py-2 border-b">${det.Complejo || '-'}</td>
            <td class="px-4 py-2 border-b">${det.proveedor?.RazonSocial || '-'}</td>
            <td class="px-4 py-2 border-b">${det.proveedor?.Banco || '-'}</td>
            <td class="px-4 py-2 border-b">${det.cotizacion?.Total ? '$' + det.cotizacion.Total : '-'}</td>
            <td class="px-4 py-2 border-b text-center">
              <button onclick="mostrarDetalleFicha(${det.ID_Solicitud}, '${det.MetodoPago}')" 
                      class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                VER
              </button>
            </td>
          </tr>
        `
        tbodyContado.insertAdjacentHTML('beforeend', fila)
      })
    }

    const hoy = new Date()
    hoy.setHours(0, 0, 0, 0)

    if (ordenesCredito.length === 0) {
      tbodyCredito.innerHTML = `<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No hay registros a crédito.</td></tr>`
    } else {
      ordenesCredito.forEach((det) => {
        const fechaVencimiento = calcularFechaVencimiento(
          det.Fecha_Aprobacion,
          det.proveedor?.Dias_Credito,
        )
        const claseFila = getClaseSemaforo(fechaVencimiento, hoy)

        const fila = `
          <tr class="${claseFila} transition">
            <td class="px-4 py-2 border-b">${det.No_Folio || '-'}</td>
            <td class="px-4 py-2 border-b">${det.DepartamentoNombre || '-'}</td>
            <td class="px-4 py-2 border-b">${det.Complejo || '-'}</td>
            <td class="px-4 py-2 border-b">${det.proveedor?.RazonSocial || '-'}</td>
            <td class="px-4 py-2 border-b">${det.proveedor?.Banco || '-'}</td>
            <td class="px-4 py-2 border-b">${det.cotizacion?.Total ? '$' + det.cotizacion.Total : '-'}</td>
            <td class="px-4 py-2 border-b text-center">
              <button onclick="mostrarDetalleFicha(${det.ID_Solicitud}, '${det.MetodoPago}')" 
                      class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">
                VER
              </button>
            </td>
          </tr>
        `
        tbodyCredito.insertAdjacentHTML('beforeend', fila)
      })
    }
  } catch (error) {
    console.error('Error al cargar las fichas:', error)
    tbodyContado.innerHTML = `<tr><td colspan="7" class="px-4 py-3 text-center text-red-500">Error al cargar datos.</td></tr>`
    tbodyCredito.innerHTML = tbodyContado.innerHTML
  }
}

// --- Función de navegación para VER detalles ---
async function mostrarDetalleFicha(id, metodoPago) {
  const detalleDiv =
    metodoPago == '0'
      ? document.getElementById('detalle-contado')
      : document.getElementById('detalle-credito')
  const tablaDiv =
    metodoPago == '0'
      ? document.getElementById('tabla-contado')
      : document.getElementById('tabla-credito')

  tablaDiv.classList.add('hidden')
  detalleDiv.classList.remove('hidden')

  detalleDiv.innerHTML = `<p class="text-center text-gray-500">Cargando detalles de la orden #${id}...</p>`

  try {
    const data = await SendDataEnd(`api/orden-compra/details/${id}`)

    const prov = data.proveedor || {}
    const totalFormateado = parseFloat(data.cotizacion?.Total || 0).toLocaleString('es-MX', {
      style: 'currency',
      currency: 'MXN',
    })

    const metodoPagoTexto =
      data.MetodoPago == 0
        ? 'Efectivo'
        : data.MetodoPago == 1
          ? 'Crédito'
          : data.MetodoPago == 9
            ? 'En Espera'
            : 'N/A'

    let html = `
      <div class="flex justify-between items-center mb-4">
        <button onclick="volverAFichas('${metodoPago}')" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
        <h2 class="text-lg font-semibold">Detalle Orden #${data.No_Folio || id}</h2>
        <div></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
        <div><strong>Fecha de solicitud:</strong> ${data.Fecha || 'N/A'}</div>
        <div><strong>Departamento:</strong> ${data.DepartamentoNombre || 'N/A'}</div>
        <div><strong>Proyecto:</strong> ${data.Complejo || 'N/A'}</div>
        <div><strong>Importe total:</strong> <span class="font-bold">${totalFormateado}</span></div>
        <div><strong>Método de pago:</strong> ${metodoPagoTexto}</div>
      </div>

      <h3 class="text-md font-semibold mb-3 text-gray-700">INFORMACIÓN DEL PROVEEDOR</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
        <div><strong>Razón social:</strong> ${prov.RazonSocial || 'N/A'}</div>
        <div><strong>RFC:</strong> ${prov.RFC || 'N/A'}</div>
        <div><strong>Banco del proveedor:</strong> ${prov.Banco || 'N/A'}</div>
        <div><strong>Cuenta del proveedor:</strong> ${prov.Cuenta || 'N/A'}</div>
        <div><strong>Clabe interbancaria:</strong> ${prov.Clabe || 'N/A'}</div>
        <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
        <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${ 
          prov.Monto_Credito
            ? parseFloat(prov.Monto_Credito).toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN',
              })
            : 'N/A'
        }</div>
      </div>

      <h3 class="text-md font-semibold mb-3 text-gray-700">PRODUCTOS DE LA ORDEN</h3>
      
      <div class="overflow-x-auto mb-6">
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

    if (data.productos && data.productos.length > 0) {
      data.productos.forEach((p) => {
        const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
        html += `
            <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>
                <td class="py-2 px-4 border-t">${p.Nombre}</td>
                <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                <td class="py-2 px-4 border-t text-right">$${costoTotal}</td>
            </tr>
        `
      })
    } else {
      html += `<tr><td colspan="5" class="text-center py-3">No hay productos en esta orden.</td></tr>`
    }

    html += `
            </tbody>
        </table>
      </div>
      `
    html += GetFiles(data)

    html += `
<div class="block mb-6 p-4 border rounded-lg">
     <label for="archivo-ficha" class="block text-sm font-medium text-black-500 ">Adjuntar Ficha (Imágene o PDF)</label>
     
     <input type="file" id="archivo-ficha" name="archivos" accept="image/*,.pdf" class="mt-1 p-1 block w-full text-sm text-black-300 border-gray-700 rounded cursor-pointer bg-gray-200 focus:outline-none border-2">
     
     <p class="mt-1 text-sm text-gray-500">Solo se permite un archivo.</p>
</div>
      
    `

    html += `
      <div class="flex justify-between mt-6 pt-4 border-t gap-4">
        <button onclick="CerrarOrden(${id}, '${metodoPago}', volverAFichas, initFichasPago)" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded-lg transition w-1/2">
          Cerrar requisición
        </button>
        <button onclick="regresarACompras(${id}, '${metodoPago}')" class="bg-green-500 hover:bg-green-700 text-white font-semibold py-1 px-4 rounded-lg transition w-1/2">
          Regresar a Compras
        </button>
      </div>
      
       <div class="flex flex-col gap-4 mt-4">
      <button onclick="CancelarOrdenFicha(${id}, '${metodoPago}', volverATabla, initPagosPendientes)" class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-4 rounded-lg ">
          Cancelar requisición
        </button>
      </div>
    `

    detalleDiv.innerHTML = html
  } catch (error) {
    console.error('Error al cargar detalle de ficha:', error)
    detalleDiv.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

function volverAFichas(metodoPago) {
  const detalleDiv =
    metodoPago == '0'
      ? document.getElementById('detalle-contado')
      : document.getElementById('detalle-credito')
  const tablaDiv =
    metodoPago == '0'
      ? document.getElementById('tabla-contado')
      : document.getElementById('tabla-credito')

  detalleDiv.classList.add('hidden')
  tablaDiv.classList.remove('hidden')
}

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

async function regresarACompras(idSolicitud, metodoPago) {
  const fichaElement = document.getElementById('archivo-ficha')
  const fichaFile = fichaElement.files[0]
  try {
    const formData = new FormData()
    formData.append('ficha', fichaFile)
    formData.append('nuevoEstado', 'Por Pagar')

    const data = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
        method: 'POST',
        body: formData,
    })

    if (data.success) {
      alert('Enviado a compras')
      volverAFichas(metodoPago) // regresar a la tabla de fichas
      initFichasPago() // refrescar tabla
    } else {
      alert('No se pudo actualizar el estado')
    }
  } catch (error) {
    console.error('Error al actualizar estado:', error)
    alert('Ocurrió un error al actualizar el estado')
  }
}

/**
 * Cierra una orden de compra, actualizando su estado a "Pagada".
 * Utiliza confirm() y alert() para mantener la coherencia del UI.
 *
 * @param {number} idSolicitud - El ID de la solicitud a cerrar.
 * @param {string} metodoPago - '0' o '1', para pasarlo a la función de 'volver'.
 * @param {function} volverCallback - La función para regresar a la tabla (ej. volverATabla).
 * @param {function} refreshCallback - La función para refrescar la lista (ej. initPagosPendientes).
 */
async function CerrarOrden(idSolicitud, metodoPago, volverCallback, refreshCallback) {
  // 1. Añadimos una confirmación (similar a tu 'Confirmar')
  const estaSeguro = confirm(
    "¿Está seguro de marcar esta orden como 'Pagada'?\n\nEsta acción es final y cerrará la requisición.",
  )

  if (!estaSeguro) {
    return // El usuario canceló
  }

  try {
    const formData = new FormData()
    formData.append('nuevoEstado', 'Pagada')
    const data = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
        method: 'POST',
        body: formData,
    })

    if (data.success) {
      // 2. Usamos alert() para el éxito
      alert('Orden finalizada correctamente.')

      // Llama a las funciones de callback para regresar y refrescar
      if (typeof volverCallback === 'function') {
        volverCallback(metodoPago)
      }
      if (typeof refreshCallback === 'function') {
        refreshCallback()
      }
    } else {
      // 3. Usamos alert() para el error
      alert(data.messages['error'])
    }
  } catch (error) {
    console.error('Error al cerrar la orden:', error) // Mantenemos el console.error para depuración
    // 4. Usamos alert() para el error de red/excepción
    alert('Ocurrió un error de red al cerrar la orden.')
  }
}


/**
 * Cancela una orden de compra, actualizando su estado a "Cancelada".
 * Utiliza confirm() y alert() para mantener la coherencia del UI.
 *
 * @param {number} idSolicitud - El ID de la solicitud a cerrar.
 * @param {string} metodoPago - '0' o '1', para pasarlo a la función de 'volver'.
 * @param {function} volverCallback - La función para regresar a la tabla (ej. volverATabla).
 * @param {function} refreshCallback - La función para refrescar la lista (ej. initPagosPendientes).
 */
async function CancelarOrdenFactura (idSolicitud, metodoPago){
  try {
    const formData = new FormData()
    formData.append('nuevoEstado', 'Cancelada')
    const res = await fetch(`${BASE_URL}api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: formData,
    })
    const data = await res.json()

    if (data.success) {
      alert('Cancelando Orden')
      volverATabla(metodoPago)
      initPagosPendientes() // refrescar tabla
    } else {
      alert(data.messages['error'])
    }
  } catch (error) {
    console.error('Error al actualizar estado:', error)
    alert('Ocurrió un error al actualizar el estado')
  }
}


async function CancelarOrdenFicha (idSolicitud, metodoPago){
  try {
    const formData = new FormData()
    formData.append('nuevoEstado', 'Cancelada')
    const res = await fetch(`${BASE_URL}api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: formData,
    })
    const data = await res.json()

    if (data.success) {
      alert('Cancelando Orden')
      volverAFichas(metodoPago)
      initFichasPago() // refrescar tabla
    } else {
      alert(data.messages['error'])
    }
  } catch (error) {
    console.error('Error al actualizar estado:', error)
    alert('Ocurrió un error al actualizar el estado')
  }
}

/**
 * Lógica para crud razon social
 */
function initCrudRazonSocial() {
  const tabla = document.getElementById('tabla-razonsocial')
  if (!tabla) return

  initRazonSocialTabla()
  initRazonSocialPantallas()
  initRazonSocialForm()
  initRazonSocialEditarForm()
  initRazonSocialActions(tabla)
}

function initRazonSocialTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-razonsocial tr[data-id]',
    paginationSelector: 'paginacion-razonsocial',
    filterFormSelector: '#form-filtros-razonsocial', // Si no existe, se ignora
    filterFunction: (row, form) => {
      const nombreFiltro = (document.getElementById('buscar-nombre')?.value || '').toLowerCase()
      const nombre = row.querySelector('.nombre')?.textContent.toLowerCase() || ''
      return nombre.includes(nombreFiltro)
    },
    rowsPerPage: 10,
  })
}

function initRazonSocialPantallas() {
  const pantallaAgregar = document.getElementById('pantalla-agregar-razonsocial')
  const pantallaEditar = document.getElementById('pantalla-editar-razonsocial')
  const pantallaLista = document.getElementById('pantalla-lista-razonsocial')

  const btnAgregar = document.getElementById('btn-agregar-razonsocial')
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

function initRazonSocialForm() {
  const formAgregar = document.getElementById('form-agregar-razonsocial')
  const pantallaAgregar = document.getElementById('pantalla-agregar-razonsocial')
  const pantallaLista = document.getElementById('pantalla-lista-razonsocial')
  if (!formAgregar) return

  formAgregar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formAgregar)

    try {
      const result = await SendDataEnd('modales/razonsocial/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Razón social agregada ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formAgregar.reset()
        abrirModal('razonsocial')
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

function initRazonSocialEditarForm() {
  const formEditar = document.getElementById('form-editar-razonsocial')
  const pantallaEditar = document.getElementById('pantalla-editar-razonsocial')
  const pantallaLista = document.getElementById('pantalla-lista-razonsocial')
  const tabla = document.getElementById('tabla-razonsocial')
  if (!formEditar) return

  formEditar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formEditar)
    const id = formData.get('ID_RazonSocial')

    try {
      const result = await SendDataEnd(`modales/razonsocial/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Razón social actualizada ✅', 'success')
        // Actualizar fila en la tabla
        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          fila.querySelector('.nombre').textContent = formData.get('Nombre')

          // Actualizar datos
          fila.querySelector('.rfc').textContent = formData.get('RFC')
          fila.dataset.rfc = formData.get('RFC')
        }

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

function initRazonSocialActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    // --- ELIMINAR ---
    const btnEliminar = e.target.closest("[id^='btn-eliminar-razonsocial-']")
    if (btnEliminar) {
      e.preventDefault()
      const id = btnEliminar.dataset.id

      if (
        !(await Confirmar(
          'Eliminar Razón Social?',
          '¿Seguro que deseas eliminar esta razón social?',
        ))
      )
        return

      SendDataEnd(`modales/razonsocial/eliminar/${id}`, {
        method: 'POST',
      })
        .then((result) => {
          if (result.success) {
            mostrarNotificacion('Razón social eliminada ✅', 'success')
            btnEliminar.closest('tr')?.remove()
          } else {
            mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
          }
        })
        .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
      return
    }

    // --- EDITAR ---
    const btnEditar = e.target.closest("[id^='btn-editar-razonsocial-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    document.getElementById('editar-ID_RazonSocial').value = fila.dataset.id
    document.getElementById('editar-Nombre').value = fila.querySelector('.nombre').textContent
    document.getElementById('editar-RFC').value = fila.dataset.rfc

    document.getElementById('pantalla-lista-razonsocial').classList.add('hidden')
    document.getElementById('pantalla-editar-razonsocial').classList.remove('hidden')
  })
}


/**
 * Lógica para limpiar almacenamiento
 */

window.initLimpiarAlmacenamiento = function () {
  let currentPath = ''
  let selectedItems = new Set()

  window.navegarA = async function (path) {
    currentPath = path

    // Boton de regresar
    const backBtnContainer = document.getElementById('back-button-container')
    if (backBtnContainer) {
      if (path === '') {
        backBtnContainer.classList.add('hidden')
      } else {
        backBtnContainer.classList.remove('hidden')
      }
    }

    actualizarToolbar()
    actualizarSelectAllCheck(false)
    renderBreadcrumbs(path)

    const tbody = document.getElementById('file-list')
    if (!tbody) return
    tbody.innerHTML =
      '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Cargando...</td></tr>'

    const files = await fetchFiles(path)
    renderFiles(files)
  }

  window.navegarArriba = function () {
    if (currentPath === '') return

    const parts = currentPath.split('/')
    parts.pop()
    const parentPath = parts.join('/')
    window.navegarA(parentPath)
  }

  window.toggleSelection = function (path) {
    if (selectedItems.has(path)) {
      selectedItems.delete(path)
    } else {
      selectedItems.add(path)
    }
    actualizarToolbar()
  }

  window.toggleSelectAll = function () {
    const mainCheckbox = document.getElementById('select-all')
    const checkboxes = document.querySelectorAll('.file-checkbox')
    const isChecked = mainCheckbox.checked
    checkboxes.forEach((cb) => {
      cb.checked = isChecked
      if (isChecked) {
        selectedItems.add(cb.value)
      } else {
        selectedItems.delete(cb.value)
      }
    })
    actualizarToolbar()
  }

  // limpiar selección
  window.limpiarSeleccion = function () {
    selectedItems.clear()
    actualizarToolbar()
    document.querySelectorAll('.file-checkbox').forEach((cb) => (cb.checked = false))
    actualizarSelectAllCheck(false)
    document
      .querySelectorAll('#file-list tr.bg-blue-50')
      .forEach((row) => row.classList.remove('bg-blue-50'))
  }

  window.ejecutarAccion = function (tipo) {
    const listaParaEnviar = Array.from(selectedItems)
    if (listaParaEnviar.length === 0) return

    const mensaje =
      tipo === 'eliminar'
        ? `¿Estás seguro de ELIMINAR ${listaParaEnviar.length} elementos?\nEsta acción no se puede deshacer.`
        : `¿Deseas comprimir ${listaParaEnviar.length} elementos?`

    if (confirm(mensaje)) {
      console.group('🚀 EJECUTANDO ACCIÓN: ' + tipo.toUpperCase())
      console.log('Rutas a procesar:', listaParaEnviar)
      console.groupEnd()
      alert(`Acción "${tipo}" simulada. Revisa la consola.`)
    }
  }

  async function fetchFiles(path) {
    try {
      return await SendDataEnd(`api/storage/list?path=${encodeURIComponent(path)}`)
    } catch (error) {
      console.error('Error al obtener archivos:', error)
      const tbody = document.getElementById('file-list')
      if (tbody)
        tbody.innerHTML = `<tr><td colspan="4" class="px-6 py-10 text-center text-red-500">Error: ${error.message}</td></tr>`
      return []
    }
  }

  function renderFiles(files) {
    const tbody = document.getElementById('file-list')
    if (!tbody) return
    tbody.innerHTML = ''

    if (!files || files.length === 0) {
      tbody.innerHTML =
        '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">Carpeta vacía</td></tr>'
      return
    }

    const iconFolder = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-500 mr-3" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" /></svg>`
    const iconFileGeneric = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>`

    files.sort((a, b) => {
      if (a.type === b.type) return a.name.localeCompare(b.name)
      return a.type === 'folder' ? -1 : 1
    })

    files.forEach((file) => {
      const isFolder = file.type === 'folder'
      const isPdf = file.name.toLowerCase().endsWith('.pdf')
      const isImg = file.name.match(/\.(png|jpg|jpeg|gif|webp)$/i)

      let currentIcon = iconFileGeneric
      if (isFolder) {
        currentIcon = iconFolder
      } else if (isPdf) {
        currentIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9z" /></svg>`
      } else if (isImg) {
        currentIcon = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`
      }

      const isChecked = selectedItems.has(file.path) ? 'checked' : ''

      const row = document.createElement('tr')
      row.className = `transition cursor-pointer ${isChecked ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50'}`

      row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap" onclick="event.stopPropagation()">
                    <input type="checkbox" value="${file.path}" ${isChecked} onchange="toggleSelection('${file.path}'); this.closest('tr').classList.toggle('bg-blue-50', this.checked);" class="file-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                </td>
                <td class="px-6 py-4 whitespace-nowrap flex items-center">
                    ${currentIcon}
                    <span class="${isFolder ? 'font-medium text-gray-900' : 'text-gray-700'}">${file.name}</span>
                </td>
                `

      row.addEventListener('click', (e) => {
        if (e.target.type === 'checkbox') return

        if (isFolder) {
          window.navegarA(file.path)
        } else {
          const url = `${BASE_URL}api/storage/serve?path=${encodeURIComponent(file.path)}`
          window.open(url, '_blank')
        }
      })

      tbody.appendChild(row)
    })
  }

  function renderBreadcrumbs(path) {
    const container = document.getElementById('breadcrumbs')
    if (!container) return

    let html = `<button onclick="navegarA('')" class="text-blue-600 hover:underline hover:bg-blue-50 px-2 py-1 rounded flex items-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>uploads</button>`

    if (path) {
      const parts = path.split('/')
      let accumulatedPath = ''
      parts.forEach((part, index) => {
        accumulatedPath += (index > 0 ? '/' : '') + part
        const pathForClick = accumulatedPath
        html += `<span class="text-gray-400 mx-1">/</span>`
        if (index === parts.length - 1) {
          html += `<span class="font-medium text-gray-800 px-2">${part}</span>`
        } else {
          html += `<button onclick="navegarA('${pathForClick}')" class="text-blue-600 hover:underline hover:bg-blue-50 px-2 py-1 rounded">${part}</button>`
        }
      })
    }
    container.innerHTML = html
  }

  function actualizarToolbar() {
    const toolbar = document.getElementById('toolbar')
    const countSpan = document.getElementById('selected-count')
    if (!toolbar || !countSpan) return

    const count = selectedItems.size
    countSpan.textContent = count
    if (count > 0) {
      toolbar.classList.remove('hidden')
      toolbar.classList.add('flex')
    } else {
      toolbar.classList.add('hidden')
      toolbar.classList.remove('flex')
    }
  }

  function actualizarSelectAllCheck(checked) {
    const cb = document.getElementById('select-all')
    if (cb) cb.checked = checked
  }

  window.navegarA('')
}

//==================================================================================================================
/**
 * Varios
 */
// Inicializar
document.addEventListener('DOMContentLoaded', initCrudProveedores)

async function GenerarOrden(id, button) {
  if (
    !(await Confirmar(
      'Generar Orden de Compra',
      '¿Está seguro de que desea generar y enviar la orden de compra al proveedor?',
    ))
  ) {
    return
  }

  const originalText = button.textContent
  button.disabled = true
  button.textContent = 'Enviando...'

  try {
    // Usamos 'POST' porque es una acción que modifica el estado en el servidor
    const result = await SendDataEnd(`api/orden/generar/${id}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    if (result.success) {
      mostrarNotificacion(result.message || 'Orden de compra enviada con éxito.', 'success')
      button.textContent = 'Enviado'
      // El botón permanece deshabilitado para evitar re-envíos

      // Opcional: refrescar la vista para ver el cambio de estado
      // abrirModal('ordenes_compra');
    } else {
      mostrarNotificacion(result.message || 'Error al enviar la orden de compra.', 'error')
      button.disabled = false
      button.textContent = originalText
    }
  } catch (error) {
    console.error('Error en GenerarOrden:', error)
    mostrarNotificacion('Ocurrió un error de red. Por favor, intente de nuevo.', 'error')
    button.disabled = false
    button.textContent = originalText
  }
}