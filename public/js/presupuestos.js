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
            bloqueadoPorRevision: false,
            usoCopia: false,
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
                this.bloqueadoPorRevision = false;
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
                    placeholderValue: 'Seleccione Complejos',
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
                this.usoCopia = false;
                this.departamentos = [];
                this.departamentosOriginales = [];
                this.mensaje = '';

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/estructura/${idsParam}/${anio}/${parseInt(mes)}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.departamentosOriginales = JSON.parse(JSON.stringify(data.departamentos || []));
                        this.departamentos = data.departamentos || [];
                        this.bloqueadoPorRevision = data.bloqueadoPorRevision || false;
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

            async exportarAsignacionExcel() {
                if (this.departamentos.length === 0) return;
                
                const notif = mostrarNotificacion('Generando archivo Excel...', 'info', 0);
                try {
                    // Preparamos los datos tal cual están en pantalla
                    const dataExport = this.departamentos.map(uni => ({
                        unidad: uni.Nombre,
                        place: uni.PlaceNombre || '',
                        grupos: uni.grupos.map(g => ({
                            nombre: g.Nombre,
                            monto: parseFloat(g.Monto_Asignado) || 0
                        }))
                    }));

                    const payload = {
                        mesAnio: this.mesAnio,
                        datos: dataExport
                    };

                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/exportar-asignacion`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `asignacion_presupuesto_${this.mesAnio}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    } else {
                        mostrarNotificacion('Error al generar el Excel', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    mostrarNotificacion('Error de conexión', 'error');
                } finally {
                    if (notif) notif.click();
                }
            },

            async exportarAnualExcel() {
                if (this.departamentos.length === 0 || !this.mesAnio) {
                    mostrarNotificacion('Por favor cargue una estructura antes de exportar el anual.', 'warning');
                    return;
                }

                const anio = this.mesAnio.split('-')[0];
                const notif = mostrarNotificacion('Generando archivo Excel Anual...', 'info', 0);
                
                try {
                    const payload = {
                        anio: anio,
                        idsPlaces: this.idsPlaces,
                        idRazonSocial: this.idRazonSocial
                    };

                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/exportar-anual`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const blob = await res.blob();
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `presupuesto_anual_${anio}.xlsx`;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                    } else {
                        mostrarNotificacion('Error al generar el archivo Excel anual', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    mostrarNotificacion('Error de red al exportar anual', 'error');
                } finally {
                    if (notif) notif.click();
                }
            },

            async copiarAnterior() {
                if (this.idsPlaces.length === 0 || !this.mesAnio) {
                    mostrarNotificacion('Seleccione al menos un Complejo y una Fecha primero.', 'error');
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-').map(Number);
                let prevMes = mes - 1;
                let prevAnio = anio;
                if (prevMes === 0) {
                    prevMes = 12;
                    prevAnio = anio - 1;
                }

                const idsParam = this.idsPlaces.join(',');
                const notif = mostrarNotificacion('Obteniendo datos del mes anterior...', 'info', 0);

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/estructura/${idsParam}/${prevAnio}/${prevMes}`);
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
                        this.usoCopia = true; // Activar la bandera de excepción
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

            async guardarMasivo(esRestoAnio = false) {
                if (this.departamentos.length === 0) return;

                const [anio, mes] = this.mesAnio.split('-');
                this.guardando = true;
                this.mensaje = '';
                this.error = false;

                let gruposParaGuardar = [];

                this.departamentos.forEach(uni => {
                    uni.grupos.forEach(grupo => {
                        const nuevoMonto = parseFloat(grupo.Monto_Asignado) || 0;
                        
                        // Buscar el monto original
                        let montoOriginal = 0;
                        const dptoOrig = this.departamentosOriginales.find(d => String(d.ID_UnidadOperativa) === String(uni.ID_UnidadOperativa));
                        if (dptoOrig && dptoOrig.grupos) {
                            const grupoOrig = dptoOrig.grupos.find(g => String(g.ID_GrupoPresupuestal) === String(grupo.ID_GrupoPresupuestal));
                            if (grupoOrig) {
                                montoOriginal = parseFloat(grupoOrig.Monto_Asignado) || 0;
                            }
                        }

                        // Solo agregar si hubo un cambio real
                        if (nuevoMonto !== montoOriginal) {
                            gruposParaGuardar.push({
                                id_unidad: uni.ID_UnidadOperativa,
                                nombre_unidad: uni.Nombre,
                                id_grupo: grupo.ID_GrupoPresupuestal,
                                nombre_grupo: grupo.Nombre,
                                id_existente: grupo.ID_PresupuestoMensual,
                                monto_asignado: nuevoMonto,
                                monto_anterior: montoOriginal
                            });
                        }
                    });
                });

                if (gruposParaGuardar.length === 0) {
                    mostrarNotificacion('No se detectaron cambios en los montos para guardar.', 'info');
                    this.guardando = false;
                    return;
                }

                let comentarios = 'Copia de mes anterior (Excepción)';
                if (!this.usoCopia) {
                    comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe brevemente el motivo de esta asignación (Obligatorio):', true);
                    if (comentarios === null) {
                        this.guardando = false;
                        return;
                    }
                }

                const payload = {
                    anio: parseInt(anio),
                    mes: parseInt(mes),
                    resto_anio: esRestoAnio,
                    grupos: gruposParaGuardar,
                    comentarios: comentarios,
                    uso_copia: this.usoCopia // Indicar al servidor que es una excepción
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
                        const result = await res.json();
                        if (result.pending_review) {
                            this.mensaje = result.message || 'Presupuestos enviados a revisión.';
                            this.error = false;
                            this.usoCopia = false;
                            this.bloqueadoPorRevision = true; // Bloqueo inmediato en el frontend
                            await this.cargarEstructura();    // Sincronizar con el servidor
                        } else {
                            this.mensaje = 'Presupuestos guardados correctamente';
                            this.error = false;
                            this.usoCopia = false;
                            await this.cargarEstructura();
                        }
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
            mesAnio: '',

            razonesSociales: [],
            todosPlaces: [],
            razonesData: [],
            razonesDataOriginales: [],

            cargando: false,
            guardando: false,
            bloqueadoPorRevision: false,
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
                this.razonesDataOriginales = [];
                this.bloqueadoPorRevision = false;
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
                this.razonesDataOriginales = [];
                this.mensaje = '';

                try {
                    const res = await fetch(`${BASE_URL}api/saldos-bancarios/estructura/${this.idRazonSocial}/${anio}/${parseInt(mes)}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.razonesData = data.razones || [];
                        this.razonesDataOriginales = JSON.parse(JSON.stringify(this.razonesData));
                        this.bloqueadoPorRevision = data.bloqueadoPorRevision || false;
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
                        const nuevoInicial = parseFloat(banco.saldo_inicial) || 0;
                        const nuevoFinal = parseFloat(banco.saldo_final) || 0;

                        // Buscar saldos originales
                        let originalInicial = 0;
                        let originalFinal = 0;
                        
                        const rsOrig = this.razonesDataOriginales.find(r => String(r.ID_RazonSocial) === String(rs.ID_RazonSocial));
                        if (rsOrig && rsOrig.bancos) {
                            const bancoOrig = rsOrig.bancos.find(b => String(b.ID_BancoDpto) === String(banco.ID_BancoDpto));
                            if (bancoOrig) {
                                originalInicial = parseFloat(bancoOrig.saldo_inicial) || 0;
                                originalFinal = parseFloat(bancoOrig.saldo_final) || 0;
                            }
                        }

                        // Solo enviar si hubo un cambio real
                        if (nuevoInicial !== originalInicial || nuevoFinal !== originalFinal) {
                            saldosParaEnviar.push({
                                id_bancodpto: banco.ID_BancoDpto,
                                nombre_banco: banco.Banco, // Añadido para UI
                                clabe_banco: banco.Clabe, // Añadido para UI
                                saldo_inicial: nuevoInicial,
                                saldo_final: nuevoFinal,
                                saldo_inicial_anterior: originalInicial,
                                saldo_final_anterior: originalFinal,
                                id_existente: banco.id_saldo_existente
                            });
                        }
                    });
                });

                if (saldosParaEnviar.length === 0) {
                    mostrarNotificacion('No se detectaron cambios en los saldos para guardar.', 'info');
                    this.guardando = false;
                    return;
                }

                const payload = {
                    anio: parseInt(anio),
                    mes: parseInt(mes),
                    saldos: saldosParaEnviar,
                    comentarios: 'Actualización directa'
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
                            if (result.pending_review) {
                                this.mensaje = result.message || 'Saldos enviados a revisión.';
                                this.error = false;
                                this.bloqueadoPorRevision = true;
                                await this.cargarEstructura();
                            } else {
                                this.mensaje = 'Saldos guardados correctamente';
                                this.error = false;
                                await this.cargarEstructura();
                            }
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
                            'completo': 'placesSelectorCompleto'
                        };
                        this.initChoicesMeses(refMapMeses[nueva]);
                        this.initChoicesPlaces(refMapPlaces[nueva]);
                    });
                }

                // ELIMINADA LÓGICA DE ANCHO DE MODAL DINÁMICO
                // Ahora se controla globalmente en mbscript.js para ReportePresupuesto
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
                    'completo': 'placesSelectorCompleto'
                };
                
                this.$nextTick(() => {
                    this.initChoicesPlaces(refMap[pantalla]);
                });
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
                // Reset bandera de excedidos antes de recalcular
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
                        const asig = parseFloat(d.presupuesto?.asignado || 0);
                        const comp = parseFloat(d.presupuesto?.comprometido || 0);
                        const ejec = parseFloat(d.presupuesto?.ejecutado || 0);
                        const gast = parseFloat(d.presupuesto?.gastado || 0);
                        
                        totales.pAsignado += asig;
                        totales.pComprometido += comp;
                        totales.pEjecutado += ejec;
                        totales.pGastado += gast;

                        // Activar bandera si esta unidad o alguno de sus detalles ya excedió
                        if (gast > asig) this.hayExcedidos = true;
                        if (d.detalles) {
                            d.detalles.forEach(det => { if (det.gastado > det.asignado) this.hayExcedidos = true; });
                        }
                    } else {
                        const src = d.totales || d;
                        const asig = parseFloat(src.asignado || 0);
                        const comp = parseFloat(src.comprometido || 0);
                        const ejec = parseFloat(src.ejecutado || 0);
                        const gast = comp + ejec;

                        totales.asignado += asig;
                        totales.comprometido += comp;
                        totales.ejecutado += ejec;

                        // Activar bandera si esta unidad o alguno de sus detalles ya excedió
                        if (gast > asig) this.hayExcedidos = true;
                        if (d.detalles) {
                            d.detalles.forEach(det => { if ((det.comprometido + det.ejecutado) > det.asignado) this.hayExcedidos = true; });
                        }
                    }
                };

                const calc = (totales) => {
                    if (this.pantalla === 'cuentas') {
                        totales.porcentaje = totales.inicial > 0 ? Math.round((totales.usado / totales.inicial) * 100 * 100) / 100 : 0;
                    } else if (this.pantalla === 'presupuesto') {
                        const totalGasto = totales.comprometido + totales.ejecutado;
                        
                        // Lógica solicitada: Si gasto > asignado, disponible = 0 y el resto es excedido
                        if (totalGasto > totales.asignado) {
                            totales.disponible = 0;
                            totales.excedido = totalGasto - totales.asignado;
                            this.hayExcedidos = true; // Activar columna globalmente
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
        e.preventDefault(); 
        
        const comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe el motivo de este cambio (Obligatorio):', true);
        if (comentarios === null) return;
        
        const fd = new FormData(fAdd);
        fd.append('comentarios', comentarios);

        try {
            const res = await SendDataEnd('modales/crud_segmentos/insertar', { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Enviado a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Agregado ✅', 'success'); 
                }
                abrirModal('SegmentoNegocio'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initSegmentosEditarForm() {
    const fEdi = document.getElementById('form-editar-segmentos'); if (!fEdi) return;
    fEdi.onsubmit = async (e) => {
        e.preventDefault(); 
        
        const comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe el motivo de esta edición (Obligatorio):', true);
        if (comentarios === null) return;

        const fd = new FormData(fEdi); 
        fd.append('comentarios', comentarios);
        const id = fd.get('id');
        
        try {
            const res = await SendDataEnd(`modales/crud_segmentos/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Edición enviada a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Actualizado ✅', 'success'); 
                }
                abrirModal('SegmentoNegocio'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initSegmentosActions(tabla) {
    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-segmentos-']"), bD = e.target.closest("[id^='btn-eliminar-segmentos-']");
        if (bD) {
            e.preventDefault(); 
            const comentarios = await InputPrompt('Confirmar Eliminación', 'Describe el motivo de la eliminación (Obligatorio):', true);
            if (comentarios === null) return;
            
            const fd = new FormData();
            fd.append('comentarios', comentarios);
            
            const res = await SendDataEnd(`modales/crud_segmentos/eliminar/${bD.dataset.id}`, { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Eliminación enviada a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Eliminado ✅', 'success'); 
                    bD.closest('tr').remove(); 
                }
                abrirModal('SegmentoNegocio');
            }
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
    const container = document.getElementById('pantalla-lista-grupos');
    const tabla = document.getElementById('tabla-grupos'); 
    if (!tabla || !container) return;
    
    const unidadesData = JSON.parse(container.dataset.unidadesJson || '[]');

    // Inicializar Choices para los filtros
    const refLugar = document.getElementById('filtro-lugar-grupo');
    const refUnidad = document.getElementById('filtro-unidad-grupo');
    let choicesLugar = null;
    let choicesUnidad = null;

    if (refLugar) {
        choicesLugar = new Choices(refLugar, { removeItemButton: true, itemSelectText: '', placeholderValue: 'Todos los complejos', searchPlaceholderValue: 'Buscar...' });
    }
    if (refUnidad) {
        choicesUnidad = new Choices(refUnidad, { removeItemButton: true, itemSelectText: '', placeholderValue: 'Todos los departamentos', searchPlaceholderValue: 'Buscar...' });
    }

    // Lógica de Sincronización de Filtros
    if (refLugar && refUnidad) {
        refLugar.addEventListener('change', () => {
            const lugaresSeleccionados = choicesLugar.getValue(true); // Nombres de los complejos
            
            let deptosFiltrados = [];
            if (lugaresSeleccionados.length === 0) {
                // Si no hay complejos, mostrar todos los departamentos únicos
                deptosFiltrados = [...new Set(unidadesData.map(u => u.Nombre))];
            } else {
                // Mostrar departamentos que pertenezcan a los complejos seleccionados
                deptosFiltrados = [...new Set(
                    unidadesData
                        .filter(u => lugaresSeleccionados.includes(u.PlaceNombre))
                        .map(u => u.Nombre)
                )];
            }

            // Actualizar las opciones del selector de Unidades
            choicesUnidad.clearStore();
            const nuevasOpciones = deptosFiltrados.sort().map(nombre => ({
                value: nombre,
                label: nombre,
                selected: false,
                disabled: false
            }));
            choicesUnidad.setChoices(nuevasOpciones, 'value', 'label', true);
            
            // Forzar refresco de la tabla
            refUnidad.dispatchEvent(new Event('change'));
        });
    }

    setupClientSideTable({ 
        rowsSelector: '#tabla-grupos tr[data-id]', 
        paginationSelector: 'paginacion-grupos', 
        filterFormSelector: '#form-filtros-grupos', 
        filterFunction: (row) => {
            const nom = (document.getElementById('buscar-nombre-grupo')?.value || '').toLowerCase();
            const lugaresSel = choicesLugar ? choicesLugar.getValue(true).map(v => v.toLowerCase()) : [];
            const unidadesSel = choicesUnidad ? choicesUnidad.getValue(true).map(v => v.toLowerCase()) : [];
            
            const textoCelda = row.querySelector('.unidad-grupo')?.textContent.toLowerCase() || '';
            
            const matchNombre = row.querySelector('.nombre-grupo')?.textContent.toLowerCase().includes(nom);
            const matchLugar = lugaresSel.length === 0 || lugaresSel.some(l => textoCelda.includes(`(${l})`));
            
            const nombreUnidadEnCelda = textoCelda.split(' (')[0];
            const matchUnidad = unidadesSel.length === 0 || unidadesSel.some(u => nombreUnidadEnCelda === u);

            return matchNombre && matchLugar && matchUnidad;
        }, 
        rowsPerPage: 10 
    });
    initGruposPantallas(); initGruposForm(); initGruposEditarForm(); initGruposActions(tabla);
}

function initGruposPantallas() {
    const pLis = document.getElementById('pantalla-lista-grupos'), pAdd = document.getElementById('pantalla-agregar-grupos'), pEdi = document.getElementById('pantalla-editar-grupos');
    const btnAdd = document.getElementById('btn-agregar-grupos');
    if (btnAdd) btnAdd.onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-grupos').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-editar-grupos').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };
}

function initGruposForm() {
    const fAdd = document.getElementById('form-agregar-grupos'); if (!fAdd) return;
    fAdd.onsubmit = async (e) => {
        e.preventDefault(); 
        
        const comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe el motivo de este cambio (Obligatorio):', true);
        if (comentarios === null) return;
        
        const fd = new FormData(fAdd);
        fd.append('comentarios', comentarios);
        
        try {
            const res = await SendDataEnd('modales/crud_grupos_presupuestales/insertar', { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Enviado a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Agregado ✅', 'success'); 
                }
                abrirModal('GrupoPresupuestal'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initGruposEditarForm() {
    const fEdi = document.getElementById('form-editar-grupos'); if (!fEdi) return;
    fEdi.onsubmit = async (e) => {
        e.preventDefault(); 
        
        const comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe el motivo de esta edición (Obligatorio):', true);
        if (comentarios === null) return;
        
        const fd = new FormData(fEdi); 
        fd.append('comentarios', comentarios);
        const id = fd.get('ID_GrupoPresupuestal');
        
        try {
            const res = await SendDataEnd(`modales/crud_grupos_presupuestales/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Edición enviada a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Actualizado ✅', 'success'); 
                }
                abrirModal('GrupoPresupuestal'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initGruposActions(tabla) {
    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-grupos-']"), bD = e.target.closest("[id^='btn-eliminar-grupos-']");
        if (bD) {
            e.preventDefault(); 
            const comentarios = await InputPrompt('Confirmar Desactivación', 'Describe el motivo de la desactivación (Obligatorio):', true);
            if (comentarios === null) return;
            
            const fd = new FormData();
            fd.append('comentarios', comentarios);
            
            const res = await SendDataEnd(`modales/crud_grupos_presupuestales/eliminar/${bD.dataset.id}`, { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Desactivación enviada a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Desactivado ✅', 'success'); 
                }
                abrirModal('GrupoPresupuestal'); 
            }
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
    const btnAdd = document.getElementById('btn-agregar-unidad');
    if (btnAdd) btnAdd.onclick = (e) => { e.preventDefault(); pLis.classList.add('hidden'); pAdd.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-unidad').onclick = (e) => { e.preventDefault(); pAdd.classList.add('hidden'); pLis.classList.remove('hidden'); };
    document.getElementById('btn-regresar-lista-editar-unidad').onclick = (e) => { e.preventDefault(); pEdi.classList.add('hidden'); pLis.classList.remove('hidden'); };

    document.getElementById('form-agregar-unidad').onsubmit = async (e) => {
        e.preventDefault(); 
        
        const comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe el motivo de este cambio (Obligatorio):', true);
        if (comentarios === null) return;
        
        const fd = new FormData(e.target);
        fd.append('comentarios', comentarios);
        
        try {
            const res = await SendDataEnd('modales/crud_unidades_operativas/insertar', { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Enviado a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Departamento agregado ✅', 'success'); 
                }
                abrirModal('UnidadOperativa'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    };

    document.getElementById('form-editar-unidad').onsubmit = async (e) => {
        e.preventDefault(); 
        
        const comentarios = await InputPrompt('Justificación del Cambio', 'Por favor, describe el motivo de esta edición (Obligatorio):', true);
        if (comentarios === null) return;
        
        const fd = new FormData(e.target); 
        fd.append('comentarios', comentarios);
        const id = fd.get('ID_UnidadOperativa');
        
        try {
            const res = await SendDataEnd(`modales/crud_unidades_operativas/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Edición enviada a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Unidad actualizada ✅', 'success'); 
                }
                abrirModal('UnidadOperativa'); 
            }
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
            e.preventDefault(); 
            const comentarios = await InputPrompt('Confirmar Desactivación', 'Describe el motivo de la desactivación (Obligatorio):', true);
            if (comentarios === null) return;
            
            const fd = new FormData();
            fd.append('comentarios', comentarios);
            
            const res = await SendDataEnd(`modales/crud_unidades_operativas/eliminar/${bD.dataset.id}`, { method: 'POST', body: fd });
            if (res.success) { 
                if (res.pending_review) {
                    mostrarNotificacion(res.message || 'Desactivación enviada a revisión ⏳', 'info');
                } else {
                    mostrarNotificacion('Desactivado ✅', 'success');
                }                abrirModal('UnidadOperativa'); 
            }
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
        e.preventDefault(); 
        const fd = new FormData(fAdd);
        try {
            const res = await SendDataEnd('modales/crud_banco_dpto/insertar', { method: 'POST', body: fd });
            if (res.success) { 
                mostrarNotificacion('Agregado ✅', 'success'); 
                abrirModal('BancoDpto'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initBancoDptoEditarForm() {
    const fEdi = document.getElementById('form-editar-banco-dpto'); if (!fEdi) return;
    fEdi.onsubmit = async (e) => {
        e.preventDefault(); 
        const fd = new FormData(fEdi); 
        const id = fd.get('ID_BancoDpto');
        try {
            const res = await SendDataEnd(`modales/crud_banco_dpto/editar/${id}`, { method: 'POST', body: fd });
            if (res.success) { 
                mostrarNotificacion('Actualizado ✅', 'success'); 
                abrirModal('BancoDpto'); 
            }
        } catch { mostrarNotificacion('Error ❌', 'error'); }
    }
}

function initBancoDptoActions(tabla) {
    tabla.addEventListener('click', async (e) => {
        const bE = e.target.closest("[id^='btn-editar-banco-dpto-']"), bD = e.target.closest("[id^='btn-eliminar-banco-dpto-']");
        if (bD) {
            e.preventDefault(); 
            if (!(await Confirmar('Eliminar Banco?', '¿Seguro que deseas eliminar este registro?'))) return;
            const res = await SendDataEnd(`modales/crud_banco_dpto/eliminar/${bD.dataset.id}`, { method: 'POST' });
            if (res.success) { 
                mostrarNotificacion('Eliminado ✅', 'success'); 
                abrirModal('BancoDpto'); 
            }
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

// --- LOGICA DICTAMEN AJUSTES PRESUPUESTO ---
function initAjustesPresupuesto() {
  if (!document.getElementById('tablaAjustesPresupuesto')) return;

  createPaginatedTable({
    tableSelector: '#tablaAjustesPresupuesto',
    paginationSelector: 'paginacion-ajustes-presupuesto',
    endpoint: 'api/presupuesto/cambios',
    noResultsMessage: 'No hay solicitudes de cambio de presupuesto pendientes.',
    renderRow: (s) => {
      let accionClass = 'bg-yellow-100 text-yellow-800';
      if (s.Accion === 'Insertar' || s.Accion === 'Masivo') accionClass = 'bg-green-100 text-green-800';
      else if (s.Accion === 'Eliminar') accionClass = 'bg-red-100 text-red-800';

      let nombreRef = s.ID_Afectado || '#' + s.ID_SolicitudCambio;
      try {
          const p = JSON.parse(s.Datos_Payload || '{}');
          const a = JSON.parse(s.Datos_Antiguos || '{}');

          // Preferir campos de nombre, luego IDs (que vienen enriquecidos del backend)
          nombreRef = a.Nombre || p.Nombre || a.nombre || p.nombre || a.Banco || p.Banco || 
                      a.ID_UnidadOperativa || p.ID_UnidadOperativa || 
                      a.ID_Place || p.ID_Place || 
                      a.ID_RazonSocial || p.ID_RazonSocial || 
                      nombreRef;
      } catch (e) {}
      return `
      <tr class='hover:bg-gray-50' data-id='${s.ID_SolicitudCambio}'>
          <td class='py-3 px-6 text-left font-medium text-gray-800'>${nombreRef}</td>
          <td class='py-3 px-6 text-left'>${s.NombreUsuario || 'Desconocido'}</td>
          <td class='py-3 px-6 text-left font-semibold text-blue-600'>${s.Modulo}</td>
          <td class='py-3 px-6 text-left'>
             <span class='px-2 py-1 text-xs font-bold rounded-full ${accionClass}'>${s.Accion}</span>
          </td>
          <td class='py-3 px-6 text-left'>${s.created_at}</td>
          <td class='py-3 px-6 text-center'>
              <button onclick='mostrarDetalleAjuste(${s.ID_SolicitudCambio})' class='px-4 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 rounded hover:bg-blue-600 hover:text-white transition-colors text-sm font-semibold'>Revisar</button>
          </td>
      </tr>`;
    }  });
}

window.mostrarDetalleAjuste = async function(id) {
  document.getElementById('div-tabla-ajustes').classList.add('hidden');
  const divVer = document.getElementById('div-ver-detalle-ajuste');
  divVer.classList.remove('hidden');

  const container = document.getElementById('detalles-ajuste-contenido');
  container.innerHTML = '<p class="text-center text-gray-500 py-10">Cargando detalles...</p>';

  try {
    const req = await fetch(`${BASE_URL}api/presupuesto/cambios`);
    const allData = await req.json();
    const data = allData.find(d => parseInt(d.ID_SolicitudCambio) === parseInt(id));

    if (!data) throw new Error('No se encontró la solicitud.');

    const payloadNew = JSON.parse(data.Datos_Payload || '{}');
    const payloadOld = JSON.parse(data.Datos_Antiguos || '{}');
    
    let nombreRef = data.ID_Afectado || '#' + data.ID_SolicitudCambio;
    try {
        nombreRef = payloadOld.Nombre || payloadNew.Nombre || payloadOld.nombre || payloadNew.nombre || payloadOld.Banco || payloadNew.Banco || 
                    payloadOld.ID_UnidadOperativa || payloadNew.ID_UnidadOperativa || 
                    payloadOld.ID_Place || payloadNew.ID_Place || 
                    payloadOld.ID_RazonSocial || payloadNew.ID_RazonSocial || 
                    nombreRef;
    } catch (e) {}

    document.getElementById('titulo-detalle-ajuste').innerText = `Detalles de la Solicitud: ${nombreRef}`;

    let htmlDiff = '';

    // Lógica para comparar Masivos (Presupuestos o Saldos)
    if (data.Accion === 'Masivo') {
        htmlDiff = `<p class="text-sm text-gray-600 mb-4">Este es un cambio masivo de <b>${data.Modulo}</b> para el periodo <b>${data.ID_Afectado}</b>.</p>`;
        
        if (data.Modulo === 'PresupuestoMensual' && payloadNew.grupos) {
            htmlDiff += `<table class="min-w-full bg-white border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-3 border-b text-left">Unidad / Partida</th>
                                    <th class="py-2 px-3 border-b text-right">Monto Anterior</th>
                                    <th class="py-2 px-3 border-b text-right">Nuevo Monto</th>
                                </tr>
                            </thead>
                            <tbody>`;
            payloadNew.grupos.forEach(g => {
                htmlDiff += `<tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-2 px-3 border-b">
                                    <span class="block font-bold text-gray-700">${g.nombre_unidad || 'Unidad ' + (g.id_unidad || g.id_dpto)}</span>
                                    <span class="block text-xs text-gray-500">${g.nombre_grupo || 'Grupo ' + g.id_grupo}</span>
                                </td>
                                <td class="py-2 px-3 border-b text-right text-red-600 line-through">
                                    $${parseFloat(g.monto_anterior || 0).toLocaleString('es-MX', {minimumFractionDigits:2})}
                                </td>
                                <td class="py-2 px-3 border-b text-right font-bold text-green-700">
                                    $${parseFloat(g.monto_asignado).toLocaleString('es-MX', {minimumFractionDigits:2})}
                                </td>
                             </tr>`;
            });
            htmlDiff += `</tbody></table>`;
        } 
        else if (data.Modulo === 'SaldosBancarios' && payloadNew.saldos) {
            htmlDiff += `<table class="min-w-full bg-white border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-2 px-3 border-b text-left">Banco / Cuenta</th>
                                    <th class="py-2 px-3 border-b text-center">Tipo de Saldo</th>
                                    <th class="py-2 px-3 border-b text-right">Monto Anterior</th>
                                    <th class="py-2 px-3 border-b text-right">Nuevo Monto</th>
                                </tr>
                            </thead>
                            <tbody>`;
            payloadNew.saldos.forEach(s => {
                const sIniNuevo = parseFloat(s.saldo_inicial);
                const sIniViejo = parseFloat(s.saldo_inicial_anterior || 0);
                const sFinNuevo = parseFloat(s.saldo_final);
                const sFinViejo = parseFloat(s.saldo_final_anterior || 0);
                
                if (sIniNuevo !== sIniViejo) {
                    htmlDiff += `<tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-2 px-3 border-b">
                                        <span class="block font-bold text-gray-700">${s.nombre_banco || 'Banco ' + s.id_bancodpto}</span>
                                        <span class="block text-xs text-gray-500 font-mono">${s.clabe_banco || ''}</span>
                                    </td>
                                    <td class="py-2 px-3 border-b text-center text-gray-600 font-medium">INICIAL</td>
                                    <td class="py-2 px-3 border-b text-right text-red-600 line-through">$${sIniViejo.toLocaleString('es-MX', {minimumFractionDigits:2})}</td>
                                    <td class="py-2 px-3 border-b text-right font-bold text-green-700">$${sIniNuevo.toLocaleString('es-MX', {minimumFractionDigits:2})}</td>
                                 </tr>`;
                }
                if (sFinNuevo !== sFinViejo) {
                    htmlDiff += `<tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-2 px-3 border-b">
                                        <span class="block font-bold text-gray-700">${s.nombre_banco || 'Banco ' + s.id_bancodpto}</span>
                                        <span class="block text-xs text-gray-500 font-mono">${s.clabe_banco || ''}</span>
                                    </td>
                                    <td class="py-2 px-3 border-b text-center text-gray-600 font-medium">FINAL</td>
                                    <td class="py-2 px-3 border-b text-right text-red-600 line-through">$${sFinViejo.toLocaleString('es-MX', {minimumFractionDigits:2})}</td>
                                    <td class="py-2 px-3 border-b text-right font-bold text-green-700">$${sFinNuevo.toLocaleString('es-MX', {minimumFractionDigits:2})}</td>
                                 </tr>`;
                }
            });
            htmlDiff += `</tbody></table>`;
        }
    } 
    // Lógica para CRUDs Individuales (Insertar, Editar, Eliminar)
    else {
        htmlDiff += `<table class="min-w-full bg-white border border-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-4 border-b text-left font-semibold text-gray-700">Campo</th>
                                <th class="py-2 px-4 border-b text-left font-semibold text-gray-700">Valor Anterior</th>
                                <th class="py-2 px-4 border-b text-left font-semibold text-gray-700">Valor Solicitado</th>
                            </tr>
                        </thead>
                        <tbody>`;
        
        const allKeys = new Set([...Object.keys(payloadNew), ...Object.keys(payloadOld)]);
        
        allKeys.forEach(key => {
            // Ignorar campos ruidosos
            if (['comentarios', 'id', 'created_at', 'updated_at'].includes(key)) return;

            let oldVal = payloadOld[key] !== undefined ? payloadOld[key] : '<i>N/A</i>';
            let newVal = payloadNew[key] !== undefined ? payloadNew[key] : '<i>N/A</i>';
            
            // Transformar booleanos para lectura fácil
            if (oldVal === true || oldVal === 't' || oldVal === '1' || oldVal === 1) oldVal = 'Sí (Activo)';
            if (oldVal === false || oldVal === 'f' || oldVal === '0' || oldVal === 0) oldVal = 'No (Inactivo)';
            if (newVal === true || newVal === 't' || newVal === '1' || newVal === 1 || newVal === 'on') newVal = 'Sí (Activo)';
            if (newVal === false || newVal === 'f' || newVal === '0' || newVal === 0 || newVal === '') newVal = 'No (Inactivo)';

            // Solo mostrar si hubo un cambio real o si es inserción
            if (String(oldVal) !== String(newVal) || data.Accion === 'Insertar') {
                htmlDiff += `<tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border-b font-semibold text-gray-800 capitalize">${key.replace(/_/g, ' ')}</td>
                                <td class="py-2 px-4 border-b text-red-600 line-through">${data.Accion === 'Insertar' ? '-' : oldVal}</td>
                                <td class="py-2 px-4 border-b text-green-700 font-bold">${data.Accion === 'Eliminar' ? '-' : newVal}</td>
                             </tr>`;
            }
        });
        htmlDiff += `</tbody></table>`;
    }

    let comentariosHtml = '';
    if (data.Comentarios_Solicitante) {
        comentariosHtml = `
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <h4 class="font-bold text-blue-800 mb-1 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                    </svg>
                    Justificación del Solicitante:
                </h4>
                <p class="text-blue-900 text-sm italic">"${data.Comentarios_Solicitante}"</p>
            </div>
        `;
    }

    let html = `
        <div class="grid grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <div><span class="block text-xs font-bold text-gray-500 uppercase">Módulo</span><span class="text-lg font-semibold text-gray-800">${data.Modulo}</span></div>
            <div><span class="block text-xs font-bold text-gray-500 uppercase">Referencia</span><span class="text-lg font-semibold text-gray-800">${nombreRef}</span></div>
            <div><span class="block text-xs font-bold text-gray-500 uppercase">Acción Solicitada</span><span class="text-lg font-semibold text-gray-800">${data.Accion}</span></div>
            <div><span class="block text-xs font-bold text-gray-500 uppercase">Solicitante</span><span class="text-gray-800">${data.NombreUsuario}</span></div>
            <div><span class="block text-xs font-bold text-gray-500 uppercase">Fecha</span><span class="text-gray-800">${data.created_at}</span></div>
        </div>
        
        ${comentariosHtml}

        <div class="mb-6">
            <h4 class="font-bold text-gray-700 mb-3 text-lg">Comparativa de Datos:</h4>
            ${htmlDiff}
        </div>
        <div class="flex justify-end space-x-4 border-t border-gray-200 pt-6">
            <button onclick="dictaminarAjuste(${id}, 'Rechazado')" class="px-6 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 shadow transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                Rechazar
            </button>
            <button onclick="dictaminarAjuste(${id}, 'Aprobado')" class="px-6 py-2.5 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 shadow transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                Aprobar y Ejecutar
            </button>
        </div>
    `;
    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = `<p class="text-center text-red-500 py-10 font-bold">Error: ${error.message}</p>`;
  }
}

window.regresarTablaAjustes = function() {
  document.getElementById('div-ver-detalle-ajuste').classList.add('hidden');
  document.getElementById('div-tabla-ajustes').classList.remove('hidden');
}

window.dictaminarAjuste = async function(id, decision) {
  const esAprobacion = decision === 'Aprobado';
  const title = esAprobacion ? 'Aprobar Ajuste' : 'Rechazar Ajuste';
  const msg = esAprobacion ? 'Agrega un comentario (Obligatorio):' : 'Indica el motivo del rechazo (Obligatorio):';
  
  const comentarios = await InputPrompt(title, msg, true); // Changed to true to make it mandatory
  if (comentarios === null) return;

  const notif = mostrarNotificacion('Procesando dictamen...', 'info', 999999);

  try {
    const res = await SendDataEnd('api/presupuesto/dictaminar', {
      method: 'POST',
      body: { ID_SolicitudCambio: id, Estado: decision, Comentarios: comentarios }
    });
    notif.click();
    if (res.success) {
        mostrarNotificacion(res.message, 'success');
        abrirModal('AjustesPresupuesto');
    } else {
        mostrarNotificacion(res.message || 'Error al procesar', 'error');
    }
  } catch (error) {
    notif.click();
    mostrarNotificacion('Error de conexión', 'error');
  }
}
