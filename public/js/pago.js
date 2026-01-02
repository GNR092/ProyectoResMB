function Pagos() {
  return {
    screen: 'menu',
    previousScreen: 'menu',
    ordenesContado: [],
    ordenesCredito: [],
    detalleOrden: null,
    selectedOrdenes: [],
    loading: true,
    loadingDetalle: false,

    async cargardatos() {
      this.loading = true
      this.selectedOrdenes = []
      try {
        const ordenes = await SendDataEnd('api/orden-compra/alldata')
        if (!ordenes || ordenes.length === 0) {
          this.ordenesContado = []
          this.ordenesCredito = []
          return
        }

        const uniqueIds = [...new Set(ordenes.map((o) => o.ID_Solicitud))]

        const detallesPromises = uniqueIds.map((id) =>
          SendDataEnd(`api/orden-compra/details/${id}`),
        )
        const detalles = await Promise.all(detallesPromises)

        if (!detalles) return

        this.ordenesContado = detalles.filter(
          (det) => det && det.EstadoOrden === 'Espera_Programacion' && det.MetodoPago == '0',
        )
        this.ordenesCredito = detalles.filter(
          (det) => det && det.EstadoOrden === 'Espera_Programacion' && det.MetodoPago == '1',
        )

        this.ordenesCredito.sort((a, b) => 0)
      } catch (error) {
        console.error('Error al cargar las órdenes:', error)
        this.ordenesContado = []
        this.ordenesCredito = []
      } finally {
        this.loading = false
      }
    },

    toggleSelectAll(event, type) {
      const list = type === 'contado' ? this.ordenesContado : this.ordenesCredito
      const ids = list.map((o) => o.ID_Solicitud)

      if (event.target.checked) {
        this.selectedOrdenes = [...new Set([...this.selectedOrdenes, ...ids])]
      } else {
        this.selectedOrdenes = this.selectedOrdenes.filter((id) => !ids.includes(id))
      }
    },

    async programarPago() {
      if (this.selectedOrdenes.length === 0) {
        mostrarNotificacion('Debe seleccionar al menos una orden para programar.', 'warning')
        return
      }

      const confirmacion = await Confirmar(
        'Programar Pagos',
        `¿Está seguro de que desea programar ${this.selectedOrdenes.length} pago(s)?`,
      )

      if (!confirmacion) return

      try {
        const result = await SendDataEnd('api/orden/programar-pagos', {
          method: 'POST',
          body: { ids: this.selectedOrdenes },
        })

        if (result.success) {
          mostrarNotificacion(result.message, 'success')
          await this.cargardatos() // Recargar los datos para refrescar las listas
        } else {
          mostrarNotificacion(result.message || 'No se pudieron programar los pagos.', 'error')
        }
      } catch (error) {
        console.error('Error al programar pagos:', error)
        mostrarNotificacion('Ocurrió un error de red al intentar programar los pagos.', 'error')
      }
    },

    mostrarDetalle(id, metodoPago) {
      this.loadingDetalle = true
      this.previousScreen = metodoPago == '0' ? 'contado' : 'credito'
      this.screen = 'detalle'

      SendDataEnd(`api/orden-compra/details/${id}`)
        .then((data) => {
          this.detalleOrden = data
          this.loadingDetalle = false
        })
        .catch((error) => {
          console.error('Error al cargar detalle de la orden:', error)
          this.loadingDetalle = false
          this.screen = this.previousScreen
        })
    },

    volverATabla() {
      this.screen = this.previousScreen
      this.detalleOrden = null
    },

    generarDetalleHtml() {
      if (!this.detalleOrden) return ''

      const data = this.detalleOrden
      const prov = data.proveedor || {}
      const totalFormateado = this.formatCurrency(data.cotizacion?.Total)
      const metodoPagoTexto = data.MetodoPago == 0 ? 'Efectivo' : 'Crédito'

      let productosHtml = ''
      if (data.productos && data.productos.length > 0) {
        data.productos.forEach((p) => {
          const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
          productosHtml += `
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>
                            <td class="py-2 px-4 border-t">${p.Nombre}</td>
                            <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                            <td class="py-2 px-4 border-t text-right">${this.formatCurrency(p.Importe)}</td>
                            <td class="py-2 px-4 border-t text-right">${this.formatCurrency(costoTotal)}</td>
                        </tr>
                    `
        })
      } else {
        productosHtml = `<tr><td colspan="5" class="text-center py-3">No hay productos en esta orden.</td></tr>`
      }

      let proveedorCreditoHtml = ''
      if (data.MetodoPago == 1) {
        // Solo mostrar si es de crédito
        proveedorCreditoHtml = `
                    <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
                    <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${this.formatCurrency(prov.Monto_Credito)}</div>
                `
      }

      return `
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
                    ${proveedorCreditoHtml}
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
                        <tbody>${productosHtml}</tbody>
                    </table>
                </div>

                ${GetFiles(data)}
            `
    },

    formatCurrency(value) {
      if (value === null || isNaN(value)) return 'N/A'
      return parseFloat(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
    },
  }
}
function ListaPagos() {
  return {
    pagos: [],
    loading: true,
    filtroMetodoPago: 'todos', // 'todos', '0' (Contado), '1' (Crédito)

    get pagosFiltrados() {
      if (this.filtroMetodoPago === 'todos') {
        return this.pagos
      }
      return this.pagos.filter((p) => p.MetodoPago === this.filtroMetodoPago)
    },

    async init() {
      this.loading = true
      try {
        const listpagos = await SendDataEnd('api/pagos/programados')
        if (!listpagos || listpagos.length === 0) {
          this.pagos = []
          return
        }
        this.pagos = listpagos
      } catch (error) {
        console.error('Error al cargar pagos programados:', error)
        this.pagos = []
      } finally {
        this.loading = false
      }
    },
    formatCurrency(value) {
      if (value === null || isNaN(value)) return 'N/A'
      return parseFloat(value).toLocaleString('es-MX', {
        style: 'currency',
        currency: 'MXN',
      })
    },
    exportarExcel() {
      let url = `${BASE_URL}api/pagos/exportar`
      if (this.filtroMetodoPago !== 'todos') {
        url += `?metodo_pago=${this.filtroMetodoPago}`
      }
      window.location.href = url
    },
  }
}

async function initFichasPago() {
  const tbodyContado = document.getElementById('body-contado')
  const tbodyCredito = document.getElementById('body-credito')

  tbodyContado.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>'
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
      tbodyContado.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros disponibles.</td></tr>'
      tbodyCredito.innerHTML = tbodyContado.innerHTML
      return
    }

    const detallesPromises = ordenes.map((o) =>
      SendDataEnd(`api/orden-compra/details/${o.ID_Solicitud}`).catch((err) => {
        return null // Retorna null para que allSettled lo marque como fulfilled con un valor null
      }),
    )
    // Usar Promise.allSettled para procesar todas las promesas y no detenerse si una falla
    const resultados = await Promise.allSettled(detallesPromises)

    let ordenesContado = []
    let ordenesCredito = []

    // Filtrar y separar solo las solicitudes que se resolvieron correctamente
    resultados.forEach((resultado) => {
      if (resultado.status === 'fulfilled' && resultado.value) {
        const det = resultado.value
        if (det.EstadoOrden !== 'Por Pagar') return 

        if (det.MetodoPago == '0') {
          ordenesContado.push(det)
        } else if (det.MetodoPago == '1') {
          ordenesCredito.push(det)
        }
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
      tbodyContado.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros de contado.</td></tr>'
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
      tbodyCredito.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros a crédito.</td></tr>'
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
    tbodyContado.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-red-500">Error al cargar fichas de pago.</td></tr>'
    tbodyCredito.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-red-500">Error al cargar fichas de pago.</td></tr>'
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
      <div class="flex justify-between mt-6 pt-4 border-t gap-4">
        <button onclick="CerrarOrden(${id}, '${metodoPago}', volverAFichas, initFichasPago)" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded-lg transition w-full">
          Cerrar requisición
        </button>
      </div>
      <div class="mt-4" id="factura-uploader-container"></div> <!-- Nuevo contenedor para el uploader de factura -->
      <div class="flex flex-col gap-4 mt-4">
      <button onclick="CancelarOrdenFicha(${id}, '${metodoPago}', volverATabla, initPagosPendientes)" class="bg-red-500 hover:bg-red-700 text-white font-semibold py-1 px-4 rounded-lg ">
          Cancelar requisición
        </button>
      </div>
    `

    detalleDiv.innerHTML = html
    renderComprobanteUploader(id);
  //  renderFacturaUploader(id); // Llamada para renderizar el uploader de factura

  } catch (error) {
    console.error('Error al cargar detalle:', error);
    detalleDiv.innerHTML = `<p class="text-center text-red-500 py-8">Error al cargar los detalles: ${error.message}</p>`;
  }
}

async function CerrarOrden(idSolicitud, metodoPago, callbackVolver, callbackRefrescar) {
  try {
    const orderDetails = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`);
    
    if (!orderDetails || !orderDetails.OrdenCompra || !orderDetails.OrdenCompra.File_Factura) {
      mostrarNotificacion('No se puede cerrar la requisición. Primero debe subir la factura.', 'warning');
      return;
    }
  } catch (error) {
    console.error('Error al verificar la factura:', error);
    mostrarNotificacion('Error al verificar el estado de la factura. Intente de nuevo.', 'error');
    return;
  }

  const confirmacion = await Confirmar(
    'Cerrar Requisición',
    '¿Está seguro de que desea cerrar esta requisición como "Pagada"? Esta acción no se puede deshacer.'
  );

  if (!confirmacion) {
    return;
  }

  try {
    const apiResult = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: { nuevoEstado: 'Pagada' },
    });

    if (apiResult.success) {
      mostrarNotificacion('Requisición cerrada con éxito.', 'success');
      if (typeof callbackVolver === 'function') {
        callbackVolver(metodoPago);
      }
      if (typeof callbackRefrescar === 'function') {
        callbackRefrescar();
      }
    } else {
      const errorMessage = apiResult.messages?.error || apiResult.message || 'No se pudo cerrar la requisición.';
      mostrarNotificacion(errorMessage, 'error');
    }
  } catch (error) {
    console.error('Error al cerrar la requisición:', error);
    mostrarNotificacion(`Ocurrió un error al intentar cerrar la requisición: ${error.message}`, 'error');
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


/**
 * Lógica para el modal "Lista de pagos pendientes"
 */

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
              <!-- MODIFICADO: Llamada a la nueva función -->
              <button onclick="mostrarDetallePago(${p.ID_Pago || p.ID_Solicitud})" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition">Ver</button>
          </td>
      </tr>
    `,
    noResultsMessage: 'No hay pagos registrados.',
  })
}

function renderComprobanteUploader(idSolicitud) { 
    const container = document.getElementById('comprobante-uploader-container'); 
    if (!container) return;

    container.innerHTML = `
        <div id="file-preview-comprobante" class="hidden mb-4 p-2 border border-dashed rounded-lg"></div> 
        <input type="file" id="archivo-comprobante" class="hidden" accept="image/*,.pdf,.xml" onchange="handleComprobanteFileSelect(this, ${idSolicitud})"> 
        <button id="btn-upload-comprobante" onclick="document.getElementById('archivo-comprobante').click()" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition"> 
            Subir Comprobante
        </button>
    `;
}

function handleComprobanteFileSelect(input, idSolicitud) { 
    const file = input.files[0];
    if (!file) {
        removeComprobanteFile(idSolicitud);
        return;
    }

    const previewContainer = document.getElementById('file-preview-comprobante'); 
    const uploadButton = document.getElementById('btn-upload-comprobante'); 
    
    // Simple file type icon logic
    let icon = '📄'; // default icon
    if (file.type.startsWith('image/')) {
        icon = '🖼️';
    } else if (file.type === 'application/pdf') {
        icon = '📕';
    } else if (file.type === 'text/xml' || file.type === 'application/xml') {
        icon = '🔗';
    }
    
    const fileSize = (file.size / 1024).toFixed(2) + ' KB';

    previewContainer.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-2xl">${icon}</span>
                <div>
                    <p class="text-sm font-medium text-gray-800 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${fileSize}</p>
                </div>
            </div>
            <button onclick="removeComprobanteFile(${idSolicitud})" class="text-red-500 hover:text-red-700 font-bold text-xl">&times;</button> 
        </div>
    `;
    previewContainer.classList.remove('hidden');

    uploadButton.innerText = 'Confirmar y Subir Comprobante'; 
    uploadButton.onclick = () => uploadComprobante(idSolicitud); 
}

function removeComprobanteFile(idSolicitud) { 
    const fileInput = document.getElementById('archivo-comprobante'); 
    if (fileInput) {
        fileInput.value = '';
    }

    const previewContainer = document.getElementById('file-preview-comprobante'); 
    if (previewContainer) {
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
    }
    
    const uploadButton = document.getElementById('btn-upload-comprobante'); 
    if(uploadButton) {
        uploadButton.innerText = 'Subir Comprobante'; 
        uploadButton.onclick = () => document.getElementById('archivo-comprobante').click(); 
    }
}

async function uploadComprobante(idSolicitud) { 
    const fileInput = document.getElementById('archivo-comprobante'); 
    const file = fileInput.files[0];

    if (!file) {
        mostrarNotificacion('No se ha seleccionado ningún archivo.', 'warning');
        return;
    }

    const previewContainer = document.getElementById('file-preview-comprobante'); 
    const uploadButton = document.getElementById('btn-upload-comprobante'); 

    previewContainer.innerHTML = `
        <div class="flex items-center justify-center gap-2">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">Subiendo comprobante...</span> 
        </div>
    `;
    uploadButton.disabled = true;

    const formData = new FormData();
    formData.append('ficha', file); 

    try {
        const result = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
            method: 'POST',
            body: formData,
        });

        if (result.success) {
            mostrarNotificacion('Comprobante subido con éxito.', 'success'); 
            removeComprobanteFile(idSolicitud); 
        } else {
            throw new Error(result.message || 'Error desconocido del servidor.');
        }
    } catch (error) {
        console.error('Error al subir comprobante:', error); 
        mostrarNotificacion(`Error al subir el archivo: ${error.message}`, 'error'); 
        handleComprobanteFileSelect(fileInput, idSolicitud); 
    } finally {
        uploadButton.disabled = false;
    }
}

async function mostrarDetallePago(id) {
  const divLista = document.getElementById('div-lista-pagos');
  const divDetalle = document.getElementById('div-detalle-pago');
  const contenedorDetalle = document.getElementById('contenido-detalle-pago');

  divLista.classList.add('hidden');
  divDetalle.classList.remove('hidden');

  contenedorDetalle.innerHTML = `<p class="text-center text-gray-500 py-8">Cargando detalles...</p>`;

  try {
    // Peticion a la API
    const data = await SendDataEnd(`api/orden-compra/details/${id}`);

    if (!data) {
      throw new Error("No se recibieron datos del servidor.");
    }

    // Preparar datos
    const prov = data.proveedor || {};
    const totalFormateado = parseFloat(data.cotizacion?.Total || 0).toLocaleString('es-MX', {
      style: 'currency',
      currency: 'MXN',
    });

    const metodoPagoTexto =
        data.MetodoPago == 0 ? 'Efectivo' :
            data.MetodoPago == 1 ? 'Crédito' :
                data.MetodoPago == 9 ? 'En Espera' : 'N/A';

    let html = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
                <div><strong>Fecha de solicitud:</strong> ${data.Fecha || 'N/A'}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre || 'N/A'}</div>
                <div><strong>Proyecto:</strong> ${data.Complejo || 'N/A'}</div>
                <div><strong>Importe total:</strong> <span class="font-bold">${totalFormateado}</span></div>
                <div><strong>Método de pago:</strong> ${metodoPagoTexto}</div>
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
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
            ? parseFloat(prov.Monto_Credito).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
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
                    <tbody class="bg-white">
        `;

    if (data.productos && data.productos.length > 0) {
      data.productos.forEach((p) => {
        const costoTotal = (p.Cantidad * p.Importe).toFixed(2);
        html += `
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>
                        <td class="py-2 px-4 border-t">${p.Nombre}</td>
                        <td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>
                        <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                        <td class="py-2 px-4 border-t text-right">$${costoTotal}</td>
                    </tr>
                `;
      });
    } else {
      html += `<tr><td colspan="5" class="text-center py-3 text-gray-500">No hay productos en esta orden.</td></tr>`;
    }

    html += `
                    </tbody>
                </table>
            </div>
        `;

    // Archivos Adjuntos
    if (typeof GetFiles === 'function') {
      html += GetFiles(data);
    } else if (data.Archivo) {
      const archivoUrl = `${BASE_URL}solicitudes/archivo/${id}`;
      html += `
                <div class="mt-6 mb-6">
                    <h4 class="text-md font-bold mb-2">Archivo Adjunto</h4>
                    <a href="${archivoUrl}" target="_blank" class="text-blue-600 hover:underline">${data.Archivo}</a>
                </div>
            `;
    }

    //Botones
    html += `
          <div class="mt-6 pt-4 border-t">
              <h3 class="text-md font-semibold mb-3 text-gray-700">ACCIONES</h3>
              <div id="comprobante-uploader-container"></div>
              <div id="factura-uploader-container" class="mt-4"></div> <!-- Nuevo contenedor para el uploader de factura -->
              <div class="grid grid-cols-1 gap-4 mt-4">
                  <button onclick="verRequisicionPago(${id})" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition w-full">
                      Ver Requisición de pago
                  </button>
                  <button onclick="guardarEstadoPorPagar(${id})" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition w-full">
                      Guardar
                  </button>
              </div>
          </div>
      `;


    // Inyectamos el HTML
    contenedorDetalle.innerHTML = html;

    // Render the uploader components
    renderComprobanteUploader(id);
  //  renderFacturaUploader(id); // Llamada para renderizar el uploader de factura

  } catch (error) {
    console.error('Error al cargar detalle:', error);
    contenedorDetalle.innerHTML = `<p class="text-center text-red-500 py-8">Error al cargar los detalles: ${error.message}</p>`;
  }
}

async function guardarEstadoPorPagar(idSolicitud) {
  // Fetch latest details to check for comprobante
  try {
    const orderDetails = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`);
    
    // Check if File_Comprobante exists and is not empty
    if (!orderDetails || !orderDetails.OrdenCompra || !orderDetails.OrdenCompra.File_Comprobante) {
      mostrarNotificacion('No se puede guardar. Primero debe subir el comprobante.', 'warning');
      return;
    }
  } catch (error) {
    console.error('Error al verificar comprobante:', error);
    mostrarNotificacion('Error al verificar el estado del comprobante. Intente de nuevo.', 'error');
    return;
  }

  const confirmacion = await Confirmar(
    'Guardar Estado',
    '¿Está seguro de que desea cambiar el estado de esta solicitud a "Por Pagar"?');

  if (!confirmacion) {
    return;
  }

  try {
    const apiResult = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: { nuevoEstado: 'Por Pagar' },
    });

    if (apiResult.success) {
      mostrarNotificacion('Guardado con éxito.', 'success'); // Changed message
      regresarListaPagos(); 
      if (typeof initListaPagos === 'function') { 
          initListaPagos();
      }
    } else {
      mostrarNotificacion(apiResult.message || 'No se pudo guardar.', 'error');
    }
  } catch (error) {
    console.error('Error al guardar estado:', error);
    mostrarNotificacion(`Ocurrió un error al intentar guardar: ${error.message}`, 'error');
  }
}

function regresarListaPagos() {
  const divLista = document.getElementById('div-lista-pagos');
  const divDetalle = document.getElementById('div-detalle-pago');
  const contenedor = document.getElementById('contenido-detalle-pago');

  contenedor.innerHTML = '';

  divDetalle.classList.add('hidden');
  divLista.classList.remove('hidden');
}

function verRequisicionPago(id) {
  const url = `${BASE_URL}api/requisicionpago/pdf/${id}`;
  window.open(url, '_blank');
}


// --- Lógica para el uploader de Factura (similar al Comprobante) ---



function handleFacturaFileSelect(input, idSolicitud) { 
    const file = input.files[0];
    if (!file) {
        removeFacturaFile(idSolicitud);
        return;
    }

    const previewContainer = document.getElementById('file-preview-factura'); 
    const uploadButton = document.getElementById('btn-upload-factura'); 
    
    // Simple file type icon logic
    let icon = '📄'; // default icon
    if (file.type.startsWith('image/')) {
        icon = '🖼️';
    } else if (file.type === 'application/pdf') {
        icon = '📕';
    } else if (file.type === 'text/xml' || file.type === 'application/xml') {
        icon = '🔗';
    }
    
    const fileSize = (file.size / 1024).toFixed(2) + ' KB';

    previewContainer.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-2xl">${icon}</span>
                <div>
                    <p class="text-sm font-medium text-gray-800 truncate">${file.name}</p>
                    <p class="text-xs text-gray-500">${fileSize}</p>
                </div>
            </div>
            <button onclick="removeFacturaFile(${idSolicitud})" class="text-red-500 hover:text-red-700 font-bold text-xl">&times;</button> 
        </div>
    `;
    previewContainer.classList.remove('hidden');

    uploadButton.innerText = 'Confirmar y Subir Factura'; 
    uploadButton.onclick = () => uploadFactura(idSolicitud); 
}

function removeFacturaFile(idSolicitud) { 
    const fileInput = document.getElementById('archivo-factura'); 
    if (fileInput) {
        fileInput.value = '';
    }

    const previewContainer = document.getElementById('file-preview-factura'); 
    if (previewContainer) {
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
    }
    
    const uploadButton = document.getElementById('btn-upload-factura'); 
    if(uploadButton) {
        uploadButton.innerText = 'Subir Factura'; 
        uploadButton.onclick = () => document.getElementById('archivo-factura').click(); 
    }
}

async function uploadFactura(idSolicitud) { 
    const fileInput = document.getElementById('archivo-factura'); 
    const file = fileInput.files[0];

    if (!file) {
        mostrarNotificacion('No se ha seleccionado ningún archivo.', 'warning');
        return;
    }

    const previewContainer = document.getElementById('file-preview-factura'); 
    const uploadButton = document.getElementById('btn-upload-factura'); 

    previewContainer.innerHTML = `
        <div class="flex items-center justify-center gap-2">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-gray-600">Subiendo factura...</span> 
        </div>
    `;
    uploadButton.disabled = true;

    const formData = new FormData();
    formData.append('factura', file); // 'factura' es el nombre esperado en el backend

    try {
        const result = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
            method: 'POST',
            body: formData,
        });

        if (result.success) {
            mostrarNotificacion('Factura subida con éxito.', 'success'); 
            removeFacturaFile(idSolicitud); 
        } else {
            throw new Error(result.message || 'Error desconocido del servidor.');
        }
    } catch (error) {
        console.error('Error al subir factura:', error); 
        mostrarNotificacion(`Error al subir el archivo: ${error.message}`, 'error'); 
        handleFacturaFileSelect(fileInput, idSolicitud); 
    } finally {
        uploadButton.disabled = false;
    }
}