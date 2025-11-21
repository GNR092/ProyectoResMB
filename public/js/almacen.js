function Almacen(productosIniciales = []) {
  return {
    productos: productosIniciales,
    productosSeleccionados: new Set(),
    productosParaEntregar: [],
    terminoBusqueda: '',
    mostrarBusqueda: false,

    currentPage: 1,
    itemsPerPage: 10,

    get productosFiltrados() {
      const idsProductosParaEntregar = new Set(this.productosParaEntregar.map((p) => p.ID_Producto))

      let filtered = this.productos

      if (this.terminoBusqueda.trim() !== '') {
        const termino = this.terminoBusqueda.toLowerCase()
        filtered = filtered.filter((p) => {
          const codigo = (p.Codigo || '').toLowerCase()
          const nombre = (p.Nombre || '').toLowerCase()
          return codigo.includes(termino) || nombre.includes(termino)
        })
      }

      filtered = filtered.filter((p) => !idsProductosParaEntregar.has(p.ID_Producto))

      return filtered
    },
    get totalSeleccionados() {
      return this.productosSeleccionados.size
    },

    get totalPages() {
      return Math.ceil(this.productosFiltrados.length / this.itemsPerPage)
    },

    get paginatedProducts() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.productosFiltrados.slice(start, end)
    },

    init() {
      console.log('Componente Almacen inicializado.')

      this.$watch('terminoBusqueda', () => {
        this.currentPage = 1
      })
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    },

    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
      }
    },

    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },

    mostrarBuscarMateriales() {
      this.mostrarBusqueda = true
    },
    regresarBuscarMateriales() {
      this.mostrarBusqueda = false
    },

    toggleSeleccionProducto(id) {
      if (this.productosSeleccionados.has(id)) {
        this.productosSeleccionados.delete(id)
      } else {
        this.productosSeleccionados.add(id)
      }
    },

    agregarProductosSeleccionados() {
      if (this.totalSeleccionados === 0) return

      this.productosSeleccionados.forEach((id) => {
        if (this.productosParaEntregar.some((p) => p.ID_Producto === id)) {
          return
        }

        const productoAAgregar = this.productos.find((p) => p.ID_Producto === id)
        if (productoAAgregar) {
          this.productosParaEntregar.push({
            ...productoAAgregar,
            cantidadAEntregar: 1,
          })
        }
      })

      this.productosSeleccionados.clear()
      this.regresarBuscarMateriales()
    },

    eliminarFilaEntrega(idProducto) {
      this.productosParaEntregar = this.productosParaEntregar.filter(
        (p) => p.ID_Producto !== idProducto,
      )
    },

    async entregarMateriales() {
      const fecha = this.$refs['a-date'].value
      const usuarioEntregaNombre = this.$refs['a-user-t'].value
      const departamentoReceptorId = this.$refs['a-departament-r'].value
      const usuarioRecibeNombre = this.$refs['a-user-r'].value

      if (!usuarioEntregaNombre || !departamentoReceptorId || !usuarioRecibeNombre) {
        mostrarNotificacion(
          'Por favor, complete todos los campos de quién entrega y quién recibe.',
          'info',
        )
        return
      }

      if (this.productosParaEntregar.length === 0) {
        mostrarNotificacion('Por favor, agregue al menos un material para entregar.', 'info')
        return
      }

      for (let producto of this.productosParaEntregar) {
        const cantidad = parseInt(producto.cantidadAEntregar, 10)
        const existencia = parseInt(producto.Existencia, 10)
        if (isNaN(cantidad) || cantidad <= 0) {
          mostrarNotificacion(
            `La cantidad para "${producto.Nombre}" debe ser un número mayor a cero.`,
            'alert',
          )
          return
        }
        if (cantidad > existencia) {
          mostrarNotificacion(
            `No puedes entregar ${cantidad} de "${producto.Nombre}" porque solo hay ${existencia} en existencia.`,
            'error',
          )
          return
        }
      }

      const actualizacionExitosa = await this.actualizarInventarioBD();
      if (!actualizacionExitosa) {
        return;
      }

      const dataParaEnviar = {
        fecha: fecha,
        nombreUsuarioEntrega: usuarioEntregaNombre,
        idDepartamentoRecibe: departamentoReceptorId,
        nombreUsuarioRecibe: usuarioRecibeNombre,
        materiales: this.productosParaEntregar.map((p) => ({
          id: p.ID_Producto,
          codigo: p.Codigo,
          nombre: p.Nombre,
          cantidad: p.cantidadAEntregar,
          existencia: p.Existencia,
        })),
      }

      try {
        const responseBlob = await SendDataEnd('api/entrega/pdf', {
          method: 'POST',
          body: dataParaEnviar,
        })

        const url = window.URL.createObjectURL(responseBlob)
        window.open(url, '_blank')

        mostrarNotificacion('PDF generado correctamente.', 'success')

        //Limpieza del formulario
        this.$refs['a-user-t'].value = '';
        this.$refs['a-departament-r'].value = '';
        this.$refs['a-user-r'].value = '';
        this.productosParaEntregar = [];
        this.productosSeleccionados.clear();

      } catch (error) {
        console.error('Error al generar PDF de entrega:', error)

        if (error.status === 400 && error.data && error.data.errors) {
          const errorMessages = Object.values(error.data.errors).join('<br>')
          mostrarNotificacion(`Error de validación:<br>${errorMessages}`, 'error')
        } else if (error.status) {
          if (typeof error.data === 'object' && error.data.errors) {
            const errorMessages = Object.values(error.data.errors).join('<br>')
            mostrarNotificacion(
              `Error del servidor (${error.status}):<br>${errorMessages}`,
              'error',
            )
          } else if (typeof error.data === 'string') {
            mostrarNotificacion(`Error del servidor (${error.status}):<br>${error.data}`, 'error')
          } else {
            mostrarNotificacion(`Error del servidor: ${error.status} ${error.statusText}`, 'error')
          }
        } else {
          mostrarNotificacion(
            'No se pudo conectar con el servidor. Por favor, intente más tarde.',
            'error',
          )
        }
      }
    },

    //Actualizar en BD
    async actualizarInventarioBD() {
      const payload = {
        materiales: this.productosParaEntregar.map(p => ({
          id: p.ID_Producto,
          cantidad: p.cantidadAEntregar
        }))
      };

      try {
        const result = await SendDataEnd('modales/descontarStock', {
          method: 'POST',
          body: payload
        });

        if (result.success) {
          // Actualizar visualmente la existencia
          this.productosParaEntregar.forEach(entregado => {
            const prodEnLista = this.productos.find(p => p.ID_Producto === entregado.ID_Producto);
            if (prodEnLista) {
              prodEnLista.Existencia = parseInt(prodEnLista.Existencia) - parseInt(entregado.cantidadAEntregar);
            }
          });
          return true;
        } else {
          mostrarNotificacion(result.message || 'Error al actualizar el inventario.', 'error');
          return false;
        }
      } catch (error) {
        console.error('Error en actualización de BD:', error);
        mostrarNotificacion('Error de conexión al intentar actualizar el inventario.', 'error');
        return false;
      }
    },
  }
}

function ReporteAlmacen(historial) {
  return {
    historialCompleto: historial,
    searchTerm: '',
    currentPage: 1,
    itemsPerPage: 15,

    get filteredReport() {
      if (!this.searchTerm.trim()) {
        return this.historialCompleto
      }
      const termino = this.searchTerm.toLowerCase()
      return this.historialCompleto.filter((item) => {
        return Object.values(item).some((val) => String(val).toLowerCase().includes(termino))
      })
    },

    get totalPages() {
      return Math.ceil(this.filteredReport.length / this.itemsPerPage)
    },

    get paginatedReport() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredReport.slice(start, end)
    },

    init() {
      this.$watch('searchTerm', () => {
        this.currentPage = 1
      })
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    },

    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
      }
    },

    goToPage(page) {
      this.currentPage = page
    },

    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      })
    },

    getChangeType(registro) {
      if (registro.CodigoAnt !== registro.CodigoNew) return 'Código'
      if (registro.NombreAnt !== registro.NombreNew) return 'Nombre'
      if (registro.ExistenciaAnt !== registro.ExistenciaNew) return 'Existencia'
      return 'N/A'
    },

    getOldValue(registro) {
      if (registro.CodigoAnt !== registro.CodigoNew) return registro.CodigoAnt
      if (registro.NombreAnt !== registro.NombreNew) return registro.NombreAnt
      if (registro.ExistenciaAnt !== registro.ExistenciaNew) return registro.ExistenciaAnt
      return '-'
    },

    getNewValue(registro) {
      if (registro.CodigoAnt !== registro.CodigoNew) return registro.CodigoNew
      if (registro.NombreAnt !== registro.NombreNew) return registro.NombreNew
      if (registro.ExistenciaAnt !== registro.ExistenciaNew) return registro.ExistenciaNew
      return '-'
    },
  }
}

function RegistrarProducto() {
  return {
    isLoading: false,

    async onSubmit() {
      const form = this.$refs.registerForm
      const messageContainer = this.$refs.formMessage
      messageContainer.innerHTML = ''

      this.isLoading = true

      const formData = new FormData(form)

      try {
        const result = await SendDataEnd('modales/registrarMaterial', {
          method: 'POST',
          body: formData,
        })

        if (result.success) {
          mostrarNotificacion(result.message || 'Producto registrado correctamente.', 'success')
          form.reset()
        } else {
          const errorMessage = result.errors
            ? Object.values(result.errors).join('<br>')
            : result.message || 'Ocurrió un error desconocido al registrar el producto.'
          mostrarNotificacion(errorMessage, 'error')
          messageContainer.innerHTML = `<p class="text-red-500">${errorMessage}</p>`
        }
      } catch (error) {
        console.error('Error al registrar producto:', error)
        mostrarNotificacion('Error de conexión al servidor.', 'error')
        messageContainer.innerHTML = `<p class="text-red-500">Error de conexión: ${error.message}</p>`
      } finally {
        this.isLoading = false
      }
    },
  }
}

function CrudProductos() {
  return {
    productos: [],
    searchTerm: '',
    currentPage: 1,
    itemsPerPage: 10,
    isLoading: true,
    isEditing: false,
    editingProducto: null,
    originalProducto: null,

    init() {
      this.fetchProductos()
    },

    async fetchProductos() {
      this.isLoading = true
      try {
        const data = await SendDataEnd('api/product/all', { method: 'GET' })
        this.productos = data
      } catch (error) {
        mostrarNotificacion('Error al cargar los productos.', 'error')
      } finally {
        this.isLoading = false
      }
    },

    get filteredProductos() {
      if (!this.searchTerm.trim()) {
        return this.productos
      }
      const termino = this.searchTerm.toLowerCase()
      return this.productos.filter((p) => {
        return (
          (p.Codigo || '').toLowerCase().includes(termino) ||
          (p.Nombre || '').toLowerCase().includes(termino)
        )
      })
    },

    get paginatedProductos() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredProductos.slice(start, end)
    },

    get totalPages() {
      return Math.ceil(this.filteredProductos.length / this.itemsPerPage)
    },

    nextPage() {
      if (this.currentPage < this.totalPages) this.currentPage++
    },

    prevPage() {
      if (this.currentPage > 1) this.currentPage--
    },

    async eliminarProducto(id) {
      if (
        !(await Confirmar(
          'Eliminar Producto',
          '¿Estás seguro de que deseas eliminar este producto?',
        ))
      )
        return

      try {
        await SendDataEnd(`modales/eliminarProducto/${id}`, { method: 'POST' })
        this.productos = this.productos.filter((p) => p.ID_Producto !== id)
        mostrarNotificacion('Producto eliminado correctamente.', 'success')
      } catch (error) {
        mostrarNotificacion('Error al eliminar el producto.', 'error')
      }
    },

    editarProducto(producto) {
      this.originalProducto = { ...producto }
      this.editingProducto = { ...producto, Razon: '' }
      this.isEditing = true
    },

    regresarTabla() {
      this.isEditing = false
      this.editingProducto = null
      this.originalProducto = null
    },

    async guardarEdicion() {
      if (!this.editingProducto.Nombre || this.editingProducto.Existencia === '') {
        mostrarNotificacion('Nombre y existencia son requeridos.', 'error')
        return
      }

      this.isLoading = true
      try {
        await SendDataEnd(`modales/actualizarProducto/${this.editingProducto.ID_Producto}`, {
          method: 'POST',
          body: {
            Nombre: this.editingProducto.Nombre,
            Existencia: this.editingProducto.Existencia,
          },
        })

        await SendDataEnd('modales/insertarHistorialProducto', {
          method: 'POST',
          body: {
            ID_Producto: this.originalProducto.ID_Producto,
            CodigoAnt: this.originalProducto.Codigo,
            NombreAnt: this.originalProducto.Nombre,
            ExistenciaAnt: this.originalProducto.Existencia,
            CodigoNew: this.editingProducto.Codigo,
            NombreNew: this.editingProducto.Nombre,
            ExistenciaNew: this.editingProducto.Existencia,
            Razon: this.editingProducto.Razon,
          },
        })

        const index = this.productos.findIndex(
          (p) => p.ID_Producto === this.editingProducto.ID_Producto,
        )
        if (index !== -1) {
          this.productos[index] = { ...this.editingProducto }
        }

        mostrarNotificacion('Producto actualizado correctamente.', 'success')
        this.regresarTabla()
      } catch (error) {
        mostrarNotificacion('Error al guardar los cambios.', 'error')
      } finally {
        this.isLoading = false
      }
    },
  }
}
