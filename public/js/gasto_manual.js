/**
 * Lógica para Registro de Gasto Manual
 */
function registrarComponenteGastoManual() {
    if (window.Alpine) {
        Alpine.data('gastoManualComponent', function () {
            return {
                idRazonSocial: '',
                idsPlaces: [],
                mesAnio: '',

                razonesSociales: [],
                todosPlaces: [],
                departamentos: [],
                departamentosOriginales: [],
                unidadesSeleccionadas: [],
                choicesPlace: null,
                choicesUnidad: null,

                cargando: false,
                guardando: false,
                mensaje: '',
                error: false,

                init() {
                    const now = new Date();
                    const anio = now.getFullYear();
                    const mes = String(now.getMonth() + 1).padStart(2, '0');
                    this.mesAnio = `${anio}-${mes}`;

                    const dataRS = this.$el.getAttribute('data-razones-json');
                    const dataP = this.$el.getAttribute('data-places-json');
                    if (dataRS) this.razonesSociales = JSON.parse(dataRS);
                    if (dataP) this.todosPlaces = JSON.parse(dataP);

                    this.$nextTick(() => {
                        this.initChoicesPlace();
                    });
                },

                get placesFiltrados() {
                    if (!this.idRazonSocial) return [];
                    return this.todosPlaces.filter(p => p.ID_RazonSocial == this.idRazonSocial);
                },

                initChoicesPlace() {
                    const el = this.$refs.filtroPlace;
                    if (!el) return;
                    this.choicesPlace = new Choices(el, {
                        removeItemButton: true,
                        placeholderValue: 'Seleccione Complejos',
                        searchPlaceholderValue: 'Buscar...',
                        noChoicesText: 'No hay más opciones',
                        itemSelectText: 'Presione para seleccionar',
                    });

                    el.addEventListener('change', () => {
                        this.idsPlaces = this.choicesPlace.getValue(true);
                        this.cargarEstructura();
                    });
                },

                actualizarChoicesPlace() {
                    if (this.choicesPlace) {
                        this.choicesPlace.clearStore();
                        const choices = this.placesFiltrados.map(p => ({
                            value: p.ID_Place,
                            label: p.Nombre_Corto,
                            selected: false
                        }));
                        this.choicesPlace.setChoices(choices, 'value', 'label', true);
                    }
                    this.cargarEstructura();
                },

                get sumaTotal() {
                    let total = 0;
                    this.departamentos.forEach(uni => {
                        uni.grupos.forEach(grupo => {
                            if (grupo.es_manual == 1 || grupo.es_manual === true || grupo.es_manual === 't') {
                                total += parseFloat(grupo.monto_ingresado) || 0;
                            }
                        });
                    });
                    return total;
                },

                getDptoTotal(uni) {
                    let total = 0;
                    if (uni.grupos) {
                        uni.grupos.forEach(grupo => {
                            if (grupo.es_manual == 1 || grupo.es_manual === true || grupo.es_manual === 't') {
                                total += parseFloat(grupo.monto_ingresado) || 0;
                            }
                        });
                    }
                    return total;
                },

                async cargarEstructura() {
                    if (this.idsPlaces.length === 0 || !this.mesAnio) {
                        this.departamentos = [];
                        this.departamentosOriginales = [];
                        return;
                    }

                    const [anio, mes] = this.mesAnio.split('-');
                    const idsParam = this.idsPlaces.join(',');
                    this.cargando = true;
                    this.mensaje = '';

                    try {
                        const res = await fetch(`${BASE_URL}api/presupuesto-mensual/estructura/${idsParam}/${anio}/${parseInt(mes)}`);

                        if (res.ok) {
                            const data = await res.json();
                            const dptos = (data.departamentos || []).map(uni => {
                                uni.grupos = uni.grupos.map(g => {
                                    g.monto_ingresado = ''; 
                                    return g;
                                });
                                return uni;
                            });
                            
                            this.departamentosOriginales = JSON.parse(JSON.stringify(dptos));
                            this.departamentos = dptos;
                            this.$nextTick(() => this.initChoicesFiltros());
                        } else {
                            this.mensaje = 'Error al cargar los datos.';
                            this.error = true;
                        }
                    } catch (e) {
                        console.error(e);
                        this.mensaje = 'Error de conexión.';
                        this.error = true;
                    } finally {
                        this.cargando = false;
                    }
                },

                initChoicesFiltros() {
                    if (this.choicesUnidad) this.choicesUnidad.destroy();
                    const elU = this.$refs.filtroUnidad;
                    if (elU) {
                        this.choicesUnidad = new Choices(elU, { removeItemButton: true, placeholderValue: 'Filtrar Deptos' });
                        elU.addEventListener('change', () => {
                            this.unidadesSeleccionadas = this.choicesUnidad.getValue(true).map(v => parseInt(v));
                            this.aplicarFiltros();
                        });
                    }
                },

                aplicarFiltros() {
                    let filtrados = JSON.parse(JSON.stringify(this.departamentosOriginales));
                    if (this.unidadesSeleccionadas.length > 0) {
                        filtrados = filtrados.filter(u => this.unidadesSeleccionadas.includes(parseInt(u.ID_UnidadOperativa)));
                    }
                    this.departamentos = filtrados;
                },

                limpiarFiltros() {
                    if (this.choicesUnidad) this.choicesUnidad.removeActiveItems();
                    this.unidadesSeleccionadas = [];
                    this.departamentos = JSON.parse(JSON.stringify(this.departamentosOriginales));
                },

                async guardarGastos() {
                    this.guardando = true;
                    this.mensaje = 'Guardando gastos...';
                    this.error = false;

                    const [anio, mes] = this.mesAnio.split('-');
                    const datos = {
                        anio: parseInt(anio),
                        mes: parseInt(mes),
                        grupos: []
                    };

                    this.departamentos.forEach(uni => {
                        uni.grupos.forEach(g => {
                            if ((g.es_manual == 1 || g.es_manual === true || g.es_manual === 't') && parseFloat(g.monto_ingresado) > 0) {
                                datos.grupos.push({
                                    id_unidad: uni.ID_UnidadOperativa,
                                    id_grupo: g.ID_GrupoPresupuestal,
                                    monto_incremento: parseFloat(g.monto_ingresado)
                                });
                            }
                        });
                    });

                    if (datos.grupos.length === 0) {
                        this.mensaje = 'No hay nuevos gastos para registrar.';
                        this.error = true;
                        this.guardando = false;
                        return;
                    }

                    try {
                        const res = await fetch(`${BASE_URL}api/presupuesto-mensual/save-gastos-manuales`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(datos)
                        });

                        const resData = await res.json();
                        if (res.ok && resData.success) {
                            this.mensaje = resData.message || 'Gastos registrados correctamente ✅';
                            this.error = false;
                            // Recargar la estructura para ver los nuevos totales actualizados
                            setTimeout(() => this.cargarEstructura(), 1500);
                        } else {
                            this.mensaje = resData.error || 'Error al guardar.';
                            this.error = true;
                        }
                    } catch (e) {
                        this.mensaje = 'Error de red.';
                        this.error = true;
                    } finally {
                        this.guardando = false;
                    }
                },

                formatearMoneda(valor) {
                    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor || 0);
                }
            };
        });
    }
}

// Inicializar al cargar el script
document.addEventListener('DOMContentLoaded', () => {
    registrarComponenteGastoManual();
});
