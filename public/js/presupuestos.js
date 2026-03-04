/**
 * Lógica para Presupuesto Mensual
 */
function registrarComponentePresupuesto() {
    Alpine.data('presupuestoEscalonado', function () {
        return {
            idRazonSocial: '',
            idPlace: '',
            mesAnio: '',

            razonesSociales: [],
            todosPlaces: [],
            departamentos: [],

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
                this.departamentos = [];
                this.mensaje = '';
            },

            // NUEVO: Calcula la suma total en tiempo real
            get sumaTotal() {
                let total = 0;
                this.departamentos.forEach(dpto => {
                    if (dpto.grupos) {
                        dpto.grupos.forEach(grupo => {
                            // Convertimos a número, si está vacío o es texto, lo tomamos como 0
                            let monto = parseFloat(grupo.Monto_Asignado) || 0;
                            total += monto;
                        });
                    }
                });
                return total;
            },

            // Calcula la suma de un departamento específico
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
                if (!this.idPlace || !this.mesAnio) {
                    this.resetEstructura();
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-');
                this.cargando = true;
                this.departamentos = [];
                this.mensaje = '';

                try {
                    const res = await fetch(`${BASE_URL}api/presupuesto-mensual/estructura/${this.idPlace}/${anio}/${parseInt(mes)}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.departamentos = data.departamentos || [];
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
                if (!this.idPlace || !this.mesAnio) {
                    mostrarNotificacion('Seleccione un Place y una Fecha primero.', 'error');
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-').map(Number);

                // Calcular mes anterior
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

                    // Mapear los montos encontrados a la estructura actual
                    let copiasRealizadas = 0;
                    this.departamentos.forEach(dptoActual => {
                        const dptoPrevio = data.departamentos.find(d => String(d.ID_Dpto) === String(dptoActual.ID_Dpto));
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

                this.departamentos.forEach(dpto => {
                    dpto.grupos.forEach(grupo => {
                        gruposParaGuardar.push({
                            id_dpto: dpto.ID_Dpto,
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
            departamentos: [],

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
                this.departamentos = [];
                this.mensaje = '';
            },

            async cargarEstructura() {
                if (!this.idPlace || !this.mesAnio) {
                    this.resetEstructura();
                    return;
                }

                const [anio, mes] = this.mesAnio.split('-');
                this.cargando = true;
                this.departamentos = [];
                this.mensaje = '';

                try {
                    const res = await fetch(`${BASE_URL}api/saldos-bancarios/estructura/${this.idPlace}/${anio}/${parseInt(mes)}`);

                    if (res.ok) {
                        const data = await res.json();
                        this.departamentos = data.departamentos || [];
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

                const notif = mostrarNotificacion('Obteniendo saldos del mes anterior...', 'info', 0);

                try {
                    const res = await fetch(`${BASE_URL}api/saldos-bancarios/estructura/${this.idPlace}/${prevAnio}/${prevMes}`);
                    const data = await res.json();

                    if (!data.departamentos || data.departamentos.length === 0) {
                        mostrarNotificacion('No se encontraron saldos en el mes anterior.', 'alert');
                        return;
                    }

                    let copiasRealizadas = 0;
                    this.departamentos.forEach(dptoActual => {
                        const dptoPrevio = data.departamentos.find(d => String(d.ID_Dpto) === String(dptoActual.ID_Dpto));
                        if (dptoPrevio && dptoPrevio.bancos) {
                            dptoActual.bancos.forEach(bancoActual => {
                                const bancoPrevio = dptoPrevio.bancos.find(b => String(b.ID_BancoDpto) === String(bancoActual.ID_BancoDpto));
                                
                                // Copiamos Saldo Inicial del mes previo al Saldo Inicial del mes actual
                                // Validamos que el valor exista (incluso si es 0)
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
                if (this.departamentos.length === 0) return;

                const [anio, mes] = this.mesAnio.split('-');
                this.guardando = true;
                this.mensaje = '';
                this.error = false;

                let saldosParaEnviar = [];

                this.departamentos.forEach(dpto => {
                    dpto.bancos.forEach(banco => {
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
            mesAnio: '',

            razonesSociales: [],
            todosPlaces: [],
            departamentos: [],
            departamentosBancos: [], // Nuevo para reporte de bancos
            departamentosOriginales: [],
            dptosSeleccionados: [],
            choicesDpto: null,
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
                const anio = now.getFullYear();
                const mes = String(now.getMonth() + 1).padStart(2, '0');
                this.mesAnio = `${anio}-${mes}`;
            },

            irAPantalla(nueva) {
                this.pantalla = nueva;
                this.departamentos = [];
                this.departamentosBancos = [];
                this.departamentosOriginales = [];
                this.dptosSeleccionados = [];
                this.verGlobal = false;
                if (this.choicesDpto) {
                    this.choicesDpto.destroy();
                    this.choicesDpto = null;
                }
                this.idPlace = '';
                this.idRazonSocial = '';
            },

            get placesFiltrados() {
                if (!this.idRazonSocial) return [];
                return this.todosPlaces.filter(p => String(p.ID_RazonSocial) === String(this.idRazonSocial));
            },

            get departamentosAgrupados() {
                const fuente = this.pantalla === 'cuentas' ? this.departamentosBancos : this.departamentos;
                const grupos = [];
                fuente.forEach(d => {
                    const rsNombre = d.RazonSocialNombre || 'Sin Razón Social';
                    let grupo = grupos.find(g => g.nombre === rsNombre);
                    if (!grupo) {
                        grupo = { 
                            nombre: rsNombre, 
                            departamentos: [],
                            totales: this.pantalla === 'cuentas' 
                                ? { inicial: 0, final: 0, usado: 0, porcentaje: 0 }
                                : { asignado: 0, comprometido: 0, ejecutado: 0, disponible: 0, porcentaje: 0 }
                        };
                        grupos.push(grupo);
                    }
                    grupo.departamentos.push(d);
                    
                    if (this.pantalla === 'cuentas') {
                        grupo.totales.inicial += parseFloat(d.totales?.inicial || 0);
                        grupo.totales.final += parseFloat(d.totales?.final || 0);
                        grupo.totales.usado += parseFloat(d.totales?.usado || 0);
                    } else {
                        grupo.totales.asignado += parseFloat(d.totales?.asignado || 0);
                        grupo.totales.comprometido += parseFloat(d.totales?.comprometido || 0);
                        grupo.totales.ejecutado += parseFloat(d.totales?.ejecutado || 0);
                    }
                });

                grupos.forEach(g => {
                    if (this.pantalla === 'cuentas') {
                        g.totales.porcentaje = g.totales.inicial > 0 ? Math.round((g.totales.usado / g.totales.inicial) * 100 * 100) / 100 : 0;
                    } else {
                        const totalGasto = g.totales.comprometido + g.totales.ejecutado;
                        g.totales.disponible = g.totales.asignado - totalGasto;
                        g.totales.porcentaje = g.totales.asignado > 0 ? Math.round((totalGasto / g.totales.asignado) * 100 * 100) / 100 : 0;
                    }
                });

                return grupos;
            },

            async cargarComparativo() {
                if (this.pantalla === 'cuentas') return this.cargarComparativoBancos();
                if (!this.verGlobal && (!this.idPlace || !this.mesAnio)) return;
                // ... resto de lógica de presupuesto igual ...
            },

            async cargarComparativoBancos() {
                if (!this.verGlobal && (!this.idPlace || !this.mesAnio)) return;

                const [anio, mes] = this.mesAnio.split('-');
                this.cargando = true;
                this.departamentosBancos = [];
                const targetPlaceId = this.verGlobal ? 0 : this.idPlace;

                try {
                    const res = await fetch(`${BASE_URL}api/bancos/comparativo/${targetPlaceId}/${anio}/${parseInt(mes)}`);
                    if (res.ok) {
                        const data = await res.json();
                        this.departamentosBancos = data.departamentos || [];
                    }
                } catch (e) { console.error(e); }
                finally { this.cargando = false; }
            },

            async cargarGlobal() {
                // Al cambiar el modo global, reseteamos selecciones locales
                this.idRazonSocial = '';
                this.idPlace = '';
                this.departamentos = [];
                this.departamentosOriginales = [];
                
                if (this.choicesDpto) {
                    this.choicesDpto.destroy();
                    this.choicesDpto = null;
                }

                // Cargamos datos (si verGlobal es true mandará 0, si es false no hará nada hasta elegir RS/Place)
                await this.cargarComparativo();
            },

            initChoicesDpto() {
                if (this.choicesDpto) this.choicesDpto.destroy();
                
                const selectEl = this.$refs.filtroDptos;
                if (!selectEl) return;

                this.choicesDpto = new Choices(selectEl, {
                    removeItemButton: true,
                    itemSelectText: '',
                    placeholderValue: 'Todos los departamentos',
                    searchPlaceholderValue: 'Buscar departamento...'
                });

                selectEl.addEventListener('change', () => {
                    this.dptosSeleccionados = this.choicesDpto.getValue(true).map(String);
                    this.aplicarFiltroLocal();
                });
            },

            aplicarFiltroLocal() {
                if (this.dptosSeleccionados.length === 0) {
                    this.departamentos = [...this.departamentosOriginales];
                } else {
                    this.departamentos = this.departamentosOriginales.filter(d => 
                        this.dptosSeleccionados.includes(String(d.ID_Dpto))
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
                    asignado,
                    comprometido,
                    ejecutado,
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
 * Lógica para el CRUD de cuentas de Grupos Presupuestales
 */
function initCrudGrupos() {
    const tabla = document.getElementById('tabla-grupos')
    if (!tabla) return

    initGruposTabla()
    initGruposPantallas()
    initGruposForm()
    initGruposEditarForm()
    initGruposActions(tabla)
}

function initGruposTabla() {
    setupClientSideTable({
        rowsSelector: '#tabla-grupos tr[data-id]',
        paginationSelector: 'paginacion-grupos',
        filterFormSelector: '#form-filtros-grupos',
        filterFunction: (row, form) => {
            const nombreFiltro = (document.getElementById('buscar-nombre-grupo')?.value || '').toLowerCase()
            const descFiltro = (document.getElementById('buscar-descripcion-grupo')?.value || '').toLowerCase()

            const nombre = row.querySelector('.nombre-grupo')?.textContent.toLowerCase() || ''
            const descripcion = row.querySelector('.descripcion-grupo')?.textContent.toLowerCase() || ''

            return nombre.includes(nombreFiltro) && descripcion.includes(descFiltro)
        },
        rowsPerPage: 10,
    })
}

function initGruposPantallas() {
    const pantallaAgregar = document.getElementById('pantalla-agregar-grupos')
    const pantallaEditar = document.getElementById('pantalla-editar-grupos')
    const pantallaLista = document.getElementById('pantalla-lista-grupos')

    const btnAgregar = document.getElementById('btn-agregar-grupos')
    const btnRegresarAgregar = document.getElementById('btn-regresar-lista-grupos')
    const btnRegresarEditar = document.getElementById('btn-regresar-lista-editar-grupos')

    if (btnAgregar)
        btnAgregar.onclick = (e) => {
            e.preventDefault()
            pantallaLista?.classList.add('hidden')
            pantallaAgregar?.classList.remove('hidden')
        }

    if (btnRegresarAgregar)
        btnRegresarAgregar.onclick = (e) => {
            e.preventDefault()
            pantallaAgregar?.classList.add('hidden')
            pantallaLista?.classList.remove('hidden')
        }

    if (btnRegresarEditar)
        btnRegresarEditar.onclick = (e) => {
            e.preventDefault()
            pantallaEditar?.classList.add('hidden')
            pantallaLista?.classList.remove('hidden')
        }
}

function initGruposForm() {
    const formAgregar = document.getElementById('form-agregar-grupos')
    const pantallaAgregar = document.getElementById('pantalla-agregar-grupos')
    const pantallaLista = document.getElementById('pantalla-lista-grupos')
    if (!formAgregar) return

    formAgregar.onsubmit = async (e) => {
        e.preventDefault()
        const formData = new FormData(formAgregar)

        try {
            const result = await SendDataEnd('modales/crud_grupos_presupuestales/insertar', {
                method: 'POST',
                body: formData,
            })

            if (result.success) {
                mostrarNotificacion('Grupo agregado correctamente ✅', 'success')
                pantallaAgregar?.classList.add('hidden')
                pantallaLista?.classList.remove('hidden')
                formAgregar.reset()
                // Recargamos el modal para ver los cambios
                abrirModal('GrupoPresupuestal')
            } else {
                mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
            }
        } catch {
            mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
        }
    }
}

function initGruposEditarForm() {
    const formEditar = document.getElementById('form-editar-grupos')
    const pantallaEditar = document.getElementById('pantalla-editar-grupos')
    const pantallaLista = document.getElementById('pantalla-lista-grupos')
    const tabla = document.getElementById('tabla-grupos')
    if (!formEditar) return

    formEditar.onsubmit = async (e) => {
        e.preventDefault()
        const formData = new FormData(formEditar)
        const id = formData.get('ID_GrupoPresupuestal')

        try {
            const result = await SendDataEnd(`modales/crud_grupos_presupuestales/editar/${id}`, {
                method: 'POST',
                body: formData,
            })

            if (result.success) {
                mostrarNotificacion('Grupo actualizado correctamente ✅', 'success')

                const fila = tabla.querySelector(`tr[data-id='${id}']`)
                if (fila) {
                    // Actualizar textos básicos
                    fila.querySelector('.nombre-grupo').textContent = formData.get('Nombre')
                    fila.querySelector('.descripcion-grupo').textContent = formData.get('Descripcion')

                    // Actualizar el Departamento
                    const selectDpto = document.getElementById('editar-ID_Dpto');
                    const dptoTexto = selectDpto.options[selectDpto.selectedIndex].text;
                    fila.querySelector('.departamento-grupo').textContent = selectDpto.value ? dptoTexto : 'N/A';

                    // Actualizar Datasets
                    fila.dataset.nombre = formData.get('Nombre')
                    fila.dataset.descripcion = formData.get('Descripcion')
                    fila.dataset.id_dpto = formData.get('ID_Dpto')
                }

                pantallaEditar?.classList.add('hidden')
                pantallaLista?.classList.remove('hidden')
            } else {
                mostrarNotificacion(result.message || 'Error al actualizar ❌', 'error')
            }
        } catch {
            mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
        }
    }
}

function initGruposActions(tabla) {
    if (!tabla) return

    tabla.addEventListener('click', async (e) => {
        // --- ELIMINAR ---
        const btnEliminar = e.target.closest("[id^='btn-eliminar-grupos-']")
        if (btnEliminar) {
            e.preventDefault()
            const id = btnEliminar.dataset.id

            if (
                !(await Confirmar(
                    'Eliminar Grupo?',
                    '¿Seguro que deseas eliminar este grupo presupuestal?',
                ))
            )
                return

            SendDataEnd(`modales/crud_grupos_presupuestales/eliminar/${id}`, {
                method: 'POST',
            })
                .then((result) => {
                    if (result.success) {
                        mostrarNotificacion('Grupo eliminado ✅', 'success')
                        btnEliminar.closest('tr')?.remove()
                        abrirModal('GrupoPresupuestal')
                    } else {
                        mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
                    }
                })
                .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
            return
        }

        // --- EDITAR ---
        const btnEditar = e.target.closest("[id^='btn-editar-grupos-']")
        if (!btnEditar) return
        e.preventDefault()

        const fila = btnEditar.closest('tr')
        if (!fila) return

        document.getElementById('editar-ID_GrupoPresupuestal').value = fila.dataset.id
        document.getElementById('editar-Nombre').value = fila.dataset.nombre
        document.getElementById('editar-Descripcion').value = fila.dataset.descripcion
        document.getElementById('editar-ID_Dpto').value = fila.dataset.id_dpto || "";

        document.getElementById('pantalla-lista-grupos').classList.add('hidden')
        document.getElementById('pantalla-editar-grupos').classList.remove('hidden')
    })
}


/**
 * Lógica para el CRUD de cuentas Bancos Dpto
 */
function initCrudBancoDpto() {
    const tabla = document.getElementById('tabla-banco-dpto')
    if (!tabla) return

    initBancoDptoTabla()
    initBancoDptoPantallas()
    initBancoDptoForm()
    initBancoDptoEditarForm()
    initBancoDptoActions(tabla)
}

function initBancoDptoTabla() {
    setupClientSideTable({
        rowsSelector: '#tabla-banco-dpto tr[data-id]',
        paginationSelector: 'paginacion-banco-dpto',
        filterFormSelector: '#form-filtros-banco-dpto',
        filterFunction: (row, form) => {
            const dptoFiltro = (document.getElementById('buscar-dpto')?.value || '').toLowerCase()
            const bancoFiltro = (document.getElementById('buscar-banco')?.value || '').toLowerCase()

            const dpto = row.querySelector('.nombre-dpto')?.textContent.toLowerCase() || ''
            const banco = row.querySelector('.nombre-banco')?.textContent.toLowerCase() || ''

            return dpto.includes(dptoFiltro) && banco.includes(bancoFiltro)
        },
        rowsPerPage: 10,
    })
}

function initBancoDptoPantallas() {
    const pantallaAgregar = document.getElementById('pantalla-agregar-banco-dpto')
    const pantallaEditar = document.getElementById('pantalla-editar-banco-dpto')
    const pantallaLista = document.getElementById('pantalla-lista-banco-dpto')

    const btnAgregar = document.getElementById('btn-agregar-banco-dpto')
    const btnRegresarAgregar = document.getElementById('btn-regresar-lista-banco-dpto')
    const btnRegresarEditar = document.getElementById('btn-regresar-lista-editar-banco-dpto')

    if (btnAgregar)
        btnAgregar.onclick = (e) => {
            e.preventDefault()
            pantallaLista?.classList.add('hidden')
            pantallaAgregar?.classList.remove('hidden')
        }

    if (btnRegresarAgregar)
        btnRegresarAgregar.onclick = (e) => {
            e.preventDefault()
            pantallaAgregar?.classList.add('hidden')
            pantallaLista?.classList.remove('hidden')
        }

    if (btnRegresarEditar)
        btnRegresarEditar.onclick = (e) => {
            e.preventDefault()
            pantallaEditar?.classList.add('hidden')
            pantallaLista?.classList.remove('hidden')
        }
}

function initBancoDptoForm() {
    const formAgregar = document.getElementById('form-agregar-banco-dpto')
    const pantallaAgregar = document.getElementById('pantalla-agregar-banco-dpto')
    const pantallaLista = document.getElementById('pantalla-lista-banco-dpto')
    if (!formAgregar) return

    formAgregar.onsubmit = async (e) => {
        e.preventDefault()
        const formData = new FormData(formAgregar)

        try {
            const result = await SendDataEnd('modales/crud_banco_dpto/insertar', {
                method: 'POST',
                body: formData,
            })

            if (result.success) {
                mostrarNotificacion('Banco agregado correctamente ✅', 'success')
                pantallaAgregar?.classList.add('hidden')
                pantallaLista?.classList.remove('hidden')
                formAgregar.reset()
                // Recargamos el modal para ver los cambios
                abrirModal('BancoDpto')
            } else {
                mostrarNotificacion(result.message || 'Error al guardar ❌', 'error')
            }
        } catch {
            mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
        }
    }
}

function initBancoDptoEditarForm() {
    const formEditar = document.getElementById('form-editar-banco-dpto')
    const pantallaEditar = document.getElementById('pantalla-editar-banco-dpto')
    const pantallaLista = document.getElementById('pantalla-lista-banco-dpto')
    const tabla = document.getElementById('tabla-banco-dpto')
    if (!formEditar) return

    formEditar.onsubmit = async (e) => {
        e.preventDefault()
        const formData = new FormData(formEditar)
        const id = formData.get('ID_BancoDpto')

        try {
            const result = await SendDataEnd(`modales/crud_banco_dpto/editar/${id}`, {
                method: 'POST',
                body: formData,
            })

            if (result.success) {
                mostrarNotificacion('Banco actualizado correctamente ✅', 'success')

                const fila = tabla.querySelector(`tr[data-id='${id}']`)
                if (fila) {
                    // Actualizar textos visuales
                    fila.querySelector('.nombre-banco').textContent = formData.get('Banco')
                    fila.querySelector('.clabe-banco').textContent = formData.get('Clabe')

                    // Actualizar Nombre Dpto visualmente desde el select
                    const selectDpto = document.getElementById('editar-ID_Dpto');
                    const dptoTexto = selectDpto.options[selectDpto.selectedIndex].text;
                    fila.querySelector('.nombre-dpto').textContent = dptoTexto;

                    // Actualizar Datasets
                    fila.dataset.banco = formData.get('Banco')
                    fila.dataset.clabe = formData.get('Clabe')
                    fila.dataset.idDpto = formData.get('ID_Dpto')
                }

                pantallaEditar?.classList.add('hidden')
                pantallaLista?.classList.remove('hidden')
            } else {
                mostrarNotificacion(result.message || 'Error al actualizar ❌', 'error')
            }
        } catch {
            mostrarNotificacion('Error de conexión con el servidor ❌', 'error')
        }
    }
}

function initBancoDptoActions(tabla) {
    if (!tabla) return

    tabla.addEventListener('click', async (e) => {
        // --- ELIMINAR ---
        const btnEliminar = e.target.closest("[id^='btn-eliminar-banco-dpto-']")
        if (btnEliminar) {
            e.preventDefault()
            const id = btnEliminar.dataset.id

            if (
                !(await Confirmar(
                    'Eliminar Banco?',
                    '¿Seguro que deseas eliminar este registro?',
                ))
            )
                return

            SendDataEnd(`modales/crud_banco_dpto/eliminar/${id}`, {
                method: 'POST',
            })
                .then((result) => {
                    if (result.success) {
                        mostrarNotificacion('Registro eliminado ✅', 'success')
                        btnEliminar.closest('tr')?.remove()
                    } else {
                        mostrarNotificacion(result.message || 'No se pudo eliminar ❌', 'error')
                    }
                })
                .catch(() => mostrarNotificacion('Error de conexión ❌', 'error'))
            return
        }

        // --- EDITAR ---
        const btnEditar = e.target.closest("[id^='btn-editar-banco-dpto-']")
        if (!btnEditar) return
        e.preventDefault()

        const fila = btnEditar.closest('tr')
        if (!fila) return

        // Cargar datos al formulario
        document.getElementById('editar-ID_BancoDpto').value = fila.dataset.id
        document.getElementById('editar-Banco').value = fila.dataset.banco
        document.getElementById('editar-Clabe').value = fila.dataset.clabe
        document.getElementById('editar-ID_Dpto').value = fila.dataset.idDpto

        // Cambiar de pantalla
        document.getElementById('pantalla-lista-banco-dpto').classList.add('hidden')
        document.getElementById('pantalla-editar-banco-dpto').classList.remove('hidden')
    })
}