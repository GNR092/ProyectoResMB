/**
 * Lógica para Presupuesto Mensual
 */
function registrarComponentePresupuesto() {
    Alpine.data('presupuestoEscalonado', function () {
        return {
            idRazonSocial: '',
            idsPlaces: [],
            mesAnio: '',

            razonesSociales: [],
            todosPlaces: [],
            departamentos: [],
            departamentosOriginales: [],
            gruposUnicos: [],
            unidadesSeleccionadas: [],
            gruposSeleccionados: [],
            choicesPlace: null,
            choicesUnidad: null,
            choicesGrupo: null,

            cargando: false,
            guardando: false,
            mensaje: '',
            error: false,

            init() {
                if (this.$el) {
                    this.razonesSociales = JSON.parse(this.$el.dataset.razonesJson || '[]');
                    this.todosPlaces     = JSON.parse(this.$el.dataset.placesJson || '[]');
                }

                const now = new Date();
                const anio = now.getFullYear();
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                this.mesAnio = `${anio}-${mes}`;

                // Esperamos al DOM para inicializar el primer Choice de Place
                this.$nextTick(() => this.initChoicesPlace());
            },

            get placesFiltrados() {
                if (!this.idRazonSocial) return [];
                return this.todosPlaces.filter(p => String(p.ID_RazonSocial) === String(this.idRazonSocial));
            },

            resetEstructura() {
                this.departamentos = [];
                this.departamentosOriginales = [];
                this.unidadesSeleccionadas = [];
                this.gruposSeleccionados = [];
                if (this.choicesUnidad) { this.choicesUnidad.destroy(); this.choicesUnidad = null; }
                if (this.choicesGrupo) { this.choicesGrupo.destroy(); this.choicesGrupo = null; }
                this.mensaje = '';
            },

            initChoicesPlace() {
                if (typeof Choices === 'undefined') return;
                const ref = this.$refs.filtroPlace;
                if (!ref) return;

                if (this.choicesPlace) this.choicesPlace.destroy();

                this.choicesPlace = new Choices(ref, {
                    removeItemButton: true,
                    itemSelectText: '',
                    placeholderValue: 'Seleccione Places',
                    searchPlaceholderValue: 'Buscar complejo...'
                });

                ref.addEventListener('change', () => {
                    this.idsPlaces = this.choicesPlace.getValue(true).map(String);
                    this.cargarEstructura();
                });
            },

            actualizarChoicesPlace() {
                if (!this.choicesPlace) return;

                // Limpiar selecciones previas y datos
                this.idsPlaces = [];
                this.resetEstructura();

                // Refrescar opciones basadas en Razón Social
                this.choicesPlace.clearChoices();
                const opciones = this.placesFiltrados.map(p => ({
                    value: String(p.ID_Place),
                    label: p.Nombre_Corto
                }));

                if (opciones.length > 0) {
                    this.choicesPlace.setChoices(opciones, 'value', 'label', true);
                    this.choicesPlace.enable();
                } else {
                    this.choicesPlace.disable();
                }
            },

            // NUEVO: Calcula la suma total en tiempo real
            get sumaTotal() {
                let total = 0;
                this.departamentos.forEach(dpto => {
                    if (dpto.grupos) {
                        dpto.grupos.forEach(grupo => {
                            let monto = parseFloat(grupo.Monto_Asignado) || 0;
                            total += monto;
                        });
                    }
                });
                return total;
            },

            getDptoTotal(dpto) {
                let total = 0;
                if (dpto.grupos) {
                    dpto.grupos.forEach(grupo => {
                        let monto = parseFloat(grupo.Monto_Asignado) || 0;
                        total += monto;
                    });
                }
                return total;
            },

            async cargarEstructura() {
                if (this.idsPlaces.length === 0 || !this.mesAnio) {
                    this.resetEstructura();
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-');
                const idsParam = this.idsPlaces.join(',');
                this.cargando = true;
                this.departamentos = [];
                this.departamentosOriginales = [];
                this.mensaje = '';

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/estructura/${idsParam}/${anio}/${parseInt(mes)}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.departamentosOriginales = JSON.parse(JSON.stringify(data.departamentos || []));
                        this.departamentos = data.departamentos || [];
                        this.extraerGruposUnicos();
                        this.$nextTick(() => this.initChoicesFiltros());
                    } else {
                        this.mensaje = 'Error al cargar los datos del servidor.';
                        this.error = true;
                    }
                } catch (e) {
                    console.error("Error cargando estructura:", e);
                    this.mensaje = 'Error de conexión.';
                    this.error = true;
                } finally {
                    this.cargando = false;
                }
            },

            extraerGruposUnicos() {
                const map = new Map();
                this.departamentosOriginales.forEach(uni => {
                    // Si hay unidades seleccionadas, solo extraemos grupos de esas unidades
                    if (this.unidadesSeleccionadas.length > 0 && !this.unidadesSeleccionadas.includes(String(uni.ID_UnidadOperativa))) {
                        return;
                    }

                    if (uni.grupos) {
                        uni.grupos.forEach(g => {
                            if (!map.has(g.ID_GrupoPresupuestal)) {
                                map.set(g.ID_GrupoPresupuestal, g.Nombre);
                            }
                        });
                    }
                });
                this.gruposUnicos = Array.from(map, ([id, nombre]) => ({ id, nombre })).sort((a, b) => a.nombre.localeCompare(b.nombre));
            },

            actualizarOpcionesGrupo() {
                if (!this.choicesGrupo) return;

                // Guardar selección actual para intentar mantenerla si sigue siendo válida
                const seleccionActual = this.choicesGrupo.getValue(true).map(String);

                this.choicesGrupo.clearChoices();
                const nuevasOpciones = this.gruposUnicos.map(g => ({
                    value: String(g.id),
                    label: g.nombre,
                    selected: seleccionActual.includes(String(g.id))
                }));

                this.choicesGrupo.setChoices(nuevasOpciones, 'value', 'label', true);
                
                // Sincronizar el array de Alpine con lo que realmente quedó seleccionado
                this.gruposSeleccionados = this.choicesGrupo.getValue(true).map(String);
            },

            initChoicesFiltros() {
                if (typeof Choices === 'undefined') return;

                if (this.choicesUnidad) { this.choicesUnidad.destroy(); this.choicesUnidad = null; }
                if (this.choicesGrupo) { this.choicesGrupo.destroy(); this.choicesGrupo = null; }

                const refUnidad = this.$refs.filtroUnidad;
                const refGrupo = this.$refs.filtroGrupo;

                if (refUnidad) {
                    this.choicesUnidad = new Choices(refUnidad, {
                        removeItemButton: true,
                        itemSelectText: '',
                        placeholderValue: 'Todas las Unidades',
                        searchPlaceholderValue: 'Buscar unidad...'
                    });
                    refUnidad.addEventListener('change', () => {
                        this.unidadesSeleccionadas = this.choicesUnidad.getValue(true).map(String);
                        
                        // Dinamismo: Recalcular grupos disponibles y refrescar el selector
                        this.extraerGruposUnicos();
                        this.actualizarOpcionesGrupo();
                        
                        this.aplicarFiltrosLocales();
                    });
                }

                if (refGrupo) {
                    this.choicesGrupo = new Choices(refGrupo, {
                        removeItemButton: true,
                        itemSelectText: '',
                        placeholderValue: 'Todas las Partidas',
                        searchPlaceholderValue: 'Buscar partida...'
                    });
                    refGrupo.addEventListener('change', () => {
                        this.gruposSeleccionados = this.choicesGrupo.getValue(true).map(String);
                        this.aplicarFiltrosLocales();
                    });
                }
            },

            aplicarFiltrosLocales() {
                let filtrados = [];
                this.departamentosOriginales.forEach(uni => {
                    if (this.unidadesSeleccionadas.length > 0 && !this.unidadesSeleccionadas.includes(String(uni.ID_UnidadOperativa))) {
                        return; // Omitir unidad
                    }

                    // Clonar para no modificar original
                    let uniClon = JSON.parse(JSON.stringify(uni));

                    if (this.gruposSeleccionados.length > 0 && uniClon.grupos) {
                        uniClon.grupos = uniClon.grupos.filter(g => this.gruposSeleccionados.includes(String(g.ID_GrupoPresupuestal)));
                    }

                    if (uniClon.grupos && uniClon.grupos.length > 0) {
                        filtrados.push(uniClon);
                    }
                });

                this.departamentos = filtrados;
            },

            limpiarFiltros() {
                if (this.choicesUnidad) this.choicesUnidad.removeActiveItems();
                if (this.choicesGrupo) this.choicesGrupo.removeActiveItems();
                
                this.unidadesSeleccionadas = [];
                this.gruposSeleccionados = [];
                
                this.extraerGruposUnicos();
                this.actualizarOpcionesGrupo();
                this.aplicarFiltrosLocales();
            },

            async copiarAnterior() {
                if (!this.idPlace || !this.mesAnio) {
                    mostrarNotificacion('Seleccione un Place y una Fecha primero.', 'error');
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-').map(Number);
                let prevMes = mes - 1;
                let prevAnio = anio;
                if (prevMes === 0) {
                    prevMes = 12;
                    prevAnio = anio - 1;
                }

                const notif = mostrarNotificacion('Obteniendo datos del mes anterior...', 'info', 0);

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/estructura/${this.idPlace}/${prevAnio}/${prevMes}`);
                    const data = await res.json();

                    if (!data.departamentos || data.departamentos.length === 0) {
                        mostrarNotificacion('No se encontraron presupuestos en el mes anterior.', 'alert');
                        return;
                    }

                    let copiasRealizadas = 0;
                    this.departamentos.forEach(dptoActual => {
                        const dptoPrevio = data.departamentos.find(d => String(d.ID_UnidadOperativa) === String(dptoActual.ID_UnidadOperativa));
                        if (dptoPrevio && dptoPrevio.grupos) {
                            dptoActual.grupos.forEach(grupoActual => {
                                const grupoPrevio = dptoPrevio.grupos.find(g => String(g.ID_GrupoPresupuestal) === String(grupoActual.ID_GrupoPresupuestal));
                                if (grupoPrevio && grupoPrevio.Monto_Asignado) {
                                    grupoActual.Monto_Asignado = grupoPrevio.Monto_Asignado;
                                    copiasRealizadas++;
                                }
                            });
                        }
                    });

                    if (copiasRealizadas > 0) {
                        mostrarNotificacion(`Se copiaron ${copiasRealizadas} montos exitosamente.`, 'success');
                    } else {
                        mostrarNotificacion('No se encontraron montos coincidentes para copiar.', 'alert');
                    }
                } catch (error) {
                    console.error('Error al copiar presupuesto anterior:', error);
                    mostrarNotificacion('Error al intentar copiar el presupuesto.', 'error');
                } finally {
                    if(notif) notif.click();
                }
            },

            async guardarMasivo() {
                if (this.departamentos.length === 0) return;

                const [anio, mes] = this.mesAnio.split('-');
                this.guardando = true;
                this.mensaje = '';
                this.error = false;

                let gruposParaGuardar = [];

                this.departamentos.forEach(uni => {
                    uni.grupos.forEach(grupo => {
                        gruposParaGuardar.push({
                            id_unidad: uni.ID_UnidadOperativa,
                            id_grupo: grupo.ID_GrupoPresupuestal,
                            id_existente: grupo.ID_PresupuestoMensual,
                            monto_asignado: parseFloat(grupo.Monto_Asignado) || 0
                        });
                    });
                });

                const payload = {
                    anio: parseInt(anio),
                    mes: parseInt(mes),
                    grupos: gruposParaGuardar
                };

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/guardar-masivo`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        this.mensaje = 'Presupuestos guardados correctamente';
                        this.error = false;
                        await this.cargarEstructura();
                    } else {
                        this.mensaje = 'Error al guardar los presupuestos';
                        this.error = true;
                    }
                } catch (e) {
                    this.mensaje = 'Error de conexión al guardar.';
                    this.error = true;
                } finally {
                    this.guardando = false;
                    if (!this.error) {
                        setTimeout(() => { this.mensaje = ''; }, 4000);
                    }
                }
            }
        };
    });
}

function registrarComponenteSaldosBancarios() {
    Alpine.data('saldosBancariosComponent', function () {
        return {
            idRazonSocial: '',
            idPlace: '',
            mesAnio: '',

            razonesSociales: [],
            todosPlaces: [],
            razonesData: [],

            cargando: false,
            guardando: false,
            mensaje: '',
            error: false,

            init() {
                if (this.$el) {
                    this.razonesSociales = JSON.parse(this.$el.dataset.razonesJson || '[]');
                    this.todosPlaces     = JSON.parse(this.$el.dataset.placesJson || '[]');
                }

                const now = new Date();
                const anio = now.getFullYear();
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                this.mesAnio = `${anio}-${mes}`;
            },

            get placesFiltrados() {
                if (!this.idRazonSocial) return [];
                return this.todosPlaces.filter(p => String(p.ID_RazonSocial) === String(this.idRazonSocial));
            },

            resetEstructura() {
                this.razonesData = [];
                this.mensaje = '';
            },

            async cargarEstructura() {
                if (!this.idRazonSocial || !this.mesAnio) {
                    this.resetEstructura();
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-');
                this.cargando = true;
                this.razonesData = [];
                this.mensaje = '';

                try {
                    const res = await fetch(`${BASE_URL}api/saldos-bancarios/estructura/${this.idRazonSocial}/${anio}/${parseInt(mes)}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.razonesData = data.razones || [];
                    } else {
                        this.mensaje = 'Error al cargar los datos del servidor.';
                        this.error = true;
                    }
                } catch (e) {
                    console.error("Error cargando estructura:", e);
                    this.mensaje = 'Error de conexión.';
                    this.error = true;
                } finally {
                    this.cargando = false;
                }
            },

            async copiarAnterior() {
                if (!this.idRazonSocial || !this.mesAnio) {
                    mostrarNotificacion('Seleccione una Razón Social y una Fecha primero.', 'error');
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-').map(Number);
                let prevMes = mes - 1;
                let prevAnio = anio;
                if (prevMes === 0) {
                    prevMes = 12;
                    prevAnio = anio - 1;
                }

                const notif = mostrarNotificacion('Obteniendo saldos del mes anterior...', 'info', 0);

                try {
                    const res = await fetch(`${BASE_URL}api/saldos-bancarios/estructura/${this.idRazonSocial}/${prevAnio}/${prevMes}`);
                    const data = await res.json();

                    if (!data.razones || data.razones.length === 0) {
                        mostrarNotificacion('No se encontraron saldos en el mes anterior.', 'alert');
                        return;
                    }

                    let copiasRealizadas = 0;
                    this.razonesData.forEach(rsActual => {
                        const rsPrevia = data.razones.find(r => String(r.ID_RazonSocial) === String(rsActual.ID_RazonSocial));
                        if (rsPrevia && rsPrevia.bancos) {
                            rsActual.bancos.forEach(bancoActual => {
                                const bancoPrevio = rsPrevia.bancos.find(b => String(b.ID_BancoDpto) === String(bancoActual.ID_BancoDpto));
                                if (bancoPrevio && (bancoPrevio.saldo_inicial !== undefined && bancoPrevio.saldo_inicial !== null)) {
                                    bancoActual.saldo_inicial = bancoPrevio.saldo_inicial;
                                    copiasRealizadas++;
                                }
                            });
                        }
                    });

                    if (copiasRealizadas > 0) {
                        mostrarNotificacion(`Se actualizaron ${copiasRealizadas} saldos iniciales.`, 'success');
                    } else {
                        mostrarNotificacion('No se encontraron saldos finales para copiar.', 'alert');
                    }
                } catch (error) {
                    console.error('Error al copiar saldos:', error);
                    mostrarNotificacion('Error al intentar copiar los saldos.', 'error');
                } finally {
                    if(notif) notif.click();
                }
            },

            async guardarSaldos() {
                if (this.razonesData.length === 0) return;

                const [anio, mes] = this.mesAnio.split('-');
                this.guardando = true;
                this.mensaje = '';
                this.error = false;

                let saldosParaEnviar = [];

                this.razonesData.forEach(rs => {
                    rs.bancos.forEach(banco => {
                        saldosParaEnviar.push({
                            id_bancodpto: banco.ID_BancoDpto,
                            saldo_inicial: parseFloat(banco.saldo_inicial) || 0,
                            saldo_final: parseFloat(banco.saldo_final) || 0,
                            id_existente: banco.id_saldo_existente
                        });
                    });
                });

                const payload = {
                    anio: parseInt(anio),
                    mes: parseInt(mes),
                    saldos: saldosParaEnviar
                };

                try {
                    const res = await fetch(`${BASE_URL}api/saldos-bancarios/guardar-masivo`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const result = await res.json();
                        if (result.success) {
                            this.mensaje = 'Saldos guardados correctamente';
                            this.error = false;
                            await this.cargarEstructura();
                        } else {
                            this.mensaje = result.message || 'Error al guardar';
                            this.error = true;
                        }
                    } else {
                        this.mensaje = 'Error al guardar los saldos';
                        this.error = true;
                    }
                } catch (e) {
                    this.mensaje = 'Error de conexión al guardar.';
                    this.error = true;
                } finally {
                    this.guardando = false;
                    if (!this.error) {
                        setTimeout(() => { this.mensaje = ''; }, 4000);
                    }
                }
            }
        };
    });
}

function registrarComponenteReportePresupuesto() {
    Alpine.data('reportePresupuestoComponent', function () {
        return {
            pantalla: 'menu', // 'menu', 'presupuesto', 'cuentas', 'completo'
            idRazonSocial: '',
            idPlace: '',
            verGlobal: false,
            anio: '',
            meses: [],

            razonesSociales: [],
            todosPlaces: [],
            departamentos: [], // Representa Unidades Operativas ahora en el reporte
            departamentosBancos: [],
            departamentosCompleto: [],
            departamentosOriginales: [],
            dptosSeleccionados: [],
            choicesDpto: null,
            choicesMeses: null,
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

            init() {
                if (this.$el) {
                    this.razonesSociales = JSON.parse(this.$el.dataset.razonesJson || '[]');
                    this.todosPlaces     = JSON.parse(this.$el.dataset.placesJson || '[]');
                }

                const now = new Date();
                const currentYear = now.getFullYear();
                
                // 1. Generar lista de años primero
                this.years = [];
                for (let i = currentYear + 2; i >= currentYear - 5; i--) {
                    this.years.push(String(i));
                }

                // 2. Establecer valores por defecto al final para asegurar el vínculo reactivo
                this.anio = String(currentYear);
                this.meses = [String(now.getMonth() + 1)];
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
                });
            },

            irAPantalla(nueva) {
                this.pantalla = nueva;
                this.departamentos = [];
                this.departamentosBancos = [];
                this.departamentosCompleto = [];
                this.departamentosOriginales = [];
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
                
                this.idPlace = '';
                this.idRazonSocial = '';

                if (nueva !== 'menu') {
                    this.$nextTick(() => {
                        const refMap = {
                            'presupuesto': 'mesesSelectorPresupuesto',
                            'cuentas': 'mesesSelectorCuentas',
                            'completo': 'mesesSelectorCompleto'
                        };
                        this.initChoicesMeses(refMap[nueva]);
                    });
                }

                // LÓGICA DE ANCHO DE MODAL DINÁMICO
                const modal = document.getElementById('modal-general');
                const modalTitle = document.getElementById('modal-title');
                const modalBox = modalTitle ? modalTitle.parentElement : null;

                if (modal && modalBox) {
                    if (nueva === 'completo') {
                        // Expandir modal (Lógica de mbscript.js para modales anchos)
                        modal.classList.remove('justify-center');
                        modalBox.classList.remove('max-w-4xl', 'mx-4', 'sm:mx-auto');
                        modalBox.classList.add('w-[95%]', 'mx-auto'); // Forzamos un ancho casi total
                    } else {
                        // Restaurar ancho estándar para las otras pantallas
                        modal.classList.add('justify-center');
                        modalBox.classList.add('max-w-4xl', 'mx-4', 'sm:mx-auto');
                        modalBox.classList.remove('w-[95%]');
                    }
                }
            },

            get placesFiltrados() {
                if (!this.idRazonSocial) return [];
                return this.todosPlaces.filter(p => String(p.ID_RazonSocial) === String(this.idRazonSocial));
            },

            get departamentosAgrupados() {
                let fuente = this.departamentos;
                if (this.pantalla === 'cuentas') fuente = this.departamentosBancos;
                if (this.pantalla === 'completo') fuente = this.departamentosCompleto;

                const rsGrupos = [];

                const crearTotales = () => {
                    if (this.pantalla === 'cuentas') return { inicial: 0, final: 0, usado: 0, porcentaje: 0 };
                    if (this.pantalla === 'completo') return { pAsignado: 0, pGastado: 0, bInicial: 0, bFinal: 0, pDisponible: 0 };
                    return { asignado: 0, comprometido: 0, ejecutado: 0, disponible: 0, porcentaje: 0 };
                };

                const sumar = (totales, d) => {
                    if (this.pantalla === 'cuentas') {
                        totales.inicial += parseFloat(d.totales?.inicial || 0);
                        totales.final += parseFloat(d.totales?.final || 0);
                        totales.usado += parseFloat(d.totales?.usado || 0);
                    } else if (this.pantalla === 'completo') {
                        // Presupuesto: Sumar en todos los niveles
                        totales.pAsignado += parseFloat(d.presupuesto?.asignado || 0);
                        totales.pGastado += parseFloat(d.presupuesto?.gastado || 0);
                        totales.pDisponible += parseFloat(d.presupuesto?.disponible || 0);
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
                        totales.disponible = totales.asignado - totalGasto;
                        totales.porcentaje = totales.asignado > 0 ? Math.round((totalGasto / totales.asignado) * 100 * 100) / 100 : 0;
                    }
                };

                fuente.forEach(d => {
                    const rsNombre = d.RazonSocialNombre || 'Sin Razón Social';
                    const segNombre = d.SegmentoNombre || 'Sin Segmento';
                    const placeNombre = d.PlaceNombre || 'Sin Place';

                    let rs = rsGrupos.find(g => g.nombre === rsNombre);
                    if (!rs) {
                        rs = { nombre: rsNombre, segmentos: [], totales: crearTotales() };
                        rsGrupos.push(rs);
                    }
                    sumar(rs.totales, d);
                    
                    if (this.pantalla === 'completo') {
                        rs.totales.bInicial += parseFloat(d.bancos?.inicial || 0);
                        rs.totales.bFinal += parseFloat(d.bancos?.final || 0);
                    }

                    let seg = rs.segmentos.find(s => s.nombre === segNombre);
                    if (!seg) {
                        seg = { nombre: segNombre, complejos: [], totales: crearTotales() };
                        rs.segmentos.push(seg);
                    }
                    sumar(seg.totales, d);

                    let complex = seg.complejos.find(c => c.nombre === placeNombre);
                    if (!complex) {
                        complex = { nombre: placeNombre, departamentos: [], totales: crearTotales() };
                        seg.complejos.push(complex);
                    }
                    sumar(complex.totales, d);

                    complex.departamentos.push(d);
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

                if (!this.verGlobal && (!this.idPlace || !this.anio || this.meses.length === 0)) return;

                const stringMeses = this.meses.join(',');
                this.cargando = true;
                this.departamentos = [];
                this.departamentosOriginales = [];
                this.mensaje = '';

                const targetPlaceId = this.verGlobal ? 0 : this.idPlace;

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
                if (!this.verGlobal && (!this.idPlace || !this.anio || this.meses.length === 0)) return;
                const stringMeses = this.meses.join(',');
                this.cargando = true;
                this.departamentosBancos = [];
                const targetPlaceId = this.verGlobal ? 0 : this.idPlace;

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
                if (!this.verGlobal && (!this.idPlace || !this.anio || this.meses.length === 0)) return;
                const stringMeses = this.meses.join(',');
                this.cargando = true;
                this.departamentosCompleto = [];
                const targetPlaceId = this.verGlobal ? 0 : this.idPlace;

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
                this.idPlace = '';
                this.departamentos = [];
                this.departamentosBancos = [];
                this.departamentosCompleto = [];
                this.departamentosOriginales = [];
                
                if (this.choicesDpto) {
                    this.choicesDpto.destroy();
                    this.choicesDpto = null;
                }

                await this.cargarComparativo();
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
    registrarComponentePresupuesto();
    registrarComponenteSaldosBancarios();
    registrarComponenteReportePresupuesto();
} else {
    document.addEventListener('alpine:init', () => {
        registrarComponentePresupuesto();
        registrarComponenteSaldosBancarios();
        registrarComponenteReportePresupuesto();
    });
}


/**
 * Lógica para el CRUD de Segmentos de Negocio
 */
function initCrudSegmentos() {
    const tabla = document.getElementById('tabla-segmentos'); if (!tabla) return;
    setupClientSideTable({
        rowsSelector: '#tabla-segmentos tr[data-id]',
        paginationSelector: 'paginacion-segmentos',
        filterFormSelector: '#form-filtros-segmentos',
        filterFunction: (row, form) => {
            const nombreFiltro = (document.getElementById('buscar-nombre-segmento')?.value || '').toLowerCase()
            const nombre = row.querySelector('.nombre-segmento')?.textContent.toLowerCase() || ''
            return nombre.includes(nombreFiltro)
        },
        rowsPerPage: 10,
    });
    initSegmentosPantallas(); initSegmentosForm(); initSegmentosEditarForm(); initSegmentosActions(tabla);
}

function initSegmentosPantallas() {
    const pLis = document.getElementById('pantalla-lista-segmentos'), pAdd = document.getElementById('pantalla-agregar-segmentos'), pEdi = document.getElementById('pantalla-editar-segmentos');
    const bAdd = document.getElementById('btn-agregar-segmentos'), bRA = document.getElementById('btn-regresar-lista-segmentos'), bRE = document.getElementById('btn-regresar-lista-editar-segmentos');
    if (bAdd) bAdd.onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); }
    if (bRA) bRA.onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); }
    if (bRE) bRE.onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); }
}

function initSegmentosForm() {
    const fAdd = document.getElementById('form-agregar-segmentos'); if (!fAdd) return;
    fAdd.onsubmit = async (e) => {
        e.preventDefault(); try {
            const res = await SendDataEnd('modales/crud_segmentos/insertar', { method: 'POST', body: new FormData(fAdd) });
            if (res.success) { mostrarNotificacion('Agregado ✅', 'success'); abrirModal('SegmentoNegocio'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initSegmentosEditarForm() {
    const fEdi = document.getElementById('form-editar-segmentos'); if (!fEdi) return;
    fEdi.onsubmit = async (e) => {
        e.preventDefault(); const fd = new FormData(fEdi); const id = fd.get('id');
        try {
            const res = await SendDataEnd(`modales/crud_segmentos/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { mostrarNotificacion('Actualizado ✅', 'success'); abrirModal('SegmentoNegocio'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initSegmentosActions(tabla) {
    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-segmentos-']"), bD = e.target.closest("[id^='btn-eliminar-segmentos-']");
        if (bD) {
            e.preventDefault(); if (!(await Confirmar('¿Eliminar?', '¿Seguro?'))) return;
            const res = await SendDataEnd(`modales/crud_segmentos/eliminar/${bD.dataset.id}`, { method: 'POST' });
            if (res.success) { mostrarNotificacion('Eliminado ✅', 'success'); bD.closest('tr').remove(); }
        }
        if (bE) {
            e.preventDefault(); const f = bE.closest('tr');
            document.getElementById('editar-id').value = f.dataset.id;
            document.getElementById('editar-nombre').value = f.dataset.nombre;
            document.getElementById('editar-descripcion').value = f.dataset.descripcion;
            document.getElementById('editar-id_razon_social').value = f.dataset.idRs;
            document.getElementById('pantalla-lista-segmentos').classList.add('hidden');
            document.getElementById('pantalla-editar-segmentos').classList.remove('hidden');
        }
    });
}

function initCrudGrupos() {
    const tabla = document.getElementById('tabla-grupos'); if (!tabla) return;
    setupClientSideTable({ rowsSelector: '#tabla-grupos tr[data-id]', paginationSelector: 'paginacion-grupos', filterFormSelector: '#form-filtros-grupos', filterFunction: (row) => {
        const nom = (document.getElementById('buscar-nombre-grupo')?.value || '').toLowerCase();
        const des = (document.getElementById('buscar-descripcion-grupo')?.value || '').toLowerCase();
        return row.querySelector('.nombre-grupo')?.textContent.toLowerCase().includes(nom) && row.querySelector('.descripcion-grupo')?.textContent.toLowerCase().includes(des);
    }, rowsPerPage: 10 });
    initGruposPantallas(); initGruposForm(); initGruposEditarForm(); initGruposActions(tabla);
}

function initGruposPantallas() {
    const pLis = document.getElementById('pantalla-lista-grupos'), pAdd = document.getElementById('pantalla-agregar-grupos'), pEdi = document.getElementById('pantalla-editar-grupos');
    document.getElementById('btn-agregar-grupos').onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-grupos').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-editar-grupos').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };
}

function initGruposForm() {
    const fAdd = document.getElementById('form-agregar-grupos'); if (!fAdd) return;
    fAdd.onsubmit = async (e) => {
        e.preventDefault(); try {
            const res = await SendDataEnd('modales/crud_grupos_presupuestales/insertar', { method: 'POST', body: new FormData(fAdd) });
            if (res.success) { mostrarNotificacion('Agregado ✅', 'success'); abrirModal('GrupoPresupuestal'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initGruposEditarForm() {
    const fEdi = document.getElementById('form-editar-grupos'); if (!fEdi) return;
    fEdi.onsubmit = async (e) => {
        e.preventDefault(); const fd = new FormData(fEdi); const id = fd.get('ID_GrupoPresupuestal');
        try {
            const res = await SendDataEnd(`modales/crud_grupos_presupuestales/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { mostrarNotificacion('Actualizado ✅', 'success'); abrirModal('GrupoPresupuestal'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initGruposActions(tabla) {
    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-grupos-']"), bD = e.target.closest("[id^='btn-eliminar-grupos-']");
        if (bD) {
            e.preventDefault(); if (!(await Confirmar('¿Desactivar?', '¿Seguro?'))) return;
            const res = await SendDataEnd(`modales/crud_grupos_presupuestales/eliminar/${bD.dataset.id}`, { method: 'POST' });
            if (res.success) { mostrarNotificacion('Desactivado ✅', 'success'); abrirModal('GrupoPresupuestal'); }
        }
        if (bE) {
            e.preventDefault(); const f = bE.closest('tr');
            document.getElementById('editar-ID_GrupoPresupuestal').value = f.dataset.id;
            document.getElementById('editar-Nombre').value = f.dataset.nombre;
            document.getElementById('editar-Descripcion').value = f.dataset.descripcion;
            document.getElementById('editar-ID_UnidadOperativa').value = f.dataset.idUnidad;
            document.getElementById('editar-activo').checked = f.dataset.activo == '1';
            document.getElementById('pantalla-lista-grupos').classList.add('hidden');
            document.getElementById('pantalla-editar-grupos').classList.remove('hidden');
        }
    });
}

function initCrudUnidades() {
    const tabla = document.getElementById('tabla-unidades'); if (!tabla) return;
    setupClientSideTable({ rowsSelector: '#tabla-unidades tr[data-id]', paginationSelector: 'paginacion-unidades', filterFormSelector: '#form-filtros-unidades', filterFunction: (row) => {
        const nom = (document.getElementById('buscar-nombre-unidad')?.value || '').toLowerCase();
        const lug = (document.getElementById('buscar-lugar-unidad')?.value || '').toLowerCase();
        return row.querySelector('.nombre-unidad')?.textContent.toLowerCase().includes(nom) && row.querySelector('.lugar-unidad')?.textContent.toLowerCase().includes(lug);
    }, rowsPerPage: 10 });

    const pLis = document.getElementById('pantalla-lista-unidades'), pAdd = document.getElementById('pantalla-agregar-unidad'), pEdi = document.getElementById('pantalla-editar-unidad');
    document.getElementById('btn-agregar-unidad').onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-unidad').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-editar-unidad').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };

    document.getElementById('form-agregar-unidad').onsubmit = async (e) => {
        e.preventDefault(); try {
            const res = await SendDataEnd('modales/crud_unidades_operativas/insertar', { method: 'POST', body: new FormData(e.target) });
            if (res.success) { mostrarNotificacion('Unidad agregada ✅', 'success'); abrirModal('UnidadOperativa'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    };

    document.getElementById('form-editar-unidad').onsubmit = async (e) => {
        e.preventDefault(); const fd = new FormData(e.target); const id = fd.get('ID_UnidadOperativa');
        try {
            const res = await SendDataEnd(`modales/crud_unidades_operativas/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { mostrarNotificacion('Unidad actualizada ✅', 'success'); abrirModal('UnidadOperativa'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    };

    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-unidad-']"), bD = e.target.closest("[id^='btn-eliminar-unidad-']");
        if (bE) {
            e.preventDefault(); const f = bE.closest('tr');
            document.getElementById('editar-ID_UnidadOperativa').value = f.dataset.id;
            document.getElementById('editar-Nombre-unidad').value = f.dataset.nombre;
            document.getElementById('editar-ID_Place-unidad').value = f.dataset.idPlace;
            document.getElementById('editar-activo-unidad').checked = f.dataset.activo == '1';
            pLis.classList.add('hidden'); pEdi.classList.remove('hidden');
        }
        if (bD) {
            e.preventDefault(); if (!(await Confirmar('¿Desactivar?', '¿Seguro?'))) return;
            const res = await SendDataEnd(`modales/crud_unidades_operativas/eliminar/${bD.dataset.id}`, { method: 'POST' });
            if (res.success) { mostrarNotificacion('Desactivada ✅', 'success'); abrirModal('UnidadOperativa'); }
        }
    });
}

function initCrudDepartamento() {
    const tabla = document.getElementById('tabla-departamentos'); if (!tabla) return;
    setupClientSideTable({ rowsSelector: '#tabla-departamentos tr[data-id]', paginationSelector: 'paginacion-departamentos', filterFormSelector: '#form-filtros-departamentos', filterFunction: (row) => {
        const nom = (document.getElementById('buscar-nombre-depto')?.value || '').toLowerCase();
        const uni = (document.getElementById('buscar-unidad-depto')?.value || '').toLowerCase();
        return row.querySelector('.nombre-depto')?.textContent.toLowerCase().includes(nom) && row.querySelector('.unidad-depto')?.textContent.toLowerCase().includes(uni);
    }, rowsPerPage: 10 });

    const pLis = document.getElementById('pantalla-lista-departamentos'), pAdd = document.getElementById('pantalla-agregar-departamento'), pEdi = document.getElementById('pantalla-editar-departamento');
    document.getElementById('btn-agregar-departamento').onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-editar').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };

    // --- SINCRONIZACIÓN AGREGAR ---
    const sPAdd = document.getElementById('ID_Place'), sUAdd = document.getElementById('ID_UnidadOperativa');
    if (sPAdd && sUAdd) {
        sPAdd.onchange = () => {
            const pid = sPAdd.value;
            Array.from(sUAdd.options).forEach(o => { if(o.value==="") return; o.hidden = o.dataset.place !== pid; });
            sUAdd.value = "";
        };
        sUAdd.onchange = () => {
            const opt = sUAdd.options[sUAdd.selectedIndex];
            if (opt && opt.dataset.place) {
                sPAdd.value = opt.dataset.place;
                Array.from(sUAdd.options).forEach(o => { if(o.value==="") return; o.hidden = o.dataset.place !== opt.dataset.place; });
            }
        };
    }

    // --- SINCRONIZACIÓN EDITAR ---
    const sPEdi = document.getElementById('editar-ID_Place'), sUEdi = document.getElementById('editar-ID_UnidadOperativa');
    if (sPEdi && sUEdi) {
        sPEdi.onchange = () => {
            const pid = sPEdi.value;
            Array.from(sUEdi.options).forEach(o => { if(o.value==="") return; o.hidden = o.dataset.place !== pid; });
            sUEdi.value = "";
        };
        sUEdi.onchange = () => {
            const opt = sUEdi.options[sUEdi.selectedIndex];
            if (opt && opt.dataset.place) {
                sPEdi.value = opt.dataset.place;
                Array.from(sUEdi.options).forEach(o => { if(o.value==="") return; o.hidden = o.dataset.place !== opt.dataset.place; });
            }
        };
    }

    document.getElementById('form-agregar-departamento').onsubmit = async (e) => {
        e.preventDefault(); try {
            const res = await SendDataEnd('modales/crud_departamentos/insertar', { method: 'POST', body: new FormData(e.target) });
            if (res.success) { mostrarNotificacion('Depto agregado ✅', 'success'); abrirModal('crud_departamento'); }
            else { mostrarNotificacion(res.message || 'Error ❌', 'error'); }
        } catch { mostrarNotificacion('Error de conexión ❌', 'error'); }
    };

    document.getElementById('form-editar-departamento').onsubmit = async (e) => {
        e.preventDefault(); const fd = new FormData(e.target); const id = fd.get('ID_Dpto');
        try {
            const res = await SendDataEnd(`modales/crud_departamentos/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { mostrarNotificacion('Depto actualizado ✅', 'success'); abrirModal('crud_departamento'); }
            else { mostrarNotificacion(res.message || 'Error ❌', 'error'); }
        } catch { mostrarNotificacion('Error de conexión ❌', 'error'); }
    };

    tabla.addEventListener('click', async (e) => {
        const btnE = e.target.closest("[id^='btn-editar-departamento-']"), btnD = e.target.closest("[id^='btn-eliminar-departamento-']");
        if (btnE) {
            e.preventDefault(); const f = btnE.closest('tr');
            document.getElementById('editar-ID_Dpto').value = f.dataset.id;
            document.getElementById('editar-Nombre').value = f.dataset.nombre;
            
            if (sPEdi && sUEdi) {
                sPEdi.value = f.dataset.idPlace;
                Array.from(sUEdi.options).forEach(o => { if(o.value==="") return; o.hidden = o.dataset.place !== f.dataset.idPlace; });
                sUEdi.value = f.dataset.idUnidad;
            }
            pLis.classList.add('hidden'); pEdi.classList.remove('hidden');
        }
        if (btnD) {
            e.preventDefault(); if (!(await Confirmar('¿Eliminar?', '¿Seguro?'))) return;
            const res = await SendDataEnd(`modales/crud_departamentos/eliminar/${btnD.dataset.id}`, { method: 'POST' });
            if (res.success) { mostrarNotificacion('Eliminado ✅', 'success'); abrirModal('crud_departamento'); }
        }
    });
}

function initCrudBancoDpto() {
    const tabla = document.getElementById('tabla-banco-dpto'); if (!tabla) return;
    setupClientSideTable({ rowsSelector: '#tabla-banco-dpto tr[data-id]', paginationSelector: 'paginacion-banco-dpto', filterFormSelector: '#form-filtros-banco-dpto', filterFunction: (row) => {
        const rs = (document.getElementById('buscar-rs')?.value || '').toLowerCase();
        const ban = (document.getElementById('buscar-banco')?.value || '').toLowerCase();
        return row.querySelector('.nombre-rs')?.textContent.toLowerCase().includes(rs) && row.querySelector('.nombre-banco')?.textContent.toLowerCase().includes(ban);
    }, rowsPerPage: 10 });
    initBancoDptoPantallas(); initBancoDptoForm(); initBancoDptoEditarForm(); initBancoDptoActions(tabla);
}

function initBancoDptoPantallas() {
    const pLis = document.getElementById('pantalla-lista-banco-dpto'), pAdd = document.getElementById('pantalla-agregar-banco-dpto'), pEdi = document.getElementById('pantalla-editar-banco-dpto');
    document.getElementById('btn-agregar-banco-dpto').onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-banco-dpto').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-editar-banco-dpto').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };
}

function initBancoDptoForm() {
    const fAdd = document.getElementById('form-agregar-banco-dpto'); if (!fAdd) return;
    fAdd.onsubmit = async (e) => {
        e.preventDefault(); try {
            const res = await SendDataEnd('modales/crud_banco_dpto/insertar', { method: 'POST', body: new FormData(fAdd) });
            if (res.success) { mostrarNotificacion('Agregado ✅', 'success'); abrirModal('BancoDpto'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initBancoDptoEditarForm() {
    const fEdi = document.getElementById('form-editar-banco-dpto'); if (!fEdi) return;
    fEdi.onsubmit = async (e) => {
        e.preventDefault(); const fd = new FormData(fEdi); const id = fd.get('ID_BancoDpto');
        try {
            const res = await SendDataEnd(`modales/crud_banco_dpto/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { mostrarNotificacion('Actualizado ✅', 'success'); abrirModal('BancoDpto'); }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initBancoDptoActions(tabla) {
    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-banco-dpto-']"), bD = e.target.closest("[id^='btn-eliminar-banco-dpto-']");
        if (bD) {
            e.preventDefault(); if (!(await Confirmar('¿Eliminar?', '¿Seguro?'))) return;
            const res = await SendDataEnd(`modales/crud_banco_dpto/eliminar/${bD.dataset.id}`, { method: 'POST' });
            if (res.success) { mostrarNotificacion('Eliminado ✅', 'success'); abrirModal('BancoDpto'); }
        }
        if (bE) {
            e.preventDefault(); const f = bE.closest('tr');
            document.getElementById('editar-ID_BancoDpto').value = f.dataset.id;
            document.getElementById('editar-Banco').value = f.dataset.banco;
            document.getElementById('editar-Clabe').value = f.dataset.clabe;
            document.getElementById('editar-ID_RazonSocial').value = f.dataset.idRs;
            document.getElementById('pantalla-lista-banco-dpto').classList.add('hidden');
            document.getElementById('pantalla-editar-banco-dpto').classList.remove('hidden');
        }
    });
}
