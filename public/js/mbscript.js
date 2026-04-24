/**
 * Funciones para manejar la apertura y cierre de modales,
 * y la inicialización de su contenido dinámico.
 */
function abrirModal(opcion) {
  const titulosHeaders = [
    'TituloOperacion',
    'TituloCompras',
    'TituloDireccion',
    'TituloTesoreria',
    'TituloAlmacen',
    'TituloContador',
  ]

  if (titulosHeaders.includes(opcion)) {
    return
  }

  const parentModals = {
    registrar_productos: 'almacen',
    crud_productos: 'almacen',
    entrega_productos: 'almacen',
    reporte_almacen: 'almacen',
    crud_usuarios: 'catalogos',
    limpiar_almacenamiento: 'ajustes',
    crud_proveedores: 'catalogos',
    reportes: 'ajustes',
    razonsocial: 'catalogos',
    micuenta: 'ajustes',
    programar_pagos: 'programar_pagos',
    crud_departamento: 'catalogos',
    crud_places: 'catalogos',
    catalogo_productos: 'catalogos',
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

  const modalesAnchos = ['reportes', 'ver_historial', 'correcciones', 'lista_pagos', 'ReportePresupuesto', 'bitacora', 'catalogo_productos']

  if (modalesAnchos.includes(opcion)) {
    modal.classList.remove('justify-center')
    modalBox.classList.remove('max-w-4xl', 'mx-4', 'sm:mx-auto')
    modalBox.classList.add('max-w-[95vw]', 'mx-4')
  } else {
    modal.classList.add('justify-center')
    modalBox.classList.remove('max-w-[95vw]')
    modalBox.classList.add('max-w-4xl', 'mx-4', 'sm:mx-auto')
  }

  let titulos = {
    solicitar_material: 'Crear Requisición',
    ver_historial: 'Historial Y Estado De Requisición',
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
    catalogos: 'Catálogos',
    almacen: 'Almacén',
    reportes: 'Reportes/Auditoria',
    razonsocial: 'Razón social',
    reporte_almacen: 'Reportes/Historial',
    micuenta: 'Ajustes De Cuenta',
    programar_pagos: 'Programar pagos',
    recepcion_material: 'Recepción de Material',
    bajas_destruccion: 'Bajas por Destrucción',
    crud_places: 'Complejos',
    crud_departamento: 'Departametos',
    lista_pagos: 'Lista de pagos',
    crud_cuentas: 'Cuentas de proveedores',
    correcciones: 'Corregir Solicitudes',
    GrupoPresupuestal: 'Partida Presupuestal',
    BancoDpto: 'Cuentas Bancarias de los Departamentos',
    PresupuestoMensual: 'Creación/Asignación De Presupuestos',
    ReportePresupuesto: 'Reportes',
    SaldosBancarios: 'Saldos de Bancos',
    SegmentoNegocio: 'Segmentos de Negocio',
    UnidadOperativa: 'Unidades operativas',
    AjustesPresupuesto: 'Ajustes presupuestales',
    GastoManual: 'Registrar Gastos Indirectos',
    bitacora: 'Auditoría de Bitácora',
    catalogo_productos: 'Catálogo Maestro de Productos',
  }
  titulos['aprobar_solicitudes'] = 'Aprobar Requisiciones de Empleados'


  titulo.innerText = titulos[opcion] ?? 'Opción'

  // Si el modal ya está abierto, iniciamos la animación de salida mientras carga la nueva vista
  if (!modal.classList.contains('hidden')) {
    modal.classList.remove('active')
  }

  SendDataEnd(`modales/${opcion}`, { responseType: 'text' })
    .then((html) => {
      // Un pequeño retraso en caso de que la carga haya sido instantánea, 
      // para permitir que la animación de salida sea visible por un instante
      setTimeout(() => {
        contenido.innerHTML = html
        
        // Asegurarnos de que el modal esté visible (por si estaba cerrado)
        modal.classList.remove('hidden')
        
        // Forzar un reflow para reiniciar las animaciones
        void modal.offsetWidth
  
        // Activar la animación de entrada
        modal.classList.add('opacity-100', 'active')
  
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
        catalogo_productos: initCatalogoProductos,
        crud_cuentas: initCrudCuentas,
        correcciones: initControlMaestro,
        GrupoPresupuestal: initCrudGrupos,
        BancoDpto: initCrudBancoDpto,
        AjustesPresupuesto: initAjustesPresupuesto,
        UnidadOperativa: initCrudUnidades,
        SegmentoNegocio: initCrudSegmentos,
        solicitar_material: initSolicitarMaterialTodo,
        GastoManual: registrarComponenteGastoManual,
      }

      const inicializador = inicializadores[opcion]
      if (inicializador) {
        inicializador()
      }
      }, 50); // Cierra el setTimeout y su callback
    })
    .catch((error) => {
      console.error('Error al cargar modal:', error)
      contenido.innerHTML = '<p class="text-red-500">Error al cargar el contenido del modal.</p>'
      modal.classList.remove('hidden')
    })
}
function cerrarModal() {
  const modal = document.getElementById('modal-general')

  // Retiramos las clases que disparan la animación de entrada
  modal.classList.remove('active', 'opacity-100')

  // Esperamos 0.3-0.4s antes de ocultar completamente el elemento
  setTimeout(() => {
    modal.classList.add('hidden')
  }, 400)

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
function initPlaceSelectors(allPlaces) {
  if (!allPlaces || !Array.isArray(allPlaces) || allPlaces.length === 0) return

  const mappings = [
    { razon: 'razonSocialMaterial', place: 'placeMaterial', contenedor: 'contenedor-place-material' },
    { razon: 'razonSocialSinCotizar', place: 'placeSinCotizar', contenedor: 'contenedor-place-sincotizar' },
    { razon: 'razonSocialServicio', place: 'placeServicio', contenedor: 'contenedor-place-servicio' },
  ]

  mappings.forEach((map) => {
    const razonSelect = document.getElementById(map.razon)
    const placeSelect = document.getElementById(map.place)
    const contenedor = document.getElementById(map.contenedor)

    if (razonSelect && placeSelect) {
      // Eliminar el listener anterior si existe para evitar duplicados
      if (razonSelect._filterHandler) {
        razonSelect.removeEventListener('change', razonSelect._filterHandler)
      }

      const filtrar = () => {
        const selectedId = razonSelect.value
        placeSelect.innerHTML = '<option value="">Seleccione un condominio</option>'
        
        if (!selectedId) {
            if (contenedor) contenedor.classList.add('hidden');
            placeSelect.required = false;
            return;
        }

        // Filtrado robusto (maneja posibles variaciones en los nombres de las propiedades)
        const filtered = allPlaces.filter((p) => {
          // Buscamos en todas las variantes posibles que PostgreSQL/PHP podrían retornar
          const rsId = p.ID_RazonSocial || p.id_razon_social || p.id_razonsocial || p.Id_RazonSocial
          return String(rsId) === String(selectedId)
        })

        if (filtered.length === 0) {
            // Si no hay lugares para esta razón social, ocultamos el selector y quitamos el required
            if (contenedor) contenedor.classList.add('hidden');
            placeSelect.required = false;
            return;
        }

        // Si hay lugares, mostramos el contenedor y ponemos el required
        if (contenedor) contenedor.classList.remove('hidden');
        placeSelect.required = true;

        // Evitar duplicados por ID_Place
        const seen = new Set()
        filtered.forEach((p) => {
          const placeId = p.ID_Place || p.id_place
          if (placeId && !seen.has(placeId)) {
            seen.add(placeId)
            const opt = document.createElement('option')
            opt.value = placeId
            opt.textContent = p.Nombre_Corto || p.nombre_corto || 'Place ' + placeId
            placeSelect.appendChild(opt)
          }
        })
      }

      // Guardar el handler en el elemento para poder removerlo después
      razonSelect._filterHandler = filtrar
      razonSelect.addEventListener('change', filtrar)
      
      // Ejecutar una vez por si hay algo preseleccionado
      filtrar()
    }
  })
}

async function initSolicitarMaterial() {
  const formulario = document.getElementById('form-upload')
  if (formulario) {
    if (formulario.dataset.init === '1') return
    formulario.dataset.init = '1'
    initAccumulatedFileInputs(formulario)
  }

  const tabla = document.getElementById('tabla-productos')
  const agregarBtn = document.getElementById('agregar-fila')
  const subtotalTd = document.getElementById('subtotal-costo')
  const totalTd = document.getElementById('total-costo')
  const chkIVA = document.getElementById('agregar-iva')

  if (!tabla) return

  // Obtener datos de lugares desde el input oculto (forma confiable para carga via AJAX)
  const placesStore = document.getElementById('ALL_PLACES_DATA_STORE')
  if (placesStore) {
    try {
      const allPlaces = JSON.parse(placesStore.value)
      initPlaceSelectors(allPlaces)
    } catch (e) {
      console.error('Error al parsear datos de condominios:', e)
    }
  }

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
    // Clonamos para limpiar eventos previos (manteniendo tu lógica actual)
    const nuevoBtn = agregarBtn.cloneNode(true)
    agregarBtn.parentNode.replaceChild(nuevoBtn, agregarBtn)

    nuevoBtn.addEventListener('click', async () => {
      const rowHtml = await getProductRowHtml()

      // Usamos insertAdjacentHTML para meter el HTML directamente al final del body
      // Esto evita errores de "firstChild" con espacios en blanco
      tabla.insertAdjacentHTML('beforeend', rowHtml.trim())

      // Ahora obtenemos la última fila insertada para asignarle eventos
      const nuevasFilas = tabla.querySelectorAll('tr')
      const nuevaFila = nuevasFilas[nuevasFilas.length - 1]

      if (nuevaFila) {
        asignarEventosFila(nuevaFila)
        actualizarNumeros()
        actualizarBotonesEliminar()
        actualizarTotal()
      }
    })
  }

  loadRazonSocialProv('ProvSelect')

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
  const formulario = document.getElementById('form-upload-sin-cotizar')
  if (formulario) {
    if (formulario.dataset.init === '1') return
    formulario.dataset.init = '1'
    initAccumulatedFileInputs(formulario)
  }

  const tabla = document.getElementById('tabla-productos-sin-cotizar')
  const agregarBtn = document.getElementById('agregar-fila-sin-cotizar')

  if (!tabla) return

  // Obtener datos de lugares desde el input oculto
  const placesStore = document.getElementById('ALL_PLACES_DATA_STORE')
  if (placesStore) {
    try {
      const allPlaces = JSON.parse(placesStore.value)
      initPlaceSelectors(allPlaces)
    } catch (e) {
      console.error('Error al parsear datos de condominios:', e)
    }
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

  if (formulario) {
    formulario.addEventListener('submit', SendData)
  }
}
async function initSolicitarServicio() {
  const formulario = document.getElementById('form-servicio-upload')
  if (formulario) {
    if (formulario.dataset.init === '1') return
    formulario.dataset.init = '1'
    initAccumulatedFileInputs(formulario)
  }

  const tabla = document.getElementById('tabla-servicios')
  const agregarBtn = document.getElementById('agregar-fila-servicio')
  const subtotalTd = document.getElementById('subtotal-servicio')
  const totalTd = document.getElementById('total-servicio')
  const chkIVA = document.getElementById('agregar-iva-servicio')

  if (!tabla) return

  // Obtener datos de lugares desde el input oculto
  const placesStore = document.getElementById('ALL_PLACES_DATA_STORE')
  if (placesStore) {
    try {
      const allPlaces = JSON.parse(placesStore.value)
      initPlaceSelectors(allPlaces)
    } catch (e) {
      console.error('Error al parsear datos de condominios:', e)
    }
  }

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
  const proveedorSelect = document.getElementById('razonSocialServicioSelect')
  const cuentaSelect = document.getElementById('cuentaProveedorSelect')

  if (proveedorSelect && cuentaSelect) {
    proveedorSelect.addEventListener('change', async () => {
      const idProveedor = proveedorSelect.value

      // Limpiar opciones anteriores
      cuentaSelect.innerHTML = '<option value="">Seleccione una cuenta</option>'

      if (!idProveedor) return

      try {
        // Ruta para obtener datos de la tabla "Cuentas"
        const cuentas = await SendDataEnd(`modales/cuentas/proveedor/${idProveedor}`, {
          method: 'GET',
        })

        if (cuentas && cuentas.length > 0) {
          cuentas.forEach((c) => {
            const option = document.createElement('option')
            option.value = c.ID_Cuenta // El valor será el ID
            option.textContent = c.Cuenta // El texto visible será el número de cuenta/CLABE
            cuentaSelect.appendChild(option)
          })
        } else {
          const option = document.createElement('option')
          option.textContent = '(Este proveedor no tiene cuentas registradas)'
          option.disabled = true
          cuentaSelect.appendChild(option)
        }
      } catch (error) {
        console.error('Error al cargar cuentas bancarias:', error)
      }
    })
  }

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
 * Inicializador general para el modal de Solicitar Material (Requisiciones)
 * Ejecuta los inicializadores de todas las sub-secciones para que los selectores
 * estén listos desde que se abre el modal.
 */
function initSolicitarMaterialTodo() {
  initSolicitarMaterial()
  initSolicitarMaterialSinCotizar()
  initSolicitarServicio()
}

/**
 * Lógica para el modal "Ver Historial"
 */
let choicesDepartamento = null
let choicesProveedor = null
let choicesRazonSocial = null
function initPaginacionHistorial() {
  const tabla = document.getElementById('tabla-historial')
  if (!tabla) return

  const filtroEl = document.getElementById('filtroDepartamento')
  if (filtroEl) {
    choicesDepartamento = new Choices(filtroEl, {
      removeItemButton: true,
      placeholder: true,
      placeholderValue: 'Todos los departamentos',
      searchPlaceholderValue: 'Buscar...',
      itemSelectText: 'Seleccionar',
      noResultsText: 'No se encontraron resultados',
      noChoicesText: 'No hay más opciones para elegir',
    })
  }

  const filtroProvEl = document.getElementById('filtro-proveedor')
  if (filtroProvEl) {
    choicesProveedor = new Choices(filtroProvEl, {
      removeItemButton: true,
      placeholder: true,
      placeholderValue: 'Todos los proveedores',
      searchPlaceholderValue: 'Buscar...',
      itemSelectText: 'Seleccionar',
      noResultsText: 'No se encontraron resultados',
      noChoicesText: 'No hay más opciones para elegir',
    })
  }

  const filtroRazonEl = document.getElementById('filtro-razon-social')
  if (filtroRazonEl) {
    choicesRazonSocial = new Choices(filtroRazonEl, {
      removeItemButton: true,
      placeholder: true,
      placeholderValue: 'Todas las razones sociales',
      searchPlaceholderValue: 'Buscar...',
      itemSelectText: 'Seleccionar',
      noResultsText: 'No se encontraron resultados',
      noChoicesText: 'No hay más opciones para elegir',
    })
  }

  //Filtro para la casilla de departamentos
  function validarFiltroDepartamento() {
    const filtro = document.getElementById('filtroDepartamento')
    if (!filtro) return
    const deptosPermitidos = [
      'Administración',
      'Compras',
      'Direccion',
      'Tesoreria',
      'Direccion Campus',
      'Contaduría',
      'Contaduria',
    ]

    const miDepto = typeof USER_DEPT_NAME !== 'undefined' ? USER_DEPT_NAME : ''

    if (!deptosPermitidos.includes(miDepto)) {
      filtro.parentElement.style.display = 'none'
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

  const exceptions = [
    'Compras',
    'Administración',
    'Direccion',
    'Tesoreria',
    'Direccion Campus',
    'Contaduría',
  ]
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
      const MetodoPag = getMetodoPago(item.MetodoPago)

      // El valor que viene del backend (item.MontoTotal) ya es el 'Total' de la tabla Cotizacion
      const totalRaw = parseFloat(item.MontoTotal) || 0

      // Formateo de moneda
      const montoFormateado = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
      }).format(totalRaw)

      return `
    <tr class="text-center hover:bg-gray-50 transition">
        <td class="hidden border px-4 py-2">${item.ID_Solicitud}</td>
        <td class="border px-4 py-2 font-medium">${item.No_Folio || 'N/A'}</td>
        <td class="border px-4 py-2 text-sm">${item.Fecha}</td>
        <td class="border px-4 py-2 text-xs font-semibold">${item.Complejo || '<span class="text-gray-400">N/A</span>'}</td>
        <td class="border px-4 py-2 text-xs text-gray-600">${item.DepartamentoNombre} - ${item.PlaceNombre}</td>
        
        <td class="border px-4 py-2 text-sm text-left px-6">${item.ProveedorNombre || '<span class="text-gray-400">N/A</span>'}</td>
        
        <td class="border px-4 py-2 font-bold text-gray-800">
            ${totalRaw > 0 ? montoFormateado : '<span class="text-gray-400">$0.00</span>'}
        </td>
        
        <td class="border px-4 py-2 col-estado" data-estado="${status}" title="${status}">
            <div class="flex flex-col items-center">
                ${svg}
                <span class="text-[10px] uppercase font-bold">${status}</span>
            </div>
        </td>
        <td class="border px-4 py-2 text-xs">${MetodoPag}</td>
        <td class="border px-4 py-2">
            <button class="text-blue-600 hover:text-blue-800 font-semibold transition" onclick="mostrarVerHistorial(${item.ID_Solicitud}); return false;">
                Detalles
            </button>
        </td>
    </tr>
  `
    },

    filterFunction: (allData, form) => {
      const fechaFiltro = document.getElementById('filtro-fecha').value
      const filtrarPorMes = document.getElementById('filtrar-por-mes').checked
      const estadoFiltro = document.getElementById('filtro-estado').value
      const folioFiltro = document.getElementById('filtro-folio')?.value.toLowerCase() || ''
      const tipoFiltro = document.getElementById('filtro-tipo-historial')?.value || ''
      const departamentosSeleccionados = choicesDepartamento
        ? choicesDepartamento.getValue(true)
        : []
      const proveedoresSeleccionados = choicesProveedor ? choicesProveedor.getValue(true) : []
      const razonesSeleccionadas = choicesRazonSocial ? choicesRazonSocial.getValue(true) : []

      return allData.filter((item) => {
        const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro
        
        const coincideTipo = !tipoFiltro || 
            (tipoFiltro === 'Producto' && (item.Tipo == 0 || item.Tipo == 1)) || 
            (tipoFiltro === 'Servicio' && item.Tipo == 2);

        const coincideProveedor =
          proveedoresSeleccionados.length === 0 ||
          (item.ProveedorNombre && proveedoresSeleccionados.includes(item.ProveedorNombre))

        const coincideFolio =
          !folioFiltro || (item.No_Folio && item.No_Folio.toLowerCase().includes(folioFiltro))

        let coincideDepartamento = true
        if (departamentosSeleccionados.length > 0) {
          const itemDepartamentoCompleto = `${item.DepartamentoNombre}|${item.PlaceNombre || ''}`
          coincideDepartamento = departamentosSeleccionados.includes(itemDepartamentoCompleto)
        } else {
          coincideDepartamento = true
        }

        let coincideRazon = true
        if (razonesSeleccionadas.length > 0) {
          coincideRazon = razonesSeleccionadas.includes(item.Complejo)
        }

        if (choicesDepartamento && choicesDepartamento.getValue(true).length === 0) {
          coincideDepartamento = true
        }

        const passesOtherFilters = coincideEstado && coincideDepartamento && coincideProveedor && coincideFolio && coincideTipo && coincideRazon

        if (!fechaFiltro) {
          return passesOtherFilters
        }

        const fechaItem = item.Fecha
        if (filtrarPorMes) {
          const mesFiltro = fechaFiltro.slice(0, 7)
          const mesItem = fechaItem.slice(0, 7)
          return (
            mesItem === mesFiltro &&
            passesOtherFilters
          )
        } else {
          return (
            fechaItem === fechaFiltro &&
            passesOtherFilters
          )
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

    html += generarSeccionAdjuntos(data)

    // Botón de Cancelar para usuarios del mismo departamento en estados previos a cotización
    const estadosCancelables = ['aprobacion pendiente', 'en espera'];
    
    // Soporte para PascalCase o minúsculas (compatibilidad PostgreSQL)
    const rawEstado = data.Estado || data.estado || '';
    const rawIdDpto = data.ID_Dpto || data.id_dpto;
    
    const estadoNormalizado = rawEstado.toLowerCase().trim();
    const mismoDepartamento = parseInt(rawIdDpto) === parseInt(window.CURRENT_DEPTO_ID);

    if (estadosCancelables.includes(estadoNormalizado) && mismoDepartamento) {
      html += `
            <div class="mt-8 flex items-center justify-end border-t pt-6">
                <button onclick="globalCancelarSolicitud(${idSolicitud}, () => abrirModal('ver_historial'))" 
                        class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition shadow-sm">
                    Cancelar Requisición
                </button>
            </div>`;
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

function exportarRequisicionesExcel() {
  window.location.href = `${BASE_URL}api/exportar-requisiciones`
}

function exportarHistorialExcel() {
  const fecha = document.getElementById('filtro-fecha').value
  const porMes = document.getElementById('filtrar-por-mes').checked
  const estado = document.getElementById('filtro-estado').value
  const tipo = document.getElementById('filtro-tipo-historial')?.value || ''

  // Obtener valores de Choices.js
  const deptosSeleccionados = choicesDepartamento ? choicesDepartamento.getValue(true) : []

  const params = new URLSearchParams()
  if (fecha) params.append('fecha', fecha)
  if (porMes) params.append('por_mes', '1')
  if (estado) params.append('estado', estado)
  if (tipo) params.append('tipo', tipo)
  if (deptosSeleccionados.length > 0) {
    params.append('dpto', deptosSeleccionados.join(','))
  }

  window.location.href = `api/historial/exportar?${params.toString()}`
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
    !(await Confirmar('Cancelar solicitud', '¿Está seguro de que desea cancelar la solicitud?'))
  ) {
    return
  }

  const comentarios = await InputPrompt(
    'Cancelar Solicitud',
    'Por favor, ingrese el motivo de la cancelación (obligatorio):',
    true,
  )

  if (comentarios === null) {
    return
  }

  const procesandoNotif = mostrarNotificacion('Cancelando solicitud...', 'info', 999999)

  try {
    const result = await SendDataEnd('api/solicitud/cancelar', {
      method: 'POST',
      body: {
        ID_Solicitud: idSolicitud,
        ComentariosAdmin: comentarios,
      },
    })

    procesandoNotif.click()

    if (result.success) {
      mostrarNotificacion(result.message || 'Solicitud cancelada correctamente.', 'success')
      abrirModal('revisar_solicitudes')
    } else {
      mostrarNotificacion(result.message || 'No se pudo cancelar la solicitud.', 'error')
    }
  } catch (error) {
    procesandoNotif.click()
    console.error('Error al cancelar solicitud:', error)
    mostrarNotificacion(error.data.messages.error || error.message, 'error')
  }
}

function regresarTabla() {
  document.getElementById('div-ver').classList.add('hidden')
  document.getElementById('div-cotizar').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
  document.getElementById('btn-exportar-requisiciones').classList.remove('hidden') // Mostrar el botón de exportar

  // Reset the state of the cotizar view
  const inputBusqueda = document.getElementById('buscar-proveedor')
  if (inputBusqueda) {
    inputBusqueda.value = ''
  }
  const tbody = document.querySelector('#div-cotizar tbody')
  if (tbody) {
    tbody.innerHTML = ''
  }
  const paginacionDiv = document.querySelector('#div-cotizar #paginacion-proveedores')
  if (paginacionDiv) {
    paginacionDiv.innerHTML = ''
  }
  const btnGenerar = document.getElementById('btn-generar-cotizacion')
  if (btnGenerar) {
    btnGenerar.onclick = null // Remove listener
    btnGenerar.disabled = true // Disable button
  }
}

/**
 * Lógica del Catálogo Maestro de Productos
 */
function initCatalogoProductos() {
  const listado = document.getElementById('pantalla-lista-catalogo')
  const formularioCont = document.getElementById('pantalla-form-catalogo')
  const form = document.getElementById('form-catalogo')
  const tabla = document.getElementById('tabla-catalogo-body')

  if (!listado || !form) return

  // Inicializar Choices.js para una mejor experiencia de búsqueda
  const configChoices = {
    removeItemButton: false,
    itemSelectText: '',
    searchPlaceholderValue: 'Buscar...',
    shouldSort: false,
    placeholder: true,
  }

  const choices = {}
  ;['form-rs', 'form-seg', 'form-place', 'form-depto', 'form-grupo'].forEach((id) => {
    const el = document.getElementById(id)
    if (el) choices[id] = new Choices(el, configChoices)
  })

  // Botones Navegación
  const btnAgregar = document.getElementById('btn-agregar-catalogo')
  if (btnAgregar) {
    btnAgregar.onclick = () => {
      form.reset()
      Object.values(choices).forEach((c) => c.setChoiceByValue(''))
      const idInput = document.getElementById('form-id-cat')
      if (idInput) idInput.value = ''
      const tituloForm = document.getElementById('form-catalogo-titulo')
      if (tituloForm) tituloForm.innerText = 'Nuevo Producto'
      listado.classList.add('hidden')
      formularioCont.classList.remove('hidden')
    }
  }

  const btnRegresar = document.getElementById('btn-regresar-catalogo')
  if (btnRegresar) {
    btnRegresar.onclick = () => {
      formularioCont.classList.add('hidden')
      listado.classList.remove('hidden')
    }
  }

  // Paginación y Filtros
  setupClientSideTable({
    rowsSelector: '#tabla-catalogo-body tr[data-id]',
    paginationSelector: 'paginacion-catalogo',
    filterFormSelector: '#form-filtros-catalogo',
    filterFunction: (row) => {
      const nombre = (document.getElementById('buscar-nombre-catalogo')?.value || '').toLowerCase()
      const depto = document.getElementById('filtro-departamento-catalogo')?.value || ''
      const grupo = document.getElementById('filtro-grupo-catalogo')?.value || ''

      const rowNombre = row.dataset.nombre.toLowerCase()
      const rowDepto = row.querySelector('.text-gray-500')?.innerText || ''
      const rowGrupo = row.querySelector('.bg-blue-100')?.innerText || ''

      return (
        rowNombre.includes(nombre) &&
        (depto === '' || rowDepto.includes(depto)) &&
        (grupo === '' || rowGrupo.includes(grupo))
      )
    },
  })

  // Guardar / Actualizar
  form.onsubmit = async (e) => {
    e.preventDefault()
    const id = document.getElementById('form-id-cat').value
    const endpoint = id ? `api/catalogo/update/${id}` : 'api/catalogo/create'
    const formData = new FormData(form)

    try {
      const res = await SendDataEnd(endpoint, {
        method: 'POST',
        body: formData,
      })

      if (res.success) {
        mostrarNotificacion('Operación exitosa ✅', 'success')
        abrirModal('catalogo_productos') // Recargar
      } else {
        mostrarNotificacion(res.message || 'Error en la operación', 'error')
      }
    } catch (err) {
      mostrarNotificacion('Error de conexión', 'error')
    }
  }

  // Acciones Tabla (Editar / Eliminar)
  if (tabla) {
    tabla.addEventListener('click', async (e) => {
      const btnEdit = e.target.closest('.btn-editar-cat')
      const btnDel = e.target.closest('.btn-eliminar-cat')

      if (btnEdit) {
        const row = btnEdit.closest('tr')
        document.getElementById('form-id-cat').value = row.dataset.id
        document.getElementById('form-nombre').value = row.dataset.nombre

        // Cargar valores en Choices
        if (choices['form-rs']) choices['form-rs'].setChoiceByValue(row.dataset.rs || '')
        if (choices['form-seg']) choices['form-seg'].setChoiceByValue(row.dataset.seg || '')
        if (choices['form-place']) choices['form-place'].setChoiceByValue(row.dataset.place || '')
        if (choices['form-depto']) choices['form-depto'].setChoiceByValue(row.dataset.depto || '')
        if (choices['form-grupo']) choices['form-grupo'].setChoiceByValue(row.dataset.grupo || '')

        document.getElementById('form-catalogo-titulo').innerText = 'Editar Producto'
        listado.classList.add('hidden')
        formularioCont.classList.remove('hidden')
      }

      if (btnDel) {
        const row = btnDel.closest('tr')
        if (await Confirmar('¿Eliminar producto?', `¿Estás seguro de eliminar "${row.dataset.nombre}"?`)) {
          try {
            const res = await SendDataEnd(`api/catalogo/delete/${row.dataset.id}`, { method: 'POST' })
            if (res.success) {
              mostrarNotificacion('Eliminado correctamente', 'success')
              row.remove()
            }
          } catch (err) {
            mostrarNotificacion('Error al eliminar', 'error')
          }
        }
      }
    })
  }

  // Lógica de Asistencia de Llenado (Upward & Downward)
  const rsSel = document.getElementById('form-rs')
  const segSel = document.getElementById('form-seg')
  const placeSel = document.getElementById('form-place')
  const deptoSel = document.getElementById('form-depto')

  // ASISTENCIA HACIA ARRIBA (Upward)
  const handleUpwardSelection = (element, parents) => {
    const selectedOption = element.options[element.selectedIndex]
    if (!selectedOption || selectedOption.value === '') return

    parents.forEach((p) => {
      const parentVal = selectedOption.dataset[p.attr]
      if (parentVal && choices[p.id]) {
        choices[p.id].setChoiceByValue(parentVal)
      }
    })
  }

  if (deptoSel) {
    deptoSel.addEventListener('change', () => {
      handleUpwardSelection(deptoSel, [{ id: 'form-place', attr: 'place' }])
      // Al cambiar place, se disparará su propio listener para RS y Segmento
      placeSel.dispatchEvent(new Event('change'))
    })
  }

  if (placeSel) {
    placeSel.addEventListener('change', () => {
      handleUpwardSelection(placeSel, [
        { id: 'form-rs', attr: 'rs' },
        { id: 'form-seg', attr: 'seg' },
      ])
    })
  }

  if (segSel) {
    segSel.addEventListener('change', () => {
      handleUpwardSelection(segSel, [{ id: 'form-rs', attr: 'rs' }])
    })
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
      const cortoFiltro = (
        document.getElementById('buscar-nombre-corto')?.value || ''
      ).toLowerCase()
      const completoFiltro = (
        document.getElementById('buscar-nombre-completo')?.value || ''
      ).toLowerCase()

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
      const result = await SendDataEnd('modales/crud_places/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Lugar agregado correctamente ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formAgregar.reset()
        // Recargamos el modal para ver los cambios
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
      const result = await SendDataEnd(`modales/crud_places/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Lugar actualizado correctamente ✅', 'success')

        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          // Actualizar textos básicos
          fila.querySelector('.nombre-corto').textContent = formData.get('Nombre_Corto')
          fila.querySelector('.nombre-completo').textContent = formData.get('Nombre_Completo')

          // --- NUEVO: Actualizar Razón Social visualmente ---
          const selectRS = document.getElementById('editar-ID_RazonSocial')
          // Obtenemos el texto de la opción seleccionada para mostrarlo en la tabla
          const rsTexto = selectRS.options[selectRS.selectedIndex].text
          // Si el value es vacío (no seleccionó nada), ponemos un guion, si no, el nombre
          fila.querySelector('.razon-social-nombre').textContent = selectRS.value ? rsTexto : '-'

          // --- ACTUALIZAR SEGMENTO ---
          const selectSeg = document.getElementById('editar-id_segmento')
          const segTexto = selectSeg.options[selectSeg.selectedIndex].text
          fila.querySelector('.segmento-nombre').textContent = selectSeg.value ? segTexto : '-'

          // Actualizar Datasets
          fila.dataset.nombreCorto = formData.get('Nombre_Corto')
          fila.dataset.nombreCompleto = formData.get('Nombre_Completo')
          // --- NUEVO: Actualizar dataset de ID RS ---
          fila.dataset.idRazonSocial = formData.get('ID_RazonSocial')
          fila.dataset.idSegmento = formData.get('id_segmento')
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
    // --- ELIMINAR ---
    const btnEliminar = e.target.closest("[id^='btn-eliminar-places-']")
    if (btnEliminar) {
      e.preventDefault()
      const id = btnEliminar.dataset.id

      if (!(await Confirmar('Eliminar Lugar?', '¿Seguro que deseas eliminar este lugar?'))) return

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

    // --- EDITAR ---
    const btnEditar = e.target.closest("[id^='btn-editar-places-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    document.getElementById('editar-ID_Place').value = fila.dataset.id
    document.getElementById('editar-Nombre_Corto').value = fila.dataset.nombreCorto
    document.getElementById('editar-Nombre_Completo').value = fila.dataset.nombreCompleto

    // --- NUEVO: Cargar el valor de la Razón Social al select ---
    // Usamos el dataset que agregamos en la vista HTML
    document.getElementById('editar-ID_RazonSocial').value = fila.dataset.idRazonSocial || ''
    document.getElementById('editar-id_segmento').value = fila.dataset.idSegmento || ''

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

/**
 * Genera el HTML para una barra de presupuesto individual
 * Fondo Verde (Total), Rojo (Usado), Amarillo Parpadeante (Impacto de esta solicitud)
 */
function generarBarraPresupuestoHTML(presupuesto) {
  if (!presupuesto) return ''

  const asignado = parseFloat(presupuesto.Monto_Asignado || 0)
  const comprometido = parseFloat(presupuesto.Monto_Comprometido || 0)
  const ejecutado = parseFloat(presupuesto.Monto_Ejecutado || 0)
  const montoImpacto = parseFloat(presupuesto.ImpactoActual || 0)
  
  const usadoAnterior = comprometido + ejecutado
  const totalConImpacto = usadoAnterior + montoImpacto
  
  // Porcentajes para la barra
  const pctUsado = asignado > 0 ? Math.min(100, (usadoAnterior / asignado) * 100) : 0
  const pctImpacto = asignado > 0 ? Math.min(100 - pctUsado, (montoImpacto / asignado) * 100) : 0
  
  // Lógica de aviso de excedente
  const excedePresupuesto = totalConImpacto > asignado
  const montoExcedido = totalConImpacto - asignado

  const fmt = (m) =>
    new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(m)

  return `
        <style>
            @keyframes budget-blink-alt {
                from { opacity: 1; }
                to { opacity: 0; }
            }
            .blink-budget-new {
                animation: budget-blink-alt 0.8s ease-in-out infinite alternate;
            }
            @keyframes row-blink-red {
                from { background-color: #fee2e2; }
                to { background-color: #ffffff; }
            }
            .blink-row-red {
                animation: row-blink-red 1s ease-in-out infinite alternate;
            }
        </style>
        <div class="bg-white p-4 border rounded-lg shadow-sm border-gray-200 mb-4">
            <div class="flex justify-between items-center mb-2">
                <h4 class="text-sm font-bold text-gray-700">Partida: <span class="text-blue-600">${presupuesto.GrupoNombre || 'N/A'}</span></h4>
                <div class="flex flex-col items-end">
                    <span class="text-[10px] text-gray-500 font-bold uppercase">Impacto de Solicitud: ${fmt(montoImpacto)}</span>
                    ${presupuesto.SinPresupuesto ? '<span class="text-[10px] text-red-700 font-black animate-pulse">🛑 BLOQUEADO: SIN PRESUPUESTO ASIGNADO</span>' : (excedePresupuesto ? `<span class="text-[10px] text-red-600 font-black animate-pulse">⚠️ EXCEDE PRESUPUESTO POR ${fmt(montoExcedido)}</span>` : '')}
                </div>
            </div>
            
            <!-- Contenedor Barra: Fondo Verde (Asignado) -->
            <div class="w-full ${presupuesto.SinPresupuesto ? 'bg-gray-300' : 'bg-green-500'} rounded-full h-6 mb-4 overflow-hidden border border-gray-300 relative">
                ${presupuesto.SinPresupuesto ? 
                    `<div class="bg-red-600 h-full w-full opacity-20 absolute top-0 left-0"></div>` :
                    `<!-- Capa Roja: Lo ya usado (Comprometido + Ejecutado) -->
                    <div class="bg-red-600 h-full absolute left-0 top-0 transition-all duration-500" style="width: ${pctUsado}%"></div>
                    
                    <!-- Capa Amarilla: Impacto de los productos de esta partida (Parpadeante) -->
                    <div class="bg-yellow-400 h-full absolute blink-budget-new transition-all duration-500" style="left: ${pctUsado}%; width: ${pctImpacto}%"></div>`
                }
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <p class="text-[9px] uppercase text-gray-500 font-bold">Total Asignado</p>
                    <p class="text-xs font-bold ${presupuesto.SinPresupuesto ? 'text-red-600' : 'text-gray-800'}">${presupuesto.SinPresupuesto ? 'NO CONFIG.' : fmt(asignado)}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase text-red-600 font-bold">Usado Anterior</p>
                    <p class="text-xs font-bold text-red-600">${fmt(usadoAnterior)}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase text-yellow-600 font-bold">Solicitud Actual</p>
                    <p class="text-xs font-bold text-yellow-600">${fmt(montoImpacto)}</p>
                </div>
                <div>
                    <p class="text-[9px] uppercase text-gray-500 font-bold">Saldo Final</p>
                    <p class="text-xs font-bold ${asignado - totalConImpacto < 0 || presupuesto.SinPresupuesto ? 'text-red-600' : 'text-green-600'}">${presupuesto.SinPresupuesto ? 'N/A' : fmt(asignado - totalConImpacto)}</p>
                </div>
            </div>
        </div>
    `
}

window.mostrarVerDictamen = async function (idSolicitud) {
  document.getElementById('div-tabla').classList.add('hidden')
  const divVer = document.getElementById('div-ver-dictamen')
  divVer.classList.remove('hidden')

  const detallesContainer = document.getElementById('detallesDictamen')
  detallesContainer.innerHTML = `<p class="text-center text-gray-500">Cargando detalles de la solicitud ${idSolicitud}...</p>`

  try {
    const data = await SendDataEnd(`api/cotizacion/details/${idSolicitud}`)
    if (data.error) throw new Error(data.error)

    let html = generarDetallesSolicitudHTML(data)

    // --- INTEGRACIÓN DE BARRA DE PRESUPUESTO (SEMÁFORO) ---
    let tieneBloqueoPresupuesto = false
    let tieneGruposAsignados = false
    const presupuestoContainer = document.getElementById('presupuesto-resumen-container')
    
    if (presupuestoContainer) {
      if (data.presupuestos_detallados && data.presupuestos_detallados.length > 0) {
        tieneGruposAsignados = true
        let presupuestoHtml = ''
        data.presupuestos_detallados.forEach((pres) => {
          if (pres.SinPresupuesto) tieneBloqueoPresupuesto = true
          presupuestoHtml += generarBarraPresupuestoHTML(pres)
        })
        presupuestoContainer.innerHTML = presupuestoHtml
        presupuestoContainer.classList.remove('hidden')
      } else {
        // No tiene grupos asignados
        presupuestoContainer.innerHTML = `
          <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-6 shadow-sm">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-orange-700 font-bold">
                  Aviso: Esta solicitud no tiene partidas presupuestales asignadas a sus ítems.
                </p>
                <p class="text-xs text-orange-600">
                  Podrá aprobarla, pero no se descontará de ningún presupuesto mensual.
                </p>
              </div>
            </div>
          </div>
        `
        presupuestoContainer.classList.remove('hidden')
      }
    }
    // -----------------------------------------------------

    html += generarComentariosHtml(data)

    html += generarProductosServiciosHTML(data)

    if (data.ComentariosUser) {
      html += `
            <div class="mt-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Comentarios del Usuario:</label>
                <div class="p-3 bg-gray-50 border border-gray-200 rounded-md text-gray-800 text-sm whitespace-pre-wrap">
                    ${data.ComentariosUser}
                </div>
            </div>`
    }

    // 2. Comentarios de la Cotización (Solo lectura)
    if (data.ComentarioCotizacion) {
      html += `
            <div class="mt-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Comentarios de la Cotización:</label>
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-md text-gray-800 text-sm whitespace-pre-wrap">
                    ${data.ComentarioCotizacion}
                </div>
            </div>`
    }

    html += generarSeccionAdjuntos(data)

    // Solo mostrar botones de acción si la solicitud está 'En revision'
    if (data.Estado === 'En revision') {
      let btnAprobar = '';
      
      if (tieneBloqueoPresupuesto) {
        btnAprobar = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative text-sm font-bold animate-pulse">
             ⚠️ No se puede aprobar: Hay productos sin presupuesto asignado para este mes.
           </div>`;
      } else {
        // Si es material y NO tiene grupos asignados, pedimos confirmación especial
        const mensajeExtra = (!tieneGruposAsignados && data.Tipo != 2) 
          ? "'Esta solicitud NO tiene partidas presupuestales. ¿Deseas aprobarla de todos modos?'" 
          : "null";

        btnAprobar = `<button onclick="dictaminarDictamen(${idSolicitud}, 'Aprobada', ${mensajeExtra})" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                        Aprobar
                    </button>`;
      }

      html += `
                <div class="mt-8 flex items-center justify-end space-x-4 border-t pt-6">
                    <button onclick="mostrarVerPdf(${idSolicitud}, 1)" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Ver PDF
                    </button>
                    
                    <button onclick="globalCancelarSolicitud(${idSolicitud}, () => abrirModal('dictamen_solicitudes'))" 
            class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition">
        Cancelar Solicitud
    </button>
    
                    ${btnAprobar}
                </div>
            `
    }

    detallesContainer.innerHTML = html
  } catch (error) {
    console.error('Error al cargar detalles para dictamen:', error)
    detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

window.regresarTablaDictamen = function () {
  document.getElementById('div-ver-dictamen').classList.add('hidden')
  document.getElementById('div-tabla').classList.remove('hidden')
}

window.dictaminarDictamen = async function (idSolicitud, nuevoEstado, mensajeExtra = null) {
  if (mensajeExtra) {
    if (!(await Confirmar('Atención', mensajeExtra))) return
  }

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

async function cancelarReqOrden(idSolicitud) {
  if (
    !(await Confirmar('Cancelar solicitud', '¿Está seguro de que desea cancelar la solicitud?'))
  ) {
    return
  }

  const comentarios = await InputPrompt(
    'Cancelar Solicitud',
    'Por favor, ingrese el motivo de la cancelación (obligatorio):',
    true,
  )

  if (comentarios === null) {
    return
  }

  const procesandoNotif = mostrarNotificacion('Cancelando solicitud...', 'info', 999999)

  try {
    const result = await SendDataEnd('api/solicitud/cancelar', {
      method: 'POST',
      body: {
        ID_Solicitud: idSolicitud,
        ComentariosAdmin: comentarios,
      },
    })

    procesandoNotif.click()

    if (result.success) {
      mostrarNotificacion(result.message || 'Solicitud cancelada correctamente.', 'success')
      abrirModal('ordenes_compra')
    } else {
      mostrarNotificacion(result.message || 'No se pudo cancelar la solicitud.', 'error')
    }
  } catch (error) {
    procesandoNotif.click()
    console.error('Error al cancelar solicitud:', error)
    mostrarNotificacion(error.data.messages.error || error.message, 'error')
  }
}

// --- FUNCIÓN MODIFICADA ---
async function mostrarVerOrdenCompra(idOrden, $idsession) {
  document.getElementById('div-tabla-ordenes').classList.add('hidden')
  document.getElementById('div-ver-orden').classList.remove('hidden')
  const detallesContainer = document.getElementById('detallesOrdenCompra')
  const iduser = $idsession

  detallesContainer.innerHTML = `<p class="text-center p-4">Cargando detalles de la orden ${idOrden}...</p>`
  try {
    const data = await SendDataEnd(`api/cotizacion/details/${idOrden}`)
    if (data.error) throw new Error(data.error)

    // 1. Generamos el HTML de encabezado
    let html = generarDetallesSolicitudHTML(data)

    html += generarComentariosHtml(data)

    // --- NUEVO: CHECKBOX DE CORRECCIÓN DE IVA ---
    // Solo permitimos corregir si está aprobada (antes de enviarse o pagarse)
    // En la función mostrarVerOrdenCompra...

    if (data.Estado === 'Aprobada') {
      const tieneIva = data.IVA == 1 || data.IVA === 't' || data.IVA === true

      // CORRECCIÓN AQUÍ: Pasamos $idsession como 3er argumento
      html += `
            <div class="flex justify-end items-center mb-2 px-4 p-2 rounded ">
                <label class="inline-flex items-center cursor-pointer select-none">
                    <span class="ml-2 text-gray-800 font-medium">Agregar IVA a los precios</span>
                    
                    <input type="checkbox"
                        id="chk-iva-correction-${idOrden}"
                        onchange="toggleIvaOrden(${idOrden}, this.checked, ${$idsession})" 
                        ${tieneIva ? 'checked' : ''}
                        class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500 border-gray-300 transition duration-150 ease-in-out cursor-pointer">
                        
                </label>
                <div id="loading-iva-${idOrden}" class="hidden ml-3">
                   </div>
            </div>`
    }

    html += generarProductosServiciosHTML(data)

    if (typeof generarSeccionAdjuntos === 'function') {
      html += generarSeccionAdjuntos(data)
    }

    // Solo mostrar botones de acción si la solicitud está 'Aprobada'
    if (data.Estado === 'Aprobada') {
      html += `
                <div class="mt-8 flex justify-end space-x-4">
                    <button onclick="mostrarOrdenPdf(${idOrden}, 1)" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Ver Orden (PDF)
                    </button>
                    
                    <button onclick="enviarOrdenCompra(${idOrden}, ${iduser}, this)" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition">
                        Enviar orden de compra
                    </button>
                </div>
            `
    }
    detallesContainer.innerHTML = html
  } catch (error) {
    console.error(error)
    detallesContainer.innerHTML = `<p class="text-center text-red-500">No se pudieron cargar los detalles. ${error.message}</p>`
  }
}

async function toggleIvaOrden(idSolicitud, nuevoEstadoIva, idSession) {
  const loadingIcon = document.getElementById(`loading-iva-${idSolicitud}`)
  const checkbox = document.getElementById(`chk-iva-correction-${idSolicitud}`)

  // UI: Mostrar carga y bloquear checkbox
  if (loadingIcon) loadingIcon.classList.remove('hidden')
  if (checkbox) checkbox.disabled = true

  try {
    // 1. Obtener datos actuales para no perder información (proveedor, cuenta, productos base)
    const currentData = await SendDataEnd(`api/cotizacion/details/${idSolicitud}`)

    if (!currentData || currentData.error) {
      throw new Error('No se pudieron obtener los datos de la orden para actualizar.')
    }

    const isServicio = currentData.Tipo == 2

    // 2. Preparar el array de productos para la API de update
    // IMPORTANTE: Enviamos el "Importe" tal cual viene de la BD (Precio Base).
    // Al cambiar el flag "iva", el backend hará la matemática (Base * 1.16) o dejará la Base sola.
    let productosPayload = []

    if (currentData.productos && currentData.productos.length > 0) {
      productosPayload = currentData.productos.map((p) => {
        // Parseamos a float para evitar errores de string
        const importeBase = parseFloat(p.Importe) || 0

        if (isServicio) {
          return {
            nombre: p.Nombre,
            importe: importeBase,
          }
        } else {
          return {
            codigo: p.Codigo,
            nombre: p.Nombre,
            cantidad: parseFloat(p.Cantidad) || 1,
            importe: importeBase,
          }
        }
      })
    }

    // 3. Determinar ID Cotización (si existe)
    const idCotizacion =
      currentData.ID_Cotizacion ||
      (currentData.cotizaciones && currentData.cotizaciones.length > 0
        ? currentData.cotizaciones[0].ID_Cotizacion
        : null)

    // 4. Construir Payload
    const payload = {
      id_solicitud: idSolicitud,
      id_cotizacion_seleccionada: idCotizacion,
      productos: productosPayload,
      comentarios: currentData.ComentariosAdmin || null, // Mantenemos comentarios del admin
      id_cuenta: currentData.ID_Cuenta || null, // Mantenemos la cuenta bancaria seleccionada
      iva: nuevoEstadoIva ? 1 : 0, // <--- AQUÍ CAMBIAMOS EL ESTADO DEL IMPUESTO
    }

    // 5. Enviar Actualización
    const result = await SendDataEnd('api/solicitud/update', {
      method: 'POST',
      body: payload,
    })

    if (result.success) {
      mostrarNotificacion('IVA actualizado y PDF regenerado correctamente.', 'success')

      // 6. Recargar la vista para ver los nuevos totales
      // Pasamos idSolicitud y el idSession que recibimos como argumento
      await mostrarVerOrdenCompra(idSolicitud, idSession)
    } else {
      throw new Error(result.message || 'Error desconocido al actualizar la orden.')
    }
  } catch (error) {
    console.error('Error en toggleIvaOrden:', error)
    mostrarNotificacion(`Error: ${error.message}`, 'error')

    // Revertir el checkbox visualmente si falló la operación
    if (checkbox) {
      checkbox.checked = !nuevoEstadoIva // Volver al estado anterior
    }
  } finally {
    // UI: Ocultar carga y desbloquear
    if (loadingIcon) loadingIcon.classList.add('hidden')
    if (checkbox) checkbox.disabled = false
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

    cargarArchivosProveedor(fila.dataset.id)

    document.getElementById('pantalla-lista-proveedores').classList.add('hidden')
    document.getElementById('pantalla-editar-proveedor').classList.remove('hidden')
  })
}

async function cargarArchivosProveedor(id) {
  const container = document.getElementById('contenedor-archivos-existentes')
  const lista = document.getElementById('lista-archivos-proveedor')

  if (!container || !lista) return

  try {
    const archivos = await SendDataEnd(`modales/proveedores/archivos/${id}`)

    lista.innerHTML = ''
    if (archivos && archivos.length > 0) {
      container.classList.remove('hidden')
      archivos.forEach((archivo) => {
        const item = document.createElement('div')
        item.className =
          'flex items-center justify-between p-2 bg-gray-50 rounded border border-gray-200 shadow-sm'
        item.innerHTML = `
          <div class="flex items-center space-x-2 truncate mr-2">
            <svg class="w-5 h-5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <span class="text-xs text-gray-700 truncate" title="${archivo.nombre_archivo}">${archivo.nombre_archivo}</span>
          </div>
          <div class="flex items-center space-x-1 shrink-0">
            <a href="${BASE_URL}api/storage/serve?path=proveedores/${archivo.nombre_archivo}" target="_blank" class="p-1 text-blue-600 hover:bg-blue-100 rounded transition" title="Ver archivo">
               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
            </a>
            <button type="button" onclick="eliminarArchivoProveedorJS(${archivo.id_archivo}, ${id})" class="p-1 text-red-600 hover:bg-red-100 rounded transition" title="Eliminar">
               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
          </div>
        `
        lista.appendChild(item)
      })
    } else {
      container.classList.add('hidden')
    }
  } catch (error) {
    console.error('Error al cargar archivos:', error)
  }
}

async function eliminarArchivoProveedorJS(idArchivo, idProveedor) {
  if (!(await Confirmar('Eliminar Archivo', '¿Seguro que deseas eliminar este archivo?'))) return

  try {
    const result = await SendDataEnd(`modales/proveedores/eliminarArchivo/${idArchivo}`, {
      method: 'POST',
    })

    if (result.success) {
      mostrarNotificacion('Archivo eliminado ✅', 'success')
      cargarArchivosProveedor(idProveedor)
    } else {
      mostrarNotificacion(result.message || 'Error al eliminar archivo ❌', 'error')
    }
  } catch (error) {
    mostrarNotificacion('Error de conexión ❌', 'error')
  }
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
          const archivos = data.Archivo.split(',')
          html += `<div class="mt-6"><h4 class="text-md font-bold mb-2">Archivos Adjuntos</h4>`
          archivos.forEach((archivo, index) => {
            const archivoUrl = `${BASE_URL}solicitudes/archivo/${idSolicitud}/${archivo}`
            html += `<div><a href="${archivoUrl}" target="_blank" class="text-blue-600 hover:underline">Archivo ${index + 1}: ${archivo}</a></div>`
          })
          html += `</div>`
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
    },
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
      document.getElementById('editar-Nombre_Comercial').value = fila.querySelector('.nombre-comercial').textContent.trim()
      document.getElementById('editar-RFC').value = fila.querySelector('.rfc').textContent
      document.getElementById('editar-Direccion').value = fila.querySelector('.direccion').textContent.trim()

      document.getElementById('pantalla-lista-razonsocial').classList.add('hidden')
      document.getElementById('pantalla-editar-razonsocial').classList.remove('hidden')
    }

    // --- ELIMINAR ---
    if (btn.classList.contains('btn-eliminar')) {
      if (
        !(await Confirmar('Eliminar Razón Social', '¿Seguro que deseas eliminar este registro?'))
      ) {
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
      const nombreFiltro = (
        document.getElementById('buscar-nombre-depto')?.value || ''
      ).toLowerCase()
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
          const selectLugar = document.getElementById('editar-ID_Place')
          const lugarTexto = selectLugar.options[selectLugar.selectedIndex].text
          fila.querySelector('.lugar-depto').textContent = lugarTexto

          // Unidad Operativa
          const selectUnidad = document.getElementById('editar-ID_UnidadOperativa')
          const unidadTexto = selectUnidad.options[selectUnidad.selectedIndex].text
          fila.querySelector('.unidad-depto').textContent = selectUnidad.value ? unidadTexto : 'N/A'

          fila.dataset.nombre = formData.get('Nombre')
          fila.dataset.idPlace = formData.get('ID_Place')
          fila.dataset.nombrePlace = lugarTexto
          fila.dataset.idUnidad = formData.get('ID_UnidadOperativa')
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
    document.getElementById('editar-ID_UnidadOperativa').value = fila.dataset.idUnidad || ''

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
      const razonSocialFiltro = (
        document.getElementById('buscar-razonsocial-cuenta')?.value || ''
      ).toLowerCase()
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
  const vistaTabla = document.getElementById('vista-tabla-cuentas-detalle')
  const vistaForm = document.getElementById('vista-form-nueva-cuenta')
  const tablaDetalle = document.getElementById('tabla-cuentas-detalle')

  const btnAgregar = document.getElementById('btn-agregar-cuenta-detalle')
  const btnCancelar = document.getElementById('btn-cancelar-nueva-cuenta')
  const btnConfirmar = document.getElementById('btn-confirmar-nueva-cuenta')

  const inputCuenta = document.getElementById('nueva-cuenta-input')
  const inputIdRef = document.getElementById('editar-ID_Ref')
  const inputIdEdicion = document.getElementById('id_cuenta_edicion')
  const tituloForm = document.getElementById('titulo-form-cuenta')

  // 1. Botón "AGREGAR"
  if (btnAgregar) {
    btnAgregar.onclick = () => {
      if (inputCuenta) inputCuenta.value = ''
      if (inputIdEdicion) inputIdEdicion.value = ''
      if (tituloForm) tituloForm.textContent = 'Nueva Cuenta'

      vistaTabla.classList.add('hidden')
      vistaForm.classList.remove('hidden')
      if (inputCuenta) inputCuenta.focus()
    }
  }

  // Eventos de eliminar y editar
  if (tablaDetalle) {
    tablaDetalle.addEventListener('click', async (e) => {
      // Botón "EDITAR"
      const btnEditar = e.target.closest('.btn-editar-detalle')
      if (btnEditar) {
        e.preventDefault()
        const idCuenta = btnEditar.dataset.id
        const numeroCuenta = btnEditar.dataset.cuenta

        if (inputIdEdicion) inputIdEdicion.value = idCuenta
        if (inputCuenta) inputCuenta.value = numeroCuenta
        if (tituloForm) tituloForm.textContent = 'Editar Cuenta'

        vistaTabla.classList.add('hidden')
        vistaForm.classList.remove('hidden')
        if (inputCuenta) inputCuenta.focus()
        return
      }

      // Botón "ELIMINAR"
      const btnEliminar = e.target.closest('.btn-eliminar-detalle')
      if (btnEliminar) {
        e.preventDefault()
        const idCuenta = btnEliminar.dataset.id

        if (
          !(await Confirmar('Eliminar Cuenta', '¿Seguro que deseas eliminar esta cuenta bancaria?'))
        ) {
          return
        }

        try {
          const result = await SendDataEnd(`modales/cuentas/eliminar/${idCuenta}`, {
            method: 'POST',
          })

          if (result.success) {
            mostrarNotificacion('Cuenta eliminada correctamente ✅', 'success')
            // Recargar la tabla usando el ID del proveedor actual
            cargarCuentasDeProveedor(inputIdRef.value)
          } else {
            mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
          }
        } catch (error) {
          console.error(error)
          mostrarNotificacion('Error de conexión al eliminar.', 'error')
        }
      }
    })
  }

  // Botón "Cancelar"
  if (btnCancelar) {
    btnCancelar.onclick = () => {
      vistaForm.classList.add('hidden')
      vistaTabla.classList.remove('hidden')
    }
  }

  // Botón "Confirmar" (Guardar)
  if (btnConfirmar) {
    btnConfirmar.onclick = async () => {
      const valor = inputCuenta.value.trim()
      const idProveedor = inputIdRef.value
      const idCuenta = inputIdEdicion.value

      if (!valor) {
        mostrarNotificacion('Por favor ingrese una cuenta.', 'warning')
        return
      }
      if (valor.length < 16 || valor.length > 20) {
        mostrarNotificacion('La cuenta debe tener entre 16 y 20 caracteres.', 'warning')
        return
      }

      const formData = new FormData()
      formData.append('Cuenta', valor)
      formData.append('ID_Proveedor', idProveedor)

      const url = idCuenta ? `modales/cuentas/editar/${idCuenta}` : `modales/cuentas/insertar`

      try {
        const result = await SendDataEnd(url, {
          method: 'POST',
          body: formData,
        })

        if (result.success) {
          const mensaje = idCuenta ? 'Cuenta actualizada ✅' : 'Cuenta agregada ✅'
          mostrarNotificacion(mensaje, 'success')

          inputCuenta.value = ''
          if (inputIdEdicion) inputIdEdicion.value = ''

          vistaForm.classList.add('hidden')
          vistaTabla.classList.remove('hidden')

          cargarCuentasDeProveedor(idProveedor)
        } else {
          mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
        }
      } catch (error) {
        console.error(error)
        mostrarNotificacion('Error de conexión al guardar.', 'error')
      }
    }
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

    const idProveedor = fila.dataset.id
    document.getElementById('editar-ID_Ref').value = idProveedor
    document.getElementById('editar-RazonSocial').value = fila.dataset.razonsocial || ''
    document.getElementById('editar-RFC').value = fila.dataset.rfc || ''

    // Resetear vistas internas: Siempre mostrar tabla primero al entrar
    document.getElementById('vista-tabla-cuentas-detalle').classList.remove('hidden')
    document.getElementById('vista-form-nueva-cuenta').classList.add('hidden')

    cargarCuentasDeProveedor(idProveedor)

    document.getElementById('pantalla-lista-cuentas').classList.add('hidden')
    document.getElementById('pantalla-editar-cuenta').classList.remove('hidden')
  })
}

async function cargarCuentasDeProveedor(idProveedor) {
  const tbody = document.getElementById('tabla-cuentas-detalle')
  tbody.innerHTML =
    '<tr><td colspan="2" class="px-4 py-3 text-center text-gray-500 text-sm">Cargando cuentas...</td></tr>'

  try {
    const cuentas = await SendDataEnd(`modales/cuentas/proveedor/${idProveedor}`, { method: 'GET' })

    if (cuentas && cuentas.length > 0) {
      let html = ''
      cuentas.forEach((c) => {
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
                `
      })
      tbody.innerHTML = html
    } else {
      tbody.innerHTML =
        '<tr><td colspan="2" class="px-4 py-3 text-center text-gray-500 text-sm">No hay cuentas registradas para este proveedor.</td></tr>'
    }
  } catch (error) {
    console.error('Error al cargar cuentas:', error)
    tbody.innerHTML =
      '<tr><td colspan="2" class="px-4 py-3 text-center text-red-500 text-sm">Error al cargar datos.</td></tr>'
  }
}

let choicesUnidadAdd = null;
let choicesUnidadEdit = null;
let choicesLugarFiltro = null;
let choicesUnidadFiltro = null;

let choicesPlaceAdd = null;
let choicesPlaceEdit = null;
let choicesLugarUnidadFiltro = null;

// Objeto para persistir filtros de Partidas Presupuestales
let filtrosPersistidosGrupos = {
  nombre: '',
  lugares: [],
  unidades: []
};

// Objeto para persistir filtros de Departamentos de Operación
let filtrosPersistidosUnidades = {
  nombre: '',
  lugares: []
};

/**
 * Guarda el estado actual de los filtros de Unidades
 */
function guardarFiltrosUnidades() {
  const inputNombre = document.getElementById('buscar-nombre-unidad');
  filtrosPersistidosUnidades = {
    nombre: inputNombre ? inputNombre.value : '',
    lugares: (choicesLugarUnidadFiltro && typeof choicesLugarUnidadFiltro.getValue === 'function') ? choicesLugarUnidadFiltro.getValue(true) : []
  };
}

/**
 * Guarda el estado actual de los filtros de Grupos Presupuestales
 */
function guardarFiltrosGrupos() {
  const inputNombre = document.getElementById('buscar-nombre-grupo');
  filtrosPersistidosGrupos = {
    nombre: inputNombre ? inputNombre.value : '',
    lugares: (choicesLugarFiltro && typeof choicesLugarFiltro.getValue === 'function') ? choicesLugarFiltro.getValue(true) : [],
    unidades: (choicesUnidadFiltro && typeof choicesUnidadFiltro.getValue === 'function') ? choicesUnidadFiltro.getValue(true) : []
  };
}

let choicesRSAdd = null;
let choicesRSEdit = null;
let choicesRSFiltro = null;

// Objeto para persistir filtros de Segmentos de Negocio
let filtrosPersistidosSegmentos = {
  nombre: '',
  razones: []
};

/**
 * Guarda el estado actual de los filtros de Segmentos
 */
function guardarFiltrosSegmentos() {
  const inputNombre = document.getElementById('buscar-nombre-segmento');
  filtrosPersistidosSegmentos = {
    nombre: inputNombre ? inputNombre.value : '',
    razones: (choicesRSFiltro && typeof choicesRSFiltro.getValue === 'function') ? choicesRSFiltro.getValue(true) : []
  };
}

/**
 * Lógica para el CRUD de Segmentos de Negocio (SegmentoNegocio)
 */
function initCrudSegmentos() {
  const tabla = document.getElementById('tabla-segmentos')
  if (!tabla) return

  // Destruir instancias previas
  if (choicesRSAdd) { choicesRSAdd.destroy(); choicesRSAdd = null; }
  if (choicesRSEdit) { choicesRSEdit.destroy(); choicesRSEdit = null; }
  if (choicesRSFiltro) { choicesRSFiltro.destroy(); choicesRSFiltro = null; }

  const selAdd = document.getElementById('id_razon_social');
  const selEdit = document.getElementById('editar-id_razon_social');

  const configSearch = {
    removeItemButton: false,
    itemSelectText: '',
    placeholderValue: 'Seleccionar...',
    searchPlaceholderValue: 'Buscar razón social...',
    fuseOptions: { threshold: 0.2, distance: 100 }
  };

  if (selAdd) choicesRSAdd = new Choices(selAdd, configSearch);
  if (selEdit) choicesRSEdit = new Choices(selEdit, configSearch);

  initSegmentosTabla()
  initSegmentosPantallas()
  initSegmentosForm()
  initSegmentosEditarForm()
  initSegmentosActions(tabla)

  // Restaurar filtros
  const inputNombre = document.getElementById('buscar-nombre-segmento');
  if (inputNombre && filtrosPersistidosSegmentos.nombre) {
    inputNombre.value = filtrosPersistidosSegmentos.nombre;
  }

  setTimeout(() => {
    if (choicesRSFiltro && filtrosPersistidosSegmentos.razones.length > 0) {
      choicesRSFiltro.setChoiceByValue(filtrosPersistidosSegmentos.razones);
    }
    const formFiltros = document.getElementById('form-filtros-segmentos');
    if (formFiltros) {
      formFiltros.dispatchEvent(new Event('input', { bubbles: true }));
      formFiltros.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, 200);
}

function initSegmentosTabla() {
  const refRS = document.getElementById('filtro-rs-segmento');
  if (refRS) {
    choicesRSFiltro = new Choices(refRS, { 
      removeItemButton: true, 
      itemSelectText: '', 
      placeholderValue: 'Todas las Razones Sociales', 
      searchPlaceholderValue: 'Buscar...',
      fuseOptions: { threshold: 0.2, distance: 100 }
    });
  }

  setupClientSideTable({
    rowsSelector: '#tabla-segmentos tr[data-id]',
    paginationSelector: 'paginacion-segmentos',
    filterFormSelector: '#form-filtros-segmentos',
    filterFunction: (row, form) => {
      const nomFiltro = (document.getElementById('buscar-nombre-segmento')?.value || '').toLowerCase();
      const rsSel = choicesRSFiltro ? choicesRSFiltro.getValue(true).map(v => v.toLowerCase()) : [];
      
      const nombreRow = row.querySelector('.nombre-segmento')?.textContent.toLowerCase() || '';
      const rsRow = row.querySelector('.razon-social-segmento')?.textContent.toLowerCase() || '';
      
      const matchNombre = nombreRow.includes(nomFiltro);
      const matchRS = rsSel.length === 0 || rsSel.some(rs => rsRow.includes(rs));

      return matchNombre && matchRS;
    },
    rowsPerPage: 10,
  })
}

function initSegmentosPantallas() {
  const pLis = document.getElementById('pantalla-lista-segmentos'), pAdd = document.getElementById('pantalla-agregar-segmentos'), pEdi = document.getElementById('pantalla-editar-segmentos');
  const btnAdd = document.getElementById('btn-agregar-segmentos');
  if (btnAdd) btnAdd.onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
  document.getElementById('btn-regresar-lista-segmentos').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
  document.getElementById('btn-regresar-lista-editar-segmentos').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };
}

function initSegmentosForm() {
  const fAdd = document.getElementById('form-agregar-segmentos'); if (!fAdd) return;
  fAdd.onsubmit = async (e) => {
    e.preventDefault();
    guardarFiltrosSegmentos();
    try {
      const res = await SendDataEnd('modales/crud_segmentos/insertar', { method: 'POST', body: new FormData(fAdd) });
      if (res.success) { mostrarNotificacion('Segmento agregado ✅', 'success'); abrirModal('SegmentoNegocio'); }
    } catch { mostrarNotificacion('Error ❌', 'error'); }
  };
}

function initSegmentosEditarForm() {
  const fEdi = document.getElementById('form-editar-segmentos'); if (!fEdi) return;
  fEdi.onsubmit = async (e) => {
    e.preventDefault();
    guardarFiltrosSegmentos();
    const fd = new FormData(fEdi);
    try {
      const res = await SendDataEnd(`modales/crud_segmentos/editar/${fd.get('id')}`, { method: 'POST', body: fd });
      if (res.success) { mostrarNotificacion('Segmento actualizado ✅', 'success'); abrirModal('SegmentoNegocio'); }
    } catch { mostrarNotificacion('Error ❌', 'error'); }
  };
}

function initSegmentosActions(tabla) {
  tabla.addEventListener('click', async (e) => {
    const bE = e.target.closest("[id^='btn-editar-segmentos-']"), bD = e.target.closest("[id^='btn-eliminar-segmentos-']");
    if (bE) {
      e.preventDefault(); const f = bE.closest('tr');
      document.getElementById('editar-id').value = f.dataset.id;
      document.getElementById('editar-nombre').value = f.dataset.nombre;
      if (choicesRSEdit && f.dataset.idRs) {
        choicesRSEdit.setChoiceByValue(String(f.dataset.idRs));
      }
      document.getElementById('editar-descripcion').value = f.dataset.descripcion;
      document.getElementById('pantalla-lista-segmentos').classList.add('hidden');
      document.getElementById('pantalla-editar-segmentos').classList.remove('hidden');
    }
    if (bD) {
      e.preventDefault();
      if (!confirm('¿Estás seguro de eliminar este segmento?')) return;
      guardarFiltrosSegmentos();
      try {
        const res = await SendDataEnd(`modales/crud_segmentos/eliminar/${bD.dataset.id}`, { method: 'POST' });
        if (res.success) { mostrarNotificacion('Eliminado ✅', 'success'); abrirModal('SegmentoNegocio'); }
      } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
  });
}

/**
 * Lógica para el CRUD de Departamentos de Operación (UnidadOperativa)
 */
function initCrudUnidades() {
  const tabla = document.getElementById('tabla-unidades')
  if (!tabla) return

  // Destruir instancias previas
  if (choicesPlaceAdd) { choicesPlaceAdd.destroy(); choicesPlaceAdd = null; }
  if (choicesPlaceEdit) { choicesPlaceEdit.destroy(); choicesPlaceEdit = null; }
  if (choicesLugarUnidadFiltro) { choicesLugarUnidadFiltro.destroy(); choicesLugarUnidadFiltro = null; }

  const selAdd = document.getElementById('ID_Place');
  const selEdit = document.getElementById('editar-ID_Place-unidad');

  const configSearch = {
    removeItemButton: false,
    itemSelectText: '',
    placeholderValue: 'Seleccionar...',
    searchPlaceholderValue: 'Buscar lugar...',
    fuseOptions: { threshold: 0.2, distance: 100 }
  };

  if (selAdd) choicesPlaceAdd = new Choices(selAdd, configSearch);
  if (selEdit) choicesPlaceEdit = new Choices(selEdit, configSearch);

  initUnidadesTabla()
  initUnidadesPantallas()
  initUnidadesForm()
  initUnidadesEditarForm()
  initUnidadesActions(tabla)

  // Restaurar filtros
  const inputNombre = document.getElementById('buscar-nombre-unidad');
  if (inputNombre && filtrosPersistidosUnidades.nombre) {
    inputNombre.value = filtrosPersistidosUnidades.nombre;
  }

  setTimeout(() => {
    if (choicesLugarUnidadFiltro && filtrosPersistidosUnidades.lugares.length > 0) {
      choicesLugarUnidadFiltro.setChoiceByValue(filtrosPersistidosUnidades.lugares);
    }
    const formFiltros = document.getElementById('form-filtros-unidades');
    if (formFiltros) {
      formFiltros.dispatchEvent(new Event('input', { bubbles: true }));
      formFiltros.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, 200);
}

function initUnidadesTabla() {
  const refLugar = document.getElementById('filtro-lugar-unidad');
  if (refLugar) {
    choicesLugarUnidadFiltro = new Choices(refLugar, { 
      removeItemButton: true, 
      itemSelectText: '', 
      placeholderValue: 'Todos los complejos', 
      searchPlaceholderValue: 'Buscar...',
      fuseOptions: { threshold: 0.2, distance: 100 }
    });
  }

  setupClientSideTable({
    rowsSelector: '#tabla-unidades tr[data-id]',
    paginationSelector: 'paginacion-unidades',
    filterFormSelector: '#form-filtros-unidades',
    filterFunction: (row, form) => {
      const nomFiltro = (document.getElementById('buscar-nombre-unidad')?.value || '').toLowerCase();
      const lugaresSel = choicesLugarUnidadFiltro ? choicesLugarUnidadFiltro.getValue(true).map(v => v.toLowerCase()) : [];
      
      const nombreRow = row.querySelector('.nombre-unidad')?.textContent.toLowerCase() || '';
      const lugarRow = row.querySelector('.lugar-unidad')?.textContent.toLowerCase() || '';
      
      const matchNombre = nombreRow.includes(nomFiltro);
      const matchLugar = lugaresSel.length === 0 || lugaresSel.some(l => lugarRow.includes(l));

      return matchNombre && matchLugar;
    },
    rowsPerPage: 10,
  })
}

function initUnidadesPantallas() {
  const pLis = document.getElementById('pantalla-lista-unidades'), pAdd = document.getElementById('pantalla-agregar-unidad'), pEdi = document.getElementById('pantalla-editar-unidad');
  const btnAdd = document.getElementById('btn-agregar-unidad');
  if (btnAdd) btnAdd.onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
  document.getElementById('btn-regresar-lista-unidad').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
  document.getElementById('btn-regresar-lista-editar-unidad').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };
}

function initUnidadesForm() {
  const fAdd = document.getElementById('form-agregar-unidad'); if (!fAdd) return;
  fAdd.onsubmit = async (e) => {
    e.preventDefault();
    guardarFiltrosUnidades();
    try {
      const res = await SendDataEnd('modales/crud_unidades_operativas/insertar', { method: 'POST', body: new FormData(fAdd) });
      if (res.success) { mostrarNotificacion('Departamento agregado ✅', 'success'); abrirModal('UnidadOperativa'); }
    } catch { mostrarNotificacion('Error ❌', 'error'); }
  };
}

function initUnidadesEditarForm() {
  const fEdi = document.getElementById('form-editar-unidad'); if (!fEdi) return;
  fEdi.onsubmit = async (e) => {
    e.preventDefault();
    guardarFiltrosUnidades();
    const fd = new FormData(fEdi);
    try {
      const res = await SendDataEnd(`modales/crud_unidades_operativas/editar/${fd.get('ID_UnidadOperativa')}`, { method: 'POST', body: fd });
      if (res.success) { mostrarNotificacion('Unidad actualizada ✅', 'success'); abrirModal('UnidadOperativa'); }
    } catch { mostrarNotificacion('Error ❌', 'error'); }
  };
}

function initUnidadesActions(tabla) {
  tabla.addEventListener('click', async (e) => {
    const bE = e.target.closest("[id^='btn-editar-unidad-']"), bD = e.target.closest("[id^='btn-eliminar-unidad-']");
    if (bE) {
      e.preventDefault(); const f = bE.closest('tr');
      document.getElementById('editar-ID_UnidadOperativa').value = f.dataset.id;
      document.getElementById('editar-Nombre-unidad').value = f.dataset.nombre;
      if (choicesPlaceEdit && f.dataset.idPlace) {
        choicesPlaceEdit.setChoiceByValue(String(f.dataset.idPlace));
      }
      document.getElementById('editar-activo-unidad').checked = f.dataset.activo == '1';
      document.getElementById('pantalla-lista-unidades').classList.add('hidden');
      document.getElementById('pantalla-editar-unidad').classList.remove('hidden');
    }
    if (bD) {
      e.preventDefault();
      if (!confirm('¿Estás seguro de desactivar este departamento de operación?')) return;
      guardarFiltrosUnidades();
      try {
        const res = await SendDataEnd(`modales/crud_unidades_operativas/eliminar/${bD.dataset.id}`, { method: 'POST' });
        if (res.success) { mostrarNotificacion('Desactivado ✅', 'success'); abrirModal('UnidadOperativa'); }
      } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
  });
}

/**
 * Lógica para el CRUD de cuentas de Grupos Presupuestales
 */
function initCrudGrupos() {
  const tabla = document.getElementById('tabla-grupos')
  if (!tabla) return

  // Destruir instancias previas si existen para evitar duplicados
  if (choicesUnidadAdd) { choicesUnidadAdd.destroy(); choicesUnidadAdd = null; }
  if (choicesUnidadEdit) { choicesUnidadEdit.destroy(); choicesUnidadEdit = null; }
  if (choicesLugarFiltro) { choicesLugarFiltro.destroy(); choicesLugarFiltro = null; }
  if (choicesUnidadFiltro) { choicesUnidadFiltro.destroy(); choicesUnidadFiltro = null; }

  const selAdd = document.getElementById('ID_UnidadOperativa');
  const selEdit = document.getElementById('editar-ID_UnidadOperativa');

  const configSearch = {
    removeItemButton: false,
    itemSelectText: '',
    placeholderValue: 'Seleccionar...',
    searchPlaceholderValue: 'Buscar departamento...',
    fuseOptions: { threshold: 0.2, distance: 100 }
  };

  if (selAdd) choicesUnidadAdd = new Choices(selAdd, configSearch);
  if (selEdit) choicesUnidadEdit = new Choices(selEdit, configSearch);

  initGruposTabla()
  initGruposPantallas()
  initGruposForm()
  initGruposEditarForm()
  initGruposActions(tabla)

  // Restaurar filtros si existen
  const inputNombre = document.getElementById('buscar-nombre-grupo');

  if (inputNombre && filtrosPersistidosGrupos.nombre) {
    inputNombre.value = filtrosPersistidosGrupos.nombre;
  }

  // Esperar un momento a que las instancias de Choices se inicialicen
  setTimeout(() => {
    if (choicesLugarFiltro && filtrosPersistidosGrupos.lugares.length > 0) {
      choicesLugarFiltro.setChoiceByValue(filtrosPersistidosGrupos.lugares);
    }
    if (choicesUnidadFiltro && filtrosPersistidosGrupos.unidades.length > 0) {
      choicesUnidadFiltro.setChoiceByValue(filtrosPersistidosGrupos.unidades);
    }

    // Forzar la actualización de la tabla disparando 'input' y 'change' en el formulario de filtros
    const formFiltros = document.getElementById('form-filtros-grupos');
    if (formFiltros) {
      formFiltros.dispatchEvent(new Event('input', { bubbles: true }));
      formFiltros.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }, 200);
}

function initGruposTabla() {
  const container = document.getElementById('pantalla-lista-grupos');
  if (!container) return;

  const unidadesData = JSON.parse(container.dataset.unidadesJson || '[]');

  // Inicializar Choices para los filtros
  const refLugar = document.getElementById('filtro-lugar-grupo');
  const refUnidad = document.getElementById('filtro-unidad-grupo');

  if (refLugar) {
    choicesLugarFiltro = new Choices(refLugar, { 
      removeItemButton: true, 
      itemSelectText: '', 
      placeholderValue: 'Todos los complejos', 
      searchPlaceholderValue: 'Buscar...',
      fuseOptions: { threshold: 0.2, distance: 100 }
    });
  }
  if (refUnidad) {
    choicesUnidadFiltro = new Choices(refUnidad, { 
      removeItemButton: true, 
      itemSelectText: '', 
      placeholderValue: 'Todos los departamentos', 
      searchPlaceholderValue: 'Buscar...',
      fuseOptions: { threshold: 0.2, distance: 100 }
    });
  }

  // Lógica de Sincronización de Filtros
  if (refLugar && refUnidad) {
    refLugar.addEventListener('change', () => {
      const lugaresSeleccionados = choicesLugarFiltro.getValue(true);
      let deptosFiltrados = [];
      if (lugaresSeleccionados.length === 0) {
        deptosFiltrados = [...new Set(unidadesData.map(u => u.Nombre))];
      } else {
        deptosFiltrados = [...new Set(
          unidadesData
            .filter(u => lugaresSeleccionados.includes(u.PlaceNombre))
            .map(u => u.Nombre)
        )];
      }
      choicesUnidadFiltro.clearStore();
      const nuevasOpciones = deptosFiltrados.sort().map(nombre => ({
        value: nombre,
        label: nombre,
        selected: false,
        disabled: false
      }));
      choicesUnidadFiltro.setChoices(nuevasOpciones, 'value', 'label', true);
      refUnidad.dispatchEvent(new Event('change'));
    });
  }

  setupClientSideTable({
    rowsSelector: '#tabla-grupos tr[data-id]',
    paginationSelector: 'paginacion-grupos',
    filterFormSelector: '#form-filtros-grupos',
    filterFunction: (row, form) => {
      const nomFiltro = (document.getElementById('buscar-nombre-grupo')?.value || '').toLowerCase();
      const lugaresSel = choicesLugarFiltro ? choicesLugarFiltro.getValue(true).map(v => v.toLowerCase()) : [];
      const unidadesSel = choicesUnidadFiltro ? choicesUnidadFiltro.getValue(true).map(v => v.toLowerCase()) : [];
      
      const textoCelda = row.querySelector('.unidad-grupo')?.textContent.toLowerCase() || '';
      const nombreRow = row.querySelector('.nombre-grupo')?.textContent.toLowerCase() || '';
      
      const matchNombre = nombreRow.includes(nomFiltro);
      const matchLugar = lugaresSel.length === 0 || lugaresSel.some(l => textoCelda.includes(`(${l})`));
      
      const nombreUnidadEnCelda = textoCelda.split(' (')[0];
      const matchUnidad = unidadesSel.length === 0 || unidadesSel.some(u => nombreUnidadEnCelda.includes(u));

      return matchNombre && matchLugar && matchUnidad;
    },
    rowsPerPage: 10,
  })
}

function initGruposPantallas() {
  const pantallaAgregar = document.getElementById('pantalla-agregar-grupos')
  const pantallaEditar = document.getElementById('pantalla-editar-grupos')
  const pantallaLista = document.getElementById('pantalla-lista-grupos')

  const btnAgregar = document.getElementById('btn-agregar-grupos')
  const btnRegresarAgregar = document.getElementById('btn-regresar-lista-grupos')
  const btnRegresarEditar = document.getElementById('btn-regresar-lista-editar-grupos')

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

function initGruposForm() {
  const formAgregar = document.getElementById('form-agregar-grupos')
  const pantallaAgregar = document.getElementById('pantalla-agregar-grupos')
  const pantallaLista = document.getElementById('pantalla-lista-grupos')
  if (!formAgregar) return

  formAgregar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formAgregar)

    try {
      const result = await SendDataEnd('modales/crud_grupos_presupuestales/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Grupo agregado correctamente ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formAgregar.reset()
        
        // Guardar filtros antes de recargar
        guardarFiltrosGrupos();
        // Recargamos el modal para ver los cambios
        abrirModal('GrupoPresupuestal')
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

function initGruposEditarForm() {
  const formEditar = document.getElementById('form-editar-grupos')
  const pantallaEditar = document.getElementById('pantalla-editar-grupos')
  const pantallaLista = document.getElementById('pantalla-lista-grupos')
  const tabla = document.getElementById('tabla-grupos')
  if (!formEditar) return

  formEditar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formEditar)
    const id = formData.get('ID_GrupoPresupuestal')

    try {
      const result = await SendDataEnd(`modales/crud_grupos_presupuestales/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Grupo actualizado correctamente ✅', 'success')
        
        // Guardar filtros antes de recargar
        guardarFiltrosGrupos();
        abrirModal('GrupoPresupuestal');

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

function initGruposActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    // --- ELIMINAR ---
    const btnEliminar = e.target.closest("[id^='btn-eliminar-grupos-']")
    if (btnEliminar) {
      e.preventDefault()
      const id = btnEliminar.dataset.id

      if (
        !(await Confirmar(
          'Eliminar Grupo?',
          '¿Seguro que deseas eliminar esta partida presupuestal?',
        ))
      )
        return

      SendDataEnd(`modales/crud_grupos_presupuestales/eliminar/${id}`, {
        method: 'POST',
      })
        .then((result) => {
          if (result.success) {
            mostrarNotificacion('Grupo eliminado ✅', 'success')
            // Guardar filtros antes de recargar
            guardarFiltrosGrupos();
            abrirModal('GrupoPresupuestal')
          } else {
            mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
          }
        })
        .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
      return
    }

    // --- EDITAR ---
    const btnEditar = e.target.closest("[id^='btn-editar-grupos-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    document.getElementById('editar-ID_GrupoPresupuestal').value = fila.dataset.id
    document.getElementById('editar-Nombre').value = fila.dataset.nombre
    document.getElementById('editar-Descripcion').value = fila.dataset.descripcion

    // Checkbox de manual
    const checkManual = document.getElementById('editar-es_manual')
    if (checkManual) checkManual.checked = fila.dataset.esManual === '1'

    // Checkbox de activo
    const checkActivo = document.getElementById('editar-activo')
    if (checkActivo) checkActivo.checked = fila.dataset.activo === '1'

    // Sincronizar el selector Choices para la edición
    if (choicesUnidadEdit && fila.dataset.idUnidad) {
      choicesUnidadEdit.setChoiceByValue(String(fila.dataset.idUnidad));
    }

    document.getElementById('pantalla-lista-grupos').classList.add('hidden')
    document.getElementById('pantalla-editar-grupos').classList.remove('hidden')
  })
}

/**
 * Lógica para el CRUD de cuentas Bancos Dpto
 */
function initCrudBancoDpto() {
  const tabla = document.getElementById('tabla-banco-dpto')
  if (!tabla) return

  initBancoDptoTabla()
  initBancoDptoPantallas()
  initBancoDptoForm()
  initBancoDptoEditarForm()
  initBancoDptoActions(tabla)
}

function initBancoDptoTabla() {
  setupClientSideTable({
    rowsSelector: '#tabla-banco-dpto tr[data-id]',
    paginationSelector: 'paginacion-banco-dpto',
    filterFormSelector: '#form-filtros-banco-dpto',
    filterFunction: (row, form) => {
      const dptoFiltro = (document.getElementById('buscar-dpto')?.value || '').toLowerCase()
      const bancoFiltro = (document.getElementById('buscar-banco')?.value || '').toLowerCase()

      const dpto = row.querySelector('.nombre-dpto')?.textContent.toLowerCase() || ''
      const banco = row.querySelector('.nombre-banco')?.textContent.toLowerCase() || ''

      return dpto.includes(dptoFiltro) && banco.includes(bancoFiltro)
    },
    rowsPerPage: 10,
  })
}

function initBancoDptoPantallas() {
  const pantallaAgregar = document.getElementById('pantalla-agregar-banco-dpto')
  const pantallaEditar = document.getElementById('pantalla-editar-banco-dpto')
  const pantallaLista = document.getElementById('pantalla-lista-banco-dpto')

  const btnAgregar = document.getElementById('btn-agregar-banco-dpto')
  const btnRegresarAgregar = document.getElementById('btn-regresar-lista-banco-dpto')
  const btnRegresarEditar = document.getElementById('btn-regresar-lista-editar-banco-dpto')

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

function initBancoDptoForm() {
  const formAgregar = document.getElementById('form-agregar-banco-dpto')
  const pantallaAgregar = document.getElementById('pantalla-agregar-banco-dpto')
  const pantallaLista = document.getElementById('pantalla-lista-banco-dpto')
  if (!formAgregar) return

  formAgregar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formAgregar)

    try {
      const result = await SendDataEnd('modales/crud_banco_dpto/insertar', {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Banco agregado correctamente ✅', 'success')
        pantallaAgregar?.classList.add('hidden')
        pantallaLista?.classList.remove('hidden')
        formAgregar.reset()
        // Recargamos el modal para ver los cambios
        abrirModal('BancoDpto')
      } else {
        mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
      }
    } catch {
      mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
    }
  }
}

function initBancoDptoEditarForm() {
  const formEditar = document.getElementById('form-editar-banco-dpto')
  const pantallaEditar = document.getElementById('pantalla-editar-banco-dpto')
  const pantallaLista = document.getElementById('pantalla-lista-banco-dpto')
  const tabla = document.getElementById('tabla-banco-dpto')
  if (!formEditar) return

  formEditar.onsubmit = async (e) => {
    e.preventDefault()
    const formData = new FormData(formEditar)
    const id = formData.get('ID_BancoDpto')

    try {
      const result = await SendDataEnd(`modales/crud_banco_dpto/editar/${id}`, {
        method: 'POST',
        body: formData,
      })

      if (result.success) {
        mostrarNotificacion('Banco actualizado correctamente ✅', 'success')

        const fila = tabla.querySelector(`tr[data-id='${id}']`)
        if (fila) {
          // Actualizar textos visuales
          fila.querySelector('.nombre-banco').textContent = formData.get('Banco')
          fila.querySelector('.clabe-banco').textContent = formData.get('Clabe')
          fila.querySelector('.alias-banco').textContent = formData.get('Alias') || '-'
          fila.querySelector('.cuenta-banco').textContent = formData.get('Cuenta') || '-'

          // Actualizar Nombre RS visualmente desde el select
          const selectRS = document.getElementById('editar-ID_RazonSocial')
          const rsTexto = selectRS.options[selectRS.selectedIndex].text
          fila.querySelector('.nombre-rs').textContent = rsTexto

          // Actualizar Datasets
          fila.dataset.banco = formData.get('Banco')
          fila.dataset.clabe = formData.get('Clabe')
          fila.dataset.alias = formData.get('Alias')
          fila.dataset.cuenta = formData.get('Cuenta')
          fila.dataset.sucursal = formData.get('Sucursal')
          fila.dataset.idRs = formData.get('ID_RazonSocial')
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

function initBancoDptoActions(tabla) {
  if (!tabla) return

  tabla.addEventListener('click', async (e) => {
    // --- ELIMINAR ---
    const btnEliminar = e.target.closest("[id^='btn-eliminar-banco-dpto-']")
    if (btnEliminar) {
      e.preventDefault()
      const id = btnEliminar.dataset.id

      if (!(await Confirmar('Eliminar Banco?', '¿Seguro que deseas eliminar este registro?')))
        return

      SendDataEnd(`modales/crud_banco_dpto/eliminar/${id}`, {
        method: 'POST',
      })
        .then((result) => {
          if (result.success) {
            mostrarNotificacion('Registro eliminado ✅', 'success')
            btnEliminar.closest('tr')?.remove()
          } else {
            mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
          }
        })
        .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
      return
    }

    // --- EDITAR ---
    const btnEditar = e.target.closest("[id^='btn-editar-banco-dpto-']")
    if (!btnEditar) return
    e.preventDefault()

    const fila = btnEditar.closest('tr')
    if (!fila) return

    // Cargar datos al formulario
    document.getElementById('editar-ID_BancoDpto').value = fila.dataset.id
    document.getElementById('editar-Alias').value = fila.dataset.alias || ''
    document.getElementById('editar-Banco').value = fila.dataset.banco
    document.getElementById('editar-Cuenta').value = fila.dataset.cuenta || ''
    document.getElementById('editar-Sucursal').value = fila.dataset.sucursal || ''
    document.getElementById('editar-Clabe').value = fila.dataset.clabe
    document.getElementById('editar-ID_RazonSocial').value = fila.dataset.idRs

    // Cambiar de pantalla
    document.getElementById('pantalla-lista-banco-dpto').classList.add('hidden')
    document.getElementById('pantalla-editar-banco-dpto').classList.remove('hidden')
  })
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

/**
 * Función Global para Cancelar/Rechazar Solicitudes
 * @param {number} idSolicitud - El ID de la base de datos
 * @param {function} callback - Función a ejecutar tras el éxito (opcional)
 */
window.globalCancelarSolicitud = async function (idSolicitud, callbackExito) {
  const title = 'Rechazar/Cancelar Solicitud'
  const message = 'Por favor, ingrese el motivo (obligatorio):'

  // Tu función existente de prompt
  const comentarios = await InputPrompt(title, message, true)

  if (!comentarios) return // Si cancela o está vacío

  const payload = {
    ID_Solicitud: idSolicitud,
    ComentariosAdmin: comentarios,
  }

  const notif = mostrarNotificacion('Procesando solicitud...', 'info', 9999)

  try {
    // Aquí usamos la ruta de la API que modificamos en el paso anterior
    const result = await SendDataEnd('api/solicitud/cancelar', {
      method: 'POST',
      body: payload,
    })

    if (notif.click) notif.click()

    if (result.success) {
      mostrarNotificacion(result.message, 'success')
      if (callbackExito) callbackExito() // Aquí recargamos la vista actual
    } else {
      mostrarNotificacion(result.message || 'Error al procesar', 'error')
    }
  } catch (error) {
    if (notif.click) notif.click()
    mostrarNotificacion('Error de conexión', 'error')
  }
}
