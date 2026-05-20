/**
 * -ESTE MODULO YA HA SIDO OPTIMIZADO Y DEPURADO-
 */


let choicesDeptoMaestro = null;
let choicesRazonMaestro = null;

function initControlMaestro() {
    const tabla = document.getElementById('tabla-maestro');
    if (!tabla) return;

    // Cache de elementos DOM para evitar búsquedas repetitivas y mejorar el rendimiento
    const filtroDeptoEl = document.getElementById('filtroDepartamentoMaestro');
    const filtroRazonEl = document.getElementById('filtro-razon-social-maestro');
    const wrapperDepto = document.getElementById('wrapper-depto-maestro');
    const inputFolio = document.getElementById('filtro-folio-maestro');
    const inputFecha = document.getElementById('filtro-fecha-maestro');
    const checkMes = document.getElementById('filtrar-por-mes-maestro');
    const selectEstado = document.getElementById('filtro-estado-maestro');
    const selectMetodo = document.getElementById('filtro-metodo-maestro');
    const selectTipo = document.getElementById('filtro-tipo-maestro');

    // Destruir instancias previas para evitar duplicados al reabrir el modal (gestión de memoria)
    if (choicesDeptoMaestro) { choicesDeptoMaestro.destroy(); choicesDeptoMaestro = null; }
    if (choicesRazonMaestro) { choicesRazonMaestro.destroy(); choicesRazonMaestro = null; }

    // 1. Configurar Selectores Avanzados (Choices.js)
    if (filtroDeptoEl) {
        choicesDeptoMaestro = new Choices(filtroDeptoEl, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Todos los departamentos',
            itemSelectText: '',
            noResultsText: 'No se encontraron resultados',
        });
        if (wrapperDepto) wrapperDepto.classList.remove('hidden');
    }

    if (filtroRazonEl) {
        choicesRazonMaestro = new Choices(filtroRazonEl, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Todas las razones sociales',
            itemSelectText: '',
            noResultsText: 'No se encontraron resultados',
        });
    }

    // --- Lógica de dependencia: Razón Social -> Departamento ---
    if (choicesRazonMaestro && choicesDeptoMaestro && filtroDeptoEl) {
        // Almacenamos las opciones originales para filtrar sin perder datos
        const originalDeptoOptions = Array.from(filtroDeptoEl.options).map(opt => ({
            value: opt.value,
            label: opt.text,
            razon: opt.dataset.razon
        }));

        filtroRazonEl.addEventListener('change', () => {
            const selectedRazones = choicesRazonMaestro.getValue().map(item => {
                const opt = Array.from(filtroRazonEl.options).find(o => o.value === item.value);
                return opt ? opt.dataset.idRazon : null;
            }).filter(id => id);

            const filteredOptions = selectedRazones.length === 0 
                ? originalDeptoOptions 
                : originalDeptoOptions.filter(opt => !opt.razon || selectedRazones.includes(opt.razon));

            choicesDeptoMaestro.clearStore();
            choicesDeptoMaestro.setChoices(filteredOptions, 'value', 'label', true);
        });
    }

    // 2. Construcción de Endpoint con Cache Buster
    let urlEndpoint = 'api/historic';
    const DEPT_EXCEPTIONS = ['Compras', 'Administración', 'Direccion', 'Tesoreria', 'Direccion Campus', 'Contaduría'];
    
    // Si el usuario no pertenece a departamentos administrativos, filtramos por su departamento
    if (typeof USER_DEPT_NAME !== 'undefined' && USER_DEPT_ID && !DEPT_EXCEPTIONS.includes(USER_DEPT_NAME)) {
        urlEndpoint = `api/historic/department/${USER_DEPT_ID}`;
    }

    const finalEndpoint = `${urlEndpoint}${urlEndpoint.includes('?') ? '&' : '?'}_t=${Date.now()}`;

    // 3. Inicializar Tabla con Paginación y Filtrado
    createPaginatedTable({
        tableSelector: '#tabla-maestro tbody',
        paginationSelector: 'paginacion-maestro',
        endpoint: finalEndpoint,
        filterFormSelector: '#filtros-maestro-container',

        renderRow: (item) => {
            // Helpers de utils.js para formateo de estados y estilos
            const status = typeof getStatusText === 'function' ? getStatusText(item.Estado) : item.Estado;
            const svg = typeof getStatusSVG === 'function' ? getStatusSVG(item.Estado) : '';
            const metodo = typeof getMetodoPago === 'function' ? getMetodoPago(item.MetodoPago) : item.MetodoPago;

            const totalRaw = parseFloat(item.MontoTotal) || 0;
            const montoFormateado = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(totalRaw);

            const faltaProv = (!item.ProveedorNombre || item.ProveedorNombre === 'N/A' || item.ProveedorNombre === 'null');
            const claseMonto = (totalRaw === 0 && !faltaProv) ? 'text-gray-400' : 'text-gray-800';

            return `
            <tr class="text-center hover:bg-gray-50 transition border-b border-gray-100">
                <td class="hidden border-r px-4 py-2">${item.ID_Solicitud}</td>
                <td class="border-r px-4 py-2 font-medium text-gray-800">${item.No_Folio || 'N/A'}</td>
                <td class="border-r px-4 py-2 text-sm text-gray-600">${item.Fecha}</td>
                <td class="border-r px-4 py-2 text-xs text-gray-500">${item.DepartamentoNombre || ''}</td>
                <td class="border-r px-4 py-2 text-sm text-left px-4">${item.ProveedorNombre || 'N/A'}</td>
                <td class="border-r px-4 py-2 font-bold ${claseMonto}">${totalRaw > 0 ? montoFormateado : '$0.00'}</td>
                <td class="border-r px-4 py-2" title="${status}">
                    <div class="flex flex-col items-center">
                        ${svg}
                        <span class="text-[10px] uppercase font-bold text-gray-600 mt-1">${status}</span>
                    </div>
                </td>
                <td class="border-r px-4 py-2 text-xs text-gray-600">${metodo}</td>
                <td class="px-4 py-2">
                    <button class="bg-white border border-red-200 text-red-600 hover:bg-red-50 font-bold text-xs py-1 px-3 rounded transition shadow-sm" 
                            onclick="cargarEditorMaestro(${item.ID_Solicitud}, '${item.No_Folio}')">
                        ✏️ EDITAR
                    </button>
                </td>
            </tr>`;
        },

        filterFunction: (allData) => {
            // OPTIMIZACIÓN: Capturamos valores del DOM una sola vez antes de iterar
            const folioFiltro = inputFolio?.value.toLowerCase().trim() || '';
            const fechaFiltro = inputFecha?.value || '';
            const filtrarPorMes = checkMes?.checked || false;
            const estadoFiltro = selectEstado?.value || '';
            const metodoFiltro = selectMetodo?.value || '';
            const tipoFiltro = selectTipo?.value || '';
            
            const deptosSeleccionados = choicesDeptoMaestro ? choicesDeptoMaestro.getValue(true) : [];
            const razonesSeleccionadas = choicesRazonMaestro ? choicesRazonMaestro.getValue(true) : [];

            return allData.filter((item) => {
                const coincideFolio = !folioFiltro || (item.No_Folio && item.No_Folio.toLowerCase().includes(folioFiltro));
                const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro;
                const coincideMetodo = !metodoFiltro || item.MetodoPago == metodoFiltro;
                
                const coincideTipo = !tipoFiltro || 
                    (tipoFiltro === 'Producto' && (item.Tipo == 0 || item.Tipo == 1)) || 
                    (tipoFiltro === 'Servicio' && item.Tipo == 2);

                let coincideDepto = true;
                if (deptosSeleccionados.length > 0) {
                    const deptoFull = `${item.DepartamentoNombre}|${item.PlaceNombre || ''}`;
                    coincideDepto = deptosSeleccionados.includes(deptoFull);
                }

                let coincideRazon = true;
                if (razonesSeleccionadas.length > 0) {
                    coincideRazon = razonesSeleccionadas.includes(item.Complejo);
                }

                const passesOtherFilters = coincideFolio && coincideEstado && coincideDepto && coincideMetodo && coincideTipo && coincideRazon;

                if (!fechaFiltro) return passesOtherFilters;
                
                // Comparación de fechas optimizada
                if (filtrarPorMes) {
                    return item.Fecha.startsWith(fechaFiltro.slice(0, 7)) && passesOtherFilters;
                }
                return item.Fecha === fechaFiltro && passesOtherFilters;
            });
        }
    });
}

/**
 * CARGA DE DATOS
 */

async function cargarEditorMaestro(idSolicitud, folio) {
    // Referencias a contenedores principales
    const divControl = document.getElementById('div-control-maestro');
    const divEditor = document.getElementById('div-editor-maestro');
    const tituloEditor = document.getElementById('titulo-editor');
    const container = document.getElementById('contenido-editor-maestro');

    // Cambiar vista y resetear scroll para mejorar UX
    if (divControl) divControl.classList.add('hidden');
    if (divEditor) divEditor.classList.remove('hidden');
    if (tituloEditor) tituloEditor.innerText = `Editando: ${folio || 'ID ' + idSolicitud}`;
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Estado de carga inicial
    container.innerHTML = `
        <div class="flex flex-col items-center justify-center py-12">
            <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-4"></div>
            <p class="text-gray-500 font-medium italic">Cargando información técnica y archivos...</p>
        </div>`;

    try {
        // Ejecución concurrente de peticiones API
        // Se usa .catch en peticiones secundarias para que un fallo no crítico no detenga la carga
        const [dataSolicitud, dataOrden, dataProveedores, dataRazones] = await Promise.all([
            SendDataEnd(`api/solicitud/details/${idSolicitud}`),
            SendDataEnd(`api/orden-compra/details/${idSolicitud}`).catch(err => {
                console.warn("No se encontró orden de compra:", err);
                return null;
            }),
            SendDataEnd(`api/providers/all`).catch(() => ({ data: [] })),
            SendDataEnd(`api/razonsocial/all`).catch(() => ({ data: [] }))
        ]);

        if (dataSolicitud.error) throw new Error(dataSolicitud.error);

        // Aplanado y normalización de la Orden de Compra
        let ordenObjeto = null;
        if (dataOrden && !dataOrden.error) {
            // Manejamos las diferentes estructuras que puede devolver el endpoint de CI4
            if (Array.isArray(dataOrden)) {
                ordenObjeto = dataOrden[0] || null;
            } else if (dataOrden.OrdenCompra) {
                ordenObjeto = Array.isArray(dataOrden.OrdenCompra) ? dataOrden.OrdenCompra[0] : dataOrden.OrdenCompra;
            } else {
                ordenObjeto = dataOrden;
            }
        }

        // Integrar datos de la orden en la solicitud principal para el renderizador
        if (ordenObjeto) {
            dataSolicitud.OrdenCompra = ordenObjeto;
            if (ordenObjeto.cotizacion) dataSolicitud.cotizacion = ordenObjeto.cotizacion;
        }

        // Helper interno para normalizar respuestas de la librería Rest.php
        const normalize = (d) => {
            if (Array.isArray(d)) return d;
            return d?.data || d?.messages || [];
        };

        const listaProveedores = normalize(dataProveedores);
        const listaRazones = normalize(dataRazones);

        // Asignar ID de solicitud al campo oculto del formulario
        const inputId = document.getElementById('maestro_id_solicitud');
        if (inputId) inputId.value = idSolicitud;

        // Disparar el renderizado del formulario (Bloque 3)
        renderizarInputsDios(dataSolicitud, container, listaProveedores, listaRazones);

    } catch (error) {
        console.error("Error en cargarEditorMaestro:", error);
        container.innerHTML = `
            <div class="text-center py-10 bg-red-50 rounded-lg border border-red-200 shadow-sm mx-auto max-w-lg">
                <div class="text-red-500 text-4xl mb-3">⚠️</div>
                <p class="font-bold text-red-800 text-lg">Error al cargar datos</p>
                <p class="text-sm text-red-600 mb-6">${error.message}</p>
                <button onclick="regresarMaestro()" class="px-6 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition shadow-sm font-semibold">
                    Volver al Historial
                </button>
            </div>`;
    }
}

/**
 * RENDERIZADO DEL FORMULARIO
 */

/**
 * RENDERIZADO DEL FORMULARIO (MODO DIOS)
 * Refactorizado con enfoque en Interface Design: Autoridad, Precisión y Trazabilidad.
 */

function renderizarInputsDios(data, container, listaProveedores = [], listaRazones = []) {
    const sol = data.solicitud || data;
    const orden = data.OrdenCompra || {};
    const coti = data.cotizacion || {};

    // 1. LÓGICA DE ESTADOS Y BLOQUEOS
    let estadoVisual = sol.Estado;
    if (sol.Estado === 'Aprobada' && orden?.Estado) {
        estadoVisual = orden.Estado;
    }

    const REGLAS_BLOQUEO = {
        'En espera':            { financiero: false, control: false },
        'Cotizando':            { financiero: false, control: false },
        'En revision':          { financiero: false, control: false },
        'Aprobacion pendiente': { financiero: false, control: false },
        'Aprobada':             { financiero: false, control: false },
        'Espera_Programacion':  { financiero: false, control: false },
        'Programada':           { financiero: false, control: true },
        'Por Pagar':            { financiero: false, control: true },
        'Pagada':               { financiero: true,  control: true }
    };

    const reglas = REGLAS_BLOQUEO[estadoVisual] || { financiero: true, control: true };
    
    // Configuración de Tokens de Diseño (Interface Design)
    const configUI = {
        disabledFinanciero: reglas.financiero ? 'disabled' : '',
        classFinanciero: reglas.financiero ? 'bg-[#F3F4F6] text-[#6B7280] cursor-not-allowed' : 'bg-white',
        disabledControl: reglas.control ? 'disabled' : '',
        classControl: reglas.control ? 'bg-[#F3F4F6] text-[#6B7280] cursor-not-allowed' : 'bg-white',
        classArchivos: reglas.financiero ? 'hidden' : 'grid',
        // Typography & Spacing
        baseInput: "mt-1 block w-full border border-[#D1D5DB] rounded-sm shadow-sm py-2 px-3 focus:ring-1 focus:ring-[#1E3A8A] focus:border-[#1E3A8A] sm:text-xs text-[#374151] transition-all",
        label: "block text-[10px] font-bold text-[#4B5563] mb-1 uppercase tracking-widest",
        section: "bg-white border border-[#E5E7EB] rounded-sm p-6 mb-6",
        // Semantic Colors
        inkBlue: "#1E3A8A",
        carbonGray: "#4B5563",
        stampRed: "#991B1B",
        auditGreen: "#065F46"
    };

    // 2. PRE-PROCESAMIENTO DE CATÁLOGOS
    const idProveedorActual = sol.ID_Proveedor || data.proveedor?.ID_Proveedor;
    const htmlProv = listaProveedores.map(p => {
        const selected = (p.ID_Proveedor == idProveedorActual) ? 'selected' : '';
        const dias = parseFloat(p.Dias_Credito || p.dias_credito || 0);
        return `<option value="${p.ID_Proveedor}" data-credito="${dias > 0 ? '1' : '0'}" ${selected}>${p.RazonSocial}</option>`;
    }).join('');

    const htmlRazon = listaRazones.map(r => {
        const selected = (r.ID_RazonSocial == sol.ID_RazonSocial) ? 'selected' : '';
        return `<option value="${r.ID_RazonSocial}" ${selected}>${r.Nombre} (${r.RFC || ''})</option>`;
    }).join('');

    let optionsGruposHtml = '<option value="">-- Sin partida asignada --</option>';
    if (data.grupos_presupuestales) {
        const gruposFiltrados = data.grupos_presupuestales;
        optionsGruposHtml += gruposFiltrados.map(g => `<option value="${g.ID_GrupoPresupuestal}">${g.Nombre}</option>`).join('');
    }

    // 3. ENSAMBLAJE DE INTERFAZ
    let htmlContent = `
        ${_renderBannerImpacto(configUI)}
        ${_renderSeccionControl(sol, orden, estadoVisual, configUI)}
        ${_renderSeccionGeneral(sol, htmlProv, htmlRazon, configUI)}
        ${_renderAsistenteLlenado(data, sol, configUI)}
        ${_renderTablaProductos(data, sol, optionsGruposHtml, configUI)}
        ${_renderSeccionArchivos(data, sol, orden, coti, configUI)}
        
        <input type="hidden" id="original-id-proveedor" value="${idProveedorActual}">
        <input type="hidden" id="flag-existe-cotizacion" value="${(coti.Cotizacion_Files || data.Cotizacion_Files) ? '1' : '0'}">
        <input type="hidden" id="flag-existe-ficha" value="${(orden.File_Comprobante || data.File_Comprobante) ? '1' : '0'}">
        <input type="hidden" id="flag-existe-factura" value="${(orden.File_Factura || data.File_Factura) ? '1' : '0'}">
        <input type="hidden" id="flag-existe-complemento" value="${(orden.File_Complemento || data.File_Complemento) ? '1' : '0'}">
    `;

    container.innerHTML = htmlContent;

    calcularTotalesUI();
    setTimeout(() => { if (window.validarCreditoProveedor) window.validarCreditoProveedor(); }, 100);
}

function _renderBannerImpacto(ui) {
    return `
    <div id="advertencia-presupuesto-maestro" class="hidden mb-6 p-4 bg-[#FEF2F2] border-l-4 border-[#B91C1C] rounded-r-sm shadow-sm">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-[#B91C1C]" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <p class="font-bold text-xs text-[#991B1B] uppercase tracking-wider">Compromiso Presupuestal Detectado</p>
        </div>
        <p class="text-[11px] mt-2 ml-8 text-[#7F1D1D] leading-relaxed">Este cambio afectará automáticamente el balance mensual del departamento. Se generará un registro en la bitácora de auditoría financiera.</p>
    </div>`;
}

function _renderSeccionControl(sol, orden, estadoVisual, ui) {
    const existeOrden = !!(orden && orden.Estado);
    const disabledFechas = (!existeOrden || ui.disabledControl) ? 'disabled' : '';
    
    // Capas sutiles: Inset para inputs deshabilitados
    const classRefPago = (!existeOrden || ui.disabledControl) ? 'bg-[#F9FAFB]' : 'bg-[#EFF6FF] border-[#BFDBFE]';
    const classPagoReal = (!existeOrden || ui.disabledControl) ? 'bg-[#F9FAFB]' : 'bg-[#ECFDF5] border-[#A7F3D0]';

    const fReg = sol.Fecha ? sol.Fecha.split(' ')[0] : 'N/A';
    const fApro = (orden.Fecha || sol.FechaOrden) ? (orden.Fecha || sol.FechaOrden).split(' ')[0] : 'SIN SELLO';
    const fRef = (orden.FechaRefPago || sol.FechaRefPago || '').split(' ')[0];
    const fPago = (orden.FechaPagoRealizado || sol.FechaPagoRealizado || '').split(' ')[0];

    return `
    <div class="${ui.section}">
        <div class="flex justify-between items-center border-b border-[#F3F4F6] pb-3 mb-5">
            <h4 class="text-xs font-bold text-[#111827] flex items-center gap-2">
                <span class="w-1.5 h-4 bg-[#1E3A8A] rounded-full"></span>
                CONTROL DE EXPEDIENTE
            </h4>
            ${ui.disabledFinanciero ? '<span class="text-[9px] bg-[#FFFBEB] text-[#92400E] px-2 py-1 rounded-sm border border-[#FEF3C7] font-bold tracking-tighter">🔒 BLOQUEO DE AUDITORÍA</span>' : ''}
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <div>
                <label class="${ui.label}">Estado Maestro</label>
                <input type="hidden" id="original-estado-maestro" value="${estadoVisual}">
                <select name="Estado" id="select-estado-maestro" onchange="window.verificarImpactoPresupuestal()" class="${ui.baseInput} bg-[#F8FAFC] border-[#1E3A8A] text-[#1E3A8A] font-bold">
                    <option value="${estadoVisual}" selected>➡ ${estadoVisual.toUpperCase()}</option>
                    <option value="En espera">EN ESPERA</option>
                    <option value="Aprobada">APROBADA</option>
                    <option value="Cotizando">COTIZANDO</option>
                    <option value="Aprobacion pendiente">APROBACIÓN PENDIENTE</option>
                    <option value="En revision">EN REVISIÓN</option>
                    <option value="Espera_Programacion">ESPERA PROGRAMACIÓN</option>
                    <option value="Programada">PROGRAMADA</option>
                    <option value="Por Pagar">POR PAGAR</option>
                    <option value="Pagada">PAGADA</option>
                </select>
            </div>
            <div>
                <label class="${ui.label}">Método de Liquidación</label>
                <select name="MetodoPago" id="select-metodo-pago" onchange="window.toggleComplementoInput(this.value)" ${ui.disabledControl} class="${ui.baseInput} ${ui.classControl}">
                    <option value="0" ${sol.MetodoPago == 0 ? 'selected' : ''}>CONTADO (DIRECTO)</option>
                    <option value="1" ${sol.MetodoPago == 1 ? 'selected' : ''}>CRÉDITO (PARTIDA)</option>
                </select>
            </div>
            <div>
                <label class="${ui.label}">Fecha de Apertura</label>
                <div class="${ui.baseInput} bg-[#F9FAFB] text-[#6B7280] font-mono border-[#E5E7EB]">${fReg}</div>
                <input type="hidden" name="Fecha" value="${fReg}">
            </div>
            <div>
                <label class="${ui.label}">Sello de Aprobación</label>
                <div class="${ui.baseInput} bg-[#F9FAFB] text-[#6B7280] font-mono border-[#E5E7EB]">${fApro}</div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5 pt-5 border-t border-[#F3F4F6]">
            <div>
                <label class="${ui.label} text-[#1D4ED8]">Programación (Ref. Pago)</label>
                <input type="date" name="FechaRefPago" value="${fRef}" ${disabledFechas} class="${ui.baseInput} ${classRefPago}">
            </div>
            <div>
                <label class="${ui.label} text-[#047857]">Ejecución (Pago Realizado)</label>
                <input type="date" name="FechaPagoRealizado" value="${fPago}" ${disabledFechas} class="${ui.baseInput} ${classPagoReal}">
            </div>
        </div>
    </div>`;
}

function _renderSeccionGeneral(sol, htmlProv, htmlRazon, ui) {
    return `
    <div class="${ui.section}">
        <h4 class="text-xs font-bold text-[#111827] border-b border-[#F3F4F6] pb-3 mb-5 flex items-center gap-2">
            <span class="w-1.5 h-4 bg-[#4B5563] rounded-full"></span>
            ENTIDADES ASOCIADAS
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="${ui.label} text-[#1E3A8A]">Entidad Proveedora</label>
                <select name="ID_Proveedor" id="select-proveedor-maestro" onchange="window.validarCreditoProveedor()" ${ui.disabledFinanciero} class="${ui.baseInput} ${ui.classFinanciero}">
                    <option value="" data-credito="0">-- SELECCIONAR PROVEEDOR --</option>
                    ${htmlProv}
                </select>
            </div>
            <div>
                <label class="${ui.label} text-[#065F46]">Centro de Costos / Proyecto</label>
                <select name="ID_RazonSocial" ${ui.disabledFinanciero} class="${ui.baseInput} ${ui.classFinanciero}">
                    <option value="">-- SELECCIONAR CENTRO --</option>
                    ${htmlRazon}
                </select>
            </div>
        </div>
    </div>`;
}

function _renderAsistenteLlenado(data, sol, ui) {
    if (!data.grupos_presupuestales) return '';
    const gruposFiltrados = data.grupos_presupuestales;
    if (gruposFiltrados.length === 0) return '';

    return `
    <div class="mb-6 bg-[#F8FAFC] p-6 border border-[#E2E8F0] rounded-sm shadow-sm flex flex-col md:flex-row items-center gap-6 w-full">
        <div class="flex-shrink-0 bg-[#1E3A8A] text-white p-3 rounded-sm shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
        </div>
        <div class="flex-grow w-full">
            <label class="block text-[10px] font-bold text-[#1E3A8A] uppercase tracking-[0.2em] mb-2">Herramienta de Asignación Masiva de Partidas</label>
            <select id="select-grupo-presupuestal-general" class="w-full border border-[#CBD5E1] rounded-sm p-3 bg-white text-[#0F172A] text-xs font-bold focus:ring-1 focus:ring-[#1E3A8A] transition-all cursor-pointer shadow-sm" onchange="window.aplicarGrupoATodos(this.value)">
                <option value="">-- SELECCIONAR PARTIDA PRESUPUESTAL PARA APLICAR A TODOS LOS ÍTEMS DEL LISTADO --</option>
                ${gruposFiltrados.map(g => `<option value="${g.ID_GrupoPresupuestal}">${g.Nombre.toUpperCase()}</option>`).join('')}
            </select>
        </div>
    </div>`;
}

function _renderTablaProductos(data, sol, optionsGruposHtml, ui) {
    const productos = Array.isArray(data.productos) ? data.productos : (Array.isArray(data.servicios) ? data.servicios : []);
    const checkedIva = (sol.IVA == 1 || sol.IVA === 't' || sol.IVA === true) ? 'checked' : '';

    return `
    <div class="${ui.section} w-full">
        <div class="flex justify-between items-center border-b border-[#F3F4F6] pb-4 mb-6">
            <h4 class="text-xs font-bold text-[#111827] flex items-center gap-3">
                <span class="w-2 h-5 bg-[#065F46] rounded-full"></span>
                DETALLE TÉCNICO Y PARTIDAS
            </h4>
            <div class="text-right">
                <span class="text-[10px] font-bold text-[#6B7280] uppercase tracking-widest block mb-1">Total del Expediente</span> 
                <span id="span-total-editor" class="text-2xl font-mono font-bold text-[#111827]"></span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="tabla-productos-editor" class="w-full text-xs text-left border-collapse">
                <thead class="bg-[#F9FAFB] text-[#4B5563] text-[9px] font-bold uppercase tracking-[0.15em] border-y border-[#E5E7EB]">
                    <tr>
                        <th class="p-4">Descripción e Ítem / Clasificación de Partida</th>
                        <th class="p-4 text-center w-32">Cantidad</th>
                        <th class="p-4 text-right w-44">Precio Unitario</th>
                        <th class="p-4 text-right w-44 border-l border-[#F3F4F6]">Subtotal Neto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F3F4F6]">
                    ${productos.map((prod, index) => {
                        const selectHtml = optionsGruposHtml.replace(`value="${prod.ID_GrupoPresupuestal}"`, `value="${prod.ID_GrupoPresupuestal}" selected`);
                        return `
                        <tr class="hover:bg-[#FCFCFD] transition-colors">
                            <td class="p-5 align-top">
                                <input type="hidden" name="productos[${index}][id]" value="${prod.ID_SolicitudProd || prod.ID_SolicitudServ || prod.ID_Detalle}">
                                <input type="text" name="productos[${index}][nombre]" value="${prod.Nombre || ''}" ${ui.disabledFinanciero} class="${ui.baseInput} font-bold border-transparent hover:border-[#E5E7EB] focus:border-[#1E3A8A] bg-transparent text-sm">
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-[9px] text-[#9CA3AF] font-bold">PARTIDA:</span>
                                    <select name="productos[${index}][id_grupo_presupuestal]" class="flex-grow text-[11px] bg-transparent border-none text-[#1D4ED8] font-bold italic focus:ring-0 cursor-pointer select-grupo-partida p-0">
                                        ${selectHtml}
                                    </select>
                                </div>
                            </td>
                            <td class="p-5 w-32 align-top pt-8">
                                <input type="number" step="1" min="1" name="productos[${index}][cantidad]" value="${parseFloat(prod.Cantidad)||1}" ${ui.disabledFinanciero} class="${ui.baseInput} text-center font-mono font-bold ${ui.classFinanciero} input-cantidad" oninput="calcularTotalesUI()">
                            </td>
                            <td class="p-5 w-44 align-top pt-8">
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-[#9CA3AF] font-mono text-xs">$</span>
                                    <input type="number" step="0.01" min="0" name="productos[${index}][precio]" value="${parseFloat(prod.Importe||prod.Precio)||0}" ${ui.disabledFinanciero} class="${ui.baseInput} text-right font-mono font-bold ${ui.classFinanciero} pl-8 input-precio" oninput="calcularTotalesUI()">
                                </div>
                            </td>
                            <td class="p-5 text-right td-subtotal font-mono font-bold text-[#111827] align-top pt-9 text-sm border-l border-[#F3F4F6] bg-[#F9FAFB]/50">$0.00</td>
                        </tr>`;
                    }).join('')}
                </tbody>
            </table>
        </div>
        <div class="mt-8 flex justify-end">
            <label class="group flex items-center text-[10px] font-bold text-[#4B5563] cursor-pointer bg-[#F9FAFB] px-6 py-4 rounded-sm border border-[#E5E7EB] hover:border-[#1E3A8A] hover:text-[#1E3A8A] transition-all shadow-sm">
                <input type="checkbox" name="IVA" id="chk_iva_maestro" value="1" ${checkedIva} ${ui.disabledFinanciero} onchange="calcularTotalesUI()" class="w-4 h-4 text-[#1E3A8A] border-[#D1D5DB] rounded-sm focus:ring-[#1E3A8A] mr-4 transition-transform group-hover:scale-110">
                APLICAR DESGLOSE DE IVA (16.00%)
            </label>
        </div>
    </div>`;
}

function _renderSeccionArchivos(data, sol, orden, coti, ui) {
    let htmlAdjuntos = '';
    if (typeof generarSeccionAdjuntos === 'function') {
        try {
            const dataClone = { ...data, ID_Solicitud: data.ID_Solicitud || sol.ID_Solicitud, OrdenCompra: orden, cotizacion: coti };
            htmlAdjuntos = generarSeccionAdjuntos(dataClone);
        } catch (e) { htmlAdjuntos = ''; }
    }

    return `
    <div class="${ui.section}">
        <h4 class="text-sm font-bold text-gray-800 border-b pb-2 mb-4">Gestión de Archivos</h4>
        <div class="mb-6 p-2 bg-gray-50 rounded border border-gray-200 text-sm">
            <h5 class="text-xs font-bold text-gray-500 mb-2">Archivos Actuales:</h5>
            ${htmlAdjuntos || '<span class="text-xs text-gray-400">Sin adjuntos previos.</span>'}
        </div>
        <div class="${ui.classArchivos} grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-4 border-t border-gray-100">
            <div><label class="block text-xs font-bold text-green-600 mb-2">Cargar Cotización</label><input type="file" name="cotizacion_files[]" id="file-cotizacion" class="hidden" accept="image/*,.pdf" multiple onchange="window.handleFileSelect(this, 'cotizacion')"><button type="button" onclick="document.getElementById('file-cotizacion').click()" class="w-full bg-white border border-green-300 text-green-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button><div id="preview-cotizacion" class="mt-2"></div></div>
            <div><label class="block text-xs font-bold text-blue-600 mb-2">Cargar Ficha Pago</label><input type="file" name="File_Comprobante" id="file-comprobante" class="hidden" accept="image/*,.pdf,.xml" onchange="window.handleFileSelect(this, 'comprobante')"><button type="button" onclick="document.getElementById('file-comprobante').click()" class="w-full bg-white border border-blue-300 text-blue-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button><div id="preview-comprobante" class="mt-2"></div></div>
            <div><label class="block text-xs font-bold text-indigo-600 mb-2">Cargar Factura</label><input type="file" name="File_Factura" id="file-factura" class="hidden" accept="image/*,.pdf,.xml" onchange="window.handleFileSelect(this, 'factura')"><button type="button" onclick="document.getElementById('file-factura').click()" class="w-full bg-white border border-indigo-300 text-indigo-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button><div id="preview-factura" class="mt-2"></div></div>
            <div id="container-upload-complemento" class="${sol.MetodoPago == 1 ? '' : 'hidden'}">
                <label class="block text-xs font-bold text-orange-600 mb-2">Cargar Complemento</label>
                <input type="file" name="File_Complemento" id="file-complemento" class="hidden" accept="image/*,.pdf,.xml" onchange="window.handleFileSelect(this, 'complemento')">
                <button type="button" onclick="document.getElementById('file-complemento').click()" class="w-full bg-white border border-orange-300 text-orange-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Selecccionar</button>
                <div id="preview-complemento" class="mt-2"></div>
            </div>
        </div>
    </div>`;
}

// Se elimina _renderUploadButton ya que se integró directamente en el HTML anterior

// --- BLOQUE 4: LÓGICA Y CÁLCULOS REACTIVOS ---

/**
 * Formateador de moneda reutilizable para evitar instanciación repetitiva en bucles.
 */
// --- BLOQUE 4: LÓGICA Y CÁLCULOS REACTIVOS ---

/**
 * Configuración centralizada de niveles de criticidad para auditoría y presupuesto.
 * Evita la duplicidad de lógica y facilita ajustes globales de reglas de negocio.
 */
const CONFIG_PROCESO = {
    NIVELES_AUDITORIA: {
        'En espera': 1, 'Cotizando': 2, 'En revision': 3, 'Aprobacion pendiente': 3,
        'Aprobada': 4, 'Espera_Programacion': 5, 'Programada': 6, 'Por Pagar': 7, 'Pagada': 8
    },
    ESTADOS_CON_IMPACTO: ['Aprobada', 'Espera_Programacion', 'Programada', 'Por Pagar', 'Pagada']
};

/**
 * Formateador de moneda reutilizable (MXN).
 * Encapsulado para asegurar consistencia en toda la interfaz.
 */
const currencyFormatter = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 2
});

/**
 * Valida la línea de crédito del proveedor y actualiza el método de liquidación.
 */
window.validarCreditoProveedor = function() {
    const selectProv = document.getElementById('select-proveedor-maestro');
    const selectMetodo = document.getElementById('select-metodo-pago');
    if (!selectProv || !selectMetodo) return;

    const optionSelected = selectProv.options[selectProv.selectedIndex];
    const tieneCredito = optionSelected?.getAttribute('data-credito') === '1';
    const optionCredito = selectMetodo.querySelector('option[value="1"]');

    if (optionCredito) {
        if (tieneCredito) {
            optionCredito.disabled = false;
            optionCredito.textContent = "CRÉDITO (PARTIDA)";
        } else {
            // Si el proveedor no tiene crédito, forzamos el método a CONTADO
            optionCredito.disabled = true;
            optionCredito.textContent = "CRÉDITO (SIN LÍNEA AUTORIZADA)";
            if (selectMetodo.value === "1") {
                selectMetodo.value = "0";
                window.toggleComplementoInput("0");
            }
        }
    }
};

/**
 * Gestiona la previsualización técnica de archivos con validación en el cliente.
 * Optimización: Añade control de tamaño y integridad de tipos antes de la subida.
 */
window.handleFileSelect = function(input, type) {
    const files = input.files;
    const previewContainer = document.getElementById(`preview-${type}`);
    if (!previewContainer) return;

    if (!files || files.length === 0) {
        // Evitamos recursividad: si no hay archivos, simplemente limpiamos la previsualización
        if (previewContainer) previewContainer.innerHTML = '';
        return;
    }

    const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB por archivo (Estándar de auditoría)
    const ALLOWED_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'text/xml', 'application/xml'];

    // Filtrado y validación de integridad
    const validFiles = Array.from(files).filter(file => {
        if (file.size > MAX_FILE_SIZE) {
            alert(`⚠️ ARCHIVO EXCEDIDO: "${file.name}" supera el límite de 10MB permitido por el sistema.`);
            return false;
        }
        if (!ALLOWED_TYPES.includes(file.type) && !file.name.endsWith('.xml')) {
            alert(`⚠️ TIPO NO VÁLIDO: "${file.name}" no es un formato aceptado (PDF, JPG, PNG, XML).`);
            return false;
        }
        return true;
    });

    // Si hubo archivos inválidos, reseteamos el input para seguridad
    if (validFiles.length !== files.length) {
        if (validFiles.length === 0) {
            window.removeFile(type);
            return;
        }
        // Nota: En JS no se pueden eliminar archivos individuales del FileList, se recomienda informar al usuario
    }

    // Generación de previsualización optimizada con mapeo técnico
    const html = validFiles.map(file => {
        const isImage = file.type.startsWith('image/');
        const isPdf = file.type === 'application/pdf';
        const isXml = file.type.includes('xml') || file.name.endsWith('.xml');
        const icon = isImage ? '🖼️' : (isPdf ? '📕' : (isXml ? '📄' : '📎'));
        const sizeFormatted = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        
        return `
            <div class="flex items-center justify-between bg-[#F9FAFB] p-2 rounded border border-[#E5E7EB] text-[9px] shadow-sm animate-fade-in group hover:border-[#1E3A8A] transition-colors mb-1">
                <div class="flex flex-col truncate pr-2">
                    <span class="truncate w-36 font-mono text-[#374151] font-bold" title="${file.name}">${icon} ${file.name}</span>
                    <span class="text-[8px] text-[#9CA3AF] uppercase tracking-tighter">Integridad: ${sizeFormatted} | OK</span>
                </div>
                <button type="button" onclick="window.removeFile('${type}')" class="text-[#991B1B] hover:scale-110 transition-all font-bold px-2 py-1 bg-white border border-[#F3F4F6] rounded-sm shadow-sm" title="Desvincular archivo">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </div>`;
    }).join('');

    previewContainer.innerHTML = html;
};

/**
 * Limpieza integral de estados de archivos y contenedores.
 */
window.removeFile = function(type) {
    const inputMap = { 
        'cotizacion': 'file-cotizacion', 'comprobante': 'file-comprobante', 
        'factura': 'file-factura', 'complemento': 'file-complemento'
    };
    const inputId = inputMap[type];
    const input = document.getElementById(inputId);
    
    if (input) {
        // Solo actuamos y disparamos el evento si hay algo que limpiar para evitar bucles infinitos
        if (input.value !== '') {
            input.value = '';
            // Disparar evento para asegurar que cualquier listener externo se entere de la limpieza
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    
    const preview = document.getElementById(`preview-${type}`);
    if (preview) {
        preview.innerHTML = '';
    }
};

/**
 * Control dinámico de visibilidad para complementos de pago.
 */
window.toggleComplementoInput = function(metodo) {
    const divComplemento = document.getElementById('container-upload-complemento');
    if (!divComplemento) return;
    
    if (metodo == "1") { 
        divComplemento.classList.remove('hidden');
    } else {
        divComplemento.classList.add('hidden');
        window.removeFile('complemento');
    }
};

/**
 * Auditoría visual de impacto presupuestal.
 * Utiliza CONFIG_PROCESO para mayor fiabilidad.
 */
window.verificarImpactoPresupuestal = function() {
    const banner = document.getElementById('advertencia-presupuesto-maestro');
    const selectEstado = document.getElementById('select-estado-maestro');
    if (!banner || !selectEstado) return;

    // Fiabilidad: Verificamos la existencia física de ítems en la tabla
    const tabla = document.getElementById('tabla-productos-editor');
    const tieneItems = tabla && tabla.querySelector('.input-cantidad') !== null;
    
    if (!tieneItems) {
        banner.classList.add('hidden');
        return;
    }

    const estadoActual = selectEstado.value;
    const implicaImpacto = CONFIG_PROCESO.ESTADOS_CON_IMPACTO.includes(estadoActual);
    
    if (implicaImpacto) {
        banner.classList.remove('hidden');
        banner.classList.add('animate-pulse-subtle');
    } else {
        banner.classList.add('hidden');
    }
};

/**
 * Motor de cálculos matemáticos para la interfaz de edición.
 * OPTIMIZACIÓN: Manejo defensivo de errores y cacheo de DOM.
 */
function calcularTotalesUI() {
    const tabla = document.getElementById('tabla-productos-editor');
    const spanTotal = document.getElementById('span-total-editor');
    const chkIva = document.getElementById('chk_iva_maestro');
    
    if (!tabla) return;

    const filas = tabla.querySelectorAll('tbody tr');
    let subtotalGlobal = 0;

    // Procesamiento de filas con validación de tipos
    filas.forEach(fila => {
        const inputCant = fila.querySelector('.input-cantidad');
        const inputPrecio = fila.querySelector('.input-precio');
        const tdSubtotal = fila.querySelector('.td-subtotal');

        const cant = Math.max(0, parseFloat(inputCant?.value) || 0);
        const precio = Math.max(0, parseFloat(inputPrecio?.value) || 0);
        const subtotal = Number((cant * precio).toFixed(2));
        
        subtotalGlobal += subtotal;

        if (tdSubtotal) {
            tdSubtotal.innerText = currencyFormatter.format(subtotal);
        }
    });

    // Aplicación de impuestos (IVA 16%)
    const factorIva = (chkIva?.checked) ? 1.16 : 1;
    const totalFinal = Number((subtotalGlobal * factorIva).toFixed(2));

    if (spanTotal) {
        spanTotal.innerText = currencyFormatter.format(totalFinal);
    }

    // Auditoría automática de presupuesto
    window.verificarImpactoPresupuestal();
}

/**
 * Realiza la persistencia de los cambios del editor maestro.
 * Incluye validaciones críticas de auditoría y reglas de negocio por niveles.
 */
/**
 * Realiza la persistencia de los cambios del editor maestro.
 * OPTIMIZACIÓN: Sincronización con CONFIG_PROCESO y gestión profesional de estados.
 */
async function guardarCambiosMaestros() {
    // 1. CACHEO Y RECOLECCIÓN DE DATOS
    const container = document.getElementById('contenido-editor-maestro');
    const form = document.getElementById('form-editor-maestro');
    const btnGuardar = document.querySelector('#div-editor-maestro button.bg-blue-600') || 
                       document.querySelector('#div-editor-maestro button[onclick="guardarCambiosMaestros()"]');
    
    if (!form || !container) return;

    const formData = new FormData(form);
    const idSolicitud = document.getElementById('maestro_id_solicitud')?.value;
    const estadoSeleccionado = form.querySelector('select[name="Estado"]')?.value;
    const metodoPago = form.querySelector('select[name="MetodoPago"]')?.value;
    const nuevoProveedorID = document.getElementById('select-proveedor-maestro')?.value;
    const originalProveedorID = document.getElementById('original-id-proveedor')?.value;
    const estadoOriginal = document.getElementById('original-estado-maestro')?.value;

    if (!idSolicitud) {
        alert("❌ ERROR TÉCNICO: Identificador de expediente no localizado.");
        return;
    }

    // Determinamos si hay un cambio de estado real para activar validaciones obstructivas
    const haCambiadoEstado = (estadoOriginal && estadoSeleccionado !== estadoOriginal);

    // Fuente de verdad única para niveles de auditoría
    const nivelDestino = CONFIG_PROCESO.NIVELES_AUDITORIA[estadoSeleccionado] || 0;

    // 2. VERIFICACIÓN TÉCNICA DE EVIDENCIA (ARCHIVOS)
    const checkEvidencia = (id) => {
        const input = document.getElementById(`file-${id}`);
        const flag = document.getElementById(`flag-existe-${id}`);
        return (input?.files?.length > 0) || (flag?.value === '1');
    };

    const tieneCoti = checkEvidencia('cotizacion');
    const tieneFicha = checkEvidencia('comprobante');
    const tieneFactura = checkEvidencia('factura');
    const tieneComplemento = checkEvidencia('complemento');
    
    const subiendoCoti = document.getElementById('file-cotizacion')?.files?.length > 0;

    // ========================================================================
    // REGLA DE AUDITORÍA: CAMBIO DE ENTIDAD PROVEEDORA
    // ========================================================================
    if (originalProveedorID && nuevoProveedorID && (originalProveedorID != nuevoProveedorID)) {
        if (!subiendoCoti) {
            alert("⚠️ PROTOCOLO DE SEGURIDAD: El cambio de proveedor requiere una nueva cotización técnica que justifique la modificación presupuestal.");
            return;
        }
    }

    if (subiendoCoti && !confirm("🔔 NOTA DE INTEGRIDAD: Se está vinculando una nueva cotización. ¿Ha verificado que los costos unitarios del desglose técnico coinciden con el documento digital?")) {
        return;
    }

    // ========================================================================
    // REGLA DE NEGOCIO: VALIDACIÓN POR NIVEL DE PROCESAMIENTO
    // Solo se ejecutan si el usuario está intentando CAMBIAR el estado de la requisición.
    // Si solo sube archivos sin mover el estado, permitimos la persistencia directa.
    // ========================================================================
    if (haCambiadoEstado) {
        let mensajeError = null;

        if (nivelDestino >= 3 && !tieneCoti) {
            mensajeError = `El estado [${estadoSeleccionado}] exige una Cotización base para el acervo documental.`;
        } else if (nivelDestino >= 7 && !tieneFicha) {
            mensajeError = `El estado [${estadoSeleccionado}] requiere el Comprobante de Pago vinculado.`;
        } else if (nivelDestino >= 8 && !tieneFactura) {
            mensajeError = `No es posible procesar como "PAGADA" sin el documento fiscal (XML/PDF) correspondiente.`;
        } else if (nivelDestino >= 8 && metodoPago == "1" && !tieneComplemento) {
            mensajeError = `Para liquidaciones a CRÉDITO en estado final, es obligatorio el Complemento de Pago.`;
        } else if (nivelDestino >= 4 && !nuevoProveedorID) {
            mensajeError = `Los estados de ejecución requieren una Entidad Proveedora vinculada para la Orden de Compra.`;
        }

        if (mensajeError) {
            alert(`⚠️ REQUISITO TÉCNICO NO CUMPLIDO\n\n${mensajeError}`);
            return;
        }

        // 3. CONFIRMACIÓN DE IMPACTO FINANCIERO (Solo en cambio de estado)
        if (CONFIG_PROCESO.ESTADOS_CON_IMPACTO.includes(estadoSeleccionado)) {
            const confirmMsg = "🚨 ADVERTENCIA PRESUPUESTAL\n\nEsta actualización impactará automáticamente el balance del departamento.\n\n¿Confirma la validez técnica de estos cambios?";
            if (!confirm(confirmMsg)) return;
        }

        if (!confirm(`¿Establecer expediente en estado: ${estadoSeleccionado.toUpperCase()}?`)) return;
    }

    // 4. PERSISTENCIA DE DATOS
    const originalHTML = btnGuardar?.innerHTML;
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="flex items-center gap-2"><div class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></div> SINCRONIZANDO...</span>`;
    }

    try {
        const response = await SendDataEnd(`api/solicitudes/update_master/${idSolicitud}`, { 
            method: 'POST', 
            body: formData 
        });

        if (response.success) {
            alert('✅ Registro actualizado y auditado satisfactoriamente.');
            regresarMaestro();
            
            // Refresco visual controlado
            const tbody = document.querySelector('#tabla-maestro tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-12 text-[#9CA3AF] italic animate-pulse">Sincronizando registros maestros...</td></tr>';
            
            setTimeout(() => { 
                if (typeof initControlMaestro === 'function') initControlMaestro(); 
            }, 300);
        } else {
            throw new Error(response.message || 'Error en la respuesta del servidor');
        }
    } catch (error) {
        console.error("Fallo en persistencia maestra:", error);
        alert(`❌ ERROR DE AUDITORÍA\n\nNo se pudo sincronizar el cambio: ${error.message}`);
    } finally {
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = originalHTML;
        }
    }
}

/**
 * Retorna a la vista de listado principal.
 */
function regresarMaestro() {
    const divEditor = document.getElementById('div-editor-maestro');
    const divControl = document.getElementById('div-control-maestro');
    
    if (divEditor) divEditor.classList.add('hidden');
    if (divControl) divControl.classList.remove('hidden');
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Asignación masiva de partidas presupuestales.
 * OPTIMIZACIÓN: Restringe la búsqueda al contexto del editor para evitar colisiones.
 */
window.aplicarGrupoATodos = function(valor) {
    if (!valor) return;
    
    const editor = document.getElementById('contenido-editor-maestro');
    if (!editor) return;

    const selectores = editor.querySelectorAll('.select-grupo-partida');
    selectores.forEach(select => {
        select.value = valor;
    });
};
