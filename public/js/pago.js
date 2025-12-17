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
        const detallesPromises = ordenes.map((o) =>
          SendDataEnd(`api/orden-compra/details/${o.ID_Solicitud}`),
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
                return this.pagos;
            }
            return this.pagos.filter(p => p.MetodoPago === this.filtroMetodoPago);
        },
        
        async init() {
            this.loading = true;
            try {
                const listpagos = await SendDataEnd('api/pagos/programados');
                if (!listpagos || listpagos.length === 0) {
                    this.pagos = [];
                    return;
                }
                this.pagos = listpagos;
            } catch (error) {
                console.error('Error al cargar pagos programados:', error);
                this.pagos = [];
            } finally {
                this.loading = false;
            }
        },
        formatCurrency(value) {
            if (value === null || isNaN(value)) return 'N/A';
            return parseFloat(value).toLocaleString('es-MX', {
                style: 'currency',
                currency: 'MXN'
            });
        },
        exportarExcel() {
            let url = `${BASE_URL}api/pagos/exportar`;
            if (this.filtroMetodoPago !== 'todos') {
                url += `?metodo_pago=${this.filtroMetodoPago}`;
            }
            window.location.href = url;
        }
    }
}
