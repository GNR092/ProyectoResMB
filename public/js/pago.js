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

      // Generación de filas de productos con cálculos integrados
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
            <td class="py-2 px-4 border-t text-right text-sm font-bold">${format(subtotal + iva)}</td>
          </tr>`;
      }).join('') || '<tr><td colspan="6" class="text-center py-3 text-gray-500">Sin productos.</td></tr>';

      const infoCredito = String(data.MetodoPago) === '1' ? `
        <div><strong>Días de crédito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
        <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${format(prov.Monto_Credito)}</div>` : '';

      // Retorno de la plantilla completa
      return `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
          <div><strong>Fecha solicitud:</strong> ${data.Fecha || 'N/A'}</div>
          <div><strong>Departamento:</strong> ${data.DepartamentoNombre || 'N/A'}</div>
          <div><strong>Proyecto:</strong> ${data.Complejo || 'N/A'}</div>
          <div><strong>Importe Total:</strong> <span class="font-bold text-lg text-blue-700">${totalCotizacion}</span></div>
          <div><strong>Método de pago:</strong> ${String(data.MetodoPago) === '0' ? 'Contado' : 'Crédito'}</div>
        </div>

        <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2 uppercase">Información del Proveedor</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mb-6 p-4 border rounded-lg bg-gray-50 text-sm">
          <div><strong>Razón social:</strong> ${prov.RazonSocial || 'N/A'}</div>
          <div><strong>RFC:</strong> ${prov.RFC || 'N/A'}</div>
          <div><strong>Banco:</strong> ${prov.Banco || 'N/A'}</div>
          <div><strong>Cuenta:</strong> ${prov.Cuenta || 'N/A'}</div>
          <div><strong>Clabe:</strong> ${prov.Clabe || 'N/A'}</div>
          ${infoCredito}
        </div>

        <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2 uppercase">Productos</h3>
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
        <div class="flex justify-end mt-6">
          <button onclick="globalCancelarSolicitud(${data.ID_Solicitud || data.ID_Orden}, () => document.getElementById('btn-volver-pagos').click())" 
                  class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition shadow-sm">
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
    <div id="file-preview-comprobante" class="hidden mb-4 p-3 border-2 border-dashed border-blue-200 rounded-xl bg-blue-50/50"></div>
    <input type="file" id="archivo-comprobante" class="hidden" accept="image/*,.pdf,.xml" 
           onchange="handleComprobanteFileSelect(this, ${idSolicitud})">
    <button id="btn-upload-comprobante" 
            onclick="document.getElementById('archivo-comprobante').click()" 
            class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl transition-all shadow-md group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        <span>Subir Comprobante Bancario</span>
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
  const getIcon = (type) => {
    if (type.startsWith('image/')) return '🖼️';
    if (type === 'application/pdf') return '📕';
    if (type.includes('xml')) return '🔗';
    return '📄';
  };

  const fileSize = (file.size / 1024).toFixed(2) + ' KB';

  previewContainer.innerHTML = `
    <div class="flex items-center justify-between bg-white p-3 rounded-lg shadow-sm border border-blue-100">
      <div class="flex items-center gap-3">
        <span class="text-3xl">${getIcon(file.type)}</span>
        <div class="overflow-hidden">
          <p class="text-sm font-bold text-gray-800 truncate max-w-[200px]" title="${file.name}">${file.name}</p>
          <p class="text-xs text-gray-500 font-medium">${fileSize}</p>
        </div>
      </div>
      <button onclick="removeComprobanteFile(${idSolicitud})" 
              class="p-1 hover:bg-red-50 text-red-500 rounded-full transition-colors" title="Quitar archivo">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  `;
  previewContainer.classList.remove('hidden');

  uploadButton.innerHTML = '<span>🚀 Confirmar y Subir Ahora</span>';
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
    uploadButton.innerHTML = '<span>Subir Comprobante Bancario</span>';
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
    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <span>Subiendo...</span>
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
  contenedorDetalle.innerHTML = '<div class="py-12 flex flex-col items-center gap-3"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div><p class="text-gray-500 font-medium">Obteniendo detalles de la orden...</p></div>';

  try {
    const data = await SendDataEnd(`api/orden-compra/details/${id}`);
    if (!data) throw new Error('No se pudieron obtener los datos.');

    const prov = data.proveedor || {};
    const format = (val) => parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    
    // Determinación del método de pago legible
    const metodos = { '0': 'Contado', '1': 'Crédito', '9': 'En Espera' };
    const metodoTexto = metodos[String(data.MetodoPago)] || 'N/A';

    // Lógica unificada para cálculos de productos e impuestos
    const tieneIVA = data.IVA == 1 || data.IVA === 't' || data.IVA === true;
    const isServicio = data.Tipo == 2;

    const productosRows = (data.productos || []).map(p => {
      const cant = isServicio ? 1 : parseFloat(p.Cantidad) || 0;
      const base = parseFloat(p.Importe) || 0;
      const subtotal = cant * base;
      const iva = tieneIVA ? subtotal * 0.16 : 0;
      const total = subtotal + iva;

      return `
        <tr class="hover:bg-gray-50 transition-colors">
          <td class="py-2 px-4 border-t text-sm text-gray-500 font-mono">${!isServicio ? p.Codigo || '-' : 'N/A'}</td>
          <td class="py-2 px-4 border-t text-sm text-gray-900 font-medium">${p.Nombre}</td>
          <td class="py-2 px-4 border-t text-right text-sm">${cant}</td>
          <td class="py-2 px-4 border-t text-right text-sm">${format(base)}</td>
          <td class="py-2 px-4 border-t text-right text-sm text-gray-600">${format(iva)}</td>
          <td class="py-2 px-4 border-t text-right text-sm font-bold text-blue-800">${format(total)}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center py-6 text-gray-400 italic">No hay productos registrados en esta orden.</td></tr>';

    // Construcción de la plantilla principal
    let html = `
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Información General</p>
          <p class="text-sm"><strong>Folio:</strong> ${data.No_Folio || '-'}</p>
          <p class="text-sm"><strong>Fecha Solicitud:</strong> ${data.Fecha || '-'}</p>
          <p class="text-sm"><strong>Método:</strong> <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-bold text-[10px] uppercase">${metodoTexto}</span></p>
        </div>
        <div class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Ubicación y Proyecto</p>
          <p class="text-sm truncate"><strong>Depto:</strong> ${data.DepartamentoNombre || '-'}</p>
          <p class="text-sm truncate"><strong>Proyecto:</strong> ${data.Complejo || '-'}</p>
        </div>
        <div class="p-4 bg-blue-600 rounded-xl shadow-md text-white">
          <p class="text-[10px] font-bold text-blue-100 uppercase tracking-widest mb-1">Total de la Orden</p>
          <p class="text-2xl font-black">${format(data.cotizacion?.Total)}</p>
          <p class="text-[10px] text-blue-100 italic">Importe total con impuestos incluidos</p>
        </div>
      </div>

      <div class="mb-8">
        <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="w-1.5 h-4 bg-blue-600 rounded-full"></span>
          DATOS BANCARIOS DEL PROVEEDOR
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 p-5 bg-white border border-gray-200 rounded-2xl shadow-sm text-sm">
          <p><strong>Razón Social:</strong> ${prov.RazonSocial || '-'}</p>
          <p><strong>RFC:</strong> ${prov.RFC || '-'}</p>
          <p><strong>Banco:</strong> ${prov.Banco || '-'}</p>
          ${data.cuenta_details 
            ? `<p><strong>Cuenta Seleccionada:</strong> <span class="font-mono text-blue-700 font-bold">${data.cuenta_details.Cuenta}</span></p>`
            : `<p><strong>Cuenta:</strong> <span class="font-mono">${prov.Cuenta || '-'}</span></p>
               <p><strong>CLABE:</strong> <span class="font-mono">${prov.Clabe || '-'}</span></p>`
          }
          <div class="md:col-span-2 mt-2 pt-2 border-t border-dashed flex gap-4 text-xs text-gray-500">
            <span><strong>Días de Crédito:</strong> ${prov.Dias_Credito || 0}</span>
            <span><strong>Límite:</strong> ${format(prov.Monto_Credito)}</span>
          </div>
        </div>
      </div>

      <div class="mb-8">
        <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
          <span class="w-1.5 h-4 bg-blue-600 rounded-full"></span>
          DETALLE DE PRODUCTOS / SERVICIOS
        </h3>
        <div class="overflow-hidden border border-gray-200 rounded-2xl shadow-sm">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-3 px-4 text-left text-[10px] font-bold text-gray-400 uppercase">Código</th>
                <th class="py-3 px-4 text-left text-[10px] font-bold text-gray-400 uppercase">Descripción</th>
                <th class="py-3 px-4 text-right text-[10px] font-bold text-gray-400 uppercase">Cant.</th>
                <th class="py-3 px-4 text-right text-[10px] font-bold text-gray-400 uppercase">P. Unit.</th>
                <th class="py-3 px-4 text-right text-[10px] font-bold text-gray-400 uppercase">IVA</th>
                <th class="py-3 px-4 text-right text-[10px] font-bold text-gray-400 uppercase">Total</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">${productosRows}</tbody>
          </table>
        </div>
      </div>
    `;

    // Adjuntos (si existe la función global)
    if (typeof generarSeccionAdjuntos === 'function') {
      html += generarSeccionAdjuntos({ ...data, ID_Solicitud: data.ID_Solicitud || data.ID_Orden });
    }

    // Acciones Finales
    html += `
      <div class="mt-10 p-6 bg-gray-100 rounded-2xl border border-gray-200">
        <h3 class="text-sm font-black text-gray-800 mb-4 uppercase tracking-tighter">Acciones de Programación</h3>
        <div id="comprobante-uploader-container" class="mb-6"></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <button onclick="verRequisicionPago(${id})" class="flex items-center justify-center gap-2 bg-slate-700 hover:bg-slate-800 text-white font-bold py-2.5 px-4 rounded-xl transition-all shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
            Vista Previa PDF
          </button>
          <button onclick="guardarEstadoPorPagar(${id})" class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl transition-all shadow-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            Confirmar y Pasar a "Por Pagar"
          </button>
        </div>
      </div>
    `;

    contenedorDetalle.innerHTML = html;
    renderComprobanteUploader(id);
    
  } catch (error) {
    console.error('Detail Error:', error);
    contenedorDetalle.innerHTML = `<div class="p-8 text-center bg-red-50 text-red-600 rounded-xl border border-red-100 font-medium">Error al cargar la información: ${error.message}</div>`;
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
    const format = (val) => parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    
    const tieneIVA = data.IVA == 1 || data.IVA === 't' || data.IVA === true;
    const isServicio = data.Tipo == 2;

    const productosRows = (data.productos || []).map(p => {
      const cant = isServicio ? 1 : parseFloat(p.Cantidad) || 0;
      const base = parseFloat(p.Importe) || 0;
      const subtotal = cant * base;
      const iva = tieneIVA ? subtotal * 0.16 : 0;
      return `
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="py-2 px-4 border-t text-[11px] text-slate-400 font-mono">${!isServicio ? p.Codigo || '-' : 'N/A'}</td>
          <td class="py-2 px-4 border-t text-sm text-slate-800 font-bold">${p.Nombre}</td>
          <td class="py-2 px-4 border-t text-right text-xs text-slate-500">${cant}</td>
          <td class="py-2 px-4 border-t text-right text-xs text-slate-500">${format(base)}</td>
          <td class="py-2 px-4 border-t text-right text-xs text-slate-400">${format(iva)}</td>
          <td class="py-2 px-4 border-t text-right text-sm font-black text-slate-900">${format(subtotal + iva)}</td>
        </tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center py-6 text-slate-400 italic">Sin productos.</td></tr>';

    let html = `
      <div class="flex justify-between items-center mb-6">
        <button onclick="volverAFichas('${metodoPago}')" class="flex items-center gap-2 text-[10px] font-black text-slate-400 hover:text-slate-800 uppercase tracking-widest transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          Regresar al listado
        </button>
        <h2 class="text-lg font-black text-slate-800 uppercase tracking-tighter">Detalle Orden #${data.No_Folio || id}</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Resumen de Solicitud</p>
          <div class="space-y-1 text-sm">
            <p><strong>Fecha Solicitud:</strong> ${data.Fecha || '-'}</p>
            <p><strong>Departamento:</strong> ${data.DepartamentoNombre || '-'}</p>
            <p><strong>Proyecto:</strong> ${data.Complejo || '-'}</p>
            <p class="pt-2 text-base"><strong>Total:</strong> <span class="font-black text-blue-700">${format(data.cotizacion?.Total)}</span></p>
          </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-sm">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Datos del Proveedor</p>
          <div class="space-y-1 text-sm">
            <p class="truncate" title="${prov.RazonSocial || ''}"><strong>Razón:</strong> ${prov.RazonSocial || '-'}</p>
            <p><strong>RFC:</strong> ${prov.RFC || '-'}</p>
            <p><strong>Banco:</strong> ${prov.Banco || '-'}</p>
            <p><strong>Cuenta:</strong> <span class="font-mono text-slate-600">${prov.Cuenta || '-'}</span></p>
          </div>
        </div>
      </div>

      <div class="mb-8 overflow-hidden border border-slate-200 rounded-2xl bg-white shadow-sm">
        <table class="min-w-full text-left">
          <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
            <tr>
              <th class="px-4 py-4">Código</th>
              <th class="px-4 py-4">Descripción</th>
              <th class="px-4 py-4 text-right">Cant.</th>
              <th class="px-4 py-4 text-right">Unit.</th>
              <th class="px-4 py-4 text-right">IVA</th>
              <th class="px-4 py-4 text-right">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">${productosRows}</tbody>
        </table>
      </div>
    `;

    if (typeof generarSeccionAdjuntos === 'function') {
      html += generarSeccionAdjuntos({ ...data, ID_Solicitud: data.ID_Solicitud || data.ID_Orden });
    }

    html += `
      <div class="mt-8 p-6 bg-slate-100 rounded-2xl border border-slate-200">
        <div id="factura-uploader-container" class="mb-6"></div> 
        <div class="flex flex-col sm:flex-row gap-3">
          <button onclick="CerrarOrden(${id}, '${metodoPago}')" 
                  class="flex-grow flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-black py-3 px-6 rounded-xl transition-all shadow-lg uppercase text-xs tracking-widest">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Finalizar y Cerrar Requisición
          </button>
        </div>
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
    <div id="file-preview-factura" class="hidden mb-4 p-3 border-2 border-dashed border-orange-200 rounded-2xl bg-orange-50/50"></div>
    <input type="file" id="archivo-factura" class="hidden" accept="image/*,.pdf,.xml" multiple 
           onchange="handleFacturaFileSelect(this, ${idSolicitud}, '${metodoPago}')">
    <button id="btn-upload-factura" onclick="document.getElementById('archivo-factura').click()" 
            class="w-full flex items-center justify-center gap-2 bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 font-black py-3 px-4 rounded-xl transition-all group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        <span>Adjuntar Facturas (PDF / XML)</span>
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

  const getIcon = (t) => t.startsWith('image/') ? '🖼️' : (t === 'application/pdf' ? '📕' : (t.includes('xml') ? '🔗' : '📄'));

  const itemsHtml = files.map(file => `
    <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-orange-100 shadow-sm">
      <span class="text-2xl">${getIcon(file.type)}</span>
      <div class="flex-grow overflow-hidden">
        <p class="text-[11px] font-bold text-slate-800 truncate" title="${file.name}">${file.name}</p>
        <p class="text-[9px] text-slate-400 font-medium uppercase">${(file.size / 1024).toFixed(1)} KB</p>
      </div>
    </div>
  `).join('');

  previewContainer.innerHTML = `
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">${itemsHtml}</div>
    <div class="text-right">
      <button onclick="removeFacturaFile(${idSolicitud})" class="text-[9px] font-black text-red-500 uppercase tracking-widest hover:underline">Limpiar Selección</button>
    </div>
  `;
  previewContainer.classList.remove('hidden');

  uploadButton.innerHTML = `<span>🚀 Confirmar y Subir ${files.length} Archivo(s)</span>`;
  uploadButton.classList.remove('bg-white', 'text-orange-600', 'border-orange-500');
  uploadButton.classList.add('bg-orange-600', 'text-white', 'border-transparent');
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
    uploadButton.innerHTML = '<span>Adjuntar Facturas (PDF / XML)</span>';
    uploadButton.className = 'w-full flex items-center justify-center gap-2 bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 font-black py-3 px-4 rounded-xl transition-all group';
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
