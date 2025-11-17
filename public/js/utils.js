async function SendDataEnd(endpoint, options = {}) {
  const url = `${BASE_URL}${endpoint}`

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

  const csrfHeaderName = document.querySelector('meta[name="csrf-token-name"]')?.content
  const csrfTokenHash = document.querySelector('meta[name="csrf-token-hash"]')?.content
  const method = (config.method || 'GET').toUpperCase()

  if (method !== 'GET' && csrfHeaderName && csrfTokenHash) {
    config.headers[csrfHeaderName] = csrfTokenHash
  }

  try {
    const response = await fetch(url, config)

    if (!response.ok) {
      let errorData
      try {
        errorData = await response.json()
      } catch (e) {
        errorData = await response.text()
      }

      const error = new Error('Error en la solicitud HTTP')
      error.status = response.status
      error.statusText = response.statusText
      error.data = errorData
      throw error
    }

    if (response.status === 204) {
      return null
    }

    const contentType = response.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      return await response.json()
    }

    return await response.blob()
  } catch (error) {
    console.error(`Fallo en la llamada API a ${endpoint}:`, error)

    throw error
  }
}

function mostrarNotificacion(mensaje, tipo = 'success', duracion = 3000) {
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
function GetFiles(data) {
  let html = ''
  if (data.OrdenCompra['File_Factura']) {
    html += `
      <div class="block mb-6 p-4 border rounded-lg">
        <p class="font-medium text-gray-800 mb-1">Factura Adjunta</p>
        <a href="${BASE_URL}api/storage/serve?path=facturas/${data.OrdenCompra['File_Factura']}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">${data.OrdenCompra['File_Factura']}</a>
      </div>
    `
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
    case 'en proceso de pago':
    case 'por pagar':
      return 'text-yellow-500'
    default:
      return 'text-gray-600'
  }
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
  const fechaAprobacionHTML = data.Fecha_Aprobacion
    ? `<div><strong>Fecha de Aprobación:</strong> ${new Date(data.Fecha_Aprobacion).toLocaleString('es-MX')}</div>`
    : ''

  return `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 border rounded-lg bg-gray-50">
            <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
            <div><strong>Fecha:</strong> ${data.Fecha}</div>
            <div><strong>Estado:</strong> <span class="font-semibold text-blue-600">${data.Estado}</span></div>
            ${fechaAprobacionHTML}
            <div><strong>Usuario:</strong> ${data.UsuarioNombre}</div>
            <div><strong>Departamento:</strong> ${data.DepartamentoNombre + ' - ' + data.ID_Place}</div>
            <div><strong>Complejo:</strong> ${data.Complejo}</div>
            <div><strong>Proveedor:</strong> ${data.cotizacion?.ProveedorNombre || 'N/A'}</div>
            <div><strong>Metodo de Pago:</strong> ${metodoPago}</div>
            <div class="md:col-span-3"><strong>Monto Total (Cotización):</strong> <span class="font-bold text-lg">${montoFormateado}</span></div>
        </div>
    `
}
