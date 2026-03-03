/**
 * Lógica para el modal "Programar Pagos"
 */
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

    // --- VARIABLES DE PAGINACIÓN AGREGADAS ---
    itemsPerPage: 10,
    pageContado: 1,
    pageCredito: 1,

    get totalSeleccionado() {
      // 1. Detectamos qué lista estamos viendo
      const listaActual = this.screen === 'contado' ? this.ordenesContado : this.ordenesCredito;

      if (!listaActual || listaActual.length === 0) return 0;

      // 2. Filtramos solo las órdenes que están en 'selectedOrdenes'
      const ordenesSeleccionadas = listaActual.filter(o => this.selectedOrdenes.includes(o.ID_Solicitud));

      // 3. Sumamos los totales (asegurando que sea número)
      const suma = ordenesSeleccionadas.reduce((acumulado, orden) => {
        return acumulado + parseFloat(orden.Total || 0);
      }, 0);

      return suma;
    },

    async cargardatos() {
      this.loading = true;
      this.selectedOrdenes = [];
      try {
        const data = await SendDataEnd('api/ordenes-programar');

        if (!data || data.length === 0) {
          this.ordenesContado = [];
          this.ordenesCredito = [];
          return;
        }

        // --- PRE-PROCESAMOS TODOS LOS DATOS PARA ASIGNAR LA FECHA ---
        const datosProcesados = data.map(orden => {
          return {
            ...orden,
            // Prioridad: FechaRefPago -> FechaOrden -> Fecha
            FechaAprobacion: orden.FechaRefPago || orden.FechaOrden || orden.Fecha
          };
        });

        // 1. Filtrar Contado usando los datos ya procesados
        this.ordenesContado = datosProcesados.filter((o) => o.MetodoPago == '0');

        // 2. Filtrar y Procesar Crédito
        const rawCredito = datosProcesados.filter((o) => o.MetodoPago == '1');

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        this.ordenesCredito = rawCredito.map(orden => {
          let claseColor = 'hover:bg-gray-50 transition';
          let diasRestantes = 9999;

          // Usamos la variable que acabamos de crear arriba
          if (orden.FechaAprobacion) {
            const fechaSimple = orden.FechaAprobacion.split(" ")[0];
            const partes = fechaSimple.split("-");

            if (partes.length === 3) {
              const anio = parseInt(partes[0]);
              const mes = parseInt(partes[1]) - 1;
              const dia = parseInt(partes[2]);

              const fechaVencimiento = new Date(anio, mes, dia);
              const diasCredito = parseInt(orden.Dias_Credito) || 0;
              fechaVencimiento.setDate(fechaVencimiento.getDate() + diasCredito);

              const diffTime = fechaVencimiento.getTime() - hoy.getTime();
              diasRestantes = Math.floor(diffTime / (1000 * 60 * 60 * 24));

              if (diasRestantes < 0) {
                claseColor = 'bg-gray-800 text-white hover:bg-gray-700 transition';
              } else if (diasRestantes < 5) {
                claseColor = 'bg-red-100 hover:bg-red-200 transition';
              } else if (diasRestantes < 15) {
                claseColor = 'bg-yellow-100 hover:bg-yellow-200 transition';
              }
            }
          }

          return {
            ...orden,
            claseColor: claseColor,
            _sortValue: diasRestantes
          };
        });

        // 3. ORDENAR Crédito
        this.ordenesCredito.sort((a, b) => a._sortValue - b._sortValue);

        this.pageContado = 1;
        this.pageCredito = 1;

      } catch (error) {
        console.error('Error al cargar las órdenes:', error);
        mostrarNotificacion('Error de conexión.', 'error');
        this.ordenesContado = [];
        this.ordenesCredito = [];
      } finally {
        this.loading = false;
      }
    },

    // --- LÓGICA DE PAGINACIÓN (COMPUTADOS) ---
    get paginatedContado() {
      if (!this.ordenesContado || !Array.isArray(this.ordenesContado) || this.ordenesContado.length === 0) return [];
      const start = (this.pageContado - 1) * this.itemsPerPage;
      return this.ordenesContado.slice(start, start + this.itemsPerPage);
    },

    get paginatedCredito() {
      if (!this.ordenesCredito || !Array.isArray(this.ordenesCredito) || this.ordenesCredito.length === 0) return [];
      const start = (this.pageCredito - 1) * this.itemsPerPage;
      return this.ordenesCredito.slice(start, start + this.itemsPerPage);
    },

    get totalPagesContado() {
      return Math.ceil(this.ordenesContado.length / this.itemsPerPage) || 1;
    },

    get totalPagesCredito() {
      return Math.ceil(this.ordenesCredito.length / this.itemsPerPage) || 1;
    },

    changePage(type, direction) {
      if (type === 'contado') {
        if (direction === 'next' && this.pageContado < this.totalPagesContado) this.pageContado++;
        if (direction === 'prev' && this.pageContado > 1) this.pageContado--;
      } else {
        if (direction === 'next' && this.pageCredito < this.totalPagesCredito) this.pageCredito++;
        if (direction === 'prev' && this.pageCredito > 1) this.pageCredito--;
      }
    },

    toggleSelectAll(event, type) {
      // para obtener SOLO los 10 elementos visibles.
      const list = type === 'contado' ? this.paginatedContado : this.paginatedCredito;

      // Obtenemos los IDs
      const pageIds = list.map((o) => o.ID_Solicitud);

      if (event.target.checked) {
        // Agregamos los IDs
        this.selectedOrdenes = [...new Set([...this.selectedOrdenes, ...pageIds])];
      } else {
        // Removemos los IDs
        this.selectedOrdenes = this.selectedOrdenes.filter((id) => !pageIds.includes(id));
      }
    },

    // Agrega esto dentro del objeto Pagos()
    isPageSelected(type) {
      const list = type === 'contado' ? this.paginatedContado : this.paginatedCredito;
      if (list.length === 0) return false;
      // Verifica si TODOS los elementos visibles están en la lista de seleccionados
      return list.every(o => this.selectedOrdenes.includes(o.ID_Solicitud));
    },

    async programarPago() {
      if (this.selectedOrdenes.length === 0) {
        mostrarNotificacion('Debe seleccionar al menos una orden para programar.', 'warning');
        return;
      }

      const confirmacion = await Confirmar(
          'Programar Pagos',
          `¿Está seguro de que desea programar ${this.selectedOrdenes.length} pago(s)?`
      );

      if (!confirmacion) return;

      try {
        const result = await SendDataEnd('api/orden/programar-pagos', {
          method: 'POST',
          body: { ids: this.selectedOrdenes },
        });

        if (result.success) {
          mostrarNotificacion(result.message, 'success');
          await this.cargardatos();
        } else {
          mostrarNotificacion(result.message || 'No se pudieron programar los pagos.', 'error');
        }
      } catch (error) {
        console.error('Error al programar pagos:', error);
        mostrarNotificacion('Ocurrió un error de red.', 'error');
      }
    },

    mostrarDetalle(id, metodoPago) {
      this.loadingDetalle = true;
      this.previousScreen = metodoPago == '0' ? 'contado' : 'credito';
      this.screen = 'detalle';

      SendDataEnd(`api/orden-compra/details/${id}`)
          .then((data) => {
            this.detalleOrden = data;
            this.loadingDetalle = false;
          })
          .catch((error) => {
            console.error('Error al cargar detalle:', error);
            mostrarNotificacion('No se pudo cargar el detalle.', 'error');
            this.loadingDetalle = false;
            this.screen = this.previousScreen;
          });
    },

    async volverATabla() {
      this.screen = this.previousScreen;
      this.detalleOrden = null;
      // Agregamos esto para refrescar la lista y que desaparezca lo cancelado
      await this.cargardatos();
    },

    formatCurrency(value) {
      if (value === null || isNaN(value)) return 'N/A';
      return parseFloat(value).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
    },

    formatDate(dateString) {
      if (!dateString) return '-';
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return '-';
      return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
    },

    // --- AGREGADO: ESTO FALTABA PARA QUE SE VIERAN LOS DETALLES ---
    generarDetalleHtml() {
      if (!this.detalleOrden) return '';

      const data = this.detalleOrden;
      const prov = data.proveedor || {};

      // Formateador
      const format = (val) => parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

      const totalFormateado = format(data.cotizacion?.Total);
      const metodoPagoTexto = data.MetodoPago == 0 ? 'Efectivo' : 'Crédito';

      // DETECTAR IVA (Soporta 1, 't', true)
      const tieneIVA = data.IVA == 1 || data.IVA === 't' || data.IVA === true;
      const isServicio = data.Tipo == 2;

      let productosHtml = '';
      if (data.productos && data.productos.length > 0) {
        data.productos.forEach((p) => {
          const cantidad = isServicio ? 1 : (parseFloat(p.Cantidad) || 0);
          const importeBase = parseFloat(p.Importe) || 0;

          const subtotalFila = cantidad * importeBase;
          const ivaFila = tieneIVA ? (subtotalFila * 0.16) : 0;
          const totalFila = subtotalFila + ivaFila;

          productosHtml += `
            <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-t text-sm text-gray-500">${!isServicio ? (p.Codigo || '') : 'N/A'}</td>
                <td class="py-2 px-4 border-t text-sm text-gray-900">${p.Nombre}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${cantidad}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${format(importeBase)}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${format(ivaFila)}</td>
                <td class="py-2 px-4 border-t text-right text-sm font-bold">${format(totalFila)}</td>
            </tr>`;
        });
      } else {
        productosHtml = `<tr><td colspan="6" class="text-center py-3 text-gray-500">No hay productos en esta orden.</td></tr>`;
      }

      let proveedorCreditoHtml = '';
      if (data.MetodoPago == 1) {
        proveedorCreditoHtml = `
            <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
            <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${format(prov.Monto_Credito)}</div>`;
      }

      // Generar el HTML completo
      let html = `
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
            ${proveedorCreditoHtml}
        </div>

        <h3 class="text-md font-semibold mb-3 text-gray-700 border-b pb-2">PRODUCTOS DE LA ORDEN</h3>
        <div class="overflow-x-auto mb-6 border rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Base</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">IVA</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">${productosHtml}</tbody>
            </table>
        </div>`;

      // Carga la sección de adjuntos
      if (typeof generarSeccionAdjuntos === 'function') {
        // Aseguramos que data tenga ID_Solicitud para que el link funcione
        if(!data.ID_Solicitud && data.ID_Orden) data.ID_Solicitud = data.ID_Orden; // Fallback por si acaso
        html += generarSeccionAdjuntos(data);
      }

      html += `
        <div class="flex justify-end mt-6">
             <button onclick="globalCancelarSolicitud(${data.ID_Solicitud}, () => document.getElementById('btn-volver-pagos').click())" 
                    class="px-6 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition shadow-sm">
                Rechazar / Cancelar Pago
            </button>
        </div>
      `;

      return html;
    }
  };
}

function ListaPagos() {
  return {
    pagos: [],
    loading: true,
    filtroMetodoPago: 'todos',

    get pagosFiltrados() {
      if (this.filtroMetodoPago === 'todos') {
        return this.pagos
      }
      return this.pagos.filter((p) => p.MetodoPago === this.filtroMetodoPago)
    },

    async init() {
      this.loading = true
      this.pagos = [];
      try {
        const url = `api/pagos/programados?t=${new Date().getTime()}`;
        const listpagos = await SendDataEnd(url);

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

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      // Crea fecha y formatea a DD/MM/AAAA
      const date = new Date(dateString);
      return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
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


/**
 * Lógica para el modal "Lista de pagos pendientes"
 */

function ListaPagos() {
  return {
    pagos: [],
    loading: true,
    filtroMetodoPago: 'todos',

    get pagosFiltrados() {
      if (this.filtroMetodoPago === 'todos') {
        return this.pagos
      }
      return this.pagos.filter((p) => p.MetodoPago === this.filtroMetodoPago)
    },

    async init() {
      this.loading = true
      this.pagos = [];
      try {
        const url = `api/pagos/programados?t=${new Date().getTime()}`;
        const listpagos = await SendDataEnd(url);

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

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return 'N/A';

      return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      });
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
  let icon = '📄';
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

      // Recargar la vista de detalles
      await mostrarDetallePago(idSolicitud);

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
    const data = await SendDataEnd(`api/orden-compra/details/${id}`);

    if (!data) throw new Error("No se recibieron datos del servidor.");

    const fechaAprobacion = (data.OrdenCompra && data.OrdenCompra.Fecha)
        ? new Date(data.OrdenCompra.Fecha).toLocaleDateString('es-MX')
        : 'Pendiente';

    const prov = data.proveedor || {};

    const format = (val) => parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

    const totalFormateado = format(data.cotizacion?.Total);

    const metodoPagoTexto =
        data.MetodoPago == 0 ? 'Contado' :
            data.MetodoPago == 1 ? 'Crédito' :
                data.MetodoPago == 9 ? 'En Espera' : 'N/A';

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
                ${data.cuenta_details
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
        const cantidad = isServicio ? 1 : (parseFloat(p.Cantidad) || 0);
        const importeBase = parseFloat(p.Importe) || 0;

        const subtotalFila = cantidad * importeBase;
        const ivaFila = tieneIVA ? (subtotalFila * 0.16) : 0;
        const totalFila = subtotalFila + ivaFila;

        html += `
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 border-t text-sm text-gray-500">${!isServicio ? (p.Codigo || '') : 'N/A'}</td>
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
      if(!data.ID_Solicitud && data.ID_Orden) data.ID_Solicitud = data.ID_Orden;
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

async function guardarEstadoPorPagar(idSolicitud) {
  // Verificación del comprobante
  try {
    const orderDetails = await SendDataEnd(`api/orden-compra/details/${idSolicitud}`);
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
      '¿Está seguro de que desea cambiar el estado de esta solicitud a "Por Pagar"?'
  );

  if (!confirmacion) return;

  try {
    const apiResult = await SendDataEnd(`api/solicitudes/cambiarEstado/${idSolicitud}`, {
      method: 'POST',
      body: { nuevoEstado: 'Por Pagar' },
    });

    if (apiResult.success) {
      mostrarNotificacion('Guardado con éxito.', 'success');

      regresarListaPagos();
      //Disparador para recarga de vista
      window.dispatchEvent(new CustomEvent('reload-pagos'));

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

/**
 * Lógica para el modal "Facturas pendientes"
 */

async function initFichasPago() {
  const tbodyContado = document.getElementById('body-contado');
  const tbodyCredito = document.getElementById('body-credito');

  // Referencias a los textos del contador en el menú
  const badgeContado = document.getElementById('count-contado-fichas');
  const badgeCredito = document.getElementById('count-credito-fichas');

  // Estado inicial
  if(badgeContado) badgeContado.textContent = 'Cargando...';
  if(badgeCredito) badgeCredito.textContent = 'Cargando...';

  tbodyContado.innerHTML = '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>';
  tbodyCredito.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">Cargando datos...</td></tr>';
  // --- Funciones auxiliares conservadas ---
  function calcularFechaVencimiento(fechaStr, diasStr) {
    if (!fechaStr) return new Date('2999-12-31');
    const fechaSimple = fechaStr.split(' ')[0];
    const partes = fechaSimple.split('-');
    if (partes.length !== 3) return new Date('2999-12-31');

    const anio = parseInt(partes[0]);
    const mes = parseInt(partes[1]) - 1;
    const dia = parseInt(partes[2]);

    const fechaAprobacion = new Date(anio, mes, dia);
    const diasCredito = parseInt(diasStr) || 0;

    fechaAprobacion.setDate(fechaAprobacion.getDate() + diasCredito);
    return fechaAprobacion;
  }

  function getClaseSemaforo(fechaVencimiento, hoyNormalizado) {
    const diffMs = fechaVencimiento.getTime() - hoyNormalizado.getTime();
    const diasDiferencia = Math.floor(diffMs / 86400000);

    if (diasDiferencia < 0) return 'bg-gray-900 text-white hover:bg-gray-800';
    if (diasDiferencia < 5) return 'bg-red-100 text-red-800 hover:bg-red-200';
    if (diasDiferencia < 15) return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200';
    return 'hover:bg-gray-50';
  }

  try {
    // 1. PETICIÓN OPTIMIZADA (Una sola llamada)
    const ordenes = await SendDataEnd('api/facturas-por-pagar');

    if (!ordenes || ordenes.length === 0) {
      if(badgeContado) badgeContado.textContent = '0 pendientes';
      if(badgeCredito) badgeCredito.textContent = '0 pendientes';
      tbodyContado.innerHTML = '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No hay registros disponibles.</td></tr>';
      tbodyCredito.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros disponibles.</td></tr>';
      return;
    }

    // 2. Filtrado en memoria
    let ordenesContado = ordenes.filter(o => o.MetodoPago == '0');
    let ordenesCredito = ordenes.filter(o => o.MetodoPago == '1');

    // Actualizar contenedores del menu
    if(badgeContado) badgeContado.textContent = `${ordenesContado.length} pendientes`;
    if(badgeCredito) badgeCredito.textContent = `${ordenesCredito.length} pendientes`;

    // Ordenar Crédito por fecha
    ordenesCredito.sort((a, b) => {
      const fechaA = calcularFechaVencimiento(a.Fecha_Aprobacion, a.Dias_Credito);
      const fechaB = calcularFechaVencimiento(b.Fecha_Aprobacion, b.Dias_Credito);
      return fechaA - fechaB;
    });

    // Limpiar tablas
    tbodyContado.innerHTML = '';
    tbodyCredito.innerHTML = '';

    // --- RENDERIZADO TABLA CONTADO ---
    if (ordenesContado.length === 0) {
      tbodyContado.innerHTML = '<tr><td colspan="7" class="px-4 py-3 text-center text-gray-500">No hay registros de contado.</td></tr>';
    } else {
      ordenesContado.forEach((det) => {
        // Nota: Usamos det.Total y det.RazonSocial directamente (ya no det.cotizacion.Total)
        const total = det.Total ? parseFloat(det.Total).toLocaleString('es-MX', {style: 'currency', currency: 'MXN'}) : '-';

        const fila = `
          <tr class="hover:bg-gray-50 transition border-b">
            <td class="px-4 py-2">${det.DepartamentoNombre || '-'}</td>
            <td class="px-4 py-2">${det.Complejo || '-'}</td>
            <td class="px-4 py-2">${det.No_Folio || '-'}</td>
            <td class="px-4 py-2">${det.RazonSocial || '-'}</td>
            <td class="px-4 py-2">${det.Banco || '-'}</td>
            <td class="px-4 py-2">${total}</td>
            <td class="px-4 py-2 text-center">
              <button onclick="mostrarDetalleFicha(${det.ID_Solicitud}, '${det.MetodoPago}')"
                      class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-xs uppercase">
                VER
              </button>
            </td>
          </tr>`;
        tbodyContado.insertAdjacentHTML('beforeend', fila);
      });
    }

    // --- RENDERIZADO TABLA CRÉDITO ---
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);

    if (ordenesCredito.length === 0) {
      tbodyCredito.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-gray-500">No hay registros a crédito.</td></tr>';
    } else {
      ordenesCredito.forEach((det) => {
        const fechaVencimiento = calcularFechaVencimiento(det.Fecha_Aprobacion, det.Dias_Credito);
        const claseFila = getClaseSemaforo(fechaVencimiento, hoy);

        const diffTime = fechaVencimiento.getTime() - hoy.getTime();
        const diffDays = Math.floor(diffTime / 86400000);

        let diasTexto = '';
        if (diffDays < 0) diasTexto = `<span class="font-bold">Vencido (${Math.abs(diffDays)} días)</span>`;
        else if (diffDays === 0) diasTexto = `<span class="font-bold">Vence hoy</span>`;
        else diasTexto = `${diffDays} días`;

        const total = det.Total ? parseFloat(det.Total).toLocaleString('es-MX', {style: 'currency', currency: 'MXN'}) : '-';

        const fila = `
          <tr class="${claseFila} transition border-b">
            <td class="px-4 py-2">${det.DepartamentoNombre || '-'}</td>
            <td class="px-4 py-2">${det.Complejo || '-'}</td>
            <td class="px-4 py-2">${det.No_Folio || '-'}</td>
            <td class="px-4 py-2">${det.RazonSocial || '-'}</td>
            <td class="px-4 py-2">${det.Banco || '-'}</td>
            <td class="px-4 py-2">${total}</td>
            <td class="px-4 py-2 text-center text-sm">${diasTexto}</td>
            <td class="px-4 py-2 text-center">
              <button onclick="mostrarDetalleFicha(${det.ID_Solicitud}, '${det.MetodoPago}')"
                      class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition text-xs uppercase">
                VER
              </button>
            </td>
          </tr>`;
        tbodyCredito.insertAdjacentHTML('beforeend', fila);
      });
    }

  } catch (error) {
    console.error(error);
    tbodyContado.innerHTML = '<tr><td colspan="7" class="px-4 py-3 text-center text-red-500">Error al cargar datos.</td></tr>';
    tbodyCredito.innerHTML = '<tr><td colspan="8" class="px-4 py-3 text-center text-red-500">Error al cargar datos.</td></tr>';
  }
}

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

    // Helper para moneda
    const format = (val) => parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

    const totalFormateado = format(data.cotizacion?.Total);

    const metodoPagoTexto =
        data.MetodoPago == 0
            ? 'Efectivo'
            : data.MetodoPago == 1
                ? 'Crédito'
                : data.MetodoPago == 9
                    ? 'En Espera'
                    : 'N/A'

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
    `

    if (data.productos && data.productos.length > 0) {
      data.productos.forEach((p) => {
        // 2. CÁLCULOS POR FILA
        const cantidad = isServicio ? 1 : (parseFloat(p.Cantidad) || 0);
        const importeBase = parseFloat(p.Importe) || 0;

        const subtotalFila = cantidad * importeBase;
        const ivaFila = tieneIVA ? (subtotalFila * 0.16) : 0;
        const totalFila = subtotalFila + ivaFila;

        html += `
            <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-t text-sm text-gray-500">${!isServicio ? (p.Codigo || '') : 'N/A'}</td>
                <td class="py-2 px-4 border-t text-sm text-gray-900">${p.Nombre}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${cantidad}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${format(importeBase)}</td>
                <td class="py-2 px-4 border-t text-right text-sm">${format(ivaFila)}</td>
                <td class="py-2 px-4 border-t text-right text-sm font-bold text-gray-900">${format(totalFila)}</td>
            </tr>
        `
      })
    } else {
      html += `<tr><td colspan="6" class="text-center py-3 text-gray-500">No hay productos en esta orden.</td></tr>`
    }

    html += `
            </tbody>
        </table>
      </div>
      `

    // Sección de Adjuntos
    if (typeof generarSeccionAdjuntos === 'function') {
      // Asegurar ID
      if(!data.ID_Solicitud && data.ID_Orden) data.ID_Solicitud = data.ID_Orden;
      html += generarSeccionAdjuntos(data);
    }

    html += `
      <div class="mt-4 pt-4 border-t" id="factura-uploader-container"></div> 
      <div class="flex justify-between mt-6 gap-4">
        <button onclick="CerrarOrden(${id}, '${metodoPago}', volverAFichas, initFichasPago)" class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded-lg transition w-full shadow-sm">
          Cerrar requisición (Pagada)
        </button>
      </div>
    `

    detalleDiv.innerHTML = html
    renderFacturaUploader(id, metodoPago);

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

function renderFacturaUploader(idSolicitud, metodoPago) {
  const container = document.getElementById('factura-uploader-container');
  if (!container) return;

  container.innerHTML = `
        <div id="file-preview-factura" class="hidden mb-4 p-2 border border-dashed rounded-lg"></div> 
        <input type="file" id="archivo-factura" class="hidden" accept="image/*,.pdf,.xml" onchange="handleFacturaFileSelect(this, ${idSolicitud}, '${metodoPago}')"> 
        <button id="btn-upload-factura" onclick="document.getElementById('archivo-factura').click()" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-4 rounded-lg transition"> 
            Subir Factura
        </button>
    `;
}

function handleFacturaFileSelect(input, idSolicitud, metodoPago) {
  const file = input.files[0];
  if (!file) {
    removeFacturaFile(idSolicitud);
    return;
  }

  const previewContainer = document.getElementById('file-preview-factura');
  const uploadButton = document.getElementById('btn-upload-factura');

  let icon = '📄';
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
  uploadButton.onclick = () => uploadFactura(idSolicitud, metodoPago);
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

async function uploadFactura(idSolicitud, metodoPago) {
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

          // --- RECARGA DE VISTAS
          if (typeof initFichasPago === 'function') initFichasPago();
          await mostrarDetalleFicha(idSolicitud, metodoPago);

        } else {
            throw new Error(result.message || 'Error desconocido del servidor.');
        }
    } catch (error) {
        console.error('Error al subir factura:', error);
        mostrarNotificacion(`Error al subir el archivo: ${error.message}`, 'error');
        handleFacturaFileSelect(fileInput, idSolicitud, metodoPago);
    } finally {
        uploadButton.disabled = false;
    }
}