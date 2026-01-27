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
    crud_departamento :'crud_departamento',
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
    crud_places: 'Complejos',
    crud_departamento:"Departametos",
    lista_pagos: "Lista de pagos",
    crud_cuentas: "Cuentas de proveedores",
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
        ficha_pago: initFichasPago,
        razonsocial: initCrudRazonSocial,
        limpiar_almacenamiento: initLimpiarAlmacenamiento,
        recepcion_material: initRecepcionMaterial,
        bajas_destruccion: initBajasDestruccion,
        crud_places: initCrudPlaces,
        crud_departamento: initCrudDepartamentos,
        crud_cuentas: initCrudCuentas,
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
  document.getElementById('modal-general').classList.add('hidden')

  // Limpiar la selección de la barra lateral
  const activeClasses = ['bg-indigo-600', 'text-white']
  const inactiveClasses = ['text-gray-300', 'hover:bg-gray-700']

  document.querySelectorAll('#sidebar-nav a[data-opcion]').forEach((link) => {
    link.classList.remove(...activeClasses)
    link.classList.add(...inactiveClasses)
  })
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
        const productRowHtmlContent = await SendDataEnd('modales/vistas/product_row', {
          responseType: 'text',
        })
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
        const serviceRowHtmlContent = await SendDataEnd('modales/vistas/service_row', {
          responseType: 'text',
        })
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

  // Cargar lista de proveedores
  loadRazonSocialProv('razonSocialServicioSelect')

  // Cargar lista de Cuentas del proveedor seleccionado
  const proveedorSelect = document.getElementById('razonSocialServicioSelect');
  const cuentaSelect = document.getElementById('cuentaProveedorSelect');

  if (proveedorSelect && cuentaSelect) {
    proveedorSelect.addEventListener('change', async () => {
      const idProveedor = proveedorSelect.value;

      // Limpiar opciones anteriores
      cuentaSelect.innerHTML = '<option value="">Seleccione una cuenta</option>';

      if (!idProveedor) return;

      try {
        // Ruta para obtener datos de la tabla "Cuentas"
        const cuentas = await SendDataEnd(`modales/cuentas/proveedor/${idProveedor}`, { method: 'GET' });

        if (cuentas && cuentas.length > 0) {
          cuentas.forEach(c => {
            const option = document.createElement('option');
            option.value = c.ID_Cuenta; // El valor será el ID
            option.textContent = c.Cuenta; // El texto visible será el número de cuenta/CLABE
            cuentaSelect.appendChild(option);
          });
        } else {
          const option = document.createElement('option');
          option.textContent = "(Este proveedor no tiene cuentas registradas)";
          option.disabled = true;
          cuentaSelect.appendChild(option);
        }
      } catch (error) {
        console.error("Error al cargar cuentas bancarias:", error);
      }
    });
  }

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
    const deptosPermitidos = ['Administración', 'Compras', 'Direccion', 'Tesoreria']

    const miDepto = typeof USER_DEPT_NAME !== 'undefined' ? USER_DEPT_NAME : ''

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

  const exceptions = ['Compras', 'Administración', 'Direccion', 'Tesoreria']
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

    html += generarComentariosHtml(data)

    html += generarProductosServiciosHTML(data)

    // Sección de Comentarios Usuario
    if (data.ComentariosUser) {
      html += `
            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p>
            </div>`
    }

    html += generarSeccionAdjuntos(data);

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

async function initRecepcionMaterial() {
  const ordenCompraSelect = document.getElementById('ordenCompraSelect')
  const solicitudFolioInput = document.getElementById('solicitudFolio')
  const proveedorNombreInput = document.getElementById('proveedorNombre')
  const productosRecepcionTable = document.getElementById('productosRecepcionTable')
  const formRecepcionMaterial = document.getElementById('form-recepcion-material')

  if (!ordenCompraSelect || !formRecepcionMaterial) return

  let allOrdenesCompra = []

  // Cargar órdenes de compra pendientes
  const loadOrdenesCompra = async () => {
    try {
      const ordenes = await SendDataEnd('api/ordenes-compra/pendientes-recepcion')
      allOrdenesCompra = ordenes // Guardar todas las órdenes para referencia
      ordenCompraSelect.innerHTML = '<option value="">-- Seleccionar Orden de Compra --</option>'
      if (ordenes.length > 0) {
        ordenes.forEach((orden) => {
          const option = document.createElement('option')
          option.value = orden.ID_Solicitud // Usar ID_Solicitud para obtener detalles
          option.textContent = `${orden.No_Folio} - ${orden.ProveedorNombre} (Total: $${parseFloat(orden.Total).toFixed(2)})`
          ordenCompraSelect.appendChild(option)
        })
      } else {
        ordenCompraSelect.innerHTML =
          '<option value="">No hay órdenes pendientes de recepción</option>'
      }
    } catch (error) {
      console.error('Error cargando órdenes de compra:', error)
      ordenCompraSelect.innerHTML = '<option value="">Error al cargar órdenes de compra</option>'
    }
  }

  // Manejar selección de Orden de Compra
  ordenCompraSelect.addEventListener('change', async () => {
    const idSolicitud = ordenCompraSelect.value
    if (!idSolicitud) {
      solicitudFolioInput.value = ''
      proveedorNombreInput.value = ''
      productosRecepcionTable.innerHTML =
        '<tr><td colspan="3" class="text-center py-2">Seleccione una Orden de Compra para ver los productos.</td></tr>'
      return
    }

    try {
      const data = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`)

      // Llenar campos de solo lectura
      solicitudFolioInput.value = data.No_Folio || ''
      proveedorNombreInput.value = data.proveedor?.RazonSocial || ''

      // Llenar tabla de productos
      productosRecepcionTable.innerHTML = ''
      if (data.productos && data.productos.length > 0) {
        data.productos.forEach((p) => {
          const tr = document.createElement('tr')
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
                    `
          productosRecepcionTable.appendChild(tr)
        })
      } else {
        productosRecepcionTable.innerHTML =
          '<tr><td colspan="3" class="text-center py-2">No hay productos en esta Orden de Compra.</td></tr>'
      }
    } catch (error) {
      console.error('Error cargando detalles de la orden de compra:', error)
      solicitudFolioInput.value = ''
      proveedorNombreInput.value = ''
      productosRecepcionTable.innerHTML =
        '<tr><td colspan="3" class="text-center py-2 text-red-500">Error al cargar detalles de la orden.</td></tr>'
    }
  })

  // Manejar envío del formulario
  formRecepcionMaterial.addEventListener('submit', async (e) => {
    e.preventDefault()

    const idOrdenCompra = ordenCompraSelect.value
    if (!idOrdenCompra) {
      mostrarNotificacion('Debe seleccionar una Orden de Compra.', 'error')
      return
    }

    const formData = new FormData(formRecepcionMaterial)
    const productosRecibidos = []
    productosRecepcionTable.querySelectorAll('tr').forEach((row) => {
      const cantidadInput = row.querySelector('.cantidad-recibida')
      if (cantidadInput) {
        const idSolicitudProd = cantidadInput.name.match(/\[(\d+)\]/)[1] // Extraer el ID
        const idProductoInput = row.querySelector(
          `input[name="productos[${idSolicitudProd}][id_producto]"]`,
        )

        productosRecibidos.push({
          id_solicitud_prod: idSolicitudProd,
          id_producto: idProductoInput ? idProductoInput.value : null,
          cantidad_recibida: parseInt(cantidadInput.value),
          cantidad_pedida: parseInt(cantidadInput.max),
        })
      }
    })

    // Obtener archivo de remisión
    const remisionFile = document.getElementById('remisionArchivo').files[0]
    if (remisionFile) {
      formData.append('remision_file', remisionFile)
    }

    // Obtener archivo de factura de entrada
    const facturaEntradaFile = document.getElementById('facturaEntradaArchivo').files[0]
    if (facturaEntradaFile) {
      formData.append('factura_entrada_file', facturaEntradaFile)
    }

    formData.append('id_orden_compra', idOrdenCompra)
    formData.append('productos_recibidos', JSON.stringify(productosRecibidos)) // Enviar como JSON string

    const procesandoNotif = mostrarNotificacion('Confirmando recepción...', 'info', 999999)

    try {
      const result = await SendDataEnd('api/recepcion/confirmar', {
        method: 'POST',
        body: formData, // FormData con el archivo y otros campos
      })

      procesandoNotif.click()

      if (result.success) {
        mostrarNotificacion(result.message, 'success')
        formRecepcionMaterial.reset()
        productosRecepcionTable.innerHTML =
          '<tr><td colspan="3" class="text-center py-2">Seleccione una Orden de Compra para ver los productos.</td></tr>'
        loadOrdenesCompra() // Recargar la lista de órdenes pendientes
      } else {
        mostrarNotificacion(result.message || 'Error al confirmar la recepción.', 'error')
      }
    } catch (error) {
      procesandoNotif.click()
      console.error('Error en la recepción de material:', error)
      mostrarNotificacion('Error de red al confirmar la recepción.', 'error')
    }
  })

  loadOrdenesCompra() // Cargar órdenes al inicializar el modal
}

async function initBajasDestruccion() {
  const productoSelect = document.getElementById('productoSelect')
  const existenciaActualInput = document.getElementById('existenciaActual')
  const cantidadBajaInput = document.getElementById('cantidadBaja')
  const formBajasDestruccion = document.getElementById('form-bajas-destruccion')

  if (!productoSelect || !formBajasDestruccion) return

  let allProducts = [] // Para almacenar todos los productos cargados

  // Cargar productos
  const loadProducts = async () => {
    try {
      const products = await SendDataEnd('api/product/all', { method: 'GET' })
      allProducts = products
      productoSelect.innerHTML = '<option value="">-- Seleccionar Producto --</option>'
      if (products.length > 0) {
        products.forEach((p) => {
          const option = document.createElement('option')
          option.value = p.ID_Producto
          option.textContent = `${p.Nombre} (Código: ${p.Codigo})`
          productoSelect.appendChild(option)
        })
      } else {
        productoSelect.innerHTML = '<option value="">No hay productos disponibles</option>'
      }
    } catch (error) {
      console.error('Error cargando productos:', error)
      productoSelect.innerHTML = '<option value="">Error al cargar productos</option>'
    }
  }

  // Manejar selección de producto
  productoSelect.addEventListener('change', () => {
    const idProducto = productoSelect.value
    if (idProducto) {
      const selectedProduct = allProducts.find((p) => String(p.ID_Producto) === idProducto)
      if (selectedProduct) {
        existenciaActualInput.value = selectedProduct.Existencia
        cantidadBajaInput.max = selectedProduct.Existencia // Establecer máximo para la cantidad a dar de baja
        cantidadBajaInput.value = '' // Limpiar campo de cantidad
      }
    } else {
      existenciaActualInput.value = ''
      cantidadBajaInput.max = ''
      cantidadBajaInput.value = ''
    }
  })

  // Manejar envío del formulario
  formBajasDestruccion.addEventListener('submit', async (e) => {
    e.preventDefault()

    const idProducto = productoSelect.value
    const cantidadBaja = parseInt(cantidadBajaInput.value)
    const existenciaActual = parseInt(existenciaActualInput.value)
    const motivoBaja = document.getElementById('motivoBaja').value
    const fechaBaja = document.getElementById('fechaBaja').value

    if (!idProducto || !cantidadBaja || !motivoBaja || !fechaBaja) {
      mostrarNotificacion('Por favor, complete todos los campos.', 'error')
      return
    }

    if (cantidadBaja <= 0 || cantidadBaja > existenciaActual) {
      mostrarNotificacion(
        'La cantidad a dar de baja debe ser mayor a 0 y no puede exceder la existencia actual.',
        'error',
      )
      return
    }

    const payload = {
      id_producto: idProducto,
      cantidad_baja: cantidadBaja,
      motivo_baja: motivoBaja,
      fecha_baja: fechaBaja,
    }

    const procesandoNotif = mostrarNotificacion('Confirmando baja...', 'info', 999999)

    try {
      const result = await SendDataEnd('api/bajas/destruccion/registrar', {
        method: 'POST',
        body: payload,
      })

      procesandoNotif.click()

      if (result.success) {
        mostrarNotificacion(result.message, 'success')
        formBajasDestruccion.reset()
        loadProducts() // Recargar productos para actualizar existencias
      } else {
        mostrarNotificacion(result.message || 'Error al confirmar la baja.', 'error')
      }
    } catch (error) {
      procesandoNotif.click()
      console.error('Error en baja por destrucción:', error)
      mostrarNotificacion('Error de red al confirmar la baja.', 'error')
    }
  })

  loadProducts() // Cargar productos al inicializar el modal
}

function exportarRequisicionesExcel() {
  window.location.href = `${BASE_URL}api/exportar-requisiciones`
}

function exportarHistorialExcel() {
  const fecha = document.getElementById('filtro-fecha').value
  const porMes = document.getElementById('filtrar-por-mes').checked
  const estado = document.getElementById('filtro-estado').value
  const dpto = document.getElementById('filtroDepartamento')?.value || ''

  const params = new URLSearchParams()
  if (fecha) {
    params.append('fecha', fecha)
  }
  if (porMes) {
    params.append('por_mes', '1')
  }
  if (estado) {
    params.append('estado', estado)
  }
  if (dpto) {
    params.append('dpto', dpto)
  }

  const queryString = params.toString()
  window.location.href = `${BASE_URL}api/historial/exportar${queryString ? '?' + queryString : ''}`
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

async function mostrarCotizar(idSolicitud, idUsuario) {
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

  async function handleGenerarCotizacion(idUsuario) {
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
          ID_Usuario: idUsuario,
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

  if (btnGenerar) {
    btnGenerar.onclick = () => handleGenerarCotizacion(idUsuario)
  }
}

async function cancelarReq(idSolicitud) {
  if (
    !(await Confirmar(
      'Cancelar solicitud',
      '¿Está seguro de que desea cancelar la solicitud?',
    ))
  ) {
    return;
  }

  const comentarios = await InputPrompt(
    'Cancelar Solicitud',
    'Por favor, ingrese el motivo de la cancelación (obligatorio):',
    true,
  );

  if (comentarios === null) {
    return; 
  }

  const procesandoNotif = mostrarNotificacion('Cancelando solicitud...', 'info', 999999);

  try {
    const result = await SendDataEnd('api/solicitud/cancelar', {
      method: 'POST',
      body: { 
        ID_Solicitud: idSolicitud,
        ComentariosAdmin: comentarios 
      },
    });

    procesandoNotif.click();

    if (result.success) {
      mostrarNotificacion(result.message || 'Solicitud cancelada correctamente.', 'success');
      abrirModal('revisar_solicitudes');
    } else {
      mostrarNotificacion(result.message || 'No se pudo cancelar la solicitud.', 'error');
    }
  } catch (error) {
    procesandoNotif.click();
    console.error('Error al cancelar solicitud:', error);
    mostrarNotificacion('Ocurrió un error de red al intentar cancelar la solicitud.', 'error');
  }
}

function regresarTabla() {
  document.getElementById('div-ver').classList.add('hidden')
  document.getElementById('div-cotizar').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
  document.getElementById('btn-exportar-requisiciones').classList.remove('hidden') // Mostrar el botón de exportar

  // Reset the state of the cotizar view
  const inputBusqueda = document.getElementById('buscar-proveedor');
  if (inputBusqueda) {
    inputBusqueda.value = '';
  }
  const tbody = document.querySelector('#div-cotizar tbody');
  if (tbody) {
    tbody.innerHTML = '';
  }
  const paginacionDiv = document.querySelector('#div-cotizar #paginacion-proveedores');
  if (paginacionDiv) {
    paginacionDiv.innerHTML = '';
  }
  const btnGenerar = document.getElementById('btn-generar-cotizacion');
  if (btnGenerar) {
    btnGenerar.onclick = null; // Remove listener
    btnGenerar.disabled = true; // Disable button
  }
}


/**
 * Lógica para el modal CRUD Places
 */

function initCrudPlaces() {
  const tabla = document.getElementById('tabla-places')
  if (!tabla) return

  initPlacesTabla()
  initPlacesPantallas()
  initPlacesForm()
  initPlacesEditarForm()
  initPlacesActions(tabla)
}

function initPlacesTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-places tr[data-id]',
    paginationSelector: 'paginacion-places',
    filterFormSelector: '#form-filtros-places',
    filterFunction: (row, form) => {
      const cortoFiltro = (document.getElementById('buscar-nombre-corto')?.value || '').toLowerCase()
      const completoFiltro = (document.getElementById('buscar-nombre-completo')?.value || '').toLowerCase()

      const nombreCorto = row.querySelector('.nombre-corto')?.textContent.toLowerCase() || ''
      const nombreCompleto = row.querySelector('.nombre-completo')?.textContent.toLowerCase() || ''

      return nombreCorto.includes(cortoFiltro) && nombreCompleto.includes(completoFiltro)
    },
    rowsPerPage: 10,
  })
}

function initPlacesPantallas() {
  const pantallaAgregar = document.getElementById('pantalla-agregar-places')
  const pantallaEditar = document.getElementById('pantalla-editar-places')
  const pantallaLista = document.getElementById('pantalla-lista-places')

  const btnAgregar = document.getElementById('btn-agregar-places')
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

function initPlacesForm() {
  const formAgregar = document.getElementById('form-agregar-places')
  const pantallaAgregar = document.getElementById('pantalla-agregar-places')
  const pantallaLista = document.getElementById('pantalla-lista-places')
  if (!formAgregar) return

  formAgregar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formAgregar)

    try {
      // --- CAMBIO: Ruta actualizada a crud_places ---
      const result = await SendDataEnd('modales/crud_places/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Lugar agregado correctamente ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formAgregar.reset()
        // --- CAMBIO: Nombre del modal actualizado ---
        abrirModal('crud_places')
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

function initPlacesEditarForm() {
  const formEditar = document.getElementById('form-editar-places')
  const pantallaEditar = document.getElementById('pantalla-editar-places')
  const pantallaLista = document.getElementById('pantalla-lista-places')
  const tabla = document.getElementById('tabla-places')
  if (!formEditar) return

  formEditar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formEditar)
    const id = formData.get('ID_Place')

    try {
      // --- CAMBIO: Ruta actualizada a crud_places ---
      const result = await SendDataEnd(`modales/crud_places/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Lugar actualizado correctamente ✅', 'success')

        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          fila.querySelector('.nombre-corto').textContent = formData.get('Nombre_Corto')
          fila.querySelector('.nombre-completo').textContent = formData.get('Nombre_Completo')

          fila.dataset.nombreCorto = formData.get('Nombre_Corto')
          fila.dataset.nombreCompleto = formData.get('Nombre_Completo')
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

function initPlacesActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    const btnEliminar = e.target.closest("[id^='btn-eliminar-places-']")
    if (btnEliminar) {
      e.preventDefault()
      const id = btnEliminar.dataset.id

      if (
          !(await Confirmar(
              'Eliminar Lugar?',
              '¿Seguro que deseas eliminar este lugar?',
          ))
      )
        return

      // --- CAMBIO: Ruta actualizada a crud_places ---
      SendDataEnd(`modales/crud_places/eliminar/${id}`, {
        method: 'POST',
      })
          .then((result) => {
            if (result.success) {
              mostrarNotificacion('Lugar eliminado ✅', 'success')
              btnEliminar.closest('tr')?.remove()
            } else {
              mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
            }
          })
          .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
      return
    }

    const btnEditar = e.target.closest("[id^='btn-editar-places-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    document.getElementById('editar-ID_Place').value = fila.dataset.id
    document.getElementById('editar-Nombre_Corto').value = fila.dataset.nombreCorto
    document.getElementById('editar-Nombre_Completo').value = fila.dataset.nombreCompleto

    document.getElementById('pantalla-lista-places').classList.add('hidden')
    document.getElementById('pantalla-editar-places').classList.remove('hidden')
  })
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


window.mostrarVerDictamen = async function(idSolicitud) {
  document.getElementById('div-tabla').classList.add('hidden')
  const divVer = document.getElementById('div-ver-dictamen')
  divVer.classList.remove('hidden')

  const detallesContainer = document.getElementById('detallesDictamen')
  detallesContainer.innerHTML = `<p class="text-center text-gray-500">Cargando detalles de la solicitud ${idSolicitud}...</p>`

  try {
    const data = await SendDataEnd(`api/cotizacion/details/${idSolicitud}`)
    if (data.error) throw new Error(data.error)

    let html = generarDetallesSolicitudHTML(data)

   html += generarComentariosHtml(data)

    html += generarProductosServiciosHTML(data)

    html += generarSeccionAdjuntos(data);

    // Solo mostrar botones de acción si la solicitud está 'En revision'
    if (data.Estado === 'En revision') {
      html += `
                <div class="mt-8 flex justify-end space-x-4 border-t pt-6">
                    <button onclick="mostrarVerPdf(${idSolicitud}, 1)" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Ver PDF
                    </button>
                    <button onclick="dictaminarDictamen(${idSolicitud}, 'Rechazada')" class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition">
                        Rechazar
                    </button>
                    <button onclick="dictaminarDictamen(${idSolicitud}, 'Aprobada')" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
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

window.regresarTablaDictamen = function() {
  document.getElementById('div-ver-dictamen').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
}

window.dictaminarDictamen = async function(idSolicitud, nuevoEstado) {
  const esAprobacion = nuevoEstado === 'Aprobada'
  const title = esAprobacion ? 'Aprobar Solicitud' : 'Rechazar Solicitud'
  const message = esAprobacion
      ? 'Puede agregar observaciones (opcional):'
      : 'Por favor, ingrese el motivo del rechazo (obligatorio):'
  const isRequired = !esAprobacion

  const comentarios = await InputPrompt(title, message, isRequired)

  if (comentarios === null) {
    return // Usuario canceló
  }

  const payload = {
    ID_Solicitud: idSolicitud,
    Estado: nuevoEstado,
    ComentariosAdmin: comentarios,
  }

  const procesandoNotif = mostrarNotificacion('Procesando dictamen...', 'info', 999999)

  try {
    // Llamada a la API correcta para esta vista
    const result = await SendDataEnd('api/solicitud/dictaminar', {
      method: 'POST',
      body: payload,
    })

    procesandoNotif.click()

    if (result.success) {
      mostrarNotificacion(result.message, 'success')
      abrirModal('dictamen_solicitudes') // Recargar vista
    } else {
      mostrarNotificacion(result.message || 'Error al procesar el dictamen.', 'error')
    }
  } catch (error) {
    procesandoNotif.click()
    mostrarNotificacion('Error de red al procesar el dictamen.', 'error')
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

    html += generarComentariosHtml(data)

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

    html += generarSeccionAdjuntos(data);

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

    if (!data.success) {
      mostrarNotificacion(data.message || 'Error desconocido al enviar la orden.', 'error')

      boton.disabled = false
      boton.innerHTML = originalHtml
      return
    }

    mostrarNotificacion(
      '✅ La orden fue enviada al proveedor y en espera de programación',
      'success',
    )

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
      const endpoint = form.action.replace(BASE_URL, '')
      const text = await SendDataEnd(endpoint, {
        method: 'POST',
        body: formData,
        responseType: 'text',
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


        html += `
            <div class="mt-8 flex justify-end space-x-4 border-t pt-6">
                <!-- Botón Rechazar -->
                <button onclick="dictaminarSolicitud(${idSolicitud}, 'rechazar')" class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition">
                    Rechazar
                </button>
                
                <!-- Botón Aprobar -->
                <button onclick="enviarAprobacionYCotizacionGlobal(${idSolicitud})" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                    Aprobar y Enviar a Cotizar
                </button>
            </div>
        `
        // ------------------------------------------------

        detallesContainer.innerHTML = html
      } catch (error) {
        detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
      }
    },

    regresarATabla: function () {
      document.getElementById('div-ver-aprobacion').classList.add('hidden')
      document.getElementById('div-tabla-aprobacion').classList.remove('hidden')
    }
  }
}

async function enviarAprobacionYCotizacionGlobal(idSolicitud) {
  const payload = {
    ID_Solicitud: idSolicitud,
  }

  const procesandoNotif = mostrarNotificacion('Procesando...', 'info', 999999)

  try {
    const result = await SendDataEnd('api/solicitud/aprobar-y-cotizar', {
      method: 'POST',
      body: payload,
    })

    procesandoNotif.click()

    if (result.success) {
      mostrarNotificacion(result.message, 'success')
      abrirModal('aprobar_solicitudes') // Recargar vista
    } else {
      mostrarNotificacion(result.message || 'Error al procesar la solicitud.', 'error')
    }
  } catch (error) {
    procesandoNotif.click()
    mostrarNotificacion('Error de red al procesar la solicitud.', 'error')
  }
}

async function dictaminarSolicitud(idSolicitud, accion) {
  const esRechazo = accion === 'rechazar'
  let comentarios = null

  if (esRechazo) {
    comentarios = await InputPrompt(
        'Rechazar Solicitud',
        'Por favor, ingrese el motivo del rechazo (obligatorio):',
        true,
    )
    if (comentarios === null) {
      return // El usuario canceló el modal
    }
  } else {
    // Caso de aprobación directa
    const confirmado = await Confirmar(
        'Aprobar Solicitud',
        '¿Está seguro de que desea aprobar esta solicitud?',
    )
    if (!confirmado) {
      return
    }
  }

  const payload = {
    ID_Solicitud: idSolicitud,
    accion: accion, // Se envía 'rechazar' o 'aprobar'
  }

  if (comentarios) {
    payload.comentarios = comentarios
  }

  const procesandoNotif = mostrarNotificacion('Procesando...', 'info', 999999)

  try {
    const result = await SendDataEnd('api/solicitud/dictaminar-jefe', {
      method: 'POST',
      body: payload,
    })

    procesandoNotif.click()

    if (result.success) {
      mostrarNotificacion(result.message, 'success')
      abrirModal('aprobar_solicitudes') // Recargar vista
    } else {
      mostrarNotificacion(result.message || `Error al ${accion} la solicitud.`, 'error')
    }
  } catch (error) {
    procesandoNotif.click()
    mostrarNotificacion(`Error de red al intentar ${accion} la solicitud.`, 'error')
  }
}

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
    filterFormSelector: '#form-filtros-razonsocial',
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
                mostrarNotificacion('Razón social agregada correctamente ✅', 'success')
                formAgregar.reset()
                abrirModal('razonsocial') // Recargar el modal para ver los cambios
            } else {
                mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
            }
        } catch (error) {
            console.error('Error al agregar razón social:', error)
            mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
        }
    }
}

function initRazonSocialEditarForm() {
    const formEditar = document.getElementById('form-editar-razonsocial')
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
                mostrarNotificacion('Razón social actualizada correctamente ✅', 'success')
                abrirModal('razonsocial') // Recargar el modal para ver los cambios
            } else {
                mostrarNotificacion(result.message || 'Error al actualizar ❌', 'error')
            }
        } catch (error) {
            console.error('Error al editar razón social:', error)
            mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
        }
    }
}

function initRazonSocialActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    const btn = e.target.closest('a.btn-editar, a.btn-eliminar')
    if (!btn) return
    e.preventDefault()

    const id = btn.dataset.id
    const fila = btn.closest('tr')

    // --- EDITAR ---
    if (btn.classList.contains('btn-editar')) {
      if (!fila) return

      document.getElementById('editar-ID_RazonSocial').value = id
      document.getElementById('editar-Nombre').value = fila.querySelector('.nombre').textContent
      document.getElementById('editar-RFC').value = fila.querySelector('.rfc').textContent

      document.getElementById('pantalla-lista-razonsocial').classList.add('hidden')
      document.getElementById('pantalla-editar-razonsocial').classList.remove('hidden')
    }

    // --- ELIMINAR ---
    if (btn.classList.contains('btn-eliminar')) {
      if (!(await Confirmar('Eliminar Razón Social', '¿Seguro que deseas eliminar este registro?'))) {
        return
      }

      try {
        const result = await SendDataEnd(`modales/razonsocial/eliminar/${id}`, {
          method: 'POST',
        })

        if (result.success) {
          mostrarNotificacion('Razón social eliminada ✅', 'success')
          fila?.remove()
        } else {
          mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
        }
      } catch (error) {
        console.error('Error al eliminar razón social:', error)
        mostrarNotificacion('Error de conexión ❌', 'error')
      }
    }
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

/**
 * Lógica para CRUD_Departamento
 */
function initCrudDepartamentos() {
  const tabla = document.getElementById('tabla-departamentos')
  if (!tabla) return

  initDepartamentosTabla()
  initDepartamentosPantallas()
  initDepartamentosForm()
  initDepartamentosEditarForm()
  initDepartamentosActions(tabla)
}

function initDepartamentosTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-departamentos tr[data-id]',
    paginationSelector: 'paginacion-departamentos',
    filterFormSelector: '#form-filtros-departamentos',
    filterFunction: (row, form) => {
      const nombreFiltro = (document.getElementById('buscar-nombre-depto')?.value || '').toLowerCase()
      const lugarFiltro = (document.getElementById('buscar-lugar-depto')?.value || '').toLowerCase()

      const nombre = row.querySelector('.nombre-depto')?.textContent.toLowerCase() || ''
      const lugar = row.querySelector('.lugar-depto')?.textContent.toLowerCase() || ''

      return nombre.includes(nombreFiltro) && lugar.includes(lugarFiltro)
    },
    rowsPerPage: 10,
  })
}

function initDepartamentosPantallas() {
  const pantallaAgregar = document.getElementById('pantalla-agregar-departamento')
  const pantallaEditar = document.getElementById('pantalla-editar-departamento')
  const pantallaLista = document.getElementById('pantalla-lista-departamentos')

  const btnAgregar = document.getElementById('btn-agregar-departamento')
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

function initDepartamentosForm() {
  const formAgregar = document.getElementById('form-agregar-departamento')
  const pantallaAgregar = document.getElementById('pantalla-agregar-departamento')
  const pantallaLista = document.getElementById('pantalla-lista-departamentos')
  if (!formAgregar) return

  formAgregar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formAgregar)

    try {
      const result = await SendDataEnd('modales/crud_departamentos/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Departamento agregado correctamente ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formAgregar.reset()
        // Recargar el modal para actualizar la lista
        abrirModal('crud_departamento')
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

function initDepartamentosEditarForm() {
  const formEditar = document.getElementById('form-editar-departamento')
  const pantallaEditar = document.getElementById('pantalla-editar-departamento')
  const pantallaLista = document.getElementById('pantalla-lista-departamentos')
  const tabla = document.getElementById('tabla-departamentos')
  if (!formEditar) return

  formEditar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formEditar)
    const id = formData.get('ID_Dpto')

    try {
      const result = await SendDataEnd(`modales/crud_departamentos/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Departamento actualizado correctamente ✅', 'success')

        // Actualizar fila
        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          fila.querySelector('.nombre-depto').textContent = formData.get('Nombre')

          // Places
          const selectLugar = document.getElementById('editar-ID_Place');
          const lugarTexto = selectLugar.options[selectLugar.selectedIndex].text;
          fila.querySelector('.lugar-depto').textContent = lugarTexto;

          fila.dataset.nombre = formData.get('Nombre')
          fila.dataset.idPlace = formData.get('ID_Place')
          fila.dataset.nombrePlace = lugarTexto
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

function initDepartamentosActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    // --- ELIMINAR ---
    const btnEliminar = e.target.closest("[id^='btn-eliminar-departamento-']")
    if (btnEliminar) {
      e.preventDefault()
      const id = btnEliminar.dataset.id

      if (
          !(await Confirmar(
              'Eliminar Departamento?',
              '¿Seguro que deseas eliminar este departamento?',
          ))
      )
        return

      SendDataEnd(`modales/crud_departamentos/eliminar/${id}`, {
        method: 'POST',
      })
          .then((result) => {
            if (result.success) {
              mostrarNotificacion('Departamento eliminado ✅', 'success')
              btnEliminar.closest('tr')?.remove()
            } else {
              mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
            }
          })
          .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
      return
    }

    // --- EDITAR ---
    const btnEditar = e.target.closest("[id^='btn-editar-departamento-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    // Cargar datos al formulario desde el dataset
    document.getElementById('editar-ID_Dpto').value = fila.dataset.id
    document.getElementById('editar-Nombre').value = fila.dataset.nombre
    document.getElementById('editar-ID_Place').value = fila.dataset.idPlace

    document.getElementById('pantalla-lista-departamentos').classList.add('hidden')
    document.getElementById('pantalla-editar-departamento').classList.remove('hidden')
  })
}


/**
 * Lógica para el CRUD de cuentas de proveedor
 */

function initCrudCuentas() {
  const tabla = document.getElementById('tabla-cuentas')
  if (!tabla) return

  initCuentasTabla()
  initCuentasPantallas()
  initSubPantallasCuentas()
  initCuentasActions(tabla)
}

function initCuentasTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-cuentas tr[data-id]',
    paginationSelector: 'paginacion-cuentas',
    filterFormSelector: '#form-filtros-cuentas',
    filterFunction: (row, form) => {
      const razonSocialFiltro = (document.getElementById('buscar-razonsocial-cuenta')?.value || '').toLowerCase()
      const rfcFiltro = (document.getElementById('buscar-rfc-cuenta')?.value || '').toLowerCase()

      const razonSocial = row.querySelector('.razonsocial')?.textContent.toLowerCase() || ''
      const rfc = row.querySelector('.rfc')?.textContent.toLowerCase() || ''

      return razonSocial.includes(razonSocialFiltro) && rfc.includes(rfcFiltro)
    },
    rowsPerPage: 10,
  })
}

function initCuentasPantallas() {
  const pantallaLista = document.getElementById('pantalla-lista-cuentas')
  const pantallaEditar = document.getElementById('pantalla-editar-cuenta')
  const btnRegresarEditar = document.getElementById('btn-regresar-lista-editar')

  if (btnRegresarEditar) {
    btnRegresarEditar.onclick = (e) => {
      e.preventDefault()
      pantallaEditar?.classList.add('hidden')
      pantallaLista?.classList.remove('hidden')
    }
  }
}

function initSubPantallasCuentas() {
  const vistaTabla = document.getElementById('vista-tabla-cuentas-detalle');
  const vistaForm = document.getElementById('vista-form-nueva-cuenta');
  const tablaDetalle = document.getElementById('tabla-cuentas-detalle');

  const btnAgregar = document.getElementById('btn-agregar-cuenta-detalle');
  const btnCancelar = document.getElementById('btn-cancelar-nueva-cuenta');
  const btnConfirmar = document.getElementById('btn-confirmar-nueva-cuenta');

  const inputCuenta = document.getElementById('nueva-cuenta-input');
  const inputIdRef = document.getElementById('editar-ID_Ref');
  const inputIdEdicion = document.getElementById('id_cuenta_edicion');
  const tituloForm = document.getElementById('titulo-form-cuenta');

  // 1. Botón "AGREGAR"
  if (btnAgregar) {
    btnAgregar.onclick = () => {
      if(inputCuenta) inputCuenta.value = '';
      if(inputIdEdicion) inputIdEdicion.value = '';
      if(tituloForm) tituloForm.textContent = 'Nueva Cuenta';

      vistaTabla.classList.add('hidden');
      vistaForm.classList.remove('hidden');
      if(inputCuenta) inputCuenta.focus();
    };
  }

  // Eventos de eliminar y editar
  if (tablaDetalle) {
    tablaDetalle.addEventListener('click', async (e) => {

      // Botón "EDITAR"
      const btnEditar = e.target.closest('.btn-editar-detalle');
      if (btnEditar) {
        e.preventDefault();
        const idCuenta = btnEditar.dataset.id;
        const numeroCuenta = btnEditar.dataset.cuenta;

        if(inputIdEdicion) inputIdEdicion.value = idCuenta;
        if(inputCuenta) inputCuenta.value = numeroCuenta;
        if(tituloForm) tituloForm.textContent = 'Editar Cuenta';

        vistaTabla.classList.add('hidden');
        vistaForm.classList.remove('hidden');
        if(inputCuenta) inputCuenta.focus();
        return;
      }

      // Botón "ELIMINAR"
      const btnEliminar = e.target.closest('.btn-eliminar-detalle');
      if (btnEliminar) {
        e.preventDefault();
        const idCuenta = btnEliminar.dataset.id;

        if (!(await Confirmar('Eliminar Cuenta', '¿Seguro que deseas eliminar esta cuenta bancaria?'))) {
          return;
        }

        try {
          const result = await SendDataEnd(`modales/cuentas/eliminar/${idCuenta}`, {
            method: 'POST'
          });

          if (result.success) {
            mostrarNotificacion('Cuenta eliminada correctamente ✅', 'success');
            // Recargar la tabla usando el ID del proveedor actual
            cargarCuentasDeProveedor(inputIdRef.value);
          } else {
            mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error');
          }
        } catch (error) {
          console.error(error);
          mostrarNotificacion('Error de conexión al eliminar.', 'error');
        }
      }
    });
  }

  // Botón "Cancelar"
  if (btnCancelar) {
    btnCancelar.onclick = () => {
      vistaForm.classList.add('hidden');
      vistaTabla.classList.remove('hidden');
    };
  }

  // Botón "Confirmar" (Guardar)
  if (btnConfirmar) {
    btnConfirmar.onclick = async () => {
      const valor = inputCuenta.value.trim();
      const idProveedor = inputIdRef.value;
      const idCuenta = inputIdEdicion.value;

      if (!valor) {
        mostrarNotificacion("Por favor ingrese una cuenta.", "warning");
        return;
      }
      if (valor.length < 16 || valor.length > 20) {
        mostrarNotificacion("La cuenta debe tener entre 16 y 20 caracteres.", "warning");
        return;
      }

      const formData = new FormData();
      formData.append('Cuenta', valor);
      formData.append('ID_Proveedor', idProveedor);

      const url = idCuenta
          ? `modales/cuentas/editar/${idCuenta}`
          : `modales/cuentas/insertar`;

      try {
        const result = await SendDataEnd(url, {
          method: 'POST',
          body: formData
        });

        if (result.success) {
          const mensaje = idCuenta ? 'Cuenta actualizada ✅' : 'Cuenta agregada ✅';
          mostrarNotificacion(mensaje, 'success');

          inputCuenta.value = '';
          if(inputIdEdicion) inputIdEdicion.value = '';

          vistaForm.classList.add('hidden');
          vistaTabla.classList.remove('hidden');

          cargarCuentasDeProveedor(idProveedor);
        } else {
          mostrarNotificacion(result.message || 'Error al guardar ❌', 'error');
        }
      } catch (error) {
        console.error(error);
        mostrarNotificacion('Error de conexión al guardar.', 'error');
      }
    };
  }
}

function initCuentasActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', (e) => {
    const btnEditar = e.target.closest("[id^='btn-editar-cuenta-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    const idProveedor = fila.dataset.id;
    document.getElementById('editar-ID_Ref').value = idProveedor
    document.getElementById('editar-RazonSocial').value = fila.dataset.razonsocial || ''
    document.getElementById('editar-RFC').value = fila.dataset.rfc || ''

    // Resetear vistas internas: Siempre mostrar tabla primero al entrar
    document.getElementById('vista-tabla-cuentas-detalle').classList.remove('hidden');
    document.getElementById('vista-form-nueva-cuenta').classList.add('hidden');

    cargarCuentasDeProveedor(idProveedor);

    document.getElementById('pantalla-lista-cuentas').classList.add('hidden')
    document.getElementById('pantalla-editar-cuenta').classList.remove('hidden')
  })
}

async function cargarCuentasDeProveedor(idProveedor) {
  const tbody = document.getElementById('tabla-cuentas-detalle');
  tbody.innerHTML = '<tr><td colspan="2" class="px-4 py-3 text-center text-gray-500 text-sm">Cargando cuentas...</td></tr>';

  try {
    const cuentas = await SendDataEnd(`modales/cuentas/proveedor/${idProveedor}`, { method: 'GET' });

    if (cuentas && cuentas.length > 0) {
      let html = '';
      cuentas.forEach(c => {
        html += `
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-700 align-middle">${c.Cuenta}</td>
                        <td class="px-4 py-2 text-center align-middle">
                            <div class="flex items-center justify-center space-x-2">
                                <!-- Botón Editar (con datos) -->
                                <a href="#" 
                                   class="text-green-600 hover:text-green-800 btn-editar-detalle" 
                                   data-id="${c.ID_Cuenta}" 
                                   data-cuenta="${c.Cuenta}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                </a>
                                <!-- Botón Eliminar -->
                                <a href="#" 
                                   class="text-red-600 hover:text-red-800 btn-eliminar-detalle" 
                                   data-id="${c.ID_Cuenta}">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
      });
      tbody.innerHTML = html;
    } else {
      tbody.innerHTML = '<tr><td colspan="2" class="px-4 py-3 text-center text-gray-500 text-sm">No hay cuentas registradas para este proveedor.</td></tr>';
    }
  } catch (error) {
    console.error('Error al cargar cuentas:', error);
    tbody.innerHTML = '<tr><td colspan="2" class="px-4 py-3 text-center text-red-500 text-sm">Error al cargar datos.</td></tr>';
  }
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
