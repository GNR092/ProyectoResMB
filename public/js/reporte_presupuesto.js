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

            vencimientos: [],
            vencimientosRaw: [], // Solicitudes individuales procesadas
            vencimientosAgrupados: [], // Proveedores agrupados
            reporteDetallado: false, 
            currentPageVencimientos: 1,
            rowsPerPageVencimientos: 15,
            filtroTextoVencimientos: '',
            
            // Nuevos filtros Choices.js para Vencimientos
            filtrosFolioVenc: '',
            filtrosProveedoresVenc: [],
            filtrosRazonesVenc: [],
            filtrosPlacesVenc: [],
            filtrosDeptosVenc: [],
            
            choicesProvVenc: null,
            choicesRazonVenc: null,
            choicesPlaceVenc: null,
            choicesDeptoVenc: null,

            // Paginación y filtrado movimientos
            currentPageMovimientos: 1,
            rowsPerPageMovimientos: 15,
            filtroTextoMovimientos: '',
            fechaInicioMovimientos: '',
            fechaFinMovimientos: '',

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
                
                // Rango por defecto para movimientos: Año completo actual
                this.fechaInicioMovimientos = `${currentYear}-01-01`;
                this.fechaFinMovimientos = `${currentYear}-12-31`;
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
                if (!selectEl) return;

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
                            'completo': 'mesesSelectorCompleto'
                        };
                        const refMapPlaces = {
                            'presupuesto': 'placesSelectorPresupuesto',
                            'cuentas': 'placesSelectorCuentas',
                            'completo': 'placesSelectorCompleto',
                            'movimientos': 'placesSelectorMovimientos'
                        };
                        
                        if (refMapMeses[nueva]) this.initChoicesMeses(refMapMeses[nueva]);
                        if (refMapPlaces[nueva]) this.initChoicesPlaces(refMapPlaces[nueva]);

                        // Carga automática si aplica
                        if (nueva === 'proveedores') this.cargarListaProveedores();
                        if (nueva === 'movimientos') this.cargarMovimientosProveedor();
                        if (nueva === 'vencimientos') {
                            this.cargarReporteVencimientos();
                            this.initChoicesVencimientos();
                        }
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
                        const data = await res.json();
                        this.movimientosProveedor = Array.isArray(data) ? data : [];
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

            async cargarReporteVencimientos() {
                this.cargando = true;
                this.vencimientosRaw = [];
                this.vencimientosAgrupados = [];
                this.currentPageVencimientos = 1;

                try {
                    const res = await fetch(`${BASE_URL}api/historic/reporte-vencimientos`);
                    if (res.ok) {
                        const rawData = await res.json();
                        const hoy = new Date();
                        hoy.setHours(0, 0, 0, 0);

                        // 1. PROCESAMIENTO INDIVIDUAL (DETALLADO)
                        this.vencimientosRaw = (Array.isArray(rawData) ? rawData : []).map(item => {
                            const baseDateStr = item.FechaRefPago || item.FechaOrden;
                            let diasVencidos = 0;
                            let claseSemaforo = 'bg-white';
                            let textoVencimiento = 'Al corriente';

                            if (baseDateStr) {
                                const baseDate = new Date(baseDateStr.replace(/-/g, '/').split(' ')[0]);
                                const fechaVencimiento = new Date(baseDate);
                                fechaVencimiento.setDate(fechaVencimiento.getDate() + (parseInt(item.Dias_Credito) || 0));
                                
                                const diffTime = hoy.getTime() - fechaVencimiento.getTime();
                                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                                if (diffDays > 0) {
                                    diasVencidos = diffDays;
                                    textoVencimiento = `${diasVencidos} d vencidos`;
                                    if (diasVencidos > 15) claseSemaforo = 'bg-gray-900 text-white';
                                    else if (diasVencidos > 5) claseSemaforo = 'bg-red-100 text-red-800';
                                    else claseSemaforo = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    textoVencimiento = `Vence en ${Math.abs(diffDays)} d`;
                                }
                            }

                            return {
                                ...item,
                                importePorPagar: parseFloat(item.Total) || 0,
                                saldoCredito: (parseFloat(item.Monto_Credito) || 0) - (parseFloat(item.Total) || 0),
                                diasVencidos: diasVencidos,
                                textoVencimiento: textoVencimiento,
                                claseSemaforo: claseSemaforo,
                                fechaReferenciaStr: baseDateStr ? new Date(baseDateStr.replace(/-/g, '/')).toLocaleDateString('es-MX') : 'N/A'
                            };
                        });

                        // 2. PROCESAMIENTO AGRUPADO (POR PROVEEDOR)
                        const agrupado = rawData.reduce((acc, item) => {
                            const idProv = item.ID_Proveedor;
                            if (!acc[idProv]) {
                                acc[idProv] = {
                                    ID_Proveedor: item.ID_Proveedor,
                                    RFC: item.RFC,
                                    RazonSocial: item.RazonSocial,
                                    Monto_Credito: parseFloat(item.Monto_Credito) || 0,
                                    Dias_Credito: parseInt(item.Dias_Credito) || 0,
                                    importePorPagar: 0,
                                    fechaReferenciaBase: null,
                                    diasVencidos: 0,
                                    solicitudes: []
                                };
                            }

                            acc[idProv].importePorPagar += (parseFloat(item.Total) || 0);
                            acc[idProv].solicitudes.push({
                                No_Folio: item.No_Folio,
                                ID_Proveedor: item.ID_Proveedor,
                                ID_RazonSocial: item.ID_RazonSocial,
                                ID_Place: item.ID_Place,
                                ID_Dpto: item.ID_Dpto
                            });

                            const baseDateStr = item.FechaRefPago || item.FechaOrden;
                            if (baseDateStr) {
                                const baseDate = new Date(baseDateStr.replace(/-/g, '/').split(' ')[0]);
                                if (!acc[idProv].fechaReferenciaBase || baseDate < acc[idProv].fechaReferenciaBase) {
                                    acc[idProv].fechaReferenciaBase = baseDate;
                                }
                            }
                            return acc;
                        }, {});

                        this.vencimientosAgrupados = Object.values(agrupado).map(prov => {
                            let diasVencidos = 0;
                            let claseSemaforo = 'bg-white';
                            let textoVencimiento = 'Al corriente';

                            if (prov.fechaReferenciaBase) {
                                const fechaVencimiento = new Date(prov.fechaReferenciaBase);
                                fechaVencimiento.setDate(fechaVencimiento.getDate() + prov.Dias_Credito);
                                const diffDays = Math.ceil((hoy.getTime() - fechaVencimiento.getTime()) / (1000 * 60 * 60 * 24));

                                if (diffDays > 0) {
                                    diasVencidos = diffDays;
                                    textoVencimiento = `${diasVencidos} d vencidos`;
                                    if (diasVencidos > 15) claseSemaforo = 'bg-gray-900 text-white';
                                    else if (diasVencidos > 5) claseSemaforo = 'bg-red-100 text-red-800';
                                    else claseSemaforo = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    textoVencimiento = `Vence en ${Math.abs(diffDays)} d`;
                                }
                            }

                            return {
                                ...prov,
                                saldoCredito: prov.Monto_Credito - prov.importePorPagar,
                                diasVencidos: diasVencidos,
                                textoVencimiento: textoVencimiento,
                                claseSemaforo: claseSemaforo,
                                fechaReferenciaStr: prov.fechaReferenciaBase ? prov.fechaReferenciaBase.toLocaleDateString('es-MX') : 'N/A'
                            };
                        });

                        // Ordenar ambas por vencimiento (descendente)
                        this.vencimientosRaw.sort((a, b) => b.diasVencidos - a.diasVencidos);
                        this.vencimientosAgrupados.sort((a, b) => b.diasVencidos - a.diasVencidos);
                    }
                } catch (e) {
                    console.error("Error cargando vencimientos:", e);
                } finally {
                    this.cargando = false;
                }
            },

            get vencimientosFiltrados() {
                const source = this.reporteDetallado ? this.vencimientosRaw : this.vencimientosAgrupados;
                
                const searchFolio = (this.filtrosFolioVenc || '').toLowerCase();
                const selProvs = (this.filtrosProveedoresVenc || []).map(String);
                const selRazones = (this.filtrosRazonesVenc || []).map(String);
                const selPlaces = (this.filtrosPlacesVenc || []).map(String);
                const selDeptos = (this.filtrosDeptosVenc || []).map(String);

                return source.filter(v => {
                    // Si es agrupado, chequeamos si alguna de sus solicitudes cumple los filtros
                    const itemsToCheck = this.reporteDetallado ? [v] : v.solicitudes;

                    return itemsToCheck.some(item => {
                        const matchFolio = !searchFolio || (item.No_Folio || '').toLowerCase().includes(searchFolio);
                        const matchProv = selProvs.length === 0 || selProvs.includes(String(item.ID_Proveedor));
                        const matchRazon = selRazones.length === 0 || selRazones.includes(String(item.ID_RazonSocial));
                        const matchPlace = selPlaces.length === 0 || selPlaces.includes(String(item.ID_Place));
                        const matchDepto = selDeptos.length === 0 || selDeptos.includes(String(item.ID_Dpto));

                        return matchFolio && matchProv && matchRazon && matchPlace && matchDepto;
                    });
                });
            },

            limpiarFiltrosVencimientos() {
                this.filtrosFolioVenc = '';
                this.filtrosProveedoresVenc = [];
                this.filtrosRazonesVenc = [];
                this.filtrosPlacesVenc = [];
                this.filtrosDeptosVenc = [];
                
                if (this.choicesProvVenc) this.choicesProvVenc.removeActiveItems();
                if (this.choicesRazonVenc) this.choicesRazonVenc.removeActiveItems();
                if (this.choicesPlaceVenc) this.choicesPlaceVenc.removeActiveItems();
                if (this.choicesDeptoVenc) this.choicesDeptoVenc.removeActiveItems();
                
                this.currentPageVencimientos = 1;
            },

            initChoicesVencimientos() {
                if (typeof Choices === 'undefined') return;

                const config = { removeItemButton: true, itemSelectText: '', allowHTML: true, shouldSort: false };

                const initOne = (ref, prop, choicesProp) => {
                    const el = this.$refs[ref];
                    if (!el) return;
                    if (this[choicesProp]) this[choicesProp].destroy();
                    this[choicesProp] = new Choices(el, config);
                    el.addEventListener('change', () => {
                        this[prop] = this[choicesProp].getValue(true).map(String);
                        this.currentPageVencimientos = 1;
                    });
                };

                initOne('choicesProvVenc', 'filtrosProveedoresVenc', 'choicesProvVenc');
                initOne('choicesRazonVenc', 'filtrosRazonesVenc', 'choicesRazonVenc');
                initOne('choicesPlaceVenc', 'filtrosPlacesVenc', 'choicesPlaceVenc');
                initOne('choicesDeptoVenc', 'filtrosDeptosVenc', 'choicesDeptoVenc');
            },

            get totalPagesVencimientos() {
                return Math.ceil(this.vencimientosFiltrados.length / this.rowsPerPageVencimientos) || 1;
            },

            get paginatedVencimientos() {
                const start = (this.currentPageVencimientos - 1) * this.rowsPerPageVencimientos;
                return this.vencimientosFiltrados.slice(start, start + this.rowsPerPageVencimientos);
            },

            cambiarPaginaVencimientos(page) {
                if (page < 1 || page > this.totalPagesVencimientos) return;
                this.currentPageVencimientos = page;
            },

            get movimientosFiltrados() {
                if (!Array.isArray(this.movimientosProveedor)) return [];
                
                const search = (this.filtroTextoMovimientos || '').toLowerCase();
                const selectedPlaces = (this.idPlace || []).map(String);
                
                // Convertimos límites de fecha a objetos Date si existen
                const fechaIni = this.fechaInicioMovimientos ? new Date(this.fechaInicioMovimientos + 'T00:00:00') : null;
                const fechaFin = this.fechaFinMovimientos ? new Date(this.fechaFinMovimientos + 'T23:59:59') : null;

                return this.movimientosProveedor.filter(m => {
                    if (!m) return false;
                    // 1. Filtro de Texto (Folio o Proveedor)
                    const matchText = !search || 
                                     (m.No_Folio || '').toLowerCase().includes(search) || 
                                     (m.ProveedorNombre || '').toLowerCase().includes(search);
                    
                    // 2. Filtro de Complejo (Place)
                    let matchPlace = true;
                    if (selectedPlaces.length > 0) {
                        matchPlace = selectedPlaces.includes(String(m.ID_Place));
                    }

                    // 3. Filtro de Rango de Fechas
                    let matchFecha = true;
                    if (m.Fecha) {
                        // Forzamos la interpretación local si m.Fecha es solo fecha (YYYY-MM-DD)
                        const fechaString = m.Fecha.includes(' ') ? m.Fecha : m.Fecha + 'T00:00:00';
                        const fechaMov = new Date(fechaString);
                        
                        if (fechaIni && fechaMov < fechaIni) matchFecha = false;
                        if (fechaFin && fechaMov > fechaFin) matchFecha = false;
                    }

                    return matchText && matchPlace && matchFecha;
                });
            },

            get totalPagesMovimientos() {
                const filtrados = this.movimientosFiltrados;
                return Math.ceil(filtrados.length / this.rowsPerPageMovimientos) || 1;
            },

            get paginatedMovimientos() {
                const filtrados = this.movimientosFiltrados;
                const start = (this.currentPageMovimientos - 1) * this.rowsPerPageMovimientos;
                const end = start + this.rowsPerPageMovimientos;
                return filtrados.slice(start, end);
            },

            cambiarPaginaMovimientos(page) {
                if (page < 1 || page > this.totalPagesMovimientos) return;
                this.currentPageMovimientos = page;
            },

            async exportarMovimientosExcel() {
                if (this.movimientosFiltrados.length === 0) {
                    alert("No hay movimientos para exportar con los filtros actuales.");
                    return;
                }

                const notif = typeof mostrarNotificacion !== 'undefined' ? mostrarNotificacion('Generando Reporte de Movimientos...', 'info', 0) : null;
                
                try {
                    const payload = {
                        titulo: 'Reporte Detallado de Movimientos de Proveedor',
                        filtros: {
                            texto: this.filtroTextoMovimientos,
                            desde: this.fechaInicioMovimientos,
                            hasta: this.fechaFinMovimientos,
                            complejos: (this.idPlace || []).length > 0 ? this.todosPlaces.filter(p => this.idPlace.includes(String(p.ID_Place))).map(p => p.Nombre).join(', ') : 'Todos'
                        },
                        datos: this.movimientosFiltrados
                    };

                    const res = await fetch(`${BASE_URL}api/historic/exportar-movimientos`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `movimientos_proveedor_${new Date().toISOString().split('T')[0]}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    } else {
                        alert("Error al generar el archivo Excel.");
                    }
                } catch (e) {
                    console.error("Error en exportarMovimientosExcel:", e);
                } finally {
                    if (notif && typeof notif.click === 'function') notif.click();
                }
            },

            async mostrarVerMovimiento(idSolicitud) {
                const divMovimientos = document.getElementById('div-movimientos');
                const divVencimientos = document.getElementById('div-vencimientos');
                const divVerMov = document.getElementById('div-ver-movimiento');
                const divVerVenc = document.getElementById('div-ver-vencimiento');
                
                const detMov = document.getElementById('detalles-movimiento-solicitud');
                const detVenc = document.getElementById('detalles-vencimiento-solicitud');

                if (divMovimientos) divMovimientos.classList.add('hidden');
                if (divVencimientos) divVencimientos.classList.add('hidden');
                
                if (divVerMov) divVerMov.classList.remove('hidden');
                if (divVerVenc) divVerVenc.classList.remove('hidden');

                const detallesContainer = detMov || detVenc;
                if (!detallesContainer) return;
                
                detallesContainer.innerHTML = '<p class="text-center p-8 text-gray-500">Cargando detalles completos...</p>';

                try {
                    // Obtenemos el registro base que ya tiene info de OrdenCompra
                    let base = this.movimientosProveedor.find(mov => mov.ID_Solicitud == idSolicitud);
                    
                    // Si no está en movimientos, buscamos en vencimientos
                    if (!base && this.vencimientosRaw) {
                        const vBase = this.vencimientosRaw.find(v => v.ID_Solicitud == idSolicitud);
                        if (vBase) {
                            base = {
                                ...vBase,
                                OrdenEstado: vBase.EstadoOrden,
                                OrdenFecha: vBase.FechaOrden,
                                // Estos podrían no venir en vencimientosRaw, pero se sacarán de 'data' luego
                            };
                        }
                    }
                    
                    // Obtenemos los detalles de productos y archivos
                    const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`);
                    if (data.error) throw new Error(data.error);

                    // Normalización: Si base no tiene info de OrdenCompra pero 'data' sí (vía Rest.php)
                    if (data.OrdenCompra) {
                        if (!base) base = {};
                        base.ID_OrdenCompra = data.OrdenCompra.ID_OrdenCompra;
                        base.OrdenEstado = data.OrdenCompra.Estado;
                        base.OrdenFecha = data.OrdenCompra.Fecha;
                        base.FechaRefPago = data.OrdenCompra.FechaRefPago;
                        base.FechaPagoRealizado = data.OrdenCompra.FechaPagoRealizado;
                        base.File_Factura = data.OrdenCompra.File_Factura;
                        base.File_Comprobante = data.OrdenCompra.File_Comprobante;
                    }

                    // Fetch adicional del proveedor para tener info completa (RFC, Correo, Clabe, etc)
                    let infoProv = null;
                    if (data.ID_Proveedor) {
                        try {
                            const resProv = await fetch(`${BASE_URL}api/provider/${data.ID_Proveedor}`);
                            if (resProv.ok) infoProv = await resProv.json();
                        } catch (e) { console.warn("No se pudo cargar info extra del proveedor"); }
                    }

                    const format = (val) => parseFloat(val || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

                    // CONSTRUCCIÓN DEL HTML MODULAR (3 BLOQUES APILADOS - MINIMALISTA CON ICONOS Y FONDO GRIS)
                    let html = `
                        <div class="space-y-4 mb-10">
                            <!-- MODULO 1: SOLICITUD -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 transition-all shadow-sm">
                                <h4 class="text-[12px] font-bold text-gray-400 uppercase tracking-[0.2em] border-b border-gray-200 pb-3 mb-5 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Información de Solicitud
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-x-8 gap-y-5">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-gray-400 uppercase font-bold">Folio y Fecha</p>
                                        <p class="text-s font-bold text-gray-700 font-mono">${data.No_Folio} <span class="font-normal text-gray-400 ml-1">/ ${data.Fecha}</span></p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Estado</p>
                                        <p class="text-[12px] font-extrabold text-blue-500 uppercase tracking-tighter">${data.Estado}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Solicitante</p>
                                        <p class="text-s text-gray-600 font-medium">${data.UsuarioNombre || 'N/A'}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Método de Pago</p>
                                        <p class="text-s text-gray-600 font-medium">${data.MetodoPago == 0 ? 'Contado' : 'Crédito'}</p>
                                    </div>
                                    <div class="md:col-span-2 flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Departamento y Unidad</p>
                                        <p class="text-s text-gray-600">${data.DepartamentoNombre} <span class="text-gray-300 mx-1">|</span> <span class="text-black-500 font-light">${data.UnidadOperativaNombre || ''}</span></p>
                                    </div>
                                    <div class="md:col-span-2 flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Complejo</p>
                                        <p class="text-s text-gray-600">${data.PlaceNombre || data.Complejo || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- MODULO 2: ORDEN DE COMPRA -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 transition-all shadow-sm">
                                <h4 class="text-[12px] font-bold text-black-500 uppercase tracking-[0.2em] border-b border-gray-200 pb-3 mb-5 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Orden de Compra y Pagos
                                </h4>
                                ${base && (base.ID_OrdenCompra || base.ID_Cotizacion) ? `
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-x-8 gap-y-5">
                                        <div class="flex flex-col gap-1">
                                            <p class="text-[10px] text-black-500 uppercase font-bold">Estado Orden</p>
                                            <p class="text-[12px] font-extrabold text-orange-500 uppercase tracking-tighter">${base.OrdenEstado || 'N/A'}</p>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <p class="text-[10px] text-black-500 uppercase font-bold">Fecha Orden</p>
                                            <p class="text-s text-gray-600 font-medium">${base.OrdenFecha || 'N/A'}</p>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <p class="text-[10px] text-black-500 uppercase font-bold">Ref. Programación</p>
                                            <p class="text-s text-gray-600 font-medium">${base.FechaRefPago || '—'}</p>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <p class="text-[10px] text-black-500 uppercase font-bold">Pago Realizado</p>
                                            <p class="text-s font-bold ${base.FechaPagoRealizado ? 'text-green-500' : 'text-black-500 italic'}">${base.FechaPagoRealizado || 'Pendiente'}</p>
                                        </div>
                                        <div class="md:col-span-4 flex items-center gap-4 pt-2 border-t border-gray-200">
                                            <p class="text-[10px] text-black-500 uppercase font-bold tracking-widest">Documentación:</p>
                                            <div class="flex gap-4">
                                                <span class="text-[12px] font-bold ${base.File_Factura ? 'text-green-600' : 'text-gray-300'}">FACTURA ${base.File_Factura ? '✓' : '✗'}</span>
                                                <span class="text-[12px] font-bold ${base.File_Comprobante ? 'text-green-600' : 'text-gray-300'}">COMPROBANTE ${base.File_Comprobante ? '✓' : '✗'}</span>
                                            </div>
                                        </div>
                                    </div>
                                ` : `
                                    <p class="text-s text-black-500 italic font-light tracking-wide">No se ha generado una Orden de Compra para esta solicitud aún.</p>
                                `}
                            </div>

                            <!-- MODULO 3: PROVEEDOR -->
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 transition-all shadow-sm">
                                <h4 class="text-[12px] font-bold text-black-500 uppercase tracking-[0.2em] border-b border-gray-200 pb-3 mb-5 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    Detalles del Proveedor
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-x-8 gap-y-5">
                                    <div class="md:col-span-2 flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Razón Social</p>
                                        <p class="text-sm font-bold text-gray-800 tracking-tight">${data.RazonSocialNombre || 'N/A'}</p>
                                    </div>
                                    <div class="md:col-span-2 flex flex-col gap-1 items-end justify-center">
                                        <p class="text-[10px] text-black-500 uppercase font-bold mb-1 tracking-widest text-gray-600">Inversión Total</p>
                                        <p class="text-2xl font-black text-gray-800 tracking-tighter drop-shadow-sm">${format(data.cotizacion?.Total || 0)}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">RFC</p>
                                        <p class="text-s text-gray-600 font-medium">${infoProv?.RFC || 'N/A'}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Banco</p>
                                        <p class="text-s text-gray-600 font-medium">${infoProv?.Banco || 'N/A'}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-black-500 uppercase font-bold">Cuenta / CLABE</p>
                                        <p class="text-[11px] text-gray-500 font-mono">${infoProv?.Cuenta || 'N/A'}</p>
                                        <p class="text-[12px] text-black-500 font-mono tracking-tighter">${infoProv?.Clabe || ''}</p>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] text-gray-400 uppercase font-bold">Contacto</p>
                                        <p class="text-s text-gray-600">${infoProv?.Nombre_Contacto || '—'}</p>
                                        <p class="text-[12px] text-gray-400 font-bold">${infoProv?.Tel_Contacto || ''}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // CONTINUACIÓN DE LA VISTA (TABLAS, COMENTARIOS, ADJUNTOS)

                    html += generarComentariosHtml(data);
                    html += generarProductosServiciosHTML(data);

                    if (data.ComentariosUser) {
                        html += `
                            <div class="mt-6 p-4 border rounded-lg bg-gray-50 border-gray-200">
                                <h4 class="text-md font-bold text-gray-800 mb-2 uppercase text-xs tracking-wider border-b pb-1">Comentarios o referencias del Usuario</h4>
                                <p class="text-gray-700 whitespace-pre-wrap text-sm italic">${data.ComentariosUser}</p>
                            </div>`;
                    }

                    html += generarSeccionAdjuntos(data);

                    detallesContainer.innerHTML = html;
                } catch (error) {
                    console.error('Error al cargar detalles del movimiento:', error);
                    detallesContainer.innerHTML = `<p class="text-center p-8 text-red-500 font-bold">No se pudieron cargar los detalles: ${error.message}</p>`;
                }
            },

            regresarAMovimientos() {
                const divVerMov = document.getElementById('div-ver-movimiento');
                const divVerVenc = document.getElementById('div-ver-vencimiento');
                const divMovimientos = document.getElementById('div-movimientos');
                const divVencimientos = document.getElementById('div-vencimientos');
                
                if (divVerMov) divVerMov.classList.add('hidden');
                if (divVerVenc) divVerVenc.classList.add('hidden');
                if (divMovimientos) divMovimientos.classList.remove('hidden');
                if (divVencimientos) divVencimientos.classList.remove('hidden');
            },

            limpiarFiltrosMovimientos() {
                this.filtroTextoMovimientos = '';
                this.idPlace = [];
                const now = new Date();
                const curYear = now.getFullYear();
                
                // Reiniciar rango a todo el año actual
                this.fechaInicioMovimientos = `${curYear}-01-01`;
                this.fechaFinMovimientos = `${curYear}-12-31`;

                if (this.choicesPlaces) {
                    this.choicesPlaces.removeActiveItems();
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

            async exportarVencimientosExcel() {
                const filteredData = this.vencimientosFiltrados;
                if (filteredData.length === 0) {
                    alert("No hay datos para exportar.");
                    return;
                }

                const notif = mostrarNotificacion('Generando Excel de Vencimientos...', 'info', 0);
                try {
                    const payload = {
                        reporteDetallado: this.reporteDetallado,
                        datos: filteredData
                    };

                    const res = await fetch(`${BASE_URL}api/vencimientos/exportar-datos`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `reporte_vencimientos_${this.reporteDetallado ? 'detallado' : 'agrupado'}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    }
                } catch (e) { 
                    console.error("Error exportando excel:", e); 
                } finally { 
                    if (notif) notif.click(); 
                }
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
