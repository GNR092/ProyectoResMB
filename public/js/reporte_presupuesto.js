/**
 * Lógica para el componente de Reporte de Presupuesto (Alpine.js)
 */
function registrarComponenteReportePresupuesto() {
    Alpine.data('reportePresupuestoComponent', function () {
        return {
            pantalla: 'menu', // 'menu', 'presupuesto', 'cuentas', 'completo', 'proveedores', 'compras', 'movimientos'
            idRazonSocial: '',
            idPlace: [], // Ahora es un array para selecciones múltiples
            verGlobal: false,
            anio: '',
            meses: [],

            razonesSociales: [],
            todosPlaces: [],
            departamentos: [], // Representa Unidades Operativas ahora en el reporte
            departamentosBancos: [],
            departamentosCompleto: [],
            departamentosOriginales: [],

            // Nuevos arrays para las sub-pantallas
            listaProveedores: [],
            listaProveedoresFiltrada: [], // Para búsqueda local
            proveedorSeleccionado: null, // Para el modal de detalles
            
            // Paginación proveedores
            currentPageProveedor: 1,
            rowsPerPageProveedor: 10,
            filtroNombreProveedor: '',
            filtroServicioProveedor: '',

            reporteCompras: [],
            movimientosProveedor: [],
            movimientoSeleccionado: null,

            // Paginación y filtrado movimientos
            currentPageMovimientos: 1,
            rowsPerPageMovimientos: 15,
            filtroTextoMovimientos: '',

            dptosSeleccionados: [],
            choicesDpto: null,
            choicesMeses: null,
            choicesPlaces: null, // Instancia para el selector de complejos
            years: [],
            totalesGenerales: {
                asignado: 0,
                comprometido: 0,
                ejecutado: 0,
                disponible: 0,
                porcentaje: 0
            },

            cargando: false,
            mensaje: '',
            error: false,
            hayExcedidos: false,

            init() {
                if (this.$el) {
                    this.razonesSociales = JSON.parse(this.$el.dataset.razonesJson || '[]');
                    this.todosPlaces     = JSON.parse(this.$el.dataset.placesJson || '[]');
                }

                const now = new Date();
                const currentYear = now.getFullYear();
                
                // 1. Generar lista de años primero
                this.years = [];
                for (let i = currentYear; i >= currentYear - 5; i--) {
                    this.years.push(String(i));
                }

                // 2. Establecer valores por defecto al final para asegurar el vínculo reactivo
                this.anio = String(currentYear);
                this.meses = [String(now.getMonth() + 1)];
                this.idPlace = [];
            },

            initChoicesMeses(refName) {
                if (typeof Choices === 'undefined') {
                    console.error('Choices.js no está cargada.');
                    return;
                }
                
                if (this.choicesMeses) {
                    this.choicesMeses.destroy();
                    this.choicesMeses = null;
                }

                if (!refName) return; // Salir si no hay nombre de referencia

                const selectEl = this.$refs[refName];
                if (!selectEl) {
                    console.warn(`No se encontró el elemento x-ref="${refName}"`);
                    return;
                }

                this.choicesMeses = new Choices(selectEl, {
                    removeItemButton: true,
                    itemSelectText: '',
                    placeholderValue: 'Seleccione meses',
                    searchEnabled: false,
                    shouldSort: false,
                    allowHTML: true
                });

                // Establecer valor inicial (asegurarse de que sean strings)
                if (this.meses && this.meses.length > 0) {
                    this.choicesMeses.removeActiveItems();
                    this.choicesMeses.setChoiceByValue(this.meses.map(String));
                }

                selectEl.addEventListener('change', () => {
                    this.meses = this.choicesMeses.getValue(true).map(String);
                    if (this.pantalla === 'presupuesto') this.cargarComparativo();
                    if (this.pantalla === 'cuentas') this.cargarComparativoBancos();
                    if (this.pantalla === 'completo') this.cargarReporteCompleto();

                    // Cargadores para las nuevas pantallas
                    if (this.pantalla === 'compras') this.cargarReporteCompras();
                    if (this.pantalla === 'movimientos') this.cargarMovimientosProveedor();
                });
            },

            initChoicesPlaces(refName) {
                if (typeof Choices === 'undefined') return;
                
                if (this.choicesPlaces) {
                    this.choicesPlaces.destroy();
                    this.choicesPlaces = null;
                }

                if (!refName) return;

                const selectEl = this.$refs[refName];
                if (!selectEl) return;

                this.choicesPlaces = new Choices(selectEl, {
                    removeItemButton: true,
                    itemSelectText: '',
                    placeholderValue: 'Seleccione Complejo(s)',
                    searchPlaceholderValue: 'Buscar complejo...',
                    shouldSort: false,
                    allowHTML: true
                });

                // Establecer valor inicial si hay algo en idPlace
                if (this.idPlace && this.idPlace.length > 0) {
                    this.choicesPlaces.setChoiceByValue(this.idPlace.map(String));
                }

                selectEl.addEventListener('change', () => {
                    this.idPlace = this.choicesPlaces.getValue(true).map(String);
                    if (this.pantalla === 'presupuesto') this.cargarComparativo();
                    if (this.pantalla === 'cuentas') this.cargarComparativoBancos();
                    if (this.pantalla === 'completo') this.cargarReporteCompleto();

                    if (this.pantalla === 'compras') this.cargarReporteCompras();
                    if (this.pantalla === 'movimientos') this.cargarMovimientosProveedor();
                });
            },

            irAPantalla(nueva) {
                this.pantalla = nueva;
                this.departamentos = [];
                this.departamentosBancos = [];
                this.departamentosCompleto = [];
                this.departamentosOriginales = [];

                this.listaProveedores = [];
                this.listaProveedoresFiltrada = [];
                this.proveedorSeleccionado = null;
                this.reporteCompras = [];
                this.movimientosProveedor = [];

                this.dptosSeleccionados = [];
                this.verGlobal = false;
                
                if (this.choicesDpto) {
                    this.choicesDpto.destroy();
                    this.choicesDpto = null;
                }
                if (this.choicesMeses) {
                    this.choicesMeses.destroy();
                    this.choicesMeses = null;
                }
                if (this.choicesPlaces) {
                    this.choicesPlaces.destroy();
                    this.choicesPlaces = null;
                }
                
                this.idPlace = [];
                this.idRazonSocial = '';

                if (nueva !== 'menu') {
                    this.$nextTick(() => {
                        const refMapMeses = {
                            'presupuesto': 'mesesSelectorPresupuesto',
                            'cuentas': 'mesesSelectorCuentas',
                            'completo': 'mesesSelectorCompleto',
                            'compras': 'mesesSelectorCompras',
                            'movimientos': 'mesesSelectorMovimientos'
                        };
                        const refMapPlaces = {
                            'presupuesto': 'placesSelectorPresupuesto',
                            'cuentas': 'placesSelectorCuentas',
                            'completo': 'placesSelectorCompleto',
                            'compras': 'placesSelectorCompras',
                            'movimientos': 'placesSelectorMovimientos'
                        };
                        
                        if (refMapMeses[nueva]) this.initChoicesMeses(refMapMeses[nueva]);
                        if (refMapPlaces[nueva]) this.initChoicesPlaces(refMapPlaces[nueva]);

                        // Carga automática si aplica
                        if (nueva === 'proveedores') this.cargarListaProveedores();
                        if (nueva === 'movimientos') this.cargarMovimientosProveedor();
                    });
                }
            },

            actualizarRazonSocial(pantalla) {
                this.idPlace = [];
                this.departamentos = [];
                this.departamentosBancos = [];
                this.departamentosCompleto = [];
                this.departamentosOriginales = [];
                
                const refMap = {
                    'presupuesto': 'placesSelectorPresupuesto',
                    'cuentas': 'placesSelectorCuentas',
                    'completo': 'placesSelectorCompleto',
                    'compras': 'placesSelectorCompras',
                    'movimientos': 'placesSelectorMovimientos'
                };
                
                this.$nextTick(() => {
                    if (refMap[pantalla]) this.initChoicesPlaces(refMap[pantalla]);
                });
            },

            // Funciones para la pantalla de proveedores
            async cargarListaProveedores() {
                this.cargando = true;
                this.listaProveedores = [];
                this.listaProveedoresFiltrada = [];
                this.currentPageProveedor = 1;
                this.filtroNombreProveedor = '';
                this.filtroServicioProveedor = '';

                try {
                    const res = await fetch(`${BASE_URL}api/providers/full-list`);
                    if (res.ok) {
                        this.listaProveedores = await res.json();
                        this.aplicarFiltrosProveedor();
                    }
                } catch (e) { console.error(e); }
                finally { this.cargando = false; }
            },

            aplicarFiltrosProveedor() {
                const nombre = this.filtroNombreProveedor.toLowerCase();
                const servicio = this.filtroServicioProveedor.toLowerCase();

                this.listaProveedoresFiltrada = this.listaProveedores.filter(p => 
                    p.RazonSocial.toLowerCase().includes(nombre) &&
                    (p.Servicio ? p.Servicio.toLowerCase().includes(servicio) : true)
                );
                this.currentPageProveedor = 1; // Reset a página 1 al filtrar
            },

            get totalPagesProveedor() {
                return Math.ceil(this.listaProveedoresFiltrada.length / this.rowsPerPageProveedor) || 1;
            },

            get paginatedProveedores() {
                const start = (this.currentPageProveedor - 1) * this.rowsPerPageProveedor;
                const end = start + this.rowsPerPageProveedor;
                return this.listaProveedoresFiltrada.slice(start, end);
            },

            get paginationDataProveedor() {
                if (typeof generatePaginationNumbers === 'undefined') return [];
                return generatePaginationNumbers(this.currentPageProveedor, this.totalPagesProveedor, 7);
            },

            cambiarPaginaProveedor(page) {
                if (page < 1 || page > this.totalPagesProveedor) return;
                this.currentPageProveedor = page;
            },

            verDetalleProveedor(p) {
                this.proveedorSeleccionado = p;
            },

            exportarProveedoresExcel() {
                window.location.href = `${BASE_URL}api/providers/exportar-excel`;
            },

            async cargarReporteCompras() {
                if (!this.verGlobal && (!this.idPlace || this.idPlace.length === 0)) return;
                this.cargando = true;
                console.log("Cargando reporte de compras...");
                setTimeout(() => { this.cargando = false; }, 500);
            },

            async cargarMovimientosProveedor() {
                // Ya no hay restricciones de verGlobal o complejos: cargamos todo para filtrar localmente
                this.cargando = true;
                this.movimientosProveedor = [];
                this.mensaje = '';
                this.currentPageMovimientos = 1;

                try {
                    const res = await fetch(`${BASE_URL}api/historic/movimientos-proveedor`);
                    if (res.ok) {
                        this.movimientosProveedor = await res.json();
                    } else {
                        this.mensaje = 'Error al cargar los movimientos del servidor.';
                        this.error = true;
                    }
                } catch (e) {
                    console.error("Error cargando movimientos:", e);
                    this.mensaje = 'Error de conexión.';
                    this.error = true;
                } finally {
                    this.cargando = false;
                }
            },

            get movimientosFiltrados() {
                const search = (this.filtroTextoMovimientos || '').toLowerCase();
                const selectedPlaces = (this.idPlace || []).map(String);
                const selectedMeses = (this.meses || []).map(String);

                return this.movimientosProveedor.filter(m => {
                    // Filtro de Texto (Folio o Proveedor)
                    const matchText = !search || 
                                     (m.No_Folio || '').toLowerCase().includes(search) || 
                                     (m.ProveedorNombre || '').toLowerCase().includes(search);
                    
                    // Filtro de Complejo (Place)
                    let matchPlace = true;
                    if (selectedPlaces.length > 0) {
                        matchPlace = selectedPlaces.includes(String(m.ID_Place));
                    }

                    // Filtro de Meses (Si no hay seleccionados, mostramos todos los del año)
                    let matchMes = true;
                    if (selectedMeses.length > 0) {
                        const mesSol = new Date(m.Fecha).getMonth() + 1;
                        matchMes = selectedMeses.includes(String(mesSol));
                    }

                    // Filtro de Año (Siempre mostramos del año seleccionado)
                    let matchAnio = true;
                    if (this.anio) {
                        const anioSol = new Date(m.Fecha).getFullYear();
                        matchAnio = anioSol === parseInt(this.anio);
                    }

                    return matchText && matchPlace && matchMes && matchAnio;
                });
            },

            get totalPagesMovimientos() {
                return Math.ceil(this.movimientosFiltrados.length / this.rowsPerPageMovimientos) || 1;
            },

            get paginatedMovimientos() {
                const start = (this.currentPageMovimientos - 1) * this.rowsPerPageMovimientos;
                const end = start + this.rowsPerPageMovimientos;
                return this.movimientosFiltrados.slice(start, end);
            },

            cambiarPaginaMovimientos(page) {
                if (page < 1 || page > this.totalPagesMovimientos) return;
                this.currentPageMovimientos = page;
            },

            async mostrarVerMovimiento(idSolicitud) {
                const divMovimientos = document.getElementById('div-movimientos');
                const divVer = document.getElementById('div-ver-movimiento');
                const detallesContainer = document.getElementById('detalles-movimiento-solicitud');

                if (divMovimientos) divMovimientos.classList.add('hidden');
                if (divVer) divVer.classList.remove('hidden');

                if (!detallesContainer) return;
                detallesContainer.innerHTML = '<p class="text-center p-8 text-gray-500">Cargando detalles completos...</p>';

                try {
                    const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`);
                    if (data.error) throw new Error(data.error);

                    let html = generarDetallesSolicitudHTML(data);
                    html += generarComentariosHtml(data);
                    html += generarProductosServiciosHTML(data);

                    if (data.ComentariosUser) {
                        html += `
                            <div class="mt-6 p-4 border rounded-lg bg-gray-100 border-gray-800">
                                <h4 class="text-md font-bold text-gray-800 mb-2">Comentarios o referencias</h4>
                                <p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p>
                            </div>`;
                    }

                    html += generarSeccionAdjuntos(data);

                    // Información Adicional de la Orden de Compra (Exclusiva de Movimientos)
                    // Obtenemos el registro actual del listado para mostrar la data de OrdenCompra pre-cargada
                    const m = this.movimientosProveedor.find(mov => mov.ID_Solicitud == idSolicitud);
                    if (m && m.ID_OrdenCompra) {
                        html += `
                            <div class="mt-8 border-t pt-6">
                                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b-2 border-teal-500 inline-block pb-1">Orden de Compra / Pagos</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Estado Orden</p>
                                        <p class="text-sm font-bold text-orange-600 px-2 py-1 bg-orange-50 rounded inline-block">${m.OrdenEstado}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Fecha Orden</p>
                                        <p class="text-sm font-medium text-gray-800">${m.OrdenFecha}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Fecha Pago Realizado</p>
                                        <p class="text-sm font-bold text-green-600">${m.FechaPagoRealizado || 'Pendiente'}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else if (m) {
                        html += `
                            <div class="mt-8 p-4 bg-orange-50 rounded-xl border border-orange-200">
                                <p class="text-sm text-orange-700 font-medium italic">No se ha generado una Orden de Compra para esta solicitud aún.</p>
                            </div>
                        `;
                    }

                    detallesContainer.innerHTML = html;
                } catch (error) {
                    console.error('Error al cargar detalles del movimiento:', error);
                    detallesContainer.innerHTML = `<p class="text-center p-8 text-red-500 font-bold">No se pudieron cargar los detalles: ${error.message}</p>`;
                }
            },

            regresarAMovimientos() {
                const divVer = document.getElementById('div-ver-movimiento');
                const divMovimientos = document.getElementById('div-movimientos');
                
                if (divVer) divVer.classList.add('hidden');
                if (divMovimientos) divMovimientos.classList.remove('hidden');
            },

            limpiarFiltrosMovimientos() {
                this.filtroTextoMovimientos = '';
                this.idPlace = [];
                const now = new Date();
                this.anio = String(now.getFullYear());
                this.meses = [String(now.getMonth() + 1)];

                if (this.choicesPlaces) {
                    this.choicesPlaces.removeActiveItems();
                }
                if (this.choicesMeses) {
                    this.choicesMeses.removeActiveItems();
                    this.choicesMeses.setChoiceByValue(this.meses.map(String));
                }

                this.cargarMovimientosProveedor();
            },

            get placesFiltrados() {
                if (!this.idRazonSocial) return [];
                return this.todosPlaces.filter(p => String(p.ID_RazonSocial) === String(this.idRazonSocial));
            },

            get departamentosAgrupados() {
                let fuente = [...this.departamentos];
                if (this.pantalla === 'cuentas') fuente = [...this.departamentosBancos];
                if (this.pantalla === 'completo') fuente = [...this.departamentosCompleto];

                // 1. Filtrar Unidades Operativas con presupuesto > 0 (si no es bancos)
                if (this.pantalla === 'presupuesto') {
                    fuente = fuente.filter(d => parseFloat(d.totales?.asignado || 0) > 0);
                } else if (this.pantalla === 'completo') {
                    fuente = fuente.filter(d => parseFloat(d.presupuesto?.asignado || 0) > 0);
                }

                const rsGrupos = [];
                if (this.pantalla === 'presupuesto') this.hayExcedidos = false;

                const crearTotales = () => {
                    if (this.pantalla === 'cuentas') return { inicial: 0, final: 0, usado: 0, porcentaje: 0 };
                    if (this.pantalla === 'completo') return { pAsignado: 0, pComprometido: 0, pEjecutado: 0, pGastado: 0, bInicial: 0, bFinal: 0, pDisponible: 0, pExcedido: 0, pPorcentaje: 0 };
                    return { asignado: 0, comprometido: 0, ejecutado: 0, disponible: 0, excedido: 0, porcentaje: 0 };
                };

                const sumar = (totales, d) => {
                    if (this.pantalla === 'cuentas') {
                        totales.inicial += parseFloat(d.totales?.inicial || 0);
                        totales.final += parseFloat(d.totales?.final || 0);
                        totales.usado += parseFloat(d.totales?.usado || 0);
                    } else if (this.pantalla === 'completo') {
                        totales.pAsignado += parseFloat(d.presupuesto?.asignado || 0);
                        totales.pComprometido += parseFloat(d.presupuesto?.comprometido || 0);
                        totales.pEjecutado += parseFloat(d.presupuesto?.ejecutado || 0);
                        totales.pGastado += parseFloat(d.presupuesto?.gastado || 0);
                    } else {
                        const src = d.totales || d;
                        totales.asignado += parseFloat(src.asignado || 0);
                        totales.comprometido += parseFloat(src.comprometido || 0);
                        totales.ejecutado += parseFloat(src.ejecutado || 0);
                    }
                };

                const calc = (totales) => {
                    if (this.pantalla === 'cuentas') {
                        totales.porcentaje = totales.inicial > 0 ? Math.round((totales.usado / totales.inicial) * 100 * 100) / 100 : 0;
                    } else if (this.pantalla === 'presupuesto') {
                        const totalGasto = totales.comprometido + totales.ejecutado;
                        if (totalGasto > totales.asignado) {
                            totales.disponible = 0;
                            totales.excedido = totalGasto - totales.asignado;
                            this.hayExcedidos = true;
                        } else {
                            totales.disponible = totales.asignado - totalGasto;
                            totales.excedido = 0;
                        }
                        totales.porcentaje = totales.asignado > 0 ? Math.round((totalGasto / totales.asignado) * 100 * 100) / 100 : 0;
                    } else if (this.pantalla === 'completo') {
                        if (totales.pGastado > totales.pAsignado) {
                            totales.pDisponible = 0;
                            totales.pExcedido = totales.pGastado - totales.pAsignado;
                            this.hayExcedidos = true;
                        } else {
                            totales.pDisponible = totales.pAsignado - totales.pGastado;
                            totales.pExcedido = 0;
                        }
                        totales.pPorcentaje = totales.pAsignado > 0 ? Math.round((totales.pGastado / totales.pAsignado) * 100 * 100) / 100 : 0;
                    }
                };

                fuente.forEach(d => {
                    const rsNombre = d.RazonSocialNombre || 'Sin Razón Social';
                    const segNombre = d.SegmentoNombre || 'Sin Segmento';
                    const placeNombre = d.PlaceNombre || 'Sin Place';

                    // CLONAMOS EL OBJETO para no mutar el array original 'this.departamentos'
                    const uniClon = { ...d };
                    
                    // Filtrar detalles internos (partidas) que no tengan presupuesto asignado
                    if (uniClon.detalles) {
                        uniClon.detalles = uniClon.detalles.filter(det => parseFloat(det.asignado || 0) > 0);
                    }

                    let rs = rsGrupos.find(g => g.nombre === rsNombre);
                    if (!rs) {
                        rs = { nombre: rsNombre, segmentos: [], totales: crearTotales() };
                        rsGrupos.push(rs);
                    }
                    sumar(rs.totales, uniClon);
                    
                    if (this.pantalla === 'completo') {
                        rs.totales.bInicial += parseFloat(uniClon.bancos?.inicial || 0);
                        rs.totales.bFinal += parseFloat(uniClon.bancos?.final || 0);
                    }

                    let seg = rs.segmentos.find(s => s.nombre === segNombre);
                    if (!seg) {
                        seg = { nombre: segNombre, complejos: [], totales: crearTotales() };
                        rs.segmentos.push(seg);
                    }
                    sumar(seg.totales, uniClon);

                    let complex = seg.complejos.find(c => c.nombre === placeNombre);
                    if (!complex) {
                        complex = { nombre: placeNombre, departamentos: [], totales: crearTotales() };
                        seg.complejos.push(complex);
                    }
                    sumar(complex.totales, uniClon);

                    complex.departamentos.push(uniClon);
                });

                rsGrupos.forEach(rs => {
                    calc(rs.totales);
                    rs.segmentos.forEach(seg => {
                        calc(seg.totales);
                        seg.complejos.forEach(c => calc(c.totales));
                    });
                });

                return rsGrupos;
            },

            async cargarComparativo() {
                if (this.pantalla === 'cuentas') return this.cargarComparativoBancos();
                if (this.pantalla === 'completo') return this.cargarReporteCompleto();

                if (!this.verGlobal && (!this.idPlace || this.idPlace.length === 0 || !this.anio || this.meses.length === 0)) return;

                const stringMeses = this.meses.join(',');
                this.cargando = true;
                this.departamentos = [];
                this.departamentosOriginales = [];
                this.mensaje = '';

                const targetPlaceId = this.verGlobal ? 0 : (Array.isArray(this.idPlace) ? this.idPlace.join(',') : this.idPlace);

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto/comparativo/${targetPlaceId}/${this.anio}/${stringMeses}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.departamentosOriginales = data.departamentos || [];
                        this.departamentos = [...this.departamentosOriginales];
                        this.totalesGenerales = data.totales_generales || this.getTotalesCero();
                        
                        this.$nextTick(() => this.initChoicesDpto());
                    } else {
                        this.mensaje = 'Error al cargar los datos del servidor.';
                        this.error = true;
                    }
                } catch (e) {
                    console.error("Error cargando comparativo:", e);
                    this.mensaje = 'Error de conexión.';
                    this.error = true;
                } finally {
                    this.cargando = false;
                }
            },

            async cargarComparativoBancos() {
                if (!this.verGlobal && (!this.idPlace || this.idPlace.length === 0 || !this.anio || this.meses.length === 0)) return;
                const stringMeses = this.meses.join(',');
                this.cargando = true;
                this.departamentosBancos = [];
                const targetPlaceId = this.verGlobal ? 0 : (Array.isArray(this.idPlace) ? this.idPlace.join(',') : this.idPlace);

                try {
                    const res = await fetch(`${BASE_URL}api/bancos/comparativo/${targetPlaceId}/${this.anio}/${stringMeses}`);
                    if (res.ok) {
                        const data = await res.json();
                        this.departamentosBancos = data.razones || [];
                    }
                } catch (e) { console.error(e); }
                finally { this.cargando = false; }
            },

            async cargarReporteCompleto() {
                if (!this.verGlobal && (!this.idPlace || this.idPlace.length === 0 || !this.anio || this.meses.length === 0)) return;
                const stringMeses = this.meses.join(',');
                this.cargando = true;
                this.departamentosCompleto = [];
                const targetPlaceId = this.verGlobal ? 0 : (Array.isArray(this.idPlace) ? this.idPlace.join(',') : this.idPlace);

                try {
                    const res = await fetch(`${BASE_URL}api/reporte/completo/${targetPlaceId}/${this.anio}/${stringMeses}`);
                    if (res.ok) {
                        const data = await res.json();
                        this.departamentosCompleto = data.departamentos || [];
                    }
                } catch (e) { console.error(e); }
                finally { this.cargando = false; }
            },

            async cargarGlobal() {
                this.idRazonSocial = '';
                this.idPlace = [];
                this.departamentos = [];
                this.departamentosBancos = [];
                this.departamentosCompleto = [];
                this.departamentosOriginales = [];
                
                if (this.choicesDpto) {
                    this.choicesDpto.destroy();
                    this.choicesDpto = null;
                }
                if (this.choicesPlaces) {
                    this.choicesPlaces.destroy();
                    this.choicesPlaces = null;
                }

                await this.cargarComparativo();
            },

            async exportarExcel() {
                if (this.departamentos.length === 0) {
                    alert("No hay datos para exportar.");
                    return;
                }
                
                const notif = mostrarNotificacion('Generando Excel de Presupuesto...', 'info', 0);
                try {
                    const payload = {
                        titulo: 'Presupuesto vs Ejecutado',
                        mesAnio: this.anio + '-' + this.meses.join(','),
                        datos: this.departamentosAgrupados,
                        hayExcedidos: this.hayExcedidos
                    };

                    const res = await fetch(`${BASE_URL}api/presupuesto/exportar-datos`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `presupuesto_vs_ejecutado_${this.anio}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    }
                } catch (e) { console.error(e); }
                finally { if (notif) notif.click(); }
            },

            async exportarBancosExcel() {
                if (this.departamentosBancos.length === 0) {
                    alert("No hay datos para exportar.");
                    return;
                }

                const notif = mostrarNotificacion('Generando Excel de Bancos...', 'info', 0);
                try {
                    const payload = {
                        titulo: 'Reporte de Cuentas Bancarias',
                        datos: this.departamentosAgrupados
                    };

                    const res = await fetch(`${BASE_URL}api/bancos/exportar-datos`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `reporte_bancos_${this.anio}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    }
                } catch (e) { console.error(e); }
                finally { if (notif) notif.click(); }
            },

            async exportarReporteCompletoExcel() {
                if (this.departamentosCompleto.length === 0) {
                    alert("No hay datos para exportar.");
                    return;
                }

                const notif = mostrarNotificacion('Generando Reporte Consolidado...', 'info', 0);
                try {
                    const payload = {
                        titulo: 'Reporte Consolidado Maestro',
                        mesAnio: this.anio + '-' + this.meses.join(','),
                        datos: this.departamentosAgrupados,
                        hayExcedidos: this.hayExcedidos
                    };

                    const res = await fetch(`${BASE_URL}api/reporte/completo/exportar-datos`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `reporte_consolidado_${this.anio}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    }
                } catch (e) { console.error(e); }
                finally { if (notif) notif.click(); }
            },

            initChoicesDpto() {
                if (this.choicesDpto) this.choicesDpto.destroy();
                const selectEl = this.$refs.filtroDptos;
                if (!selectEl) return;

                this.choicesDpto = new Choices(selectEl, {
                    removeItemButton: true,
                    itemSelectText: '',
                    placeholderValue: 'Todas las Unidades',
                    searchPlaceholderValue: 'Buscar unidad...'
                });

                selectEl.onchange = () => {
                    this.dptosSeleccionados = this.choicesDpto.getValue(true).map(String);
                    this.aplicarFiltroLocal();
                };
            },

            aplicarFiltroLocal() {
                if (this.dptosSeleccionados.length === 0) {
                    this.departamentos = [...this.departamentosOriginales];
                } else {
                    this.departamentos = this.departamentosOriginales.filter(d => 
                        this.dptosSeleccionados.includes(String(d.ID_UnidadOperativa))
                    );
                }
                this.recalcularTotales();
            },

            recalcularTotales() {
                let asignado = 0, comprometido = 0, ejecutado = 0;
                this.departamentos.forEach(d => {
                    asignado += parseFloat(d.totales?.asignado || 0);
                    comprometido += parseFloat(d.totales?.comprometido || 0);
                    ejecutado += parseFloat(d.totales?.ejecutado || 0);
                });
                const totalGasto = comprometido + ejecutado;
                this.totalesGenerales = {
                    asignado, comprometido, ejecutado,
                    disponible: asignado - totalGasto,
                    porcentaje: asignado > 0 ? Math.round((totalGasto / asignado) * 100 * 100) / 100 : 0
                };
            },

            getTotalesCero() {
                return { asignado: 0, comprometido: 0, ejecutado: 0, disponible: 0, porcentaje: 0 };
            },

            formatearMoneda(monto) {
                return new Intl.NumberFormat('es-MX', {
                    style: 'currency',
                    currency: 'MXN'
                }).format(monto || 0);
            },

            getClaseSemaforo(porcentaje) {
                if (porcentaje >= 100) return 'text-red-600 font-bold';
                if (porcentaje >= 80)  return 'text-orange-600 font-bold';
                return 'text-green-600 font-bold';
            }
        };
    });
}

if (window.Alpine) {
    registrarComponenteReportePresupuesto();
} else {
    document.addEventListener('alpine:init', () => {
        registrarComponenteReportePresupuesto();
    });
}
