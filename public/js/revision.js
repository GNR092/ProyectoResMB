/**
 * Lógica para el modal "Enviar a Revisión"
 */
function RevisionX() {
  /*
    ID del proveedor seleccionado puedes usar /api/provider/ID_Proveedor para obtener los montos y los datos
    verifica primero si idprov esta definida dado caso si hay multiples proveedores, de lo contrario si no hay multiples proveedores
    toma el proveedor de la solicitud ya que si no se hace mostrara error
  */
  let idprov = null
  return {
    init() {
      this.loadTable()
    },
    loadTable() {
      createPaginatedTable({
        tableSelector: '#tabla-enviar tbody',
        paginationSelector: 'paginacion-enviar-revision',
        endpoint: 'api/solicitudes/cotizadas',
        processData: (data) => {
          const agrupado = data.reduce((acc, s) => {
            if (s.Estado === 'En revision') return acc
            if (!acc[s.ID_Solicitud]) {
              acc[s.ID_Solicitud] = {
                ...s,
                Monto: parseFloat(s.Monto) || 0,
                Proveedor: new Set([s.Proveedor]),
                cotizaciones: [s],
              }
            } else {
              acc[s.ID_Solicitud].Monto += parseFloat(s.Monto) || 0
              acc[s.ID_Solicitud].Proveedor.add(s.Proveedor)
              acc[s.ID_Solicitud].cotizaciones.push(s)
            }
            return acc
          }, {})

          return Object.values(agrupado).map((s) => {
            if (s.Proveedor.size > 1) {
              s.Proveedor = 'Múltiples proveedores'
            } else {
              s.Proveedor = [...s.Proveedor][0] || 'N/A'
            }
            return s
          })
        },
        noResultsMessage: 'No hay solicitudes cotizadas para mostrar.',
        renderRow: (s) => {
          const monto = parseFloat(s.Monto || 0).toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
          })
          return `
            <tr class="hover:bg-gray-50" data-id="${s.ID_Solicitud}">
                <td class="py-3 px-6 text-left">${s.Folio}</td>
                <td class="py-3 px-6 text-left">${s.Usuario || 'N/A'}</td>
                <td class="py-3 px-6 text-left">${s.Departamento || 'N/A'}</td>
                <td class="py-3 px-6 text-left">${s.Proveedor}</td>
                <td class="py-3 px-6 text-left">${monto}</td>
                <td class="py-3 px-6 text-left">${s.Estado}</td>
                <td class="py-3 px-6 text-left">
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded btn-enviar" @click="VerDetalle(${s.ID_Solicitud})">
                        Ver
                    </button>
                </td>
            </tr>
          `
        },
      }).catch((error) => {
        console.error('Error al cargar tabla de revisión:', error)
        mostrarNotificacion('Error al cargar solicitudes para revisión.', 'error')
      })
    },

    /**
     * Obtiene los días de crédito.
     * Siempre consulta la API del proveedor para obtener los días de crédito.
     * @param {object} data - El objeto de detalles de la solicitud
     * @returns {Promise<number>} - Promesa que resuelve a los días de crédito
     */
    async _getDiasDeCredito(data) {
      let diasCredito = 0
      let providerIdToQuery = null

      if (idprov) {
        providerIdToQuery = idprov
      } else if (data && data.ID_Proveedor) {
        providerIdToQuery = data.ID_Proveedor
      } else if (data && data.cotizacion && data.cotizacion.ID_Proveedor) {
        providerIdToQuery = data.cotizacion.ID_Proveedor
      }

      if (!providerIdToQuery) {
        console.warn('No se pudo determinar un ID de Proveedor para consultar los días de crédito.')
        return 0
      }

      try {
        const proveedorData = await SendDataEnd(`api/provider/${providerIdToQuery}`)
        diasCredito = parseInt(proveedorData.Dias_Credito) || 0
      } catch (error) {
        console.error(`Error al buscar proveedor ${providerIdToQuery}:`, error)
        diasCredito = 0
      }

      return diasCredito
    },

    /**
     * (NUEVA FUNCIÓN INTERNA - CORREGIDA)
     * Valida y actualiza la UI del radio button de crédito.
     * @param {object} data - El objeto de detalles de la solicitud
     */
    async validarOpcionCredito(data) {
      const radioCredito = document.querySelector(
        '#form-enviar-revision input[name="tipo_pago"][value="credito"]',
      )
      const radioContado = document.querySelector(
        '#form-enviar-revision input[name="tipo_pago"][value="efectivo"]',
      )
      if (!radioCredito || !radioContado) return

      radioCredito.disabled = true
      radioCredito.closest('label').classList.add('text-gray-400', 'cursor-not-allowed')

      const diasCredito = await this._getDiasDeCredito(data)

      if (diasCredito <= 0) {
        radioCredito.disabled = true
        radioCredito.checked = false
        radioContado.checked = true
        radioCredito.closest('label').classList.add('text-gray-400', 'cursor-not-allowed')
      } else {
        radioCredito.disabled = false
        radioCredito.closest('label').classList.remove('text-gray-400', 'cursor-not-allowed')
      }
    },

    VerDetalle: async function (idSolicitud) {
      console.log(`ID seleccionado ${idprov}`)
      const divTabla = document.getElementById('div-tabla-enviar')
      const divRevision = document.getElementById('div-enviar-revision')
      const detallesContainer = document.getElementById('detalles-para-revision')
      const form = document.getElementById('form-enviar-revision')
      const btnConfirmar = document.getElementById('btn-confirmar-revision')

      divTabla.classList.add('hidden')
      divRevision.classList.remove('hidden')
      detallesContainer.innerHTML = '<p class="text-center">Cargando detalles...</p>'

      try {
        const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)
        console.log('Datos de la solicitud (Un proveedor):', data)

        let estadoClass = getStatus(data.Estado)
        const monto = parseFloat(data.cotizacion?.Total || 0).toLocaleString('es-MX', {
          style: 'currency',
          currency: 'MXN',
        })
        const proveedorNombre =
          data.cotizaciones && data.cotizaciones.length > 1
            ? 'Múltiples proveedores'
            : data.cotizacion?.ProveedorNombre || 'N/A'
        let html = `
           <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
                <div><strong>Fecha:</strong> ${data.Fecha}</div>
                <div><strong>Estado:</strong> <span class="font-semibold ${estadoClass}">${data.Estado === 'Dept_Rechazada' ? 'Rechazada' : data.Estado || 'N/A'}</span></div>
                <div><strong>Usuario:</strong> ${data.UsuarioNombre}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre}</div>
                <div><strong>Complejo:</strong> ${data.Complejo}</div>
                <div><strong>Proveedor (Cotización):</strong> ${proveedorNombre}</div>
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
                        <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>
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
                        <h4 class="text-md font-bold mb-2">Archivo Adjunto (Solicitante)</h4>
                        <a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.Archivo}</a>
                    </div>
                `
        }
        html += `
                <div class="mt-6">
                    <button onclick="mostrarVerPdf(${idSolicitud})" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Ver PDF</button>
                    <button @click="mostrarModalModificarMontos(${idSolicitud})" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Modificar valores</button>
                </div>
                `
        detallesContainer.innerHTML = html

        const checkboxInput = document.getElementById('adjuntar-solicitante-check')
        const checkboxLabel = document.getElementById('adjuntar-solicitante-label')
        const inputArchivos = document.getElementById('archivos-revision')

        if (checkboxInput && checkboxLabel && inputArchivos) {
          checkboxInput.checked = false
          checkboxInput.disabled = false
          inputArchivos.disabled = false
          inputArchivos.value = ''
          inputArchivos.classList.remove('bg-gray-100', 'cursor-not-allowed')
          checkboxLabel.textContent = 'Adjuntar solo la cotización del solicitante'
          checkboxLabel.classList.remove('text-gray-500', 'cursor-not-allowed')

          if (!data.Archivo) {
            checkboxInput.disabled = true
            checkboxLabel.textContent += ' (No disponible)'

            checkboxLabel.classList.add('text-red-700', 'cursor-not-allowed')
            checkboxInput.classList.add(
              'cursor-not-allowed',
              'bg-red-100',
              'border-red-400',
              'accent-red-500',
            )
            checkboxInput.classList.remove('text-indigo-600', 'focus:ring-indigo-500')
          } else {
            checkboxInput.onchange = (e) => {
              inputArchivos.disabled = e.target.checked
              inputArchivos.classList.toggle('bg-gray-100', e.target.checked)
              inputArchivos.classList.toggle('cursor-not-allowed', e.target.checked)
              if (e.target.checked) {
                inputArchivos.value = ''
              }
            }
          }
        }
        this.validarOpcionCredito(data)

        const radioCredito = form.querySelector('input[name="tipo_pago"][value="credito"]')
        if (radioCredito) {
          radioCredito.onclick = () => this.validarOpcionCredito(data)
        }

        form.onsubmit = async (e) => {
          e.preventDefault()

          const tipoPagoRadio = document.querySelector('input[name="tipo_pago"]:checked')

          if (!tipoPagoRadio) {
            mostrarNotificacion('Por favor, seleccione un tipo de pago.', 'error')
            return
          }

          if (tipoPagoRadio.value === 'credito') {
            const diasCredito = await this._getDiasDeCredito(data)

            if (diasCredito <= 0) {
              mostrarNotificacion(
                'No se puede enviar: El proveedor no tiene crédito aprobado.',
                'error',
              )
              return
            }
          }

          if (
            !(await Confirmar(
              'Enviar a revisión',
              '¿Está seguro de que desea enviar la solicitud a revisión? Esta acción es irreversible y las cotizaciones no seleccionadas serán eliminadas.',
            ))
          ) {
            return
          }

          const formData = new FormData()
          formData.append('ID_Solicitud', idSolicitud)

          const selectedCotizacionId = document.getElementById('proveedor-select')?.value
          if (selectedCotizacionId) {
            formData.append('id_cotizacion_seleccionada', selectedCotizacionId)
          }

          const adjuntarSoloSolicitante =
            document.getElementById('adjuntar-solicitante-check')?.checked || false
          const archivos = document.getElementById('archivos-revision').files

          if (!adjuntarSoloSolicitante && archivos.length === 0) {
            mostrarNotificacion(
              'Debe adjuntar al menos un archivo de cotización o marcar la opción de usar el del solicitante.',
              'error',
            )
            return
          }

          if (!adjuntarSoloSolicitante && archivos.length > 0) {
            for (let i = 0; i < archivos.length; i++) {
              formData.append('cotizacion_files[]', archivos[i])
            }
          }

          const tipoPago = document.querySelector('input[name="tipo_pago"]:checked')
          if (!tipoPago) {
            mostrarNotificacion('Por favor, seleccione un tipo de pago.', 'error')
            return
          }
          formData.append('tipo_pago', tipoPago.value)

          formData.append('usar_archivo_solicitante', adjuntarSoloSolicitante)

          btnConfirmar.disabled = true
          btnConfirmar.textContent = 'Enviando...'

          try {
            const result = await SendDataEnd('api/solicitud/enviar-revision', {
              method: 'POST',
              body: formData,
            })

            if (result.success) {
              mostrarNotificacion(result.message || 'Solicitud enviada a revisión.', 'success')
              this.regresar()
              this.loadTable()
            } else {
              if (
                !adjuntarSoloSolicitante &&
                archivos.length === 0 &&
                result.message &&
                result.message.includes('archivo')
              ) {
                mostrarNotificacion(
                  'Debe adjuntar al menos un archivo de cotización o marcar la opción de usar el del solicitante.',
                  'error',
                )
              } else {
                mostrarNotificacion(result.message || 'Error al enviar a revisión.', 'error')
              }
            }
          } catch (error) {
            console.error('Error:', error)
            mostrarNotificacion('Error de red al enviar a revisión.', 'error')
          } finally {
            btnConfirmar.disabled = false
            btnConfirmar.textContent = 'Solicitar Autorización'
          }
        }
      } catch (error) {
        detallesContainer.innerHTML = `<p class="text-red-500 text-center">${error.message}</p>`
      }
    },
    regresar: function () {
      idprov = null
      document.getElementById('div-tabla-enviar').classList.remove('hidden')
      document.getElementById('div-enviar-revision').classList.add('hidden')
      const form = document.getElementById('form-enviar-revision')
      if (form) form.reset()
      const detalles = document.getElementById('detalles-para-revision')
      if (detalles) detalles.innerHTML = ''
    },
    mostrarModalModificarMontos: async function (idSolicitud) {
      const modalModificar = document.getElementById('modal-modificar-montos')
      const productosContainer = document.getElementById('productos-modificar-container')
      const formModificar = document.getElementById('form-modificar-montos')
      const idSolicitudInput = document.getElementById('modificar_id_solicitud')
      const proveedorSelectContainer = document.getElementById('proveedor-select-container')

      const subtotalEl = document.getElementById('subtotal-modificar')
      const totalEl = document.getElementById('total-modificar')

      if (
        !modalModificar ||
        !productosContainer ||
        !formModificar ||
        !idSolicitudInput ||
        !proveedorSelectContainer ||
        !subtotalEl ||
        !totalEl
      ) {
        console.error('Elementos del modal de modificación no encontrados.')
        return
      }

      idSolicitudInput.value = idSolicitud
      productosContainer.innerHTML =
        '<p class="text-center text-gray-500">Cargando productos...</p>'
      proveedorSelectContainer.innerHTML = ''
      modalModificar.classList.remove('hidden')

      try {
        const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)

        if (data.error) throw new Error(data.error)

        const cotizacionesData = data.cotizaciones || []

        function actualizarTotalesModificar() {
          let subtotal = 0
          const inputsImporte = formModificar.querySelectorAll(
            'input[name^="productos["][name$="[importe]"]',
          )
          const inputsCantidad = formModificar.querySelectorAll(
            'input[name^="productos["][name$="[cantidad]"]',
          )

          inputsImporte.forEach((importeInput, index) => {
            const importe = parseFloat(importeInput.value) || 0
            const cantidad = parseFloat(inputsCantidad[index].value) || 0
            subtotal += importe * cantidad
          })

          const total = subtotal

          subtotalEl.textContent = subtotal.toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
          })
          totalEl.textContent = total.toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
          })
        }

        if (cotizacionesData.length > 1) {
          let selectHtml =
            '<label for="proveedor-select" class="block text-sm font-medium text-gray-700">Seleccionar Proveedor:</label>'
          selectHtml +=
            '<select id="proveedor-select" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-2 border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">'
          selectHtml += '<option value="">Seleccione un proveedor</option>'
          cotizacionesData.forEach((cot) => {
            selectHtml += `<option value="${cot.ID_Cotizacion}">${cot.ProveedorNombre}</option>`
          })
          selectHtml += '</select>'
          proveedorSelectContainer.innerHTML = selectHtml

          const proveedorSelect = document.getElementById('proveedor-select')
          proveedorSelect.addEventListener('change', (e) => {
            const selectedCotizacionId = e.target.value
            const selectedCotizacion = cotizacionesData.find(
              (cot) => cot.ID_Cotizacion == selectedCotizacionId,
            )
            if (selectedCotizacion) {
              idprov = selectedCotizacion.ID_Proveedor
              console.log(`provedor seleccionado ${idprov}`)
            }

            this.validarOpcionCredito(data)
          })
        }

        function actualizarProductos(productos) {
          let productosHtml = `
            <div class="overflow-x-auto">
              <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                  <tr>
                    <th class="py-2 px-4 text-left">Código</th>
                    <th class="py-2 px-4 text-left">Producto</th>
                    <th class="py-2 px-4 text-right">Cantidad</th>
                    <th class="py-2 px-4 text-right">Importe</th>
                  </tr>
                </thead>
                <tbody>
          `
          productos.forEach((p, index) => {
            productosHtml += `
                  <tr class="hover:bg-gray-50">
                      <td class="py-2 px-4 border-t text-right">
                          <input type="text" name="productos[${index}][codigo]" placeholder="N/A" value="${p.Codigo || ''}" class="w-full px-2 py-1 border rounded text-left">
                      </td>
                      <td class="py-2 px-4 border-t">
                          <input type="text" name="productos[${index}][nombre]" value="${p.Nombre}" class="w-full px-2 py-1 border rounded text-left">
                      </td>
                      <td class="py-2 px-4 border-t text-right">
                          <input type="number" name="productos[${index}][cantidad]" value="${p.Cantidad}" min="1" class="w-full px-2 py-1 border rounded text-right producto-cantidad">
                      </td>
                      <td class="py-2 px-4 border-t text-right">
                          <input type="number" name="productos[${index}][importe]" value="${parseFloat(p.Importe).toFixed(2)}" step="0.01" min="0" class="w-full px-2 py-1 border rounded text-right producto-importe">
                      </td>
                  </tr>
            `
          })
          productosHtml += `
                </tbody>
              </table>
            </div>
          `
          productosContainer.innerHTML = productosHtml

          const inputs = productosContainer.querySelectorAll(
            '.producto-cantidad, .producto-importe',
          )
          inputs.forEach((input) => input.addEventListener('input', actualizarTotalesModificar))
        }

        actualizarProductos(data.productos)
        actualizarTotalesModificar()

        formModificar.onsubmit = async (e) => {
          e.preventDefault()
          const formData = new FormData(formModificar)
          const productosModificados = []
          let nuevoSubtotal = 0

          const commnt = formData.get('comentarios')

          data.productos.forEach((p, index) => {
            const c = formData.get(`productos[${index}][codigo]`)
            const cantidad = formData.get(`productos[${index}][cantidad]`)
            const importe = formData.get(`productos[${index}][importe]`)
            nuevoSubtotal += (parseFloat(cantidad) || 0) * (parseFloat(importe) || 0)
            productosModificados.push({
              codigo: c === '' ? null : c,
              nombre: formData.get(`productos[${index}][nombre]`),
              cantidad: cantidad,
              importe: importe,
            })
          })

          const nuevoTotal = nuevoSubtotal

          const selectedCotizacionId = document.getElementById('proveedor-select')?.value

          const selectedCotizacion = selectedCotizacionId
            ? cotizacionesData.find((cot) => cot.ID_Cotizacion == selectedCotizacionId)
            : cotizacionesData.length === 1
              ? cotizacionesData[0]
              : null

          const proveedor = selectedCotizacion
            ? selectedCotizacion.proveedor
            : data.proveedor || null

          if (proveedor && proveedor.Monto_Credito && parseFloat(proveedor.Monto_Credito) > 0) {
            const montoCredito = parseFloat(proveedor.Monto_Credito)
            if (nuevoTotal > montoCredito) {
              if (
                !(await Confirmar(
                  'Monto Excedido',
                  `ALERTA: El monto total (${nuevoTotal.toFixed(2)}) excede el límite de crédito del proveedor ${proveedor.RazonSocial} (${montoCredito.toFixed(2)}).

¿Desea continuar?`,
                ))
              ) {
                return
              }
            }
          }

          const payload = {
            id_solicitud: idSolicitud,
            id_cotizacion_seleccionada: selectedCotizacionId,
            productos: productosModificados,
            comentarios: commnt === '' ? null : commnt,
          }

          try {
            const updateResult = await SendDataEnd('api/solicitud/update', {
              method: 'POST',
              body: payload,
            })

            if (updateResult.success) {
              mostrarNotificacion(
                updateResult.message || 'Montos actualizados correctamente.',
                'success',
              )
              this.cerrarModalModificarMontos(idSolicitud)
              this.VerDetalle(idSolicitud)
            } else {
              mostrarNotificacion(updateResult.message || 'Error al actualizar montos.', 'error')
            }
          } catch (updateError) {
            console.error('Error al enviar actualización de montos:', updateError)
            mostrarNotificacion('Error de red al actualizar montos.', 'error')
          }
        }
      } catch (error) {
        console.error('Error al cargar detalles para modificar montos:', error)
        productosContainer.innerHTML = `<p class="text-red-500 text-center">No se pudieron cargar los detalles para modificar. ${error.message}</p>`
      }
    },
    cerrarModalModificarMontos: function (idSolicitud) {
      document.getElementById('modal-modificar-montos').classList.add('hidden')
      this.VerDetalle(idSolicitud)
    },
  }
}
