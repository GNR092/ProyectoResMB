/**
 * Lógica para el modal "Enviar a Revisión"
 */
let choicesDepartamentoRevision = null;

function RevisionX() {
  /*
    ID del proveedor seleccionado puedes usar /api/provider/ID_Proveedor para obtener los montos y los datos
    verifica primero si idprov esta definida dado caso si hay multiples proveedores, de lo contrario si no hay multiples proveedores
    toma el proveedor de la solicitud ya que si no se hace mostrara error
  */
  let idprov = null
  return {
    init() {
        this.initFilters();
        this.loadTable();
    },

      // --- Filtros de Dpto ---
      initFilters() {
          const filtroEl = document.getElementById('filtroDepartamentoRevision');
          if (filtroEl) {
              // Destruir instancia previa si existe para evitar duplicados
              if (choicesDepartamentoRevision) {
                  // Verificamos si la instancia tiene el método destroy (por seguridad)
                  try { choicesDepartamentoRevision.destroy(); } catch (e) {}
              }

              // Inicializar Choices sin condiciones de ocultamiento
              choicesDepartamentoRevision = new Choices(filtroEl, {
                  removeItemButton: true,
                  placeholder: true,
                  placeholderValue: 'Todos los departamentos',
                  searchPlaceholderValue: 'Buscar...',
                  itemSelectText: 'Seleccionar',
                  noResultsText: 'No se encontraron resultados',
                  noChoicesText: 'No hay más opciones para elegir',
              });
          }
      },


    loadTable() {
      createPaginatedTable({
        tableSelector: '#tabla-enviar tbody',
        paginationSelector: 'paginacion-enviar-revision',
        endpoint: 'api/solicitudes/cotizadas',

          filterFormSelector: '#contenedor-filtros-revision',

          filterFunction: (allData) => {
              const selecciones = choicesDepartamentoRevision ? choicesDepartamentoRevision.getValue(true) : [];

              const nombresSeleccionados = selecciones.length > 0
                  ? selecciones.map(val => val.split('|')[0])
                  : [];

              return allData.filter((item) => {
                  if (nombresSeleccionados.length === 0) {
                      return true;
                  }
                  if (!item.Departamento) return false;
                  // Comparamos solo el nombre del departamento
                  return nombresSeleccionados.includes(item.Departamento);
              });
          },

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
          const divTabla = document.getElementById('div-tabla-enviar')
          const divRevision = document.getElementById('div-enviar-revision')
          const detallesContainer = document.getElementById('detalles-para-revision')
          const form = document.getElementById('form-enviar-revision')
          const btnConfirmar = document.getElementById('btn-confirmar-revision')

          // Ocultar filtros si existen
          const divFiltros = document.getElementById('contenedor-filtros-revision')
          if (divFiltros) divFiltros.classList.add('hidden')

          divTabla.classList.add('hidden')
          divRevision.classList.remove('hidden')
          detallesContainer.innerHTML = '<p class="text-center">Cargando detalles...</p>'

          try {
              const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)

              // Lógica de respaldo: Si no hay objeto cotización, y hay ID_Proveedor en la raíz, lo usamos.
              if (!data.cotizacion && data.ID_Proveedor) {
                  idprov = data.ID_Proveedor;
              }

              const isServicio = data.Tipo == 2
              const isMultiple = data.cotizaciones && data.cotizaciones.length > 1
              let estadoClass = getStatus(data.Estado)

              // Lógica para determinar el nombre del proveedor a mostrar
              let proveedorNombre = 'N/A';
              if (data.cotizacion && data.cotizacion.ProveedorNombre) {
                  proveedorNombre = data.cotizacion.ProveedorNombre;
              } else if (data.RazonSocialNombre) {
                  proveedorNombre = data.RazonSocialNombre;
              } else if (data.ProveedorNombre) {
                  proveedorNombre = data.ProveedorNombre;
              }

              let proveedorHtml = ''
              let montoHtml = '' // Nota: montoHtml estaba vacío en tu código original, lo mantengo así.

              if (isMultiple) {
                  proveedorHtml = `
            <div class="md:col-span-2">
              <label for="proveedor-select" class="block text-sm font-medium text-gray-700"><strong>Proveedor (Múltiples cotizaciones):</strong></label>
              </div>
          `
              } else {
                  proveedorHtml = `<div><strong>Proveedor:</strong> ${proveedorNombre}</div>`
              }

              // --- CONSTRUCCIÓN DEL HTML ---
              let html = `
           <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
                <div><strong>Fecha:</strong> ${data.Fecha}</div>
                <div><strong>Estado:</strong> <span class="font-semibold ${estadoClass}">${
                  data.Estado === 'Dept_Rechazada' ? 'Rechazada' : data.Estado || 'N/A'
              }</span></div>
                <div><strong>Usuario:</strong> ${data.UsuarioNombre}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre}</div>
                <div><strong>Complejo:</strong> ${data.Complejo}</div>
                ${proveedorHtml}
                ${montoHtml}
            </div>
        `

              if (data.ComentariosAdmin) {
                  html += `
                <div class="mb-6 p-4 border rounded-lg bg-red-50 border-red-200">
                    <h4 class="text-md font-bold text-red-700 mb-2">Comentarios / Motivo del Rechazo</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
                </div>`
              }

              // === NUEVO INPUT: ComentarioCotizacion ===
              // Insertado antes de la tabla de productos
              html += `
            <div class="mb-6">
                <label for="input-comentario-cotizacion-main" class="block text-sm font-bold text-gray-700 mb-1">
                    Comentarios de la Cotización <span class="text-gray-400 font-normal text-xs">(Opcional: detalles de entrega, notas del proveedor, etc.)</span>
                </label>
                <textarea 
                    id="input-comentario-cotizacion-main" 
                    rows="2" 
                    class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                    placeholder="Escriba aquí los comentarios relacionados a la cotización..."></textarea>
            </div>
        `
              // =========================================

              // --- LÓGICA SELECTOR GRUPO GENERAL (ELIMINADA: SOLO LECTURA) ---
              let grupoGeneralHtml = '';
              // -------------------------------------------------

              html += grupoGeneralHtml;

              html += `
                <h4 class="text-md font-bold mb-2">${
                  isServicio ? 'Servicios' : 'Productos'
              } Solicitados</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                ${!isServicio ? '<th class="py-2 px-4 text-left">Código</th>' : ''}
                                <th class="py-2 px-4 text-left">${
                  isServicio ? 'Servicio' : 'Producto'
              }</th>
                                ${
                  !isServicio
                      ? '<th class="py-2 px-4 text-right">Cantidad</th>'
                      : ''
              }
                                <th class="py-2 px-4 text-right">Importe</th>
                                ${
                  !isServicio
                      ? '<th class="py-2 px-4 text-right">Costo Total</th>'
                      : ''
              }
                                <th class="py-2 px-4 text-left">Partida Presupuestal</th>
                            </tr>
                        </thead>
                        <tbody>
            `
              data.productos.forEach((p) => {
                  const factorIVA = data.IVA ? 1.16 : 1.0;
                  const costoTotal = !isServicio ? (p.Cantidad * p.Importe * factorIVA).toFixed(2) : ''

                  let gruposHtml = '';
                  const itemId = isServicio ? p.ID_SolicitudServ : p.ID_SolicitudProd;
                  
                  // Obtener el ID del grupo asignado o sugerido
                  const idGrupoAsignado = p.ID_GrupoPresupuestal || p.ID_GrupoSugerido;
                  let nombreGrupo = 'No asignado';
                  let idGrupo = '';

                  if (data.grupos_presupuestales && idGrupoAsignado) {
                      const grupo = data.grupos_presupuestales.find(g => g.ID_GrupoPresupuestal == idGrupoAsignado);
                      if (grupo) {
                          nombreGrupo = grupo.Nombre;
                          idGrupo = grupo.ID_GrupoPresupuestal;
                      }
                  }

                  // FIX: Fallback al nombre del JOIN si no se encontró en grupos_presupuestales
                  if (nombreGrupo === 'No asignado' && p.GrupoPresupuestalNombre) {
                      nombreGrupo = p.GrupoPresupuestalNombre;
                      idGrupo = idGrupoAsignado || '';
                  }

                  gruposHtml = `
                    <div class="text-sm font-semibold text-blue-700">${nombreGrupo}</div>
                    <input type="hidden" name="id_grupo_presupuestal[${itemId}]" value="${idGrupo}">
                  `;

                  html += `
                    <tr class="hover:bg-gray-50">
                        ${
                      !isServicio ? `<td class="py-2 px-4 border-t">${p.Codigo || 'N/A'}</td>` : ''
                  }
                        <td class="py-2 px-4 border-t">${p.Nombre}</td>
                        ${
                      !isServicio
                          ? `<td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>`
                          : ''
                  }
                        <td class="py-2 px-4 border-t text-right">${parseFloat(p.Importe).toFixed(
                      2,
                  )}</td>
                        ${
                      !isServicio
                          ? `<td class="py-2 px-4 border-t text-right">${costoTotal}</td>`
                          : ''
                  }
                        <td class="py-2 px-4 border-t">${gruposHtml}</td>
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
                    <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias (Usuario)</h4>
                    <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p>
                </div>`
              }

              if (data.Archivo) {
                  const archivos = data.Archivo.split(',')
                  html += `<div class="mt-6">
                            <h4 class="text-md font-bold mb-2">Archivos Adjuntos (Solicitante)</h4>
                            <div class="flex flex-col gap-2">`
                  archivos.forEach((archivo, index) => {
                      const archivoUrl = `${BASE_URL}solicitudes/archivo/${idSolicitud}/${archivo}`
                      html += `<a href="${archivoUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">
                                 Archivo ${index + 1}: ${archivo}
                              </a>`
                  })
                  html += `</div></div>`
              }

              html += `
                <div class="mt-6">
                    <button onclick="mostrarVerPdf(${idSolicitud})" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Ver PDF</button>
                    <button @click="mostrarModalModificarMontos(${idSolicitud})" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Modificar valores</button>
                    <button onclick="globalCancelarSolicitud(${idSolicitud}, () => document.getElementById('btn-regresar-revision').click())" 
                    class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition ml-2">
                    Cancelar Solicitud
                    </button>
                </div>
                `
              detallesContainer.innerHTML = html

              // --- EVENTO PARA EL SELECTOR GRUPO GENERAL ---
              const selectGeneral = document.getElementById('select-grupo-presupuestal-general');
              if (selectGeneral) {
                  selectGeneral.addEventListener('change', function(e) {
                      const valor = e.target.value;
                      if (valor !== '') {
                          document.querySelectorAll('.grupo-presupuestal-select').forEach(select => {
                              select.value = valor;
                          });
                      }
                  });
              }
              // ----------------------------------------------

              // --- VALIDACIONES DE CRÉDITO Y PROVEEDOR ---
              if (isMultiple) {
                  if (data.cotizaciones && data.cotizaciones.length > 0) {
                      // Tomamos el ID del primer proveedor de la lista para validar inicialmente
                      this.validarOpcionCredito({ ID_Proveedor: data.cotizaciones[0].ID_Proveedor });
                  } else {
                      this.validarOpcionCredito({});
                  }
              } else {
                  this.validarOpcionCredito(data);
              }

              // --- RESETEO DEL FORMULARIO INFERIOR ---
              const checkboxInput = document.getElementById('adjuntar-solicitante-check')
              const checkboxLabel = document.getElementById('adjuntar-solicitante-label')
              const inputArchivos = document.getElementById('archivos-revision')

              if (inputArchivos && typeof setupAccumulatedFileInput === 'function') {
                  setupAccumulatedFileInput(inputArchivos)
                  if (typeof clearAccumulatedFileInput === 'function') {
                      clearAccumulatedFileInput(inputArchivos)
                  } else {
                      inputArchivos.value = ''
                  }
              }

              // También limpiamos el nuevo input si existe (para que no tenga datos viejos)
              const nuevoInputComentario = document.getElementById('input-comentario-cotizacion-main');
              if (nuevoInputComentario) nuevoInputComentario.value = '';

              if (checkboxInput && checkboxLabel && inputArchivos) {
                  checkboxInput.checked = false
                  checkboxInput.disabled = false
                  inputArchivos.disabled = false
                  if (typeof clearAccumulatedFileInput === 'function') {
                      clearAccumulatedFileInput(inputArchivos)
                  } else {
                      inputArchivos.value = ''
                  }
                  inputArchivos.classList.remove('bg-gray-100', 'cursor-not-allowed')
                  checkboxLabel.classList.remove('text-gray-500', 'cursor-not-allowed')

                  if (!data.Archivo) {
                      checkboxInput.classList.remove('text-indigo-600', 'focus:ring-indigo-500')
                      // Aquí tenías código comentado en tu versión original, lo dejo limpio.
                  } else {
                       checkboxInput.onchange = (e) => {
                           inputArchivos.disabled = e.target.checked
                           inputArchivos.classList.toggle('bg-gray-100', e.target.checked)
                           inputArchivos.classList.toggle('cursor-not-allowed', e.target.checked)
                           if (e.target.checked) {
                               if (typeof clearAccumulatedFileInput === 'function') {
                                   clearAccumulatedFileInput(inputArchivos)
                               } else {
                                   inputArchivos.value = ''
                               }
                           }
                       }
                   }
               }

              // Listener para cambio de tipo de pago (si es múltiple proveedor)
              const radioCredito = form.querySelector('input[name="tipo_pago"][value="credito"]')
              if (radioCredito) {
                  radioCredito.onclick = () => {
                      if (isMultiple) {
                          const selectedCotizacion = data.cotizaciones.find(
                              (cot) => cot.ID_Cotizacion == document.getElementById('proveedor-select').value,
                          )
                          if (selectedCotizacion)
                              this.validarOpcionCredito({ ID_Proveedor: selectedCotizacion.ID_Proveedor })
                      } else {
                          this.validarOpcionCredito(data)
                      }
                  }
              }

              // --- ENVÍO DEL FORMULARIO ---
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
                  } else if (isMultiple) {
                      mostrarNotificacion('Por favor, seleccione una cotización de la lista.', 'error')
                      return
                  }

                  // === CAPTURAR NUEVO CAMPO ===
                  const comentarioCotizacionVal = document.getElementById('input-comentario-cotizacion-main')?.value || '';
                  formData.append('ComentarioCotizacion', comentarioCotizacionVal);
                  // ============================

                  // === CAPTURAR PARTIDAS PRESUPUESTALES ===
                  const selectGrupos = document.querySelectorAll('.grupo-presupuestal-select');
                  let todasPartidasSeleccionadas = true;

                  selectGrupos.forEach(select => {
                      // Verificar si el select tiene más de 1 opción (el placeholder + partidas reales)
                      const tieneOpciones = select.options.length > 1;
                      
                      if (tieneOpciones && (!select.value || select.value === "")) {
                          todasPartidasSeleccionadas = false;
                          select.classList.add('border-red-500'); // Resaltar el error visualmente
                      } else {
                          select.classList.remove('border-red-500');
                      }
                      
                      // Solo agregamos al formData si tiene un valor seleccionado
                      if (select.value) {
                          formData.append(select.name, select.value);
                      }
                  });

                  if (!todasPartidasSeleccionadas) {
                      mostrarNotificacion('Por favor, asigne una partida presupuestal a todos los ítems que tengan opciones disponibles.', 'error');
                      return;
                  }
                  // ========================================

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
          
          const divFiltros = document.getElementById('contenedor-filtros-revision')
          if (divFiltros) divFiltros.classList.remove('hidden')

          document.getElementById('div-enviar-revision').classList.add('hidden')

          const form = document.getElementById('form-enviar-revision')
          if (form) form.reset()

          const inputArchivos = document.getElementById('archivos-revision')
          if (inputArchivos && typeof clearAccumulatedFileInput === 'function') {
              clearAccumulatedFileInput(inputArchivos)
          } else if (inputArchivos) {
              inputArchivos.value = ''
          }

          const detalles = document.getElementById('detalles-para-revision')
          if (detalles) detalles.innerHTML = ''
          this.loadTable();
      },


   async cargarCuentasProveedor(idProveedor) {
      const cuentaSelectContainer = document.getElementById('cuenta-select-container');
      if (!cuentaSelectContainer) return;

      cuentaSelectContainer.innerHTML = '<p class="text-sm text-gray-500">Cargando cuentas...</p>';

      if (!idProveedor) {
          cuentaSelectContainer.innerHTML = '';
          return;
      }

      try {
          const cuentas = await SendDataEnd(`modales/cuentas/proveedor/${idProveedor}`);
          
          if (cuentas && cuentas.length > 0) {
              let selectHtml = `
                  <label for="cuenta-select" class="block text-sm font-medium text-gray-700">Seleccionar Cuenta para Pago:</label>
                  <select id="cuenta-select" name="id_cuenta" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                      <option value="">Seleccione una cuenta</option>
              `;
              cuentas.forEach(cuenta => {
                  selectHtml += `<option value="${cuenta.ID_Cuenta}">${cuenta.Cuenta}</option>`;
              });
              selectHtml += '</select>';
              cuentaSelectContainer.innerHTML = selectHtml;
          } else {
              cuentaSelectContainer.innerHTML = '<p class="text-sm text-red-500">Este proveedor no tiene cuentas de pago registradas.</p>';
          }
      } catch (error) {
          console.error('Error al cargar cuentas del proveedor:', error);
          cuentaSelectContainer.innerHTML = '<p class="text-sm text-red-500">Error al cargar las cuentas.</p>';
      }
    },

      mostrarModalModificarMontos: async function (idSolicitud) {
          const modalModificar = document.getElementById('modal-modificar-montos')
          const productosContainer = document.getElementById('productos-modificar-container')
          const formModificar = document.getElementById('form-modificar-montos')
          const idSolicitudInput = document.getElementById('modificar_id_solicitud')
          const proveedorSelectContainer = document.getElementById('proveedor-select-container')
          const cuentaSelectContainer = document.getElementById('cuenta-select-container')

          // Referencia al checkbox de IVA
          const ivaCheckbox = document.getElementById('modificar_iva')

          // Referencia al input de comentarios que se borraba
          const inputComentariosModificar = document.getElementById('modificar_comentarios')

          const subtotalEl = document.getElementById('subtotal-modificar')
          const totalEl = document.getElementById('total-modificar')

          if (!modalModificar || !productosContainer || !formModificar || !idSolicitudInput || !subtotalEl || !totalEl || !ivaCheckbox) {
              console.error('Elementos del modal de modificación no encontrados.')
              return
          }

          // Reset UI
          idSolicitudInput.value = idSolicitud
          productosContainer.innerHTML = '<p class="text-center text-gray-500">Cargando productos...</p>'
          proveedorSelectContainer.innerHTML = ''
          if (cuentaSelectContainer) cuentaSelectContainer.innerHTML = '';
          modalModificar.classList.remove('hidden')

          try {
              const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`)
              if (data.error) throw new Error(data.error)

              // === CORRECCIÓN DEL BUG: Pre-llenar comentarios ===
              if (inputComentariosModificar) {
                  // Si data.ComentariosUser es null, pone string vacío
                  inputComentariosModificar.value = data.ComentariosUser || '';
              }
              // ==================================================

              const isServicio = data.Tipo == 2;
              const cotizacionesData = data.cotizaciones || []

              // Inicializar el checkbox de IVA
              // Si la solicitud ya tenía IVA (1 o true), marcamos la casilla.
              ivaCheckbox.checked = (data.IVA == 1 || data.IVA === true || data.IVA === 't');

              // --- LÓGICA DE CÁLCULO ---
              function actualizarTotalesModificar() {
                  let sumaInputs = 0

                  // 1. Sumamos tal cual lo que el usuario escribió en los inputs
                  const inputsImporte = formModificar.querySelectorAll('input[name^="productos["][name$="[importe]"]')

                  if(isServicio) {
                      inputsImporte.forEach((importeInput) => {
                          sumaInputs += parseFloat(importeInput.value) || 0
                      });
                  } else {
                      const inputsCantidad = formModificar.querySelectorAll('input[name^="productos["][name$="[cantidad]"]')
                      inputsImporte.forEach((importeInput, index) => {
                          const importe = parseFloat(importeInput.value) || 0
                          const cantidad = parseFloat(inputsCantidad[index].value) || 0
                          sumaInputs += importe * cantidad
                      })
                  }

                  // 2. Aplicamos la lógica según el estado del Checkbox
                  const esMasIva = ivaCheckbox.checked; // true = Precios + IVA, false = IVA Incluido

                  let subtotalCalculado = 0;
                  let totalCalculado = 0;

                  if (esMasIva) {
                      // CASO A: El usuario dice "Estos precios son MAS IVA"
                      // Lo que escribió es el Subtotal. El Total aumenta.
                      subtotalCalculado = sumaInputs;
                      totalCalculado = subtotalCalculado * 1.16;
                  } else {
                      // CASO B: El usuario dice "El IVA ya está INCLUIDO"
                      // Lo que escribió es el Total. El Subtotal disminuye.
                      totalCalculado = sumaInputs;
                      subtotalCalculado = totalCalculado / 1.16;
                  }

                  // 3. Renderizamos
                  subtotalEl.textContent = subtotalCalculado.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
                  totalEl.textContent = totalCalculado.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
              }

              // Escuchar cambios en el checkbox para recalcular al instante
              ivaCheckbox.onchange = actualizarTotalesModificar;

              // --- MANEJO DE PROVEEDORES ---
              if (cotizacionesData.length > 1) {
                  // CASO 1: Hay múltiples cotizaciones -> Mostrar Select
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
                          this.cargarCuentasProveedor(idprov); // Nota: Asegúrate de tener esta función en tu objeto o usar `RevisionX().cargarCuentasProveedor`
                      } else {
                          idprov = null;
                          this.cargarCuentasProveedor(null);
                      }
                      this.validarOpcionCredito(data)
                  })

              } else if (cotizacionesData.length === 1) {
                  // CASO 2: Solo una cotización en el array -> Asignar directo
                  idprov = cotizacionesData[0].ID_Proveedor;
                  this.cargarCuentasProveedor(idprov);

              } else if (data.ID_Proveedor) {
                  // CASO 3 (EL ARREGLO): No hay array de cotizaciones, pero la solicitud tiene proveedor directo
                  idprov = data.ID_Proveedor;
                  this.cargarCuentasProveedor(idprov);
              }

              // --- GENERACIÓN DE TABLA DE PRODUCTOS ---
              function actualizarProductos(productos) {
                  let productosHtml = `
            <div class="overflow-x-auto">
              <table class="min-w-full border border-gray-300">
                <thead class="bg-gray-100">
                  <tr>
                    ${!isServicio ? '<th class="py-2 px-4 text-left">Código</th>' : ''}
                    <th class="py-2 px-4 text-left">${isServicio ? 'Servicio' : 'Producto'}</th>
                    ${!isServicio ? '<th class="py-2 px-4 text-right">Cantidad</th>' : ''}
                    <th class="py-2 px-4 text-right">Importe</th>
                  </tr>
                </thead>
                <tbody>
          `
                  productos.forEach((p, index) => {
                      productosHtml += `
                  <tr class="hover:bg-gray-50">
                      ${!isServicio ? `<td class="py-2 px-4 border-t text-right">
                          <input type="text" name="productos[${index}][codigo]" placeholder="N/A" value="${p.Codigo || ''}" class="w-full px-2 py-1 border rounded text-left">
                      </td>` : ''}
                      <td class="py-2 px-4 border-t">
                          <input type="text" name="productos[${index}][nombre]" value="${p.Nombre}" class="w-full px-2 py-1 border rounded text-left">
                      </td>
                      ${!isServicio ? `<td class="py-2 px-4 border-t text-right">
                          <input type="number" name="productos[${index}][cantidad]" value="${p.Cantidad || 1}" min="1" class="w-full px-2 py-1 border rounded text-right producto-cantidad">
                      </td>` : ''}
                      <td class="py-2 px-4 border-t text-right">
                          <input type="number" name="productos[${index}][importe]" value="${parseFloat(p.Importe).toFixed(2)}" step="0.01" min="0" class="w-full px-2 py-1 border rounded text-right producto-importe">
                      </td>
                  </tr>
            `
                  })
                  productosHtml += `</tbody></table></div>`
                  productosContainer.innerHTML = productosHtml

                  const inputs = productosContainer.querySelectorAll('.producto-cantidad, .producto-importe')
                  inputs.forEach((input) => input.addEventListener('input', actualizarTotalesModificar))
              }

              actualizarProductos(data.productos)
              actualizarTotalesModificar() // Calcular inicial

              // --- ENVÍO DE DATOS ---
              formModificar.onsubmit = async (e) => {
                  e.preventDefault()
                  const formData = new FormData(formModificar)
                  const productosModificados = []
                  const commnt = formData.get('comentarios')
                  const idCuenta = formData.get('id_cuenta');

                  // Detectamos modo para saber si hay que "limpiar" el precio antes de enviarlo
                  const esMasIva = ivaCheckbox.checked;
                  let subtotalAcumuladoParaValidacion = 0;

                  data.productos.forEach((p, index) => {
                      if(isServicio) {
                          // Obtener valor del input
                          let importeUsuario = parseFloat(formData.get(`productos[${index}][importe]`)) || 0

                          // Si el usuario dijo "IVA Incluido", le quitamos el 16% para guardar la BASE
                          let importeBase = esMasIva ? importeUsuario : (importeUsuario / 1.16);

                          subtotalAcumuladoParaValidacion += importeBase;

                          productosModificados.push({
                              nombre: formData.get(`productos[${index}][nombre]`),
                              importe: importeBase // Enviamos siempre el precio base
                          });
                      } else {
                          const c = formData.get(`productos[${index}][codigo]`)
                          const cantidad = parseFloat(formData.get(`productos[${index}][cantidad]`)) || 0
                          let importeUsuario = parseFloat(formData.get(`productos[${index}][importe]`)) || 0

                          // Si el usuario dijo "IVA Incluido", le quitamos el 16% para guardar la BASE
                          let importeBase = esMasIva ? importeUsuario : (importeUsuario / 1.16);

                          subtotalAcumuladoParaValidacion += (cantidad * importeBase);

                          productosModificados.push({
                              codigo: c === '' ? null : c,
                              nombre: formData.get(`productos[${index}][nombre]`),
                              cantidad: cantidad,
                              importe: importeBase, // Enviamos siempre el precio base
                          })
                      }
                  })

                  // El total real de la operación siempre lleva IVA (ya sea que se sumó o estaba incluido)
                  const nuevoTotalFinal = subtotalAcumuladoParaValidacion * 1.16;

                  const selectedCotizacionId = document.getElementById('proveedor-select')?.value
                  const selectedCotizacion = selectedCotizacionId
                      ? cotizacionesData.find((cot) => cot.ID_Cotizacion == selectedCotizacionId)
                      : cotizacionesData.length === 1
                          ? cotizacionesData[0]
                          : null

                  const proveedor = selectedCotizacion ? selectedCotizacion.proveedor : data.proveedor || null

                  if (proveedor && proveedor.Monto_Credito && parseFloat(proveedor.Monto_Credito) > 0) {
                      const montoCredito = parseFloat(proveedor.Monto_Credito)
                      if (nuevoTotalFinal > montoCredito) {
                          if (!(await Confirmar('Monto Excedido', `ALERTA: El monto total (${nuevoTotalFinal.toFixed(2)}) excede el límite de crédito del proveedor...`))) {
                              return
                          }
                      }
                  }

                  const payload = {
                      id_solicitud: idSolicitud,
                      id_cotizacion_seleccionada: selectedCotizacionId,
                      productos: productosModificados,
                      comentarios: commnt === '' ? null : commnt,
                      id_cuenta: idCuenta,
                      // IMPORTANTE: Siempre enviamos 1.
                      // ¿Por qué? Porque si estaba desmarcado ("Incluido"), ya desglosamos el precio a Base.
                      // Al guardar Base + IVA=1, el PDF volverá a calcular el total original que puso el usuario.
                      iva: 1
                  }

                  try {
                      const updateResult = await SendDataEnd('api/solicitud/update', { method: 'POST', body: payload })

                      if (updateResult.success) {
                          mostrarNotificacion(updateResult.message || 'Montos actualizados.', 'success')
                          this.cerrarModalModificarMontos(idSolicitud)
                          this.VerDetalle(idSolicitud)
                      } else {
                          mostrarNotificacion(updateResult.message || 'Error al actualizar.', 'error')
                      }
                  } catch (updateError) {
                      console.error(updateError)
                      mostrarNotificacion('Error de red.', 'error')
                  }
              }
          } catch (error) {
              productosContainer.innerHTML = `<p class="text-red-500 text-center">${error.message}</p>`
          }
      },


    cerrarModalModificarMontos: function (idSolicitud) {
      document.getElementById('modal-modificar-montos').classList.add('hidden')
      this.VerDetalle(idSolicitud)
    },
  }
}
