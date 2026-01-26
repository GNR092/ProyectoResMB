function Reportes(initialData = []) {
  return {
    allData: [],
    filteredData: [],
    paginatedData: [],

    fecha: '',
    porMes: false,
    estado: '',
    departamento: '',
    razonSocial: '',
    proveedor: '',
    metodoPago: '',

    currentPage: 1,
    rowsPerPage: 10,
    get totalPages() {
      return Math.ceil(this.filteredData.length / this.rowsPerPage)
    },

    init() {
      const cleanData = initialData.filter(item => item.ID_Solicitud != null && item.ID_Solicitud !== '');

      this.allData = cleanData.map((item) => {
        item.ProveedorFiltro = item.proveedor ? item.proveedor.RazonSocial : 'N/A'
        return item
      })

      this.applyFiltersAndPaginate()

      this.$watch('fecha', () => this.applyFiltersAndPaginate())
      this.$watch('porMes', (value) => {
        if (!value) {
          this.fecha = ''
        }
        this.applyFiltersAndPaginate()
      })
      this.$watch('estado', () => this.applyFiltersAndPaginate())
      this.$watch('departamento', () => this.applyFiltersAndPaginate())
      this.$watch('razonSocial', () => this.applyFiltersAndPaginate())
      this.$watch('proveedor', () => this.applyFiltersAndPaginate())
      this.$watch('metodoPago', () => this.applyFiltersAndPaginate())
    },

    applyFiltersAndPaginate() {
      this.currentPage = 1
      this.applyFilters()
      this.paginate()
    },

    applyFilters() {
      this.filteredData = this.allData.filter((item) => {
        const filterFecha = this.fecha
        let fechaMatch = true
        if (filterFecha) {
          const itemDate = new Date(item.Fecha + 'T00:00:00')
          const filterDate = new Date(filterFecha + 'T00:00:00')
          if (this.porMes) {
            fechaMatch =
              itemDate.getUTCFullYear() === filterDate.getUTCFullYear() &&
              itemDate.getUTCMonth() === filterDate.getUTCMonth()
          } else {
            fechaMatch =
              itemDate.getUTCDate() === filterDate.getUTCDate() &&
              itemDate.getUTCMonth() === filterDate.getUTCMonth() &&
              itemDate.getUTCFullYear() === filterDate.getUTCFullYear()
          }
        }
        const estadoMatch = !this.estado || item.EstadoOrden === this.estado
        const deptoMatch = !this.departamento || item.DepartamentoNombre === this.departamento
        const razonSocialMatch = !this.razonSocial || item.Complejo === this.razonSocial
        const proveedorMatch = !this.proveedor || item.ProveedorFiltro === this.proveedor
        const metodoPagoMatch = !this.metodoPago || item.MetodoPago == this.metodoPago

        return (
          fechaMatch &&
          estadoMatch &&
          deptoMatch &&
          razonSocialMatch &&
          proveedorMatch &&
          metodoPagoMatch
        )
      })
    },

    paginate() {
      const start = (this.currentPage - 1) * this.rowsPerPage
      const end = start + this.rowsPerPage
      this.paginatedData = this.filteredData.slice(start, end)
    },

    goToPage(page) {
      if (page < 1 || page > this.totalPages) return
      this.currentPage = page
      this.paginate()
    },

    nextPage() {
      this.goToPage(this.currentPage + 1)
    },

    prevPage() {
      this.goToPage(this.currentPage - 1)
    },

    mostrarVerReporte(index) {
      const data = this.paginatedData[index]
      if (!data) return

      document.getElementById('div-reportes').classList.add('hidden')
      document.getElementById('div-ver-reporte').classList.remove('hidden')

      const detallesContainer = document.getElementById('div-ver-reporte')

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
          <button onclick="regresarReportes()" class="text-sm text-gray-600 hover:text-gray-900">&larr; Regresar</button>
          <h2 class="text-lg font-semibold">Detalle Orden #${data.No_Folio || data.ID_Solicitud}</h2>
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
          ${
            data.MetodoPago != 0
              ? `
            <div><strong>Días de credito:</strong> ${prov.Dias_Credito || 'N/A'}</div>
            <div class="md:col-span-2"><strong>Monto máximo del crédito:</strong> ${
              prov.Monto_Credito
                ? parseFloat(prov.Monto_Credito).toLocaleString('es-MX', {
                    style: 'currency',
                    currency: 'MXN',
                  })
                : 'N/A'
            }</div>
          `
              : ''
          }
        </div>
        <h3 class="text-md font-semibold mb-3 text-gray-700">PRODUCTOS DE LA ORDEN</h3>
        <div class="overflow-x-auto shadow rounded-lg mb-6">
          <table class="min-w-full border border-gray-300">
              <thead class="bg-gray-100">
                  <tr>
                      <th class="py-2 px-4 text-left text-sm">Código</th>
                      <th class="py-2 px-4 text-left text-sm">Producto</th>
                      <th class="py-2 px-4 text-right text-sm">Cantidad</th>
                      <th class="py-2 px-4 text-right text-sm">Importe Unit.</th>
                      <th class="py-2 px-4 text-right text-sm">Costo Total</th>
                  </tr>
              </thead>
              <tbody class="bg-white">
      `
      if (data.productos && data.productos.length > 0) {
        data.productos.forEach((p) => {
          const costoTotal = (p.Cantidad * p.Importe).toFixed(2)
          html += `
              <tr class="hover:bg-gray-50">
                  <td class="py-2 px-4 border-t text-sm">${p.Codigo || 'N/A'}</td>
                  <td class="py-2 px-4 border-t text-sm">${p.Nombre}</td>
                  <td class="py-2 px-4 border-t text-right text-sm">${p.Cantidad}</td>
                  <td class="py-2 px-4 border-t text-right text-sm">$${parseFloat(p.Importe).toFixed(2)}</td>
                  <td class="py-2 px-4 border-t text-right text-sm">$${costoTotal}</td>
              </tr>
          `
        })
      } else {
        html += `<tr><td colspan="5" class="text-center py-3 text-sm">No hay productos en esta orden.</td></tr>`
      }
      html += `
              </tbody>
          </table>
      `


      html += generarSeccionAdjuntos(data);

      html +=`
        </div>
      `
      detallesContainer.innerHTML = html
    },
    generarReporteCSV() {
      if (this.filteredData.length === 0) {
        alert('No hay datos filtrados para generar el reporte.')
        return
      }

      const headers = [
        'Folio',
        'Fecha',
        'Departamento',
        'Razon Social',
        'Proveedor',
        'Estado',
        'Metodo de Pago',
        'Importe Total',
      ]

      const escapeCSV = (value) => {
        if (value === null || value === undefined) return ''
        let str = String(value)
        if (str.includes(',') || str.includes('\n') || str.includes('"')) {
          str = str.replace(/"/g, '""')
          return `"${str}"`
        }
        return str
      }

      const rows = this.filteredData.map((item) => {
        const metodoPagoTexto =
          item.MetodoPago == 0 ? 'Efectivo' : item.MetodoPago == 1 ? 'Crédito' : 'En Espera'
        const total = item.cotizacion?.Total || '0.00'

        return [
          escapeCSV(item.No_Folio),
          escapeCSV(item.Fecha),
          escapeCSV(item.DepartamentoNombre),
          escapeCSV(item.Complejo),
          escapeCSV(item.ProveedorFiltro),
          escapeCSV(item.EstadoOrden),
          escapeCSV(metodoPagoTexto),
          total,
        ].join(',')
      })

      const granTotal = this.filteredData.reduce((sum, item) => {
        return sum + parseFloat(item.cotizacion?.Total || 0)
      }, 0)

      const totalRow = ['', '', '', '', '', '', 'TOTAL', granTotal.toFixed(2)].join(',')

      const csvContent = [headers.join(','), ...rows, '', totalRow].join('\n')
      const blob = new Blob([`\uFEFF${csvContent}`], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')

      if (link.download !== undefined) {
        const url = URL.createObjectURL(blob)
        link.setAttribute('href', url)
        link.setAttribute('download', 'reporte_de_gastos.csv')
        link.style.visibility = 'hidden'
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
      }
    },

    clearFilters() {
      this.fecha = ''
      this.porMes = false
      this.estado = ''
      this.departamento = ''
      this.razonSocial = ''
      this.proveedor = ''
      this.metodoPago = ''
      this.applyFiltersAndPaginate()
    }
  }
}

function regresarReportes() {
  document.getElementById('div-reportes').classList.remove('hidden')
  document.getElementById('div-ver-reporte').classList.add('hidden')
}
