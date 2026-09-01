/**
 * Escapa caracteres especiales de HTML para prevenir ataques XSS.
 * @param {string|number} str - El valor a escapar.
 * @returns {string} - El valor escapado.
 */
function escapeHTML(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/[&<>"']/g, (m) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[m]);
}

/**
 * Formatea un número como moneda (MXN).
 * @param {number|string} valor - El valor a formatear.
 * @returns {string} - El valor formateado.
 */
function formatearMoneda(valor) {
  const numero = parseFloat(valor) || 0;
  return numero.toLocaleString('es-MX', {
    style: 'currency',
    currency: 'MXN',
  });
}

async function SendDataEnd(endpoint, options = {}) {
  const url = `${BASE_URL}${endpoint}`

  // Nano-Loader Start
  const loaderId = 'nano-loader-' + Math.random().toString(36).substr(2, 9);
  const header = document.querySelector('header');
  if (header) {
    const loader = document.createElement('div');
    loader.id = loaderId;
    loader.className = 'absolute top-0 left-0 h-[2px] bg-yellow-500 transition-all duration-300 ease-out z-50';
    loader.style.width = '0%';
    header.style.position = 'relative';
    header.appendChild(loader);
    requestAnimationFrame(() => loader.style.width = '30%');
  }

  const defaultHeaders = {
    'X-Requested-With': 'XMLHttpRequest',
  }

  const config = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  }

  // New: Introduce a delay if specified
  if (options.delay && typeof options.delay === 'number' && options.delay > 0) {
    await new Promise((resolve) => setTimeout(resolve, options.delay))
  }

  if (config.body) {
    if (config.body instanceof FormData) {
      delete config.headers['Content-Type']
    } else if (typeof config.body === 'object' && !(config.body instanceof Blob)) {
      config.body = JSON.stringify(config.body)
      if (!config.headers['Content-Type']) {
        config.headers['Content-Type'] = 'application/json'
      }
    }
  }

  const csrfHeaderName = 'X-CSRF-TOKEN'
  const csrfTokenHash = document.querySelector('meta[name="csrf-token-hash"]')?.content
  const method = (config.method || 'GET').toUpperCase()

  if (method !== 'GET' && csrfHeaderName && csrfTokenHash) {
    config.headers[csrfHeaderName] = csrfTokenHash
  }

  try {
    if (header) {
      const loader = document.getElementById(loaderId);
      if (loader) loader.style.width = '70%';
    }

    const response = await fetch(url, config)

    if (!response.ok) {
      let errorData
      try {
        errorData = await response.clone().json()
      } catch (e) {
        errorData = await response.text()
      }

      const serverMsg = (typeof errorData === 'object' && errorData?.message) ? errorData.message : (typeof errorData === 'string' ? errorData : 'Error en la solicitud HTTP')
      const error = new Error(serverMsg)
      error.status = response.status
      error.statusText = response.statusText
      error.data = errorData
      throw error
    }

    if (header) {
      const loader = document.getElementById(loaderId);
      if (loader) {
        loader.style.width = '100%';
        setTimeout(() => loader.remove(), 200);
      }
    }

    if (response.status === 204) {
      return null
    }

    // Check if a specific responseType is requested
    if (options.responseType === 'text') {
      return await response.text()
    }
    if (options.responseType === 'blob') {
      return await response.blob()
    }

    const contentType = response.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      return await response.json()
    }

    return await response.blob()
  } catch (error) {
    if (header) {
      const loader = document.getElementById(loaderId);
      if (loader) loader.remove();
    }
    console.error(`Fallo en la llamada API a ${endpoint}:`, error)

    throw error
  }
}

function mostrarNotificacion(mensaje, tipo = 'success', duracion = 5000) {
  const CT_ID = '__app_toast_container'
  let container = document.getElementById(CT_ID)

  if (!container) {
    container = document.createElement('div')
    container.id = CT_ID
    Object.assign(container.style, {
      position: 'fixed',
      top: '1rem',
      right: '1rem',
      zIndex: 2147483647,
      display: 'flex',
      flexDirection: 'column',
      gap: '0.5rem',
      alignItems: 'flex-end',
      pointerEvents: 'none',
    })
    document.body.appendChild(container)
  }

  const toast = document.createElement('div')
  toast.setAttribute('role', 'statusus')
  toast.setAttribute('aria-live', 'polite')
  Object.assign(toast.style, {
    pointerEvents: 'auto',
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

  if (tipo === 'success') {
    toast.style.backgroundColor = '#16a34a' // verde
  } else if (tipo === 'error') {
    toast.style.backgroundColor = '#dc2626' // rojo
  } else if (tipo === 'alert') {
    toast.style.backgroundColor = '#FFAB00' // naranja
  } else {
    toast.style.backgroundColor = '#0369a1' // azul/info
  }

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

  const text = document.createElement('div')
  Object.assign(text.style, {
    whiteSpace: 'normal',
    wordBreak: 'break-word',
    flex: '1 1 auto',
  })
  text.textContent = mensaje
  toast.appendChild(text)

  container.appendChild(toast)

  requestAnimationFrame(() => {
    toast.style.transform = 'translateX(0)'
    toast.style.opacity = '1'
  })

  let timeoutId = setTimeout(hide, duracion)

  function hide() {
    clearTimeout(timeoutId)

    toast.style.transform = 'translateX(120%)'
    toast.style.opacity = '0'
    setTimeout(() => {
      toast.remove()

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

  return toast
}
/**
 * Muestra un modal de confirmación personalizado y devuelve una promesa que se resuelve a true si se confirma, o false si se cancela.
 * @param {string} title - El título del modal de confirmación.
 * @param {string} message - El mensaje a mostrar en el modal de confirmación.
 * @returns {Promise<boolean>} - Una promesa que se resuelve a true si el usuario confirma, o false si cancela.
 */
function Confirmar(title, message) {
  return new Promise((resolve) => {
    const modalOverlay = document.createElement('div')
    modalOverlay.className = 'fixed inset-0 bg-gray-200/25 flex items-center justify-center z-50'
    modalOverlay.style.zIndex = '2147483647'

    let modalHtml = `
    <div class="bg-gray-400 rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
      <h3 class="text-lg font-bold mb-4">${title}</h3>
      <p class="mb-4">${message}</p>
      <div class="mt-6 flex justify-end space-x-4">
        <button id="cancelarBtn" class="px-4 py-2 bg-red-500 border border-gray-300 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
        <button id="confirmarBtn" class="px-4 py-2 text-white rounded-md bg-green-600 hover:bg-green-700">Confirmar</button>
      </div>
    </div>
  `
    modalOverlay.innerHTML = modalHtml
    document.body.appendChild(modalOverlay)

    const closeModal = (result) => {
      modalOverlay.remove()
      resolve(result)
    }

    document.getElementById('cancelarBtn').addEventListener('click', () => closeModal(false))
    document.getElementById('confirmarBtn').addEventListener('click', () => closeModal(true))

    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        closeModal(false)
      }
    })
  })
}

/**
 * Muestra un modal con un campo de texto y devuelve una promesa que se resuelve con el texto introducido.
 * @param {string} title - El título del modal.
 * @param {string} message - Un mensaje o etiqueta para el campo de texto.
 * @param {boolean} isRequired - Si el campo de texto es obligatorio.
 * @returns {Promise<string|null>} - Una promesa que se resuelve con el texto introducido, o null si se cancela.
 */
function InputPrompt(title, message, isRequired = true) {
  return new Promise((resolve) => {
    const modalOverlay = document.createElement('div')
    modalOverlay.className =
      'fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50'
    modalOverlay.style.zIndex = '2147483647'

    let modalHtml = `
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-bold mb-4">${title}</h3>
                <label for="promptInput" class="block text-sm font-medium text-gray-700 mb-2">${message}</label>
                <textarea id="promptInput" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <div class="mt-6 flex justify-end space-x-4">
                    <button id="cancelarBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                    <button id="confirmarBtn" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">Aceptar</button>
                </div>
            </div>
        `
    modalOverlay.innerHTML = modalHtml
    document.body.appendChild(modalOverlay)

    const input = document.getElementById('promptInput')
    input.focus()

    const closeModal = (value) => {
      modalOverlay.remove()
      resolve(value)
    }

    document.getElementById('cancelarBtn').addEventListener('click', () => closeModal(null))

    document.getElementById('confirmarBtn').addEventListener('click', () => {
      const value = input.value.trim()
      if (isRequired && !value) {
        mostrarNotificacion('Este campo es obligatorio.', 'error')
        input.focus()
        input.classList.add('border-red-500')
        return
      }
      closeModal(value)
    })

    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        closeModal(null)
      }
    })
  })
}

/**
 * Muestra un modal para capturar la fecha del comprobante de pago (obligatoria).
 * Se usa al intentar guardar el comprobante: si se cancela o no se llena, se resuelve null
 * y el comprobante NO se adjunta.
 * Valor inicial por defecto: la fecha actual (editable por el usuario).
 * @param {string} valorInicial - Fecha inicial (AAAA-MM-DD), opcional. Vacío = fecha de hoy.
 * @returns {Promise<string|null>} - Fecha capturada (AAAA-MM-DD) o null si se cancela.
 */
function PromptFechaComprobante(valorInicial = '') {
  return new Promise((resolve) => {
    const modalOverlay = document.createElement('div')
    modalOverlay.className =
      'fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center z-50'
    modalOverlay.style.zIndex = '2147483647'

    // Valor inicial por defecto: fecha actual en zona local (AAAA-MM-DD), siempre editable.
    if (!valorInicial) {
      const hoy = new Date()
      valorInicial = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`
    }

    let modalHtml = `
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-bold mb-4">Fecha del comprobante</h3>
                <p class="text-sm text-gray-600 mb-4">Indique la fecha que aparece en el comprobante de pago (foto/PDF). Este paso es obligatorio.</p>
                <label for="promptFechaInput" class="block text-sm font-medium text-gray-700 mb-2">Fecha del comprobante</label>
                <input type="date" id="promptFechaInput" value="${valorInicial}"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <div class="mt-6 flex justify-end space-x-4">
                    <button id="cancelarFechaBtn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancelar</button>
                    <button id="confirmarFechaBtn" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">Confirmar</button>
                </div>
            </div>
        `
    modalOverlay.innerHTML = modalHtml
    document.body.appendChild(modalOverlay)

    const input = document.getElementById('promptFechaInput')
    input.focus()

    const closeModal = (value) => {
      modalOverlay.remove()
      resolve(value)
    }

    document.getElementById('cancelarFechaBtn').addEventListener('click', () => closeModal(null))

    document.getElementById('confirmarFechaBtn').addEventListener('click', () => {
      const value = input.value.trim()
      if (!value) {
        mostrarNotificacion('La fecha del comprobante es obligatoria.', 'error')
        input.focus()
        input.classList.add('border-red-500')
        return
      }
      closeModal(value)
    })

    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        closeModal(null)
      }
    })
  })
}

function GetFiles(data) {
  let html = ''
  if (data.OrdenCompra['File_Factura']) {
    const facturas = data.OrdenCompra['File_Factura'].split(',')
    facturas.forEach((factura, index) => {
      const trimmed = factura.trim()
      if (!trimmed) return
      html += `
      <div class="block mb-6 p-4 border rounded-lg">
        <p class="font-medium text-gray-800 mb-1">Factura Adjunta${index > 0 ? ' ' + (index + 1) : ''}</p>
        <a href="${BASE_URL}api/storage/serve?path=facturas/${trimmed}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${trimmed}</a>
      </div>
    `
    })
  }

  if (data.OrdenCompra['File_Comprobante']) {
    html += `
      <div class="block mb-6 p-4 border rounded-lg">
        <p class="font-medium text-gray-800 mb-1">Ficha Adjunta</p>
        <a href="${BASE_URL}api/storage/serve?path=comprobantes/${data.OrdenCompra['File_Comprobante']}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.OrdenCompra['File_Comprobante']}</a>
      </div>
    `
  }

  return html
}
async function getProviderDetails(id) {
  const result = await SendDataEnd(`api/provider/${id}`, { method: 'GET' })
  return result
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
    case 'espera_programacion':
    case 'aprobacion pendiente':
    case 'en espera':
      return 'text-yellow-600'
    case 'en proceso de pago':
    case 'por pagar':
      return 'text-yellow-500'
    default:
      return 'text-gray-600'
  }
}
function getStatusText(status) {
  switch (status) {
    case 'Dept_Rechazada':
      return 'Rechazada'
    case 'Espera_Programacion':
      return 'En espera de programación de pago'
    case 'Por Pagar':
      return 'En espera de factura'
    default:
      return status
  }
}
function GetMetodoPago(metodo) {
  let metodoPago = ''
  switch (metodo) {
    case '0':
      metodoPago = `<div><strong>Metodo de Pago:</strong> Contado</div>`
      return metodoPago
    case '1':
      metodoPago = `<div><strong>Metodo de Pago:</strong> Crédito</div>`
      return metodoPago
    default:
      return ''
  }
}
/**
 * Genera el HTML para mostrar los detalles de una solicitud.
 * @param {object} data - Objeto con los datos de la solicitud.
 * @returns {string} - Cadena de texto con el HTML.
 */
function generarDetallesSolicitudHTML(data) {
  // 1. Formateo y sanitización de datos básicos
  const montoFormateado = formatearMoneda(data.cotizacion?.Total || 0);
  const metodoPago = GetMetodoPago(data.MetodoPago);
  const estadoText = escapeHTML(getStatusText(data.EstadoOrden ?? data.Estado) || 'N/A');
  const estadoClass = getStatus(data.EstadoOrden ?? data.Estado);
  
  // Sanitización de nombres y folios para prevenir XSS
  const folio = escapeHTML(data.No_Folio || 'N/A');
  const solicitante = escapeHTML(data.UsuarioNombre || 'N/A');
  const complejo = escapeHTML(data.Complejo || 'N/A');
  const providerName = escapeHTML(data.cotizacion?.ProveedorNombre || data.RazonSocialNombre || 'N/A');

  // Construcción limpia del nombre del departamento con complejo si existe
  const deptoNombre = escapeHTML(data.DepartamentoNombre || 'N/A');
  const deptoDetalle = data.PlaceNombre ? ` (${escapeHTML(data.PlaceNombre)})` : '';
  const departamentoFull = deptoNombre + deptoDetalle;

  // 2. Pre-procesamiento de fechas para limpieza visual en el template
  const fAprobacion = data.Fecha_Aprobacion
    ? `<div><strong>Fecha de Aprobación:</strong> ${new Date(data.Fecha_Aprobacion).toLocaleString('es-MX')}</div>`
    : '';

  const fOC = data.OrdenCompra?.Fecha 
    ? `<div><strong>Fecha Creación OC:</strong> ${new Date(data.OrdenCompra.Fecha).toLocaleDateString('es-MX')}</div>` 
    : '';
    
  const fRefPago = data.OrdenCompra?.FechaRefPago 
    ? `<div><strong>Fecha Ref. Pago OC:</strong> ${new Date(data.OrdenCompra.FechaRefPago).toLocaleDateString('es-MX')}</div>` 
    : '';
    
  const fPagoReal = data.OrdenCompra?.FechaPagoRealizado 
    ? `<div><strong>Fecha Pago Realizado OC:</strong> ${new Date(data.OrdenCompra.FechaPagoRealizado).toLocaleDateString('es-MX')}</div>` 
    : '';

  // 3. Lógica de visibilidad para WhatsApp: Solo el dueño y en estados no finales
  const finalStates = ['pagada', 'rechazada', 'cancelada'];
  const currentState = (data.EstadoOrden ?? data.Estado ?? '').toLowerCase().trim();
  const isFinalState = finalStates.includes(currentState);
  const isOwner = parseInt(data.ID_Usuario) === parseInt(window.CURRENT_USER_ID);
  const showWhatsApp = isOwner && !isFinalState;

  const isWA = data.notificaciones_whatsapp === 't' || data.notificaciones_whatsapp === true || data.notificaciones_whatsapp == 1;
  
  const whatsappButtonHtml = showWhatsApp ? `
        <!-- Botón WhatsApp arriba a la derecha -->
        <div class="absolute top-2 right-2 flex items-center gap-1">
            <label class="text-[9px] font-bold text-gray-400 uppercase">WhatsApp</label>
            <button onclick="toggleWhatsAppDetails(${data.ID_Solicitud}, this); return false;" 
                    class="p-2 rounded-full hover:bg-gray-200 transition-all active:scale-90"
                    title="${isWA ? 'Desactivar WhatsApp' : 'Activar WhatsApp'}">
                <svg class="size-7 ${isWA ? 'text-yellow-500' : 'text-gray-400'} transition-colors" fill="none" stroke-width="1.5" stroke="currentColor">
                    <use xlink:href="/icons/icons.svg#${isWA ? 'bell-allert' : 'bell'}"></use>
                </svg>
            </button>
        </div>` : '';

  return `
    <div class="relative grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
        ${whatsappButtonHtml}

        <div><strong>Folio:</strong> ${folio}</div>
        <div><strong>Fecha:</strong> ${escapeHTML(data.Fecha)}</div>
        <div><strong>Estado:</strong> <span class="font-semibold ${estadoClass}">${estadoText}</span></div>
        ${fAprobacion}
        <div><strong>Solicitante:</strong> ${solicitante}</div>
        <div><strong>Departamento:</strong> ${departamentoFull}</div>
        <div><strong>Complejo:</strong> ${complejo}</div>
        <div><strong>Proveedor:</strong> ${providerName}</div>
        ${metodoPago}
        ${fOC}
        ${fRefPago}
        ${fPagoReal}
        <div class="md:col-span-3"><strong>Monto Total (Cotización):</strong> <span class="font-bold text-lg">${montoFormateado}</span></div>
    </div>
  `;
}

function generarComentariosHtml(data) {
  let html = ''

  if (data.ComentariosAdmin) {
    let rawComment = data.ComentariosAdmin
    let mensajeMostrar = rawComment
    let nombreAutor = null

    // --- CORRECCIÓN AQUÍ ---
    // 1. Intentamos detectar el formato [Nombre]: Comentario
    const match = rawComment.match(/^\[(.*?)\]:\s*([\s\S]*)/)

    if (match) {
      // Si match[1] está vacío (el error de tu captura "[]"), forzamos 'Admin'
      // Si tiene texto, usamos el nombre real.
      nombreAutor = match[1].trim() ? match[1] : 'Admin'
      mensajeMostrar = match[2] // El mensaje limpio
    }
    // -----------------------

    // BLOQUE 1: RECHAZO
    if (data.TipoComentarioAdmin === 'Rechazo') {
      const titulo = nombreAutor
        ? `Rechazado por ${nombreAutor}, motivo:`
        : 'Comentarios / Motivo del Rechazo'

      html += `
              <div class="mb-6 p-4 border rounded-lg bg-red-50 border-red-200">
                  <h4 class="text-md font-bold text-red-700 mb-2">${titulo}</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${mensajeMostrar}</p>
              </div>`

      // BLOQUE 2: OBSERVACIÓN
    } else if (data.TipoComentarioAdmin === 'Observacion') {
      const titulo = nombreAutor ? `Observación de ${nombreAutor}:` : 'Observación'

      html += `
              <div class="mb-6 p-4 border rounded-lg bg-yellow-50 border-yellow-200">
                  <h4 class="text-md font-bold text-yellow-700 mb-2">${titulo}</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${mensajeMostrar}</p>
              </div>`

      // BLOQUE 3: CANCELACIÓN (Aquí es donde tenías el problema visual)
    } else if (data.TipoComentarioAdmin === 'Cancelacion') {
      const titulo = nombreAutor ? `Cancelado por ${nombreAutor}, motivo:` : 'Motivo de Cancelación'

      html += `
              <div class="mb-6 p-4 border rounded-lg bg-red-50 border-red-200">
                  <h4 class="text-md font-bold text-red-700 mb-2">${titulo}</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${mensajeMostrar}</p>
              </div>`

      // BLOQUE 4: GENÉRICO
    } else {
      html += `
              <div class="mb-6 p-4 border rounded-lg bg-gray-100 border-gray-300">
                  <h4 class="text-md font-bold text-gray-700 mb-2">Comentario</h4>
                  <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosAdmin}</p>
              </div>`
    }
  }
  return html
}

function generarProductosServiciosHTML(data) {
  const iva = data.IVA === 't'
  let html = `
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
                            <th class="py-2 px-4 text-left">Partida</th>
                        </tr>
                    </thead>
                    <tbody>
        `

  data.productos.forEach((p) => {
    const cantidad = parseFloat(p.Cantidad || 1)
    const importe = parseFloat(p.Importe || 0)
    const costoTotal = iva ? 1.16 * (cantidad * importe) : (cantidad * importe)

    // Verificamos si este producto pertenece a un grupo sin presupuesto (solo para dictamen)
    let sinPresupuesto = false;
    if (data.presupuestos_detallados) {
        const pDetalle = data.presupuestos_detallados.find(d => d.ID_GrupoPresupuestal == p.ID_GrupoPresupuestal);
        if (pDetalle && pDetalle.SinPresupuesto) {
            sinPresupuesto = true;
        }
    }
    const rowClass = sinPresupuesto ? 'bg-red-50 blink-row-red' : 'hover:bg-gray-50';

    html += `
                <tr class="${rowClass}">
                    <td class="py-2 px-4 border-t">${p.Codigo || 'N/A'} </td>
                    <td class="py-2 px-4 border-t">
                        ${p.Nombre}
                        ${sinPresupuesto ? '<br><span class="text-[10px] text-red-600 font-bold uppercase">⚠️ Sin presupuesto asignado para este mes</span>' : ''}
                    </td>
                    ${data.Tipo == 2 ? '' : `<td class="py-2 px-4 border-t text-right">${p.Cantidad}</td>`}
                    <td class="py-2 px-4 border-t text-right">$${parseFloat(p.Importe).toFixed(2)}</td>
                    ${iva ? `<td class="py-2 px-4 border-t text-right">$${parseFloat(0.16 * p.Importe).toFixed(2)}</td>` : ''}
                    ${data.Tipo == 2 ? '' : `<td class="py-2 px-4 border-t text-right">$${parseFloat(costoTotal).toFixed(2)}</td>`}
                    <td class="py-2 px-4 border-t text-left">
                        <span class="text-xs font-semibold text-blue-700">${p.GrupoPresupuestalNombre || 'N/A'}</span>
                    </td>
                </tr>
            `
  })

  html += `
                    </tbody>
                </table>
            </div>
        `
  return html
}

/**
 * Genera array para paginación estilo Google con botones de navegación
 * @param {number} currentPage - Página actual (1-indexed)
 * @param {number} totalPages - Total de páginas
 * @param {number} maxVisible - Máximo números visibles (default 7)
 * @returns {Array} Array con objetos: {type: 'number'|'first'|'prev'|'next'|'last'|'...', value: number|null, active: boolean}
 */
function generatePaginationNumbers(currentPage, totalPages, maxVisible = 7) {
  if (totalPages <= 1) return []

  const pages = []

  if (totalPages <= maxVisible) {
    for (let i = 1; i <= totalPages; i++) {
      pages.push({ type: 'number', value: i, active: i === currentPage })
    }
    return pages
  }

  const half = Math.floor(maxVisible / 2)
  let start = Math.max(2, currentPage - half)
  let end = Math.min(totalPages - 1, currentPage + half)

  if (currentPage <= half + 1) {
    start = 2
    end = maxVisible - 1
  }
  if (currentPage > totalPages - half - 1) {
    start = totalPages - maxVisible + 2
    end = totalPages - 1
  }

  pages.push({ type: 'first', value: 1 })
  pages.push({ type: 'prev', value: currentPage > 1 ? currentPage - 1 : null })

  pages.push({ type: 'number', value: 1, active: currentPage === 1 })

  if (start > 2) pages.push({ type: '...' })

  for (let i = start; i <= end; i++) {
    pages.push({ type: 'number', value: i, active: i === currentPage })
  }

  if (end < totalPages - 1) pages.push({ type: '...' })

  pages.push({ type: 'number', value: totalPages, active: currentPage === totalPages })

  pages.push({ type: 'next', value: currentPage < totalPages ? currentPage + 1 : null })
  pages.push({ type: 'last', value: totalPages })

  return pages
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
  } = config

  const tbody = document.querySelector(tableSelector)
  const paginacion = document.getElementById(paginationSelector)
  const filterForm = filterFormSelector ? document.querySelector(filterFormSelector) : null

  if (!tbody) {
    console.error(`Elemento no encontrado: ${tableSelector}`)
    return
  }

  let allData = []
  let currentPage = 1

  async function fetchData() {
    tbody.innerHTML = `<tr><td colspan="100%" class="text-center p-4">${loadingMessage}</td></tr>`
    try {
      const rawData = await SendDataEnd(endpoint, {})
      allData = processData(rawData)
      updateTable()
      if (onDataLoaded) {
        onDataLoaded(allData)
      }
    } catch (error) {
      console.error(error)
      tbody.innerHTML = `<tr><td colspan="100%" class="text-center text-red-500 p-4">${error.message}</td></tr>`
    }
  }

  function renderTable(data) {
    tbody.innerHTML = ''
    if (data.length === 0) {
      tbody.innerHTML = `<tr><td colspan="100%" class="text-center p-4 text-gray-500">${noResultsMessage}</td></tr>`
      return
    }
    data.forEach((item) => {
      tbody.insertAdjacentHTML('beforeend', renderRow(item))
    })
  }

  function renderPagination(totalRows) {
    if (!paginacion) return
    paginacion.innerHTML = ''
    const totalPages = Math.ceil(totalRows / rowsPerPage)
    if (totalPages <= 1) return

    paginacion.style.flexWrap = 'wrap'
    paginacion.style.justifyContent = 'center'
    paginacion.style.gap = '0.5rem'

    const pageData = generatePaginationNumbers(currentPage, totalPages, 7)

    pageData.forEach((item) => {
      const button = document.createElement('button')

      switch (item.type) {
        case 'first':
          button.innerHTML = '&laquo;'
          button.title = 'Primera página'
          button.disabled = currentPage === 1
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (!button.disabled) button.onclick = () => showPage(1, getFilteredData())
          break
        case 'prev':
          button.innerHTML = '&lsaquo;'
          button.title = 'Página anterior'
          button.disabled = !item.value
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (item.value) button.onclick = () => showPage(item.value, getFilteredData())
          break
        case 'next':
          button.innerHTML = '&rsaquo;'
          button.title = 'Página siguiente'
          button.disabled = !item.value
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (item.value) button.onclick = () => showPage(item.value, getFilteredData())
          break
        case 'last':
          button.innerHTML = '&raquo;'
          button.title = 'Última página'
          button.disabled = currentPage === totalPages
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (!button.disabled) button.onclick = () => showPage(totalPages, getFilteredData())
          break
        case '...':
          button.textContent = '...'
          button.className = 'px-2 text-gray-400 cursor-default'
          button.disabled = true
          break
        case 'number':
          button.textContent = item.value
          button.className = `px-3 py-1 border rounded ${item.active ? 'bg-blue-500 text-white' : 'bg-white text-black hover:bg-gray-100'}`
          button.onclick = () => showPage(item.value, getFilteredData())
          break
      }

      paginacion.appendChild(button)
    })
  }

  function showPage(page, filteredData) {
    currentPage = page
    const start = (page - 1) * rowsPerPage
    const end = start + rowsPerPage
    const pageData = filteredData.slice(start, end)
    renderTable(pageData)
    renderPagination(filteredData.length)
  }

  function getFilteredData() {
    if (filterFunction && filterForm) {
      return filterFunction(allData, filterForm)
    }
    return allData
  }

  function updateTable() {
    if (!document.body.contains(tbody)) {
      return
    }
    const filteredData = getFilteredData()
    showPage(1, filteredData)
  }

  if (filterForm) {
    filterForm.addEventListener('input', updateTable)
    filterForm.addEventListener('change', updateTable)
  }

  await fetchData()
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
  } = config

  const allRows = Array.from(document.querySelectorAll(rowsSelector))
  const pagination = document.getElementById(paginationSelector)
  const filterForm = filterFormSelector ? document.querySelector(filterFormSelector) : null

  if (!allRows.length) {
    if (pagination) pagination.innerHTML = ''
    return
  }

  let currentPage = 1
  let filteredRows = [...allRows]

  function applyFilters() {
    if (filterFunction && filterForm) {
      filteredRows = allRows.filter((row) => filterFunction(row, filterForm))
    } else {
      filteredRows = [...allRows]
    }
    showPage(1)
  }

  function showPage(page) {
    currentPage = page
    const start = (page - 1) * rowsPerPage
    const end = start + rowsPerPage

    allRows.forEach((row) => (row.style.display = 'none'))
    filteredRows.slice(start, end).forEach((row) => {
      row.style.display = ''
    })

    renderPagination()
  }

  function renderPagination() {
    if (!pagination) return
    pagination.innerHTML = ''
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage)
    if (totalPages <= 1) {
      pagination.style.display = 'none'
      return
    }

    pagination.style.display = 'flex'
    pagination.style.flexWrap = 'wrap'
    pagination.style.justifyContent = 'center'
    pagination.style.gap = '0.5rem'

    for (let i = 1; i <= totalPages; i++) {
      const button = document.createElement('button')
      button.textContent = i
      button.className = `px-3 py-1 border rounded ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-white text-black'}`
      button.addEventListener('click', (e) => {
        e.preventDefault()
        showPage(i)
      })
      pagination.appendChild(button)
    }
  }

  if (filterForm) {
    filterForm.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault()
      }
    })
    filterForm.addEventListener('input', applyFilters)
    filterForm.addEventListener('change', applyFilters)
  }

  applyFilters()
}

/**
 * loadRazonSocialProv: Función para cargar las opciones de razón social desde la API
 * y agregarlas a un elemento <select> en el DOM.
 */
async function loadRazonSocialProv(selectId) {
  const ProvSelect = document.getElementById(selectId)
  if (!ProvSelect) return

  try {
    const data = await SendDataEnd('api/providers/all')
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
    const data = await SendDataEnd('api/departments/all')
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

const fileAccumulatorState = new WeakMap()

function getFileKey(file) {
  return [file.name, file.size, file.lastModified, file.type].join('::')
}

function syncFileInputFromState(input, state) {
  const dt = new DataTransfer()
  state.files.forEach((file) => dt.items.add(file))
  input.files = dt.files
}

function renderAccumulatedFileSummary(state) {
  if (!state.summaryText) return

  const total = state.files.length
  if (total === 0) {
    state.summaryText.textContent = 'Sin archivos seleccionados'
    return
  }

  if (total === 1) {
    state.summaryText.textContent = state.files[0].name
    return
  }

  state.summaryText.textContent = `${total} archivos seleccionados`
}

function renderAccumulatedFileList(input, state) {
  if (!state.listContainer) return

  state.listContainer.innerHTML = ''

  if (state.files.length === 0) {
    const empty = document.createElement('p')
    empty.className = 'mt-2 text-xs text-gray-500'
    empty.textContent = 'Sin archivos seleccionados.'
    state.listContainer.appendChild(empty)
    renderAccumulatedFileSummary(state)
    return
  }

  const list = document.createElement('ul')
  list.className = 'mt-2 space-y-1'

  state.files.forEach((file, index) => {
    const item = document.createElement('li')
    item.className = 'flex items-center justify-between gap-2 text-xs text-gray-700 bg-gray-50 border border-gray-200 rounded px-2 py-1'

    const label = document.createElement('span')
    label.className = 'truncate'
    label.title = file.name
    label.textContent = file.name

    const removeBtn = document.createElement('button')
    removeBtn.type = 'button'
    removeBtn.className = 'text-red-600 hover:text-red-800 shrink-0'
    removeBtn.textContent = 'Quitar'
    removeBtn.addEventListener('click', () => {
      state.files.splice(index, 1)
      syncFileInputFromState(input, state)
      renderAccumulatedFileList(input, state)
    })

    item.appendChild(label)
    item.appendChild(removeBtn)
    list.appendChild(item)
  })

  state.listContainer.appendChild(list)
  renderAccumulatedFileSummary(state)
}

function setupAccumulatedFileInput(input) {
  if (!input || input.dataset.accumulateInit === '1') return
  input.dataset.accumulateInit = '1'

  const pickerShell = document.createElement('div')
  pickerShell.className = 'mt-1 flex items-center gap-3'

  const pickerButton = document.createElement('button')
  pickerButton.type = 'button'
  pickerButton.className =
    'inline-flex items-center rounded border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50'
  pickerButton.textContent = 'Agregar archivos'

  const summaryText = document.createElement('span')
  summaryText.className = 'text-sm text-gray-600 truncate'
  summaryText.textContent = 'Sin archivos seleccionados'

  pickerButton.addEventListener('click', () => input.click())

  pickerShell.appendChild(pickerButton)
  pickerShell.appendChild(summaryText)

  input.style.display = 'none'
  input.insertAdjacentElement('afterend', pickerShell)

  const listContainer = document.createElement('div')
  listContainer.className = 'archivo-acumulado-lista'
  pickerShell.insertAdjacentElement('afterend', listContainer)

  const state = {
    files: [],
    listContainer,
    summaryText,
  }
  fileAccumulatorState.set(input, state)

  input.addEventListener('change', () => {
    const selected = Array.from(input.files || [])
    const existing = new Set(state.files.map(getFileKey))

    selected.forEach((file) => {
      const key = getFileKey(file)
      if (!existing.has(key)) {
        state.files.push(file)
        existing.add(key)
      }
    })

    syncFileInputFromState(input, state)
    renderAccumulatedFileList(input, state)
  })

  renderAccumulatedFileList(input, state)
}

function clearAccumulatedFileInput(input) {
  const state = fileAccumulatorState.get(input)
  if (!state) return
  state.files = []
  input.value = ''
  syncFileInputFromState(input, state)
  renderAccumulatedFileList(input, state)
}

function initAccumulatedFileInputs(formulario) {
  if (!formulario) return

  const fileInputs = Array.from(formulario.querySelectorAll('input[type="file"][name="archivo[]"]'))
  if (fileInputs.length === 0) return

  fileInputs.forEach((input) => setupAccumulatedFileInput(input))

  if (formulario.dataset.accumulateResetInit === '1') return
  formulario.dataset.accumulateResetInit = '1'

  formulario.addEventListener('reset', () => {
    fileInputs.forEach((input) => clearAccumulatedFileInput(input))
  })
}

function initGlobalAccumulatedFileInputs() {
  const selectors = [
    'input[type="file"][name="archivo[]"]',
    'input[type="file"][name="archivos[]"]',
    'input[type="file"][name="cotizacion_files[]"]',
  ]

  const inputs = document.querySelectorAll(selectors.join(','))
  inputs.forEach((input) => setupAccumulatedFileInput(input))
}

document.addEventListener('DOMContentLoaded', initGlobalAccumulatedFileInputs)

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
    // Inyectar estado de WhatsApp si existe el botón global
    const btnWhatsApp = document.querySelector('.btn-notif-whatsapp-global svg');
    if (btnWhatsApp && btnWhatsApp.classList.contains('text-yellow-500')) {
      formData.append('notificaciones_whatsapp', '1');
    }

    const data = await SendDataEnd('solicitudes/registrar', {
      method: 'POST',
      body: formData,
      headers: { Accept: 'application/json' },
    })

    if (data.success) {
      if (messageContainer) {
        messageContainer.innerHTML = `<p class="text-green-600">${data.message}</p>`
        mostrarNotificacion(data.message, 'success')
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

function getStatusSVG(status) {
  if (!status) return ''
  const statusLower = status.toLowerCase()
  const iconUrl = `icons/icons.svg?v=${window.ICON_SVG_VERSION || '1.0'}`
  let svgClass = ''
  let iconId = ''

  switch (statusLower) {
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
    case 'espera_programacion':
      svgClass = 'text-orange-500'
      iconId = 'espera_programacion'
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
    case 'programada':
      svgClass = 'text-blue-500'
      iconId = 'programado'
      break
    case 'dept_rechazada':
      svgClass = 'text-red-500'
      iconId = 'rechazado'
      break
    default:
      return ''
  }
  return `<svg class="${svgClass} mx-auto size-6" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#${iconId}"></use></svg>`
}
function getMetodoPago(metodo) {
  switch (metodo) {
    case '0':
      return 'Contado'
    case '1':
      return 'Credito'
    default:
      return 'No asignado'
  }
}

//*** Función del manejador de archivos (mostrar archivos adjuntos)  ***//

/**
 * Genera la sección de archivos adjuntos (PDFs, facturas, comprobantes, etc.)
 * @param {object} data - Datos de la solicitud y orden de compra.
 * @returns {string} - HTML de la sección de adjuntos.
 */
function generarSeccionAdjuntos(data) {
  const sol = data.solicitud || data;
  const ordenObj = data.OrdenCompra || data.orden_compra || data.orden || {};
  const cotizacionObj = data.cotizacion || {};
  
  const idSolicitud = sol.ID_Solicitud || data.ID_Solicitud;
  const folio = escapeHTML(sol.No_Folio || data.No_Folio || idSolicitud);
  const existeOrden = ordenObj && Object.keys(ordenObj).length > 0;

  /**
   * Helper interno para generar el HTML de un enlace a archivo de forma segura.
   */
  const crearLinkArchivo = (label, url, fileName, isMissing = false) => {
    if (isMissing || !fileName) {
      return `<div class="text-gray-400"><strong>${escapeHTML(label)}:</strong> No adjuntada</div>`;
    }
    return `
      <div>
        <strong>${escapeHTML(label)}:</strong> 
        <a href="${url}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline"> 
          ${escapeHTML(fileName)}
        </a>
      </div>`;
  };

  /**
   * Helper para procesar strings de archivos separados por comas.
   */
  const procesarArchivosMultiples = (fileString, labelBase, urlBuilder) => {
    if (!fileString) return '';
    return fileString.split(',')
      .map(f => f.trim())
      .filter(f => f)
      .map((f, i) => {
        const label = labelBase + (i > 0 ? ` ${i + 1}` : '');
        return crearLinkArchivo(label, urlBuilder(f), f);
      }).join('');
  };

  // --- 1. Requisición y Archivos de Referencia ---
  let htmlAdjuntos = idSolicitud ? crearLinkArchivo('Solicitud (PDF)', `${BASE_URL}api/solicitud/pdf/${idSolicitud}`, `Requisicion-${folio}.pdf`) : '';
  
  htmlAdjuntos += procesarArchivosMultiples(sol.Archivo, 'Referencia', (f) => `${BASE_URL}solicitudes/archivo/${idSolicitud}/${f}`);

  // --- 2. Cotización ---
  const cotizacionFile = cotizacionObj.Cotizacion_Files || data.Cotizacion_Files;
  const fechaCarpeta = (sol.Fecha ? sol.Fecha.split(' ')[0] : data.Fecha) || 'default';
  
  if (cotizacionFile) {
    htmlAdjuntos += procesarArchivosMultiples(cotizacionFile, 'Cotizacion', (f) => `${BASE_URL}api/storage/serve?path=cotizaciones/${fechaCarpeta}/${f}`);
  } else {
    htmlAdjuntos += crearLinkArchivo('Cotizacion', null, null, true);
  }

  // --- 3. Documentos de la Orden de Compra ---
  if (folio && existeOrden) {
    htmlAdjuntos += crearLinkArchivo('Orden de Compra', `${BASE_URL}api/storage/serve?path=pdf_ordenes/OrdenCompra-${folio}.pdf`, `OrdenCompra-${folio}.pdf`);
    htmlAdjuntos += crearLinkArchivo('Requisición de Pago', `${BASE_URL}api/requisicionpago/pdf/${idSolicitud}`, `RequisicionPago-${folio}.pdf`);
  }

  // --- 4. Ficha, Factura y Complemento ---
  const comprobanteFile = ordenObj.File_Comprobante || data.File_Comprobante || ordenObj.comprobante || data.comprobante;
  htmlAdjuntos += crearLinkArchivo('Ficha de pago', `${BASE_URL}api/storage/serve?path=comprobantes/${comprobanteFile}`, comprobanteFile, !comprobanteFile);

  const complementoFile = ordenObj.File_Complemento || data.File_Complemento || ordenObj.complemento || data.complemento;
  if (sol.MetodoPago == '1' && complementoFile) {
    htmlAdjuntos += crearLinkArchivo('Complemento de Pago', `${BASE_URL}api/storage/serve?path=complementos/${complementoFile}`, complementoFile);
  }

  const facturaFile = ordenObj.File_Factura || data.File_Factura || ordenObj.factura || data.factura;
  if (facturaFile) {
    htmlAdjuntos += procesarArchivosMultiples(facturaFile, 'Factura', (f) => `${BASE_URL}api/storage/serve?path=facturas/${f}`);
  } else {
    htmlAdjuntos += crearLinkArchivo('Factura', null, null, true);
  }

  return `
    <div class="mt-6">
        <h4 class="text-md font-bold mb-3 text-gray-700">Adjuntos</h4>
        <div class="flex flex-col space-y-2 mb-6 p-4 border rounded-lg bg-gray-50 text-sm text-left">
            ${htmlAdjuntos}
        </div>
        
        <div class="mt-4 mb-4 flex justify-start space-x-2">
            <button onclick="mostrarExpedientePdf(${idSolicitud})" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md transition text-sm">
                Ver Expediente (PDF)
            </button>
            <a href="${BASE_URL}api/download-attachments/${idSolicitud}" class="bg-carbon-700 hover:bg-carbon-800 text-white font-semibold px-4 py-2 rounded-md transition text-sm">
                Descargar Todo (ZIP)
            </a>
        </div>
    </div>
  `;
}

function mostrarExpedientePdf(idSolicitud) {
  const url = `${BASE_URL}api/solicitud/pdf-consolidado/${idSolicitud}`
  window.open(url, '_blank')
}

/**
 * Toglea el estado de notificaciones de WhatsApp para una solicitud específica (Detalles)
 */
async function toggleWhatsAppDetails(idSolicitud, btn) {
  try {
    const response = await fetch(`${BASE_URL}api/solicitud/toggle-whatsapp`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ ID_Solicitud: idSolicitud }),
    })

    const data = await response.json()

    if (data.success) {
      const svg = btn.querySelector('svg')
      const use = svg.querySelector('use')
      const isEnabled = data.enabled

      if (isEnabled) {
        svg.classList.remove('text-gray-400')
        svg.classList.add('text-yellow-500')
        use.setAttribute('xlink:href', `/icons/icons.svg#bell-allert`)
        btn.title = 'Desactivar WhatsApp'
      } else {
        svg.classList.remove('text-yellow-500')
        svg.classList.add('text-gray-400')
        use.setAttribute('xlink:href', `/icons/icons.svg#bell`)
        btn.title = 'Activar WhatsApp'
      }

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: data.message,
          showConfirmButton: false,
          timer: 2000,
        })
      }
    } else {
      if (typeof Swal !== 'undefined') {
        Swal.fire('Error', data.message || 'Error al cambiar estado', 'error')
      } else {
        alert(data.message || 'Error al cambiar estado')
      }
    }
  } catch (error) {
    console.error('Error toggling WhatsApp:', error)
  }
}

/**
 * Toglea el estado visual de WhatsApp en el modal de creación (Global)
 */
function toggleWhatsAppGlobal(btn) {
  const btns = document.querySelectorAll('.btn-notif-whatsapp-global')
  const isEnabled = btn.querySelector('svg').classList.contains('text-yellow-500')

  btns.forEach((b) => {
    const svg = b.querySelector('svg')
    const use = svg.querySelector('use')
    if (isEnabled) {
      svg.classList.remove('text-yellow-500')
      svg.classList.add('text-gray-400')
      use.setAttribute('xlink:href', '/icons/icons.svg#bell')
      b.title = 'Activar WhatsApp'
    } else {
      svg.classList.remove('text-gray-400')
      svg.classList.add('text-yellow-500')
      use.setAttribute('xlink:href', '/icons/icons.svg#bell-allert')
      b.title = 'Desactivar WhatsApp'
    }
  })
}

/**
 * Crea una tabla paginada con datos obtenidos del servidor (paginación server-side).
 * Escalable a miles de registros sin límite.
 * @param {object} config
 * @param {string} config.tableSelector - Selector del tbody de la tabla.
 * @param {string} config.paginationSelector - Selector del contenedor de la paginación.
 * @param {string} config.endpoint - URL base de la API (ej: '/api/historic/paginated').
 * @param {function} config.renderRow - Función que recibe un item y devuelve el HTML de la fila (tr).
 * @param {number} [config.rowsPerPage=10] - Filas por página.
 * @param {string} [config.filterFormSelector] - Selector del formulario de filtros.
 * @param {function} [config.buildFilterParams] - Función que recibe el form y devuelve objeto con params extra.
 * @param {string} [config.loadingMessage='Cargando...'] - Mensaje de carga.
 * @param {string} [config.noResultsMessage='No se encontraron resultados.'] - Mensaje sin resultados.
 * @param {function} [config.onDataLoaded] - Callback que se ejecuta después de cargar y renderizar los datos.
 */
function createPaginatedTableServer(config) {
  const table = document.querySelector(config.tableSelector)
  const paginationEl = document.querySelector(config.paginationSelector)
  const rowsPerPage = config.rowsPerPage || 10
  const filterForm = config.filterFormSelector ? document.querySelector(config.filterFormSelector) : null

  let currentPage = 1
  let totalPages = 0
  let currentData = []
  let isLoading = false

  function buildUrl(page) {
    const params = new URLSearchParams()
    params.set('page', page)
    params.set('per_page', rowsPerPage)

    if (filterForm && config.buildFilterParams) {
      const extra = config.buildFilterParams(filterForm)
      for (const [key, value] of Object.entries(extra)) {
        if (value !== null && value !== undefined && value !== '') {
          if (Array.isArray(value)) {
            value.forEach(v => params.append(key + '[]', v))
          } else {
            params.set(key, value)
          }
        }
      }
    }

    return config.endpoint + '?' + params.toString()
  }

  async function loadPage(page) {
    if (isLoading) return
    isLoading = true

    if (config.loadingMessage !== false) {
      table.innerHTML = `<tr><td colspan="100" class="text-center py-4">${config.loadingMessage || 'Cargando...'}</td></tr>`
    }

    try {
      const url = buildUrl(page)
      const response = await fetch(url)
      const result = await response.json()

      currentData = result.data || []
      currentPage = result.page || page
      totalPages = Math.ceil((result.total || 0) / rowsPerPage)

      renderTable(currentData)
      renderPagination(totalPages, currentPage)

      if (config.onDataLoaded) config.onDataLoaded(currentData)
    } catch (err) {
      console.error('Error loading page:', err)
      table.innerHTML = `<tr><td colspan="100" class="text-center py-4 text-red-500">Error al cargar datos</td></tr>`
    } finally {
      isLoading = false
    }
  }

  function renderTable(data) {
    if (!data || data.length === 0) {
      table.innerHTML = `<tr><td colspan="100" class="text-center py-4">${config.noResultsMessage || 'No se encontraron resultados.'}</td></tr>`
      return
    }

    table.innerHTML = data.map(item => config.renderRow(item)).join('')
  }

  function renderPagination(totalPages, currentPage) {
    if (!paginationEl) return
    paginationEl.innerHTML = ''
    if (totalPages <= 1) return

    paginationEl.style.flexWrap = 'wrap'
    paginationEl.style.justifyContent = 'center'
    paginationEl.style.gap = '0.5rem'

    const pages = generatePaginationNumbers(currentPage, totalPages, 7)

    pages.forEach((item) => {
      const button = document.createElement('button')

      switch (item.type) {
        case 'first':
          button.innerHTML = '&laquo;'
          button.title = 'Primera página'
          button.disabled = currentPage === 1
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (!button.disabled) button.onclick = () => loadPage(1)
          break
        case 'prev':
          button.innerHTML = '&lsaquo;'
          button.title = 'Página anterior'
          button.disabled = !item.value
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (item.value) button.onclick = () => loadPage(item.value)
          break
        case 'next':
          button.innerHTML = '&rsaquo;'
          button.title = 'Página siguiente'
          button.disabled = !item.value
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (item.value) button.onclick = () => loadPage(item.value)
          break
        case 'last':
          button.innerHTML = '&raquo;'
          button.title = 'Última página'
          button.disabled = currentPage === totalPages
          button.className =
            'px-2 py-1 border rounded bg-white text-black hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed'
          if (!button.disabled) button.onclick = () => loadPage(totalPages)
          break
        case '...':
          button.textContent = '...'
          button.className = 'px-2 text-gray-400 cursor-default'
          button.disabled = true
          break
        case 'number':
          button.textContent = item.value
          button.className = `px-3 py-1 border rounded ${item.active ? 'bg-blue-500 text-white' : 'bg-white text-black hover:bg-gray-100'}`
          button.onclick = () => loadPage(item.value)
          break
      }

      paginationEl.appendChild(button)
    })
  }

  // Public method to reload (e.g., after filter change)
  function reload() {
    currentPage = 1
    loadPage(1)
  }

  // Expose reload for external calls
  config.reload = reload

  // Watch filter changes with simple debounce
  let filterTimer = null
  if (filterForm) {
    const handleFilterChange = () => {
      if (filterTimer) clearTimeout(filterTimer)
      filterTimer = setTimeout(reload, 300)
    }
    filterForm.addEventListener('input', handleFilterChange)
    filterForm.addEventListener('change', handleFilterChange)
  }

  // Initial load
  loadPage(currentPage)
}

// Export escapeHTML for global use (used by mbscript.js)
window.escapeHTML = escapeHTML;
