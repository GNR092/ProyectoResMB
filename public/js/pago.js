/**
 * Lógica para el modal "Programar Pagos"
 * Gestiona la selección y programación de pagos para órdenes de contado y crédito.
 */
function Pagos() {
  return {
    // --- ESTADO ---
    screen: 'menu',
    previousScreen: 'menu',
    ordenesContado: [],
    ordenesCredito: [],
    detalleOrden: null,
    selectedOrdenes: [],
    loading: true,
    loadingDetalle: false,

    // --- PAGINACIÓN ---
    itemsPerPage: 10,
    pageContado: 1,
    pageCredito: 1,

    /**
     * Calcula el importe total de las órdenes seleccionadas en la vista actual.
     */
    get totalSeleccionado() {
      const listaActual = this.screen === 'contado' ? this.ordenesContado : this.ordenesCredito;
      if (!listaActual || listaActual.length === 0) return 0;

      return listaActual
        .filter(o => this.selectedOrdenes.includes(o.ID_Solicitud))
        .reduce((sum, o) => sum + parseFloat(o.Total || 0), 0);
    },

    /**
     * Carga y procesa las órdenes desde la API.
     * Realiza el filtrado y la semaforización de crédito en un solo paso.
     */
    async cargardatos() {
      this.loading = true;
      this.selectedOrdenes = [];
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);

      try {
        const data = await SendDataEnd('api/ordenes-programar');
        if (!data || data.length === 0) {
          this.ordenesContado = [];
          this.ordenesCredito = [];
          return;
        }

        const contado = [];
        const credito = [];

        data.forEach(orden => {
          // Normalización de la fecha de aprobación (prioridad definida por negocio)
          const fechaAprobacion = orden.FechaRefPago || orden.FechaOrden || orden.Fecha;
          const ordenProcesada = { ...orden, FechaAprobacion: fechaAprobacion };

          if (String(orden.MetodoPago) === '0') {
            contado.push(ordenProcesada);
          } else if (String(orden.MetodoPago) === '1') {
            // Lógica de semaforización para Crédito
            let claseColor = 'hover:bg-gray-50 transition';
            let diasRestantes = 9999;

            if (fechaAprobacion) {
              const [anio, mes, dia] = fechaAprobacion.split(' ')[0].split('-').map(Number);
              if (!isNaN(anio)) {
                const fechaVencimiento = new Date(anio, mes - 1, dia);
                const diasCredito = parseInt(orden.Dias_Credito) || 0;
                fechaVencimiento.setDate(fechaVencimiento.getDate() + diasCredito);

                const diffTime = fechaVencimiento.getTime() - hoy.getTime();
                diasRestantes = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                // Determinación del color según la urgencia del pago
                if (diasRestantes < 0) claseColor = 'bg-gray-800 text-white hover:bg-gray-700 transition';
                else if (diasRestantes < 5) claseColor = 'bg-red-100 hover:bg-red-200 transition';
                else if (diasRestantes < 15) claseColor = 'bg-yellow-100 hover:bg-yellow-200 transition';
              }
            }
            credito.push({ ...ordenProcesada, claseColor, _sortValue: diasRestantes });
          }
        });

        this.ordenesContado = contado;
        // Ordenamos crédito por fecha de vencimiento (más urgentes primero)
        this.ordenesCredito = credito.sort((a, b) => a._sortValue - b._sortValue);

        this.pageContado = 1;
        this.pageCredito = 1;
      } catch (error) {
        console.error('Error al cargar órdenes:', error);
        mostrarNotificacion('Error de conexión al cargar datos.', 'error');
      } finally {
        this.loading = false;
      }
    },

    // --- LÓGICA DE PAGINACIÓN UNIFICADA ---
    get paginatedContado() { return this.getPaginated('contado'); },
    get paginatedCredito() { return this.getPaginated('credito'); },
    get totalPagesContado() { return this.getTotalPages('contado'); },
    get totalPagesCredito() { return this.getTotalPages('credito'); },
    get pageNumbersContado() { return generatePaginationNumbers(this.pageContado, this.totalPagesContado, 7); },
    get pageNumbersCredito() { return generatePaginationNumbers(this.pageCredito, this.totalPagesCredito, 7); },

    getPaginated(type) {
      const list = type === 'contado' ? this.ordenesContado : this.ordenesCredito;
      const page = type === 'contado' ? this.pageContado : this.pageCredito;
      const start = (page - 1) * this.itemsPerPage;
      return list.slice(start, start + this.itemsPerPage);
    },

    getTotalPages(type) {
      const list = type === 'contado' ? this.ordenesContado : this.ordenesCredito;
      return Math.ceil(list.length / this.itemsPerPage) || 1;
    },

    goToPage(type, page) {
      const total = this.getTotalPages(type);
      if (page < 1 || page > total) return;
      if (type === 'contado') this.pageContado = page;
      else this.pageCredito = page;
    },

    // Métodos para compatibilidad con el HTML existente
    goToPageContado(p) { this.goToPage('contado', p); },
    goToPageCredito(p) { this.goToPage('credito', p); },
    firstPageContado() { this.goToPage('contado', 1); },
    lastPageContado() { this.goToPage('contado', this.totalPagesContado); },
    firstPageCredito() { this.goToPage('credito', 1); },
    lastPageCredito() { this.goToPage('credito', this.totalPagesCredito); },

    changePage(type, direction) {
      const current = type === 'contado' ? this.pageContado : this.pageCredito;
      this.goToPage(type, direction === 'next' ? current + 1 : current - 1);
    },

    // --- SELECCIÓN ---
    /**
     * Selecciona o deselecciona todos los elementos visibles en la página actual.
     */
    toggleSelectAll(event, type) {
      const pageIds = this.getPaginated(type).map(o => o.ID_Solicitud);

      if (event.target.checked) {
        this.selectedOrdenes = [...new Set([...this.selectedOrdenes, ...pageIds])];
      } else {
        this.selectedOrdenes = this.selectedOrdenes.filter(id => !pageIds.includes(id));
      }
    },

    isPageSelected(type) {
      const list = this.getPaginated(type);
      return list.length > 0 && list.every(o => this.selectedOrdenes.includes(o.ID_Solicitud));
    },

    // --- ACCIONES ---
    async programarPago() {
      if (this.selectedOrdenes.length === 0) {
        mostrarNotificacion('Debe seleccionar al menos una orden para programar.', 'warning');
        return;
      }

      const confirm = await Confirmar('Programar Pagos', `¿Desea programar ${this.selectedOrdenes.length} pago(s)?`);
      if (!confirm) return;

      try {
        const result = await SendDataEnd('api/orden/programar-pagos', {
          method: 'POST',
          body: { ids: this.selectedOrdenes },
        });

        if (result.success) {
          mostrarNotificacion(result.message, 'success');
          await this.cargardatos();
        } else {
          mostrarNotificacion(result.message || 'Error al programar pagos.', 'error');
        }
      } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de red al procesar.', 'error');
      }
    },

    mostrarDetalle(id, metodoPago) {
      this.loadingDetalle = true;
      this.previousScreen = String(metodoPago) === '0' ? 'contado' : 'credito';
      this.screen = 'detalle';

      SendDataEnd(`api/orden-compra/details/${id}`)
        .then(data => {
          this.detalleOrden = data;
        })
        .catch(error => {
          console.error('Error detalle:', error);
          mostrarNotificacion('No se pudo cargar el detalle.', 'error');
          this.screen = this.previousScreen;
        })
        .finally(() => {
          this.loadingDetalle = false;
        });
    },

    async volverATabla() {
      this.screen = this.previousScreen;
      this.detalleOrden = null;
      await this.cargardatos();
    },

    // --- FORMATEO ---
    formatCurrency(v) {
      if (v === null || isNaN(v)) return 'N/A';
      return parseFloat(v).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    },

    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      return isNaN(date.getTime()) ? '-' : date.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
    },

    /**
     * Genera el HTML dinámico para la vista de detalle de la orden.
     * Centraliza la lógica de renderizado en un solo lugar.
     */
    generarDetalleHtml() {
      if (!this.detalleOrden) return '';
      const data = this.detalleOrden;
      const prov = data.proveedor || {};
      const format = val => this.formatCurrency(val);

      const totalCotizacion = format(data.cotizacion?.Total);
      const tieneIVA = data.IVA == 1 || data.IVA === 't' || data.IVA === true;
      const isServicio = data.Tipo == 2;

      const fechaAprobacion = data.OrdenCompra && data.OrdenCompra.Fecha
        ? new Date(data.OrdenCompra.Fecha).toLocaleDateString('es-MX')
        : 'Pendiente';

      const metodoPagoTexto = String(data.MetodoPago) === '0' ? 'Contado' : 'Crédito';

      // Generación de filas de productos
      const productosHtml = (data.productos || []).map(p => {
        const cant = isServicio ? 1 : parseFloat(p.Cantidad) || 0;
        const base = parseFloat(p.Importe) || 0;
        const subtotal = cant * base;
        const iva = tieneIVA ? subtotal * 0.16 : 0;
        return `
          <tr class="hover:bg-gray-50">
            <td class="py-2 px-4 border-t text-sm text-gray-500">${!isServicio ? p.Codigo || '' : 'N/A'}</td>
            <td class="py-2 px-4 border-t text-sm text-gray-900">${p.Nombre}</td>
            <td class="py-2 px-4 border-t text-right text-sm">${cant}</td>
            <td class="py-2 px-4 border-t text-right text-sm">${format(base)}</td>
            <td class="py-2 px-4 border-t text-right text-sm">${format(iva)}</td>
            <td class="py-2 px-4 border-t text-right text-sm font-bold text-gray-900">${format(subtotal + iva)}</td>
          </tr>`;
      }).join('') || '<tr><td colspan="6" class="text-center py-3 text-gray-500">Sin productos.</td></tr>';

      const infoCredito = String(data.MetodoPago) === '1' ? `
        <div><strong>Días de crédito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
        <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${format(prov.Monto_Credito)}</div>` : '';

      return `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
          <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
          <div><strong>Fecha solicitud:</strong> ${data.Fecha || 'N/A'}</div>
          <div><strong>Fecha aprobación:</strong> ${fechaAprobacion}</div>
          <div><strong>Departamento:</strong> ${data.DepartamentoNombre || 'N/A'}</div>
          <div><strong>Proyecto:</strong> ${data.Complejo || 'N/A'}</div>
          <div><strong>Importe Total:</strong> <span class="font-bold text-lg text-blue-700">${totalCotizacion}</span></div>
          <div><strong>Método de pago:</strong> ${metodoPagoTexto}</div>
        </div>

        <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">INFORMACIÓN DEL PROVEEDOR</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
          <div><strong>Razón social:</strong> ${prov.RazonSocial || 'N/A'}</div>
          <div><strong>RFC:</strong> ${prov.RFC || 'N/A'}</div>
          <div><strong>Banco del proveedor:</strong> ${prov.Banco || 'N/A'}</div>
          ${data.cuenta_details 
            ? `<div><strong>Cuenta seleccionada:</strong> ${data.cuenta_details.Cuenta}</div>`
            : `<div><strong>Cuenta del proveedor:</strong> ${prov.Cuenta || 'N/A'}</div>
               <div><strong>Clabe interbancaria:</strong> ${prov.Clabe || 'N/A'}</div>`
          }
          ${infoCredito}
        </div>

        <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">PRODUCTOS DE LA ORDEN</h3>
        <div class="overflow-x-auto mb-6 border rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cant.</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Base</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">IVA</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">${productosHtml}</tbody>
          </table>
        </div>
        ${typeof generarSeccionAdjuntos === 'function' ? generarSeccionAdjuntos({ ...data, ID_Solicitud: data.ID_Solicitud || data.ID_Orden }) : ''}
        <div class="flex justify-end mt-6 pt-4 border-t">
          <button onclick="globalCancelarSolicitud(${data.ID_Solicitud || data.ID_Orden}, () => document.getElementById('btn-volver-pagos').click())" 
                  class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition shadow-sm">
            Rechazar / Cancelar Pago
          </button>
        </div>`;
    },
  }
}

/**
 * Lógica para el modal "Lista de pagos pendientes"
 */

/**
 * Lógica para el modal "Lista de pagos pendientes"
 * Administra la visualización, filtrado y paginación de los pagos ya programados.
 */
function ListaPagos() {
  return {
    // --- ESTADO ---
    pagos: [],
    loading: true,
    filtroMetodoPago: 'todos',
    filtroSearch: '',
    filtroDepto: '',
    filtroRazonSocial: '',
    currentPage: 1,
    rowsPerPage: 10,

    // Listas para los selectores de filtro (se llenan al cargar datos)
    deptosDisponibles: [],
    razonesDisponibles: [],

    /**
     * Conteos rápidos derivados de la lista base.
     */
    get conteoContado() {
      return this.pagos.filter((p) => String(p.MetodoPago) === '0').length;
    },
    get conteoCredito() {
      return this.pagos.filter((p) => String(p.MetodoPago) === '1').length;
    },

    /**
     * Filtra la lista de pagos en una sola pasada para maximizar el rendimiento.
     */
    get pagosFiltrados() {
      const query = this.filtroSearch.toLowerCase().trim();

      return this.pagos.filter((p) => {
        // 1. Filtro por Método de Pago
        if (this.filtroMetodoPago !== 'todos' && String(p.MetodoPago) !== String(this.filtroMetodoPago)) {
          return false;
        }

        // 2. Filtro por Búsqueda (Folio o Proveedor)
        if (query) {
          const folio = (p.No_Folio || '').toLowerCase();
          const prov = (p.Proveedor || '').toLowerCase();
          if (!folio.includes(query) && !prov.includes(query)) return false;
        }

        // 3. Filtro por Departamento
        if (this.filtroDepto && p.Departamento !== this.filtroDepto) {
          return false;
        }

        // 4. Filtro por Razón Social
        if (this.filtroRazonSocial && p.RazonSocial !== this.filtroRazonSocial) {
          return false;
        }

        return true;
      });
    },

    // --- PAGINACIÓN ---
    get totalPages() {
      return Math.ceil(this.pagosFiltrados.length / this.rowsPerPage) || 1;
    },

    get paginatedPagos() {
      const start = (this.currentPage - 1) * this.rowsPerPage;
      return this.pagosFiltrados.slice(start, start + this.rowsPerPage);
    },

    get pageNumbers() {
      return generatePaginationNumbers(this.currentPage, this.totalPages, 7);
    },

    goToPage(page) {
      if (page < 1 || page > this.totalPages) return;
      this.currentPage = page;
    },

    // Métodos de navegación simplificados
    prevPage() { this.goToPage(this.currentPage - 1); },
    nextPage() { this.goToPage(this.currentPage + 1); },
    firstPage() { this.goToPage(1); },
    lastPage() { this.goToPage(this.totalPages); },

    /**
     * Inicializa el componente cargando los datos y preparando los filtros.
     */
    async init() {
      this.loading = true;
      this.pagos = [];
      this.currentPage = 1;
      const root = this.$el;

      try {
        // Evitamos cache con timestamp
        const url = `api/pagos/programados?t=${Date.now()}`;
        const data = await SendDataEnd(url);

        // Verificamos que el componente siga montado antes de actualizar estado
        if (!document.body.contains(root)) return;

        if (Array.isArray(data)) {
          this.pagos = data;
          // Pre-calculamos las listas únicas para los filtros para evitar re-cálculos en cada render
          this.deptosDisponibles = [...new Set(data.map(p => p.Departamento))].filter(Boolean).sort();
          this.razonesDisponibles = [...new Set(data.map(p => p.RazonSocial))].filter(Boolean).sort();
        }
      } catch (error) {
        console.error('Error al cargar pagos programados:', error);
        if (document.body.contains(root)) this.pagos = [];
      } finally {
        if (document.body.contains(root)) this.loading = false;
      }
    },

    // --- HELPERS DE FORMATEO ---
    formatCurrency(value) {
      if (value === null || isNaN(value)) return 'N/A';
      return parseFloat(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      return isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
    },

    exportarExcel() {
      let url = `${BASE_URL}api/pagos/exportar`;
      if (this.filtroMetodoPago !== 'todos') {
        url += `?metodo_pago=${this.filtroMetodoPago}`;
      }
      window.location.href = url;
    },
  }
}

/**
 * Renderiza el contenedor para la subida del comprobante bancario.
 */
function renderComprobanteUploader(idSolicitud) {
  const container = document.getElementById('comprobante-uploader-container');
  if (!container) return;

  container.innerHTML = `
    <div id="file-preview-comprobante" class="hidden mb-4 p-2 border border-dashed rounded-lg"></div>
    <input type="file" id="archivo-comprobante" class="hidden" accept="image/*,.pdf,.xml" 
           onchange="handleComprobanteFileSelect(this, ${idSolicitud})">
    <button id="btn-upload-comprobante" 
            onclick="document.getElementById('archivo-comprobante').click()" 
            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
        Subir Comprobante Bancario
    </button>
  `;
}

/**
 * Gestiona la selección visual del archivo antes de subirlo.
 */
function handleComprobanteFileSelect(input, idSolicitud) {
  const file = input.files[0];
  if (!file) {
    removeComprobanteFile(idSolicitud);
    return;
  }

  const previewContainer = document.getElementById('file-preview-comprobante');
  const uploadButton = document.getElementById('btn-upload-comprobante');

  // Determinar icono por tipo de archivo
  let icon = '📄';
  if (file.type.startsWith('image/')) icon = '🖼️';
  else if (file.type === 'application/pdf') icon = '📕';
  else if (file.type === 'text/xml' || file.type === 'application/xml') icon = '🔗';

  const fileSize = (file.size / 1024).toFixed(2) + ' KB';

  previewContainer.innerHTML = `
    <div class="flex items-center justify-between bg-white p-2 rounded border">
      <div class="flex items-center gap-2">
        <span class="text-xl">${icon}</span>
        <div>
          <p class="text-xs font-medium text-gray-800 truncate max-w-[200px]">${file.name}</p>
          <p class="text-[10px] text-gray-500">${fileSize}</p>
        </div>
      </div>
      <button onclick="removeComprobanteFile(${idSolicitud})" 
              class="text-red-500 hover:text-red-700" title="Quitar archivo">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  `;
  previewContainer.classList.remove('hidden');

  uploadButton.innerText = 'Confirmar y Subir Ahora';
  uploadButton.classList.replace('bg-green-600', 'bg-blue-600');
  uploadButton.onclick = () => uploadComprobante(idSolicitud);
}

function removeComprobanteFile(idSolicitud) {
  const fileInput = document.getElementById('archivo-comprobante');
  if (fileInput) fileInput.value = '';

  const previewContainer = document.getElementById('file-preview-comprobante');
  if (previewContainer) {
    previewContainer.innerHTML = '';
    previewContainer.classList.add('hidden');
  }

  const uploadButton = document.getElementById('btn-upload-comprobante');
  if (uploadButton) {
    uploadButton.innerText = 'Subir Comprobante Bancario';
    uploadButton.classList.replace('bg-blue-600', 'bg-green-600');
    uploadButton.onclick = () => document.getElementById('archivo-comprobante').click();
  }
}

/**
 * Ejecuta la carga física del archivo a través de la API.
 */
async function uploadComprobante(idSolicitud) {
  const fileInput = document.getElementById('archivo-comprobante');
  const file = fileInput?.files[0];

  if (!file) {
    mostrarNotificacion('Seleccione un archivo primero.', 'warning');
    return;
  }

  const uploadButton = document.getElementById('btn-upload-comprobante');
  const originalHtml = uploadButton.innerHTML;

  // Estado de carga visual
  uploadButton.disabled = true;
  uploadButton.innerHTML = `
    <div class="flex items-center justify-center gap-2">
      <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span class="text-sm">Subiendo...</span>
    </div>
  `;

  const formData = new FormData();
  formData.append('ficha', file);

  try {
    const result = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: formData,
    });

    if (result.success) {
      mostrarNotificacion('Comprobante guardado con éxito.', 'success');
      removeComprobanteFile(idSolicitud);
      await mostrarDetallePago(idSolicitud);
    } else {
      throw new Error(result.message || 'Error en el servidor.');
    }
  } catch (error) {
    console.error('Upload error:', error);
    mostrarNotificacion(error.message, 'error');
    uploadButton.innerHTML = originalHtml;
    uploadButton.disabled = false;
  }
}

/**
 * Genera y muestra la vista de detalle para un pago programado.
 * Centraliza la lógica de renderizado y cálculos de productos.
 */
async function mostrarDetallePago(id) {
  const divLista = document.getElementById('div-lista-pagos');
  const divDetalle = document.getElementById('div-detalle-pago');
  const contenedorDetalle = document.getElementById('contenido-detalle-pago');

  if (!divLista || !divDetalle || !contenedorDetalle) return;

  divLista.classList.add('hidden');
  divDetalle.classList.remove('hidden');
  contenedorDetalle.innerHTML = '<p class="text-center text-gray-500 py-8">Cargando detalles...</p>';

  try {
    const data = await SendDataEnd(`api/orden-compra/details/${id}`);
    if (!data) throw new Error('No se recibieron datos del servidor.');

    const fechaAprobacion =
      data.OrdenCompra && data.OrdenCompra.Fecha
        ? new Date(data.OrdenCompra.Fecha).toLocaleDateString('es-MX')
        : 'Pendiente';

    const prov = data.proveedor || {};
    const format = (val) =>
      parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

    const totalFormateado = format(data.cotizacion?.Total);

    const metodoPagoTexto =
      data.MetodoPago == 0
        ? 'Contado'
        : data.MetodoPago == 1
          ? 'Crédito'
          : data.MetodoPago == 9
            ? 'En Espera'
            : 'N/A';

    const tieneIVA = data.IVA == 1 || data.IVA === 't' || data.IVA === true;
    const isServicio = data.Tipo == 2;

    let html = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
                <div><strong>Folio:</strong> ${data.No_Folio || 'N/A'}</div>
                <div><strong>Fecha solicitud:</strong> ${data.Fecha || 'N/A'}</div>
                <div><strong>Fecha aprobación:</strong> ${fechaAprobacion}</div>
                <div><strong>Departamento:</strong> ${data.DepartamentoNombre || 'N/A'}</div>
                <div><strong>Proyecto:</strong> ${data.Complejo || 'N/A'}</div>
                <div><strong>Importe Total:</strong> <span class="font-bold text-lg text-blue-700">${totalFormateado}</span></div>
                <div><strong>Método de pago:</strong> ${metodoPagoTexto}</div>
            </div>

            <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">INFORMACIÓN DEL PROVEEDOR</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
                <div><strong>Razón social:</strong> ${prov.RazonSocial || 'N/A'}</div>
                <div><strong>RFC:</strong> ${prov.RFC || 'N/A'}</div>
                <div><strong>Banco del proveedor:</strong> ${prov.Banco || 'N/A'}</div>
                ${
                  data.cuenta_details
                    ? `<div><strong>Cuenta seleccionada:</strong> ${data.cuenta_details.Cuenta}</div>`
                    : `<div><strong>Cuenta del proveedor:</strong> ${prov.Cuenta || 'N/A'}</div>
           <div><strong>Clabe interbancaria:</strong> ${prov.Clabe || 'N/A'}</div>`
                }
                <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
                <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${
                  prov.Monto_Credito ? format(prov.Monto_Credito) : 'N/A'
                }</div>
            </div>

            <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">PRODUCTOS DE LA ORDEN</h3>

            <div class="overflow-x-auto mb-6 border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase">Cant.</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase">Base</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase">IVA</th>
                            <th class="py-2 px-4 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
        `;

    if (data.productos && data.productos.length > 0) {
      data.productos.forEach((p) => {
        const cantidad = isServicio ? 1 : parseFloat(p.Cantidad) || 0;
        const importeBase = parseFloat(p.Importe) || 0;

        const subtotalFila = cantidad * importeBase;
        const ivaFila = tieneIVA ? subtotalFila * 0.16 : 0;
        const totalFila = subtotalFila + ivaFila;

        html += `
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-t text-sm text-gray-500">${!isServicio ? p.Codigo || '' : 'N/A'}</td>
                        <td class="py-2 px-4 border-t text-sm text-gray-900">${p.Nombre}</td>
                        <td class="py-2 px-4 border-t text-right text-sm">${cantidad}</td>
                        <td class="py-2 px-4 border-t text-right text-sm">${format(importeBase)}</td>
                        <td class="py-2 px-4 border-t text-right text-sm">${format(ivaFila)}</td>
                        <td class="py-2 px-4 border-t text-right text-sm font-bold text-gray-900">${format(totalFila)}</td>
                    </tr>
                `;
      });
    } else {
      html += `<tr><td colspan="6" class="text-center py-3 text-gray-500">No hay productos en esta orden.</td></tr>`;
    }

    html += `</tbody></table></div>`;

    if (typeof generarSeccionAdjuntos === 'function') {
      if (!data.ID_Solicitud && data.ID_Orden) data.ID_Solicitud = data.ID_Orden;
      html += generarSeccionAdjuntos(data);
    }

    html += `
          <div class="mt-6 pt-4 border-t">
              <h3 class="text-md font-semibold mb-3 text-gray-700">ACCIONES</h3>

              <div id="comprobante-uploader-container"></div>

              <div class="grid grid-cols-1 gap-4 mt-4">
                  <button onclick="verRequisicionPago(${id})" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition w-full shadow-sm">
                      Ver Requisición de pago (PDF)
                  </button>
                  <button onclick="guardarEstadoPorPagar(${id})" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition w-full shadow-sm">
                      Confirmar y Guardar Estado
                  </button>
              </div>
          </div>
      `;

    contenedorDetalle.innerHTML = html;
    renderComprobanteUploader(id);
  } catch (error) {
    console.error('Error al cargar detalle:', error);
    contenedorDetalle.innerHTML = `<p class="text-center text-red-500 py-8">Error al cargar los detalles: ${error.message}</p>`;
  }
}

/**
 * Valida la existencia del comprobante y cambia el estado de la solicitud.
 */
async function guardarEstadoPorPagar(idSolicitud) {
  try {
    // Verificación obligatoria del archivo de comprobante antes de permitir el cambio de estado
    const details = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`);
    if (!details?.OrdenCompra?.File_Comprobante) {
      mostrarNotificacion('¡Atención! Debe subir el comprobante bancario antes de confirmar.', 'warning');
      return;
    }

    const confirm = await Confirmar('Cambiar Estado', '¿Está seguro de marcar esta solicitud como "Por Pagar"? Esta acción notificará al área correspondiente.');
    if (!confirm) return;

    const result = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: { nuevoEstado: 'Por Pagar' },
    });

    if (result.success) {
      mostrarNotificacion('Estado actualizado correctamente.', 'success');
      regresarListaPagos();
      // Notificamos a la lista para que se recargue automáticamente
      window.dispatchEvent(new CustomEvent('reload-pagos'));
    } else {
      mostrarNotificacion(result.message || 'No se pudo actualizar el estado.', 'error');
    }
  } catch (error) {
    console.error('State Update Error:', error);
    mostrarNotificacion('Ocurrió un fallo al intentar guardar los cambios.', 'error');
  }
}

function regresarListaPagos() {
  const divLista = document.getElementById('div-lista-pagos');
  const divDetalle = document.getElementById('div-detalle-pago');
  if (divLista && divDetalle) {
    divDetalle.classList.add('hidden');
    divLista.classList.remove('hidden');
    document.getElementById('contenido-detalle-pago').innerHTML = '';
  }
}

function verRequisicionPago(id) {
  if (!id) return;
  window.open(`${BASE_URL}api/requisicionpago/pdf/${id}`, '_blank');
}

/**
 * Lógica para el modal "Facturas pendientes" (Fichas de Pago)
 * Gestiona el listado de órdenes listas para pago final con semaforización de vencimientos.
 */
function FichasPago() {
  return {
    // --- ESTADO ---
    todasLasFichas: [],
    loading: true,
    
    // Filtros por pestaña (contado/credito)
    filtros: {
      contado: { search: '', depto: '', complejo: '' },
      credito: { search: '', depto: '', complejo: '' }
    },

    // Listas únicas para los selectores de filtro
    opcionesFiltro: { deptos: [], complejos: [] },

    /**
     * Inicializa la carga de facturas y procesa la semaforización.
     */
    async init() {
      this.loading = true;
      try {
        const data = await SendDataEnd('api/facturas-por-pagar');
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        this.todasLasFichas = (data || []).map(ficha => {
          // Procesamiento preventivo de semaforización para Crédito
          let semaforo = { clase: 'hover:bg-gray-50', diasTexto: '', sort: 9999 };
          
          if (String(ficha.MetodoPago) === '1' && ficha.Fecha_Aprobacion) {
            const [anio, mes, dia] = ficha.Fecha_Aprobacion.split(' ')[0].split('-').map(Number);
            const vencimiento = new Date(anio, mes - 1, dia);
            vencimiento.setDate(vencimiento.getDate() + (parseInt(ficha.Dias_Credito) || 0));

            const diffDays = Math.floor((vencimiento - hoy) / 86400000);
            semaforo.sort = diffDays;

            if (diffDays < 0) {
              semaforo.clase = 'bg-gray-900 text-white hover:bg-gray-800';
              semaforo.diasTexto = `Vencido (${Math.abs(diffDays)} días)`;
            } else if (diffDays === 0) {
              semaforo.clase = 'bg-red-100 text-red-800 hover:bg-red-200 font-bold';
              semaforo.diasTexto = 'Vence hoy';
            } else if (diffDays < 5) {
              semaforo.clase = 'bg-red-100 text-red-800 hover:bg-red-200';
              semaforo.diasTexto = `${diffDays} días`;
            } else if (diffDays < 15) {
              semaforo.clase = 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200';
              semaforo.diasTexto = `${diffDays} días`;
            } else {
              semaforo.diasTexto = `${diffDays} días`;
            }
          }

          return { ...ficha, semaforo };
        });

        // Extraer opciones de filtro una sola vez
        this.opcionesFiltro.deptos = [...new Set(this.todasLasFichas.map(f => f.DepartamentoNombre))].filter(Boolean).sort();
        this.opcionesFiltro.complejos = [...new Set(this.todasLasFichas.map(f => f.Complejo))].filter(Boolean).sort();

        // Sincronizar badges del menú (compatibilidad con elementos externos si existen)
        this.actualizarBadgesGlobales();

      } catch (error) {
        console.error('Error FichasPago:', error);
        mostrarNotificacion('Error al cargar las facturas pendientes.', 'error');
      } finally {
        this.loading = false;
      }
    },

    /**
     * Retorna la lista filtrada y ordenada según el método de pago solicitado.
     */
    getFichas(metodo) {
      const tipo = metodo === '0' ? 'contado' : 'credito';
      const f = this.filtros[tipo];
      const search = f.search.toLowerCase().trim();

      let filtradas = this.todasLasFichas.filter(ficha => {
        if (String(ficha.MetodoPago) !== String(metodo)) return false;
        if (f.depto && ficha.DepartamentoNombre !== f.depto) return false;
        if (f.complejo && ficha.Complejo !== f.complejo) return false;
        if (search) {
          const folio = (ficha.No_Folio || '').toLowerCase();
          const prov = (ficha.RazonSocial || '').toLowerCase();
          if (!folio.includes(search) && !prov.includes(search)) return false;
        }
        return true;
      });

      // Ordenar crédito por urgencia
      if (metodo === '1') {
        filtradas.sort((a, b) => a.semaforo.sort - b.semaforo.sort);
      }

      return filtradas;
    },

    /**
     * Getters de conteo para la interfaz
     */
    get countContado() { return this.todasLasFichas.filter(f => String(f.MetodoPago) === '0').length; },
    get countCredito() { return this.todasLasFichas.filter(f => String(f.MetodoPago) === '1').length; },

    actualizarBadgesGlobales() {
      // Intenta actualizar badges que podrían estar fuera del componente
      const bCont = document.getElementById('count-contado-fichas');
      const bCred = document.getElementById('count-credito-fichas');
      if (bCont) bCont.textContent = `${this.countContado} pendientes`;
      if (bCred) bCred.textContent = `${this.countCredito} pendientes`;
    },

    formatCurrency(v) {
      return parseFloat(v || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    }
  };
}

/**
 * Función puente para mantener compatibilidad con el inicializador de mbscript.js
 */
function initFichasPago() {
  // Esta función ahora solo sirve como disparador si se necesita lógica extra,
  // pero la reactividad la maneja el x-data="FichasPago()" en el HTML.
  console.log('Iniciando componente reactivo de Fichas de Pago...');
}

/**
 * Muestra el detalle de una ficha de pago pendiente de factura.
 * Reutiliza la estética de tarjetas y tablas optimizada en bloques anteriores.
 */
async function mostrarDetalleFicha(id, metodoPago) {
  const suffix = metodoPago == '0' ? 'contado' : 'credito';
  const detalleDiv = document.getElementById(`detalle-${suffix}`);
  const tablaDiv = document.getElementById(`tabla-${suffix}`);

  if (!detalleDiv || !tablaDiv) return;

  tablaDiv.classList.add('hidden');
  detalleDiv.classList.remove('hidden');
  detalleDiv.innerHTML = '<div class="py-12 flex flex-col items-center gap-3"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-slate-600"></div><p class="text-slate-500 font-medium text-sm">Cargando información de facturación...</p></div>';

  try {
    const data = await SendDataEnd(`api/orden-compra/details/${id}`);
    if (!data) throw new Error('Datos no disponibles.');

    const prov = data.proveedor || {};
    const format = (val) =>
      parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

    const totalFormateado = format(data.cotizacion?.Total);

    const metodoPagoTexto =
      data.MetodoPago == 0
        ? 'Efectivo'
        : data.MetodoPago == 1
          ? 'Crédito'
          : data.MetodoPago == 9
            ? 'En Espera'
            : 'N/A';

    // 1. DETECTAR IVA Y TIPO
    const tieneIVA = data.IVA == 1 || data.IVA === 't' || data.IVA === true;
    const isServicio = data.Tipo == 2;

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
        <div><strong>Importe Total (Con IVA):</strong> <span class="font-bold text-lg text-blue-700">${totalFormateado}</span></div>
        <div><strong>Método de pago:</strong> ${metodoPagoTexto}</div>
      </div>

      <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">INFORMACIÓN DEL PROVEEDOR</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
        <div><strong>Razón social:</strong> ${prov.RazonSocial || 'N/A'}</div>
        <div><strong>RFC:</strong> ${prov.RFC || 'N/A'}</div>
        <div><strong>Banco del proveedor:</strong> ${prov.Banco || 'N/A'}</div>
        <div><strong>Cuenta del proveedor:</strong> ${prov.Cuenta || 'N/A'}</div>
        <div><strong>Clabe interbancaria:</strong> ${prov.Clabe || 'N/A'}</div>
        <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
        <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${
          prov.Monto_Credito ? format(prov.Monto_Credito) : 'N/A'
        }</div>
      </div>

      <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">PRODUCTOS DE LA ORDEN</h3>

      <div class="overflow-x-auto mb-6 border rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cant.</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Base</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">IVA</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    `;

    if (data.productos && data.productos.length > 0) {
      data.productos.forEach((p) => {
        // 2. CÁLCULOS POR FILA
        const cantidad = isServicio ? 1 : parseFloat(p.Cantidad) || 0;
        const importeBase = parseFloat(p.Importe) || 0;

        const subtotalFila = cantidad * importeBase;
        const ivaFila = tieneIVA ? subtotalFila * 0.16 : 0;
        const totalFila = subtotalFila + ivaFila;

        html += `
            <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-t text-sm text-gray-500">${!isServicio ? p.Codigo || '' : 'N/A'}</td>
                <td class="py-2 px-4 border-t text-sm text-gray-900">${p.Nombre}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${cantidad}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${format(importeBase)}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${format(ivaFila)}</td>
                <td class="py-2 px-4 border-t text-right text-sm font-bold text-gray-900">${format(totalFila)}</td>
            </tr>
        `;
      });
    } else {
      html += `<tr><td colspan="6" class="text-center py-3 text-gray-500">No hay productos en esta orden.</td></tr>`;
    }

    html += `
            </tbody>
        </table>
      </div>
      `;

    // Sección de Adjuntos
    if (typeof generarSeccionAdjuntos === 'function') {
      // Asegurar ID
      if (!data.ID_Solicitud && data.ID_Orden) data.ID_Solicitud = data.ID_Orden;
      html += generarSeccionAdjuntos(data);
    }

    html += `
      <div class="mt-4 pt-4 border-t" id="factura-uploader-container"></div>
      <div class="flex justify-between mt-6 gap-4">
        <button onclick="CerrarOrden(${id}, '${metodoPago}')" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded-lg transition w-full shadow-sm">
          Cerrar requisición (Pagada)
        </button>
      </div>
    `;

    detalleDiv.innerHTML = html;
    renderFacturaUploader(id, metodoPago);
  } catch (error) {
    console.error('Ficha Detail Error:', error);
    detalleDiv.innerHTML = `<div class="p-8 text-center bg-red-50 text-red-600 rounded-2xl border border-red-100 font-bold uppercase text-xs tracking-widest">Error: ${error.message}</div>`;
  }
}

/**
 * Renderiza el uploader para múltiples facturas (PDF/XML).
 */
function renderFacturaUploader(idSolicitud, metodoPago) {
  const container = document.getElementById('factura-uploader-container');
  if (!container) return;

  container.innerHTML = `
    <div id="file-preview-factura" class="hidden mb-4 p-2 border border-dashed rounded-lg"></div>
    <input type="file" id="archivo-factura" class="hidden" accept="image/*,.pdf,.xml" multiple 
           onchange="handleFacturaFileSelect(this, ${idSolicitud}, '${metodoPago}')">
    <button id="btn-upload-factura" onclick="document.getElementById('archivo-factura').click()" 
            class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded-lg transition">
        Subir Factura(s)
    </button>
  `;
}

/**
 * Gestiona la lista de archivos seleccionados para facturación.
 */
function handleFacturaFileSelect(input, idSolicitud, metodoPago) {
  const files = Array.from(input.files);
  if (files.length === 0) {
    removeFacturaFile(idSolicitud);
    return;
  }

  const previewContainer = document.getElementById('file-preview-factura');
  const uploadButton = document.getElementById('btn-upload-factura');

  let html = '<div class="space-y-2">';
  files.forEach((file) => {
    let icon = '📄';
    if (file.type.startsWith('image/')) {
      icon = '🖼️';
    } else if (file.type === 'application/pdf') {
      icon = '📕';
    } else if (file.type === 'text/xml' || file.type === 'application/xml') {
      icon = '🔗';
    }

    const fileSize = (file.size / 1024).toFixed(2) + ' KB';
    html += `
        <div class="flex items-center justify-between bg-white p-2 rounded border">
            <div class="flex items-center gap-2">
                <span class="text-xl">${icon}</span>
                <div>
                    <p class="text-xs font-medium text-gray-800 truncate max-w-[200px]">${file.name}</p>
                    <p class="text-[10px] text-gray-500">${fileSize}</p>
                </div>
            </div>
        </div>
    `;
  });
  html += `
        <div class="mt-2 text-right">
            <button onclick="removeFacturaFile(${idSolicitud})" class="text-red-500 hover:text-red-700 text-xs font-bold underline">Limpiar selección</button>
        </div>
    </div>`;

  previewContainer.innerHTML = html;
  previewContainer.classList.remove('hidden');

  uploadButton.innerText =
    files.length > 1 ? `Confirmar y Subir ${files.length} Facturas` : 'Confirmar y Subir Factura';
  uploadButton.onclick = () => uploadFactura(idSolicitud, metodoPago);
}

function removeFacturaFile(idSolicitud) {
  const fileInput = document.getElementById('archivo-factura');
  if (fileInput) fileInput.value = '';

  const previewContainer = document.getElementById('file-preview-factura');
  if (previewContainer) {
    previewContainer.innerHTML = '';
    previewContainer.classList.add('hidden');
  }

  const uploadButton = document.getElementById('btn-upload-factura');
  if (uploadButton) {
    uploadButton.innerText = 'Subir Factura(s)';
    uploadButton.onclick = () => document.getElementById('archivo-factura').click();
  }
}

/**
 * Procesa la subida masiva de facturas.
 */
async function uploadFactura(idSolicitud, metodoPago) {
  const fileInput = document.getElementById('archivo-factura');
  const files = fileInput?.files;

  if (!files || files.length === 0) {
    mostrarNotificacion('Seleccione al menos una factura.', 'warning');
    return;
  }

  const uploadButton = document.getElementById('btn-upload-factura');
  const originalText = uploadButton.innerText;
  
  uploadButton.disabled = true;
  uploadButton.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Subiendo archivos...</span>';

  const formData = new FormData();
  for (let i = 0; i < files.length; i++) formData.append('factura[]', files[i]);

  try {
    const result = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, { method: 'POST', body: formData });
    if (result.success) {
      mostrarNotificacion('Facturas cargadas correctamente.', 'success');
      removeFacturaFile(idSolicitud);
      // Sincronización: Emitimos evento global para que la lista madre se entere (Bloque 4 reactivo)
      window.dispatchEvent(new CustomEvent('reload-pagos-fichas')); 
      await mostrarDetalleFicha(idSolicitud, metodoPago);
    } else {
      throw new Error(result.message || 'Error al subir facturas.');
    }
  } catch (error) {
    console.error('Factura Upload Error:', error);
    mostrarNotificacion(error.message, 'error');
    uploadButton.disabled = false;
    uploadButton.innerText = originalText;
  }
}

/**
 * Ejecuta el cierre final de la orden cambiándola a estado "Pagada".
 */
async function CerrarOrden(idSolicitud, metodoPago) {
  try {
    // Validación de seguridad: Verificar existencia de factura antes de permitir el cierre
    const details = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`);
    if (!details?.OrdenCompra?.File_Factura) {
      mostrarNotificacion('¡Importante! No se puede liquidar sin haber subido la factura correspondiente.', 'warning');
      return;
    }

    const confirm = await Confirmar('Finalizar Requisición', '¿Confirma que esta orden ha sido liquidada? Este proceso cerrará el flujo de pago y no es reversible.');
    if (!confirm) return;

    const result = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: { nuevoEstado: 'Pagada' },
    });

    if (result.success) {
      mostrarNotificacion('Requisición cerrada con éxito.', 'success');
      volverAFichas(metodoPago);
      // Notificamos para recarga del componente FichasPago()
      window.dispatchEvent(new CustomEvent('reload-pagos-fichas'));
    } else {
      mostrarNotificacion(result.message || 'Fallo al cerrar la orden.', 'error');
    }
  } catch (error) {
    console.error('CerrarOrden Error:', error);
    mostrarNotificacion('Ocurrió un error crítico al intentar finalizar la orden.', 'error');
  }
}

function volverAFichas(metodoPago) {
  const suffix = metodoPago == '0' ? 'contado' : 'credito';
  const det = document.getElementById(`detalle-${suffix}`);
  const tab = document.getElementById(`tabla-${suffix}`);
  if (det && tab) {
    det.classList.add('hidden');
    tab.classList.remove('hidden');
    det.innerHTML = '';
  }
}

function mostrarFichaContado() {
  const menu = document.getElementById('pagos-menu');
  const cont = document.getElementById('pago-contado');
  if (menu && cont) { menu.classList.add('hidden'); cont.classList.remove('hidden'); }
}

function mostrarFichaCredito() {
  const menu = document.getElementById('pagos-menu');
  const cred = document.getElementById('pago-credito');
  if (menu && cred) { menu.classList.add('hidden'); cred.classList.remove('hidden'); }
}

function regresarFichaMenu() {
  const menu = document.getElementById('pagos-menu');
  const cont = document.getElementById('pago-contado');
  const cred = document.getElementById('pago-credito');
  if (menu) menu.classList.remove('hidden');
  if (cont) cont.classList.add('hidden');
  if (cred) cred.classList.add('hidden');
}
