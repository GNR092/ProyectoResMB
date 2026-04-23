let choicesDeptoMaestro = null;
let choicesRazonMaestro = null;

function initControlMaestro() {
    const tabla = document.getElementById('tabla-maestro');
    if (!tabla) return;

    // 1. Configurar Choices
    const filtroEl = document.getElementById('filtroDepartamentoMaestro');
    if (filtroEl) {
        if (!choicesDeptoMaestro) {
            choicesDeptoMaestro = new Choices(filtroEl, {
                removeItemButton: true,
                placeholder: true,
                placeholderValue: 'Todos los departamentos',
                itemSelectText: '',
                noResultsText: 'No se encontraron resultados',
            });
        }
        document.getElementById('wrapper-depto-maestro').classList.remove('hidden');
    }

    const filtroRazonEl = document.getElementById('filtro-razon-social-maestro');
    if (filtroRazonEl) {
        if (!choicesRazonMaestro) {
            choicesRazonMaestro = new Choices(filtroRazonEl, {
                removeItemButton: true,
                placeholder: true,
                placeholderValue: 'Todas las razones sociales',
                itemSelectText: '',
                noResultsText: 'No se encontraron resultados',
            });
        }
    }

    // 2. URL + Cache Buster
    let urlEndpoint = 'api/historic';
    const exceptions = ['Compras', 'Administración', 'Direccion', 'Tesoreria', 'Direccion Campus', 'Contaduría'];
    if (typeof USER_DEPT_NAME !== 'undefined' && USER_DEPT_ID && !exceptions.includes(USER_DEPT_NAME)) {
        urlEndpoint = `api/historic/department/${USER_DEPT_ID}`;
    }

    const cacheBuster = `?_t=${new Date().getTime()}`;
    const finalEndpoint = urlEndpoint.includes('?') ? (urlEndpoint + '&' + cacheBuster.substring(1)) : (urlEndpoint + cacheBuster);

    // 3. Inicializar Tabla
    createPaginatedTable({
        tableSelector: '#tabla-maestro tbody',
        paginationSelector: 'paginacion-maestro',
        endpoint: finalEndpoint,
        filterFormSelector: '#filtros-maestro-container',

        renderRow: (item) => {
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
            const fechaFiltro = document.getElementById('filtro-fecha-maestro').value;
            const filtrarPorMes = document.getElementById('filtrar-por-mes-maestro').checked;
            const estadoFiltro = document.getElementById('filtro-estado-maestro').value;
            const metodoFiltro = document.getElementById('filtro-metodo-maestro').value;
            const tipoFiltro = document.getElementById('filtro-tipo-maestro')?.value || '';
            const deptosSeleccionados = choicesDeptoMaestro ? choicesDeptoMaestro.getValue(true) : [];
            const razonesSeleccionadas = choicesRazonMaestro ? choicesRazonMaestro.getValue(true) : [];

            return allData.filter((item) => {
                const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro;
                const coincideMetodo = !metodoFiltro || item.MetodoPago == metodoFiltro;
                
                const coincideTipo = !tipoFiltro || 
                    (tipoFiltro === 'Producto' && (item.Tipo == 0 || item.Tipo == 1)) || 
                    (tipoFiltro === 'Servicio' && item.Tipo == 2);

                let coincideDepto = true;
                if (deptosSeleccionados.length > 0) {
                    const deptoFull = `${item.DepartamentoNombre}|${item.PlaceNombre || ''}`;
                    coincideDepto = deptosSeleccionados.includes(deptoFull);
                } else if (choicesDeptoMaestro && choicesDeptoMaestro.getValue(true).length === 0) {
                    coincideDepto = true;
                }

                let coincideRazon = true;
                if (razonesSeleccionadas.length > 0) {
                    coincideRazon = razonesSeleccionadas.includes(item.Complejo);
                }

                const passesOtherFilters = coincideEstado && coincideDepto && coincideMetodo && coincideTipo && coincideRazon;

                if (!fechaFiltro) return passesOtherFilters;
                const fechaItem = item.Fecha;
                if (filtrarPorMes) {
                    return fechaItem.slice(0, 7) === fechaFiltro.slice(0, 7) && passesOtherFilters;
                }
                return fechaItem === fechaFiltro && passesOtherFilters;
            });
        }
    });
}

/**
 * CARGA DE DATOS
 */

async function cargarEditorMaestro(idSolicitud, folio) {
    document.getElementById('div-control-maestro').classList.add('hidden');
    document.getElementById('div-editor-maestro').classList.remove('hidden');
    document.getElementById('titulo-editor').innerText = `Editando: ${folio || 'ID '+idSolicitud}`;

    const container = document.getElementById('contenido-editor-maestro');

    container.innerHTML = `
        <div class="flex flex-col items-center justify-center py-12">
            <p class="text-gray-500 font-medium animate-pulse">Cargando datos y archivos...</p>
        </div>`;

    try {
        const [dataSolicitud, dataOrden, dataProveedores, dataRazones] = await Promise.all([
            SendDataEnd(`api/solicitud/details/${idSolicitud}`),
            SendDataEnd(`api/orden-compra/details/${idSolicitud}`).catch(() => null),
            SendDataEnd(`api/providers/all`),
            SendDataEnd(`api/razonsocial/all`)
        ]);

        if (dataSolicitud.error) throw new Error(dataSolicitud.error);

        let ordenObjeto = null;
        if (dataOrden && !dataOrden.error) {
            if (Array.isArray(dataOrden)) {
                ordenObjeto = dataOrden.length > 0 ? dataOrden[0] : null;
            } else if (dataOrden.OrdenCompra) {
                ordenObjeto = Array.isArray(dataOrden.OrdenCompra) ? dataOrden.OrdenCompra[0] : dataOrden.OrdenCompra;
            } else {
                ordenObjeto = dataOrden;
            }
        }

        if (ordenObjeto) {
            dataSolicitud.OrdenCompra = ordenObjeto;
            if (ordenObjeto.cotizacion) {
                dataSolicitud.cotizacion = ordenObjeto.cotizacion;
            }
        }

        const normalize = (d) => Array.isArray(d) ? d : (d.data || d.messages || []);
        const listaProveedores = normalize(dataProveedores);
        const listaRazones = normalize(dataRazones);

        document.getElementById('maestro_id_solicitud').value = idSolicitud;
        renderizarInputsDios(dataSolicitud, container, listaProveedores, listaRazones);

    } catch (error) {
        console.error(error);
        container.innerHTML = `
            <div class="text-center text-red-500 py-10 bg-red-50 rounded border border-red-200">
                <p class="font-bold">Error al cargar datos</p>
                <p class="text-sm">${error.message}</p>
                <button onclick="regresarMaestro()" class="mt-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Volver</button>
            </div>`;
    }
}

/**
 * RENDERIZADO DEL FORMULARIO
 */

function renderizarInputsDios(data, container, listaProveedores = [], listaRazones = []) {
    const sol = data.solicitud || data;
    const orden = data.OrdenCompra || {};
    const coti = data.cotizacion || {};

    let estadoVisual = sol.Estado;
    if (sol.Estado === 'Aprobada' && orden && orden.Estado) {
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
    const disabledFinanciero = reglas.financiero ? 'disabled' : '';
    const classFinanciero    = reglas.financiero ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white';
    const disabledControl    = reglas.control ? 'disabled' : '';
    const classControl       = reglas.control ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white';
    const classArchivos      = reglas.financiero ? 'hidden' : 'grid';

    const existeOrden = (orden && orden.Estado) ? true : false;
    const disabledFechasOrden = (!existeOrden || reglas.control) ? 'disabled' : '';
    const classInputRefPago = (!existeOrden || reglas.control) ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : 'bg-blue-50 border-blue-200';
    const classInputPagoReal = (!existeOrden || reglas.control) ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200' : 'bg-green-50 border-green-200';

    let idProveedorActual = sol.ID_Proveedor;
    if (!idProveedorActual && data.proveedor) idProveedorActual = data.proveedor.ID_Proveedor;

    let htmlProv = `<option value="" data-credito="0">-- Seleccionar --</option>`;
    listaProveedores.forEach(p => {
        const selected = (p.ID_Proveedor == idProveedorActual) ? 'selected' : '';
        const dias = parseFloat(p.Dias_Credito || p.dias_credito || 0);
        htmlProv += `<option value="${p.ID_Proveedor}" data-credito="${dias > 0 ? '1' : '0'}" ${selected}>${p.RazonSocial}</option>`;
    });

    let htmlRazon = `<option value="">-- Seleccionar Proyecto --</option>`;
    listaRazones.forEach(r => {
        const selected = (r.ID_RazonSocial == sol.ID_RazonSocial) ? 'selected' : '';
        htmlRazon += `<option value="${r.ID_RazonSocial}" ${selected}>${r.Nombre} (${r.RFC || ''})</option>`;
    });

    const checkedIva = (sol.IVA == 1 || sol.IVA === 't' || sol.IVA === true) ? 'checked' : '';
    const productos = Array.isArray(data.productos) ? data.productos : (Array.isArray(data.servicios) ? data.servicios : []);

    const fechaRegistroVista = sol.Fecha ? sol.Fecha.split(' ')[0] : '';
    const valorFechaAprobacion = (orden.Fecha || sol.FechaOrden) ? (orden.Fecha || sol.FechaOrden).split(' ')[0] : '';
    const valorFechaRefPago = (orden.FechaRefPago || sol.FechaRefPago) ? (orden.FechaRefPago || sol.FechaRefPago).split(' ')[0] : '';
    const valorFechaPagoReal = (orden.FechaPagoRealizado || sol.FechaPagoRealizado) ? (orden.FechaPagoRealizado || sol.FechaPagoRealizado).split(' ')[0] : '';

    const baseInputClass = "mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-xs text-gray-700";
    const labelClass = "block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wide";
    const sectionClass = "bg-white shadow rounded-lg p-6 border border-gray-200 mb-6";

    // ASISTENTE DE LLENADO
    let grupoGeneralHtml = '';
    if (data.grupos_presupuestales && !reglas.financiero) {
        const requestPlaceId = sol.ID_Place || data.ID_Place;
        const gruposFiltrados = data.grupos_presupuestales.filter(g => requestPlaceId && g.ID_Place == requestPlaceId);

        if (gruposFiltrados.length > 0) {
            grupoGeneralHtml = `
            <div id="contenedor-grupo-general" class="mb-4 bg-blue-50 p-4 border border-blue-200 rounded-lg">
                <label class="block text-xs font-bold text-blue-800 mb-1 tracking-wide">Asistente de Llenado: Asignar partida a todos los ítems</label>
                <div id="select-grupo-general-container">
                    <select id="select-grupo-presupuestal-general" class="w-full border rounded-md p-2 bg-white text-blue-900 text-xs font-semibold focus:ring-2 focus:ring-blue-500 shadow-sm cursor-pointer" onchange="window.aplicarGrupoATodos(this.value)">
                        <option value="">-- Seleccionar grupo para aplicar a todo --</option>
                        ${gruposFiltrados.map(grupo => `<option value="${grupo.ID_GrupoPresupuestal}">${grupo.Nombre}</option>`).join('')}
                    </select>
                </div>
            </div>`;
        }
    }

    let htmlAdjuntos = '';
    if (typeof generarSeccionAdjuntos === 'function') {
        try {
            if(!data.ID_Solicitud && sol.ID_Solicitud) data.ID_Solicitud = sol.ID_Solicitud;
            data.OrdenCompra = orden; data.cotizacion = coti;
            htmlAdjuntos = generarSeccionAdjuntos(data);
        } catch (e) { htmlAdjuntos = ''; }
    }

    const existeCotizacion = (coti.Cotizacion_Files || data.Cotizacion_Files) ? '1' : '0';
    const existeFicha      = (orden.File_Comprobante || data.File_Comprobante) ? '1' : '0';
    const existeFactura    = (orden.File_Factura || data.File_Factura) ? '1' : '0';
    const existeComplemento = (orden.File_Complemento || data.File_Complemento) ? '1' : '0';

    container.innerHTML = `
    <div id="advertencia-presupuesto-maestro" class="hidden mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 rounded shadow-sm">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <p class="font-bold text-sm">AVISO DE IMPACTO PRESUPUESTAL</p>
        </div>
        <p class="text-xs mt-1 ml-7">Los cambios realizados (estado o montos) se verán reflejados automáticamente en el presupuesto mensual del departamento.</p>
    </div>

    <div class="${sectionClass}">
        <div class="flex justify-between items-center border-b pb-2 mb-4">
            <h4 class="text-sm font-bold text-gray-800">Control del Sistema</h4>
            ${reglas.financiero ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded border border-yellow-200">🔒 Edición restringida</span>' : ''}
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-bold text-red-600 mb-1">Estado</label>
                <select name="Estado" id="select-estado-maestro" onchange="window.verificarImpactoPresupuestal()" class="${baseInputClass} bg-red-50 border-red-300 text-red-900 font-bold">
                    <option value="${estadoVisual}" selected>➡ ${estadoVisual}</option>
                    <option value="En espera">En espera</option>
                    <option value="Aprobada">Aprobada</option>
                    <option value="Cotizando">Cotizando</option>
                    <option value="Aprobacion pendiente">Aprobación Pendiente</option>
                    <option value="En revision">En revisión</option>
                    <option value="Espera_Programacion">Espera Programación</option>
                    <option value="Programada">Programada</option>
                    <option value="Por Pagar">Por Pagar</option>
                    <option value="Pagada">Pagada</option>
                </select>
            </div>
            <div>
                <label class="${labelClass}">Método Pago</label>
                <select name="MetodoPago" id="select-metodo-pago" onchange="window.toggleComplementoInput(this.value)" ${disabledControl} class="${baseInputClass} ${classControl}">
                    <option value="${sol.MetodoPago}" selected>Actual: ${sol.MetodoPago == 1 ? 'Crédito' : 'Contado'}</option>
                    <option value="0">Contado</option>
                    <option value="1">Crédito</option>
                </select>
            </div>
            <div>
                <label class="${labelClass}">Fecha Registro</label>
                <div class="${baseInputClass} bg-gray-100 text-gray-500 border-gray-200 font-mono">${fechaRegistroVista || 'N/A'}</div>
                <input type="hidden" name="Fecha" value="${fechaRegistroVista}">
            </div>
            <div>
                <label class="${labelClass}">Fecha Aprobación</label>
                <div class="${baseInputClass} bg-gray-100 text-gray-500 border-gray-200 font-mono">${valorFechaAprobacion || 'Aún no generada'}</div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 pt-4 border-t border-gray-100">
            <div><label class="${labelClass} text-blue-700">Fecha Ref Pago</label><input type="date" name="FechaRefPago" value="${valorFechaRefPago}" ${disabledFechasOrden} class="${baseInputClass} ${classInputRefPago}"></div>
            <div><label class="${labelClass} text-green-700">Fecha Pago Realizado</label><input type="date" name="FechaPagoRealizado" value="${valorFechaPagoReal}" ${disabledFechasOrden} class="${baseInputClass} ${classInputPagoReal}"></div>
        </div>
    </div>

    <div class="${sectionClass}">
        <h4 class="text-sm font-bold text-gray-800 border-b pb-2 mb-4">Información General</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="${labelClass} text-indigo-700">Proveedor Asignado <span class="text-red-500">*</span></label>
                <select name="ID_Proveedor" id="select-proveedor-maestro" onchange="window.validarCreditoProveedor()" ${disabledFinanciero} class="${baseInputClass} border-indigo-200 ${classFinanciero}">${htmlProv}</select>
            </div>
            <div><label class="${labelClass} text-green-700">Proyecto</label><select name="ID_RazonSocial" ${disabledFinanciero} class="${baseInputClass} border-green-200 ${classFinanciero}">${htmlRazon}</select></div>
        </div>
    </div>

    ${grupoGeneralHtml}

    <div class="${sectionClass}">
        <div class="flex justify-between items-end mb-4 border-b pb-2">
            <h4 class="text-sm font-bold text-gray-800">Detalle de Productos / Servicios</h4>
            <div class="text-right"><span class="text-xs font-bold text-gray-500">Total:</span> <span id="span-total-editor" class="text-sm font-bold text-gray-800"></span></div>
        </div>
        <table id="tabla-productos-editor" class="w-full text-xs text-left">
            <tbody class="divide-y">
                ${productos.map((prod, index) => {
                    let gruposHtml = '';
                    if (data.grupos_presupuestales) {
                        const requestPlaceId = sol.ID_Place || data.ID_Place;
                        const gruposFiltrados = data.grupos_presupuestales.filter(g => requestPlaceId && g.ID_Place == requestPlaceId);
                        gruposHtml = `<select name="productos[${index}][id_grupo_presupuestal]" ${disabledFinanciero} class="${baseInputClass} ${classFinanciero} select-grupo-partida mt-1">`;
                        gruposHtml += `<option value="">-- Sin partida asignada --</option>`;
                        gruposFiltrados.forEach((grupo) => {
                            const selected = prod.ID_GrupoPresupuestal == grupo.ID_GrupoPresupuestal ? 'selected' : '';
                            gruposHtml += `<option value="${grupo.ID_GrupoPresupuestal}" ${selected}>${grupo.Nombre}</option>`;
                        });
                        gruposHtml += `</select>`;
                    }
                    return `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-2 align-top">
                            <input type="hidden" name="productos[${index}][id]" value="${prod.ID_SolicitudProd || prod.ID_SolicitudServ || prod.ID_Detalle}">
                            <input type="text" name="productos[${index}][nombre]" value="${prod.Nombre || ''}" ${disabledFinanciero} class="${baseInputClass} ${classFinanciero}">
                            ${gruposHtml}
                        </td>
                        <td class="p-2 w-24 align-top"><input type="number" step="1.00" min="1" name="productos[${index}][cantidad]" value="${parseFloat(prod.Cantidad)||1}" ${disabledFinanciero} class="${baseInputClass} text-center input-cantidad ${classFinanciero}" oninput="calcularTotalesUI()"></td>
                        <td class="p-2 w-32 align-top"><input type="number" step="0.01" min="0" name="productos[${index}][precio]" value="${parseFloat(prod.Importe||prod.Precio)||0}" ${disabledFinanciero} class="${baseInputClass} text-right input-precio ${classFinanciero}" oninput="calcularTotalesUI()"></td>
                        <td class="p-2 text-right td-subtotal font-mono text-gray-500 align-top pt-4">$0.00</td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>
        <div class="mt-4 flex justify-end"><label class="flex items-center text-xs font-bold cursor-pointer"><input type="checkbox" name="IVA" id="chk_iva_maestro" value="1" ${checkedIva} ${disabledFinanciero} onchange="calcularTotalesUI()" class="mr-2"> + IVA (16%)</label></div>
    </div>

    <div class="${sectionClass}">
        <h4 class="text-sm font-bold text-gray-800 border-b pb-2 mb-4">Gestión de Archivos</h4>
        <div class="mb-6 p-2 bg-gray-50 rounded border border-gray-200 text-sm">
            <h5 class="text-xs font-bold text-gray-500 mb-2">Archivos Actuales:</h5>
            ${htmlAdjuntos || '<span class="text-xs text-gray-400">Sin adjuntos previos.</span>'}
        </div>
        <div class="${classArchivos} grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 pt-4 border-t border-gray-100">
            <div><label class="block text-xs font-bold text-green-600 mb-2">Cargar Cotización</label><input type="file" name="cotizacion_files[]" id="file-cotizacion" class="hidden" accept="image/*,.pdf" multiple onchange="window.handleFileSelect(this, 'cotizacion')"><button type="button" onclick="document.getElementById('file-cotizacion').click()" class="w-full bg-white border border-green-300 text-green-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button><div id="preview-cotizacion" class="mt-2"></div></div>
            <div><label class="block text-xs font-bold text-blue-600 mb-2">Cargar Ficha Pago</label><input type="file" name="File_Comprobante" id="file-comprobante" class="hidden" accept="image/*,.pdf,.xml" onchange="window.handleFileSelect(this, 'comprobante')"><button type="button" onclick="document.getElementById('file-comprobante').click()" class="w-full bg-white border border-blue-300 text-blue-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button><div id="preview-comprobante" class="mt-2"></div></div>
            <div><label class="block text-xs font-bold text-indigo-600 mb-2">Cargar Factura</label><input type="file" name="File_Factura" id="file-factura" class="hidden" accept="image/*,.pdf,.xml" onchange="window.handleFileSelect(this, 'factura')"><button type="button" onclick="document.getElementById('file-factura').click()" class="w-full bg-white border border-indigo-300 text-indigo-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button><div id="preview-factura" class="mt-2"></div></div>
            <div id="container-upload-complemento" class="${sol.MetodoPago == 1 ? '' : 'hidden'}">
                <label class="block text-xs font-bold text-orange-600 mb-2">Cargar Complemento</label>
                <input type="file" name="File_Complemento" id="file-complemento" class="hidden" accept="image/*,.pdf,.xml" onchange="window.handleFileSelect(this, 'complemento')">
                <button type="button" onclick="document.getElementById('file-complemento').click()" class="w-full bg-white border border-orange-300 text-orange-600 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button>
                <div id="preview-complemento" class="mt-2"></div>
            </div>
        </div>
    </div>

    <input type="hidden" id="original-id-proveedor" value="${idProveedorActual}">
    <input type="hidden" id="flag-existe-cotizacion" value="${existeCotizacion}">
    <input type="hidden" id="flag-existe-ficha" value="${existeFicha}">
    <input type="hidden" id="flag-existe-factura" value="${existeFactura}">
    <input type="hidden" id="flag-existe-complemento" value="${existeComplemento}">
    `;

    calcularTotalesUI();
    setTimeout(() => { window.validarCreditoProveedor(); }, 100);
}

window.validarCreditoProveedor = function() {
    const selectProv = document.getElementById('select-proveedor-maestro');
    const selectMetodo = document.getElementById('select-metodo-pago');
    if (!selectProv || !selectMetodo) return;
    const optionSelected = selectProv.options[selectProv.selectedIndex];
    const tieneCredito = (optionSelected && optionSelected.getAttribute('data-credito') === '1');
    const optionCredito = selectMetodo.querySelector('option[value="1"]');
    if (optionCredito) {
        if (tieneCredito) {
            optionCredito.disabled = false;
            optionCredito.textContent = "Crédito";
        } else {
            optionCredito.disabled = true;
            optionCredito.textContent = "Crédito (No disponible)";
            if (selectMetodo.value === "1") selectMetodo.value = "0";
        }
    }
};

window.handleFileSelect = function(input, type) {
    const files = input.files;
    const previewContainer = document.getElementById('preview-' + type);
    if (!files || files.length === 0) { window.removeFile(type); return; }
    let html = "";
    Array.from(files).forEach(file => {
        let icon = file.type.startsWith('image/') ? '🖼️' : (file.type === 'application/pdf' ? '📕' : '📄');
        html += `<div class="flex items-center justify-between bg-white p-2 mb-1 rounded border text-[10px] shadow-sm">
            <span class="truncate w-32">${icon} ${file.name}</span>
            <button type="button" onclick="window.removeFile('${type}')" class="text-red-500 font-bold">&times;</button>
        </div>`;
    });
    previewContainer.innerHTML = html;
}

window.removeFile = function(type) {
    const inputIds = { 
        'cotizacion': 'file-cotizacion', 
        'comprobante': 'file-comprobante', 
        'factura': 'file-factura',
        'complemento': 'file-complemento'
    };
    const input = document.getElementById(inputIds[type]);
    if(input) input.value = '';
    const preview = document.getElementById('preview-' + type);
    if(preview) preview.innerHTML = '';
}

window.toggleComplementoInput = function(metodo) {
    const divComplemento = document.getElementById('container-upload-complemento');
    if (!divComplemento) return;
    
    // Solo mostrar para Crédito (1)
    if (metodo == "1") {
        divComplemento.classList.remove('hidden');
    } else {
        divComplemento.classList.add('hidden');
        window.removeFile('complemento');
    }
}

window.verificarImpactoPresupuestal = function() {
    const banner = document.getElementById('advertencia-presupuesto-maestro');
    if (!banner) return;
    const esMateriales = document.querySelector('.input-cantidad') !== null;
    if (!esMateriales) return;
    const selectEstado = document.getElementById('select-estado-maestro');
    const NIVELES = { 'En espera': 1, 'Cotizando': 2, 'En revision': 3, 'Aprobacion pendiente': 3, 'Aprobada': 4, 'Espera_Programacion': 5, 'Programada': 6, 'Por Pagar': 7, 'Pagada': 8 };
    if ((NIVELES[selectEstado.value] || 0) >= 4) banner.classList.remove('hidden');
    else banner.classList.add('hidden');
}

function calcularTotalesUI() {
    const filas = document.querySelectorAll('#tabla-productos-editor tbody tr');
    let subtotalGlobal = 0;
    filas.forEach(fila => {
        const cant = parseFloat(fila.querySelector('.input-cantidad')?.value) || 0;
        const precio = parseFloat(fila.querySelector('.input-precio')?.value) || 0;
        const subtotal = cant * precio;
        subtotalGlobal += subtotal;
        const td = fila.querySelector('.td-subtotal');
        if(td) td.innerText = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(subtotal);
    });
    const totalFinal = document.getElementById('chk_iva_maestro')?.checked ? subtotalGlobal * 1.16 : subtotalGlobal;
    const spanTotal = document.getElementById('span-total-editor');
    if (spanTotal) spanTotal.innerText = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(totalFinal);
    window.verificarImpactoPresupuestal();
}

async function guardarCambiosMaestros() {
    const form = document.getElementById('form-editor-maestro');
    const formData = new FormData(form);
    const idSolicitud = document.getElementById('maestro_id_solicitud').value;

    // --- MAPA DE NIVELES (Para lógica de validación) ---
    const NIVELES = {
        'En espera': 1, 'Cotizando': 2, 'En revision': 3, 'Aprobacion pendiente': 3,
        'Aprobada': 4, 'Espera_Programacion': 5, 'Programada': 6, 'Por Pagar': 7, 'Pagada': 8
    };

    // 1. OBTENER DATOS Y BANDERAS
    const estadoSeleccionado = form.querySelector('select[name="Estado"]').value;
    const selectProveedor = document.getElementById('select-proveedor-maestro');

    // Nivel numérico del estado destino
    const nivelDestino = NIVELES[estadoSeleccionado] || 0;

    // Banderas de BD
    const elCoti = document.getElementById('flag-existe-cotizacion');
    const elFicha = document.getElementById('flag-existe-ficha');
    const elFactu = document.getElementById('flag-existe-factura');
    const elComple = document.getElementById('flag-existe-complemento');

    const tieneCotizacionBD = elCoti ? elCoti.value === '1' : false;
    const tieneFichaBD      = elFicha ? elFicha.value === '1' : false;
    const tieneFacturaBD    = elFactu ? elFactu.value === '1' : false;
    const tieneComplementoBD = elComple ? elComple.value === '1' : false;

    // Archivos nuevos
    const inputCoti = document.getElementById('file-cotizacion');
    const inputFicha = document.getElementById('file-comprobante');
    const inputFactura = document.getElementById('file-factura');
    const inputComple = document.getElementById('file-complemento');

    const subiendoCoti = inputCoti && inputCoti.files.length > 0;
    const subiendoFicha = inputFicha && inputFicha.files.length > 0;
    const subiendoFactura = inputFactura && inputFactura.files.length > 0;
    const subiendoComplemento = inputComple && inputComple.files.length > 0;

    const tieneCoti = tieneCotizacionBD || subiendoCoti;
    const tieneFicha = tieneFichaBD || subiendoFicha;
    const tieneFactura = tieneFacturaBD || subiendoFactura;
    const tieneComplemento = tieneComplementoBD || subiendoComplemento;

    // ========================================================================
    // REGLA 1: CAMBIO DE PROVEEDOR (Prioridad Alta)
    // ========================================================================
    const originalProveedorID = document.getElementById('original-id-proveedor').value;
    const nuevoProveedorID = selectProveedor ? selectProveedor.value : null;

    if (originalProveedorID && nuevoProveedorID && (originalProveedorID != nuevoProveedorID)) {
        if (!subiendoCoti) {
            alert("⚠️ ALERTA DE SEGURIDAD:\n\nHas cambiado el proveedor asignado. Es OBLIGATORIO subir la nueva cotización que justifique este cambio.");
            return;
        }
    }

    if (subiendoCoti) {
        if (!confirm("🔔 NOTA IMPORTANTE:\n\nEstás subiendo una nueva Cotización.\n¿Te has asegurado de que los PRECIOS en la lista coincidan con los de este nuevo archivo?")) {
            return;
        }
    }

    // ========================================================================
    // REGLA 2: EXIGENCIAS POR NIVEL
    // ========================================================================
    let errorValidacion = null;

    // Nivel 3 (En revision) o superior -> Exige Cotización
    if (nivelDestino >= 3 && !tieneCoti) {
        errorValidacion = `Para el estado "${estadoSeleccionado}", es obligatoria la Cotización.`;
    }

    // Nivel 7 (Por Pagar) o superior -> Exige Ficha
    if (nivelDestino >= 7 && !tieneFicha) {
        errorValidacion = `Para el estado "${estadoSeleccionado}", es obligatoria la Ficha de Pago.`;
    }

    // Nivel 8 (Pagada) -> Exige Factura
    if (nivelDestino >= 8 && !tieneFactura) {
        errorValidacion = `Para el estado "${estadoSeleccionado}", es obligatoria la Factura.`;
    }

    // Nivel 8 (Pagada) + Crédito -> Exige Complemento de Pago
    const metodoPago = form.querySelector('select[name="MetodoPago"]').value;
    if (nivelDestino >= 8 && metodoPago == "1" && !tieneComplemento) {
        errorValidacion = `Al ser una solicitud a CRÉDITO, para el estado "${estadoSeleccionado}" es obligatorio subir el Complemento de Pago.`;
    }

    // Validación de Proveedor para niveles que generan Orden (Nivel 4+)
    const estadosConOrden = ['Aprobada', 'Espera_Programacion', 'Programada', 'Por Pagar', 'Pagada'];
    if (estadosConOrden.includes(estadoSeleccionado)) {
        if (!nuevoProveedorID) {
            errorValidacion = `El estado "${estadoSeleccionado}" requiere un Proveedor asignado para la Orden de Compra.`;
        }
    }

    if (errorValidacion) {
        alert(`⚠️ REQUISITO NO CUMPLIDO\n\n${errorValidacion}`);
        return;
    }

    // --- NUEVA CONFIRMACIÓN DE PRESUPUESTO ---
    if (nivelDestino >= 4) {
        if (!confirm("🚨 ADVERTENCIA DE PRESUPUESTO:\n\nHas realizado cambios que afectan directamente al presupuesto del departamento.\n\n¿Estás seguro de que deseas proceder con el ajuste financiero automático?")) {
            return;
        }
    }

    // Confirmación final
    if(!confirm(`¿Confirmar todos los cambios y establecer estado como: ${estadoSeleccionado}?`)) return;

    // ENVÍO
    const btnGuardar = document.querySelector('#div-editor-maestro button.bg-blue-600');
    if(btnGuardar) { 
        btnGuardar.disabled = true; 
        const originalText = btnGuardar.innerHTML;
        btnGuardar.innerText = "Guardando..."; 
    }

    try {
        const result = await SendDataEnd(`api/solicitudes/update_master/${idSolicitud}`, { method: 'POST', body: formData });
        if (result.success) {
            alert('✅ Datos actualizados correctamente.');
            regresarMaestro();
            const tbody = document.querySelector('#tabla-maestro tbody');
            if(tbody) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-12 text-gray-400 animate-pulse">Refrescando datos...</td></tr>';
            setTimeout(() => { initControlMaestro(); }, 300);
        } else {
            alert('❌ Error: ' + (result.message || 'Desconocido'));
        }
    } catch (e) {
        console.error(e);
        alert('❌ Error de conexión.');
    } finally {
        if(btnGuardar) { 
            btnGuardar.disabled = false; 
            btnGuardar.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg> Guardar Cambios`;
        }
    }
}

function regresarMaestro() {
    document.getElementById('div-editor-maestro').classList.add('hidden');
    document.getElementById('div-control-maestro').classList.remove('hidden');
}

window.aplicarGrupoATodos = function(valor) {
    if (valor === '') return;
    document.querySelectorAll('.select-grupo-partida').forEach(select => {
        if (!select.disabled) select.value = valor;
    });
};
