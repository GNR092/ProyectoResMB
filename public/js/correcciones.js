let choicesDeptoMaestro = null;

function initControlMaestro() {
    const tabla = document.getElementById('tabla-maestro');
    if (!tabla) return;

    // 1. Configurar Choices
    const filtroEl = document.getElementById('filtroDepartamentoMaestro');
    if (filtroEl) {
        const originalSelect = document.getElementById('filtroDepartamento');
        if(originalSelect && filtroEl.options.length <= 1) {
            filtroEl.innerHTML = originalSelect.innerHTML;
        }

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
            // 1. Capturamos el valor del nuevo filtro
            const metodoFiltro = document.getElementById('filtro-metodo-maestro').value;
            const deptosSeleccionados = choicesDeptoMaestro ? choicesDeptoMaestro.getValue(true) : [];

            return allData.filter((item) => {
                const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro;

                // 2. Evaluamos si coincide el método de pago.
                // Usamos == en lugar de === por si el item.MetodoPago viene como entero (0 o 1) y metodoFiltro como string ("0" o "1")
                const coincideMetodo = !metodoFiltro || item.MetodoPago == metodoFiltro;

                let coincideDepto = true;
                if (deptosSeleccionados.length > 0) {
                    const deptoFull = `${item.DepartamentoNombre}|${item.PlaceNombre || ''}`;
                    coincideDepto = deptosSeleccionados.includes(deptoFull);
                } else if (choicesDeptoMaestro && choicesDeptoMaestro.getValue(true).length === 0) {
                    coincideDepto = true;
                }

                // 3. Agregamos coincideMetodo a las condiciones de retorno
                if (!fechaFiltro) return coincideEstado && coincideDepto && coincideMetodo;
                const fechaItem = item.Fecha;
                if (filtrarPorMes) {
                    return fechaItem.slice(0, 7) === fechaFiltro.slice(0, 7) && coincideEstado && coincideDepto && coincideMetodo;
                }
                return fechaItem === fechaFiltro && coincideEstado && coincideDepto && coincideMetodo;
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

    // Spinner
    container.innerHTML = `
        <div class="flex flex-col items-center justify-center py-12">
            <p class="text-gray-500 font-medium animate-pulse">Cargando datos y archivos...</p>
        </div>`;

    try {
        // Peticiones
        const [dataSolicitud, dataOrden, dataProveedores, dataRazones] = await Promise.all([
            SendDataEnd(`api/solicitud/details/${idSolicitud}`),
            SendDataEnd(`api/orden-compra/details/${idSolicitud}`).catch(() => null),
            SendDataEnd(`api/providers/all`),
            SendDataEnd(`api/razonsocial/all`)
        ]);

        if (dataSolicitud.error) throw new Error(dataSolicitud.error);

        // -----------------------------------------------------------
        // CORRECCIÓN CRÍTICA: EXTRACCIÓN ROBUSTA DE LA ORDEN DE COMPRA
        // -----------------------------------------------------------
        let ordenObjeto = null;

        if (dataOrden && !dataOrden.error) {
            // Caso 1: La API devuelve un Array (ej. findAll) -> Tomamos el primero
            if (Array.isArray(dataOrden)) {
                ordenObjeto = dataOrden.length > 0 ? dataOrden[0] : null;
            }
            // Caso 2: La API devuelve un objeto envuelto en "OrdenCompra" (ej. format standar)
            else if (dataOrden.OrdenCompra) {
                ordenObjeto = Array.isArray(dataOrden.OrdenCompra) ? dataOrden.OrdenCompra[0] : dataOrden.OrdenCompra;
            }
            // Caso 3: La API devuelve el objeto directo
            else {
                ordenObjeto = dataOrden;
            }
        }

        // Inyectamos el objeto limpio (NO array) en la solicitud
        if (ordenObjeto) {
            console.log("Orden detectada y procesada:", ordenObjeto); // Para depuración
            dataSolicitud.OrdenCompra = ordenObjeto;

            // Inyectamos cotización si viene dentro de la orden
            if (ordenObjeto.cotizacion) {
                dataSolicitud.cotizacion = ordenObjeto.cotizacion;
            }
        }
        // -----------------------------------------------------------

        const normalize = (d) => Array.isArray(d) ? d : (d.data || d.messages || []);
        const listaProveedores = normalize(dataProveedores);
        const listaRazones = normalize(dataRazones);

        document.getElementById('maestro_id_solicitud').value = idSolicitud;

        // Renderizamos
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
 * RENDERIZADO DEL FORMULARIO CON CARGA DE ARCHIVOS VISUAL
 */

function renderizarInputsDios(data, container, listaProveedores = [], listaRazones = []) {
    const sol = data.solicitud || data;
    const orden = data.OrdenCompra || {};
    const coti = data.cotizacion || {};

    // 0. ESTADO VISUAL
    let estadoVisual = sol.Estado;
    if (sol.Estado === 'Aprobada' && orden && orden.Estado) {
        estadoVisual = orden.Estado;
    }

    // 1. REGLAS DE BLOQUEO
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
    const classArchivos      = 'grid';

    // ------------------------------------------------------------------------
    // CANDADO DE SEGURIDAD PARA FECHAS DE ORDEN
    // ------------------------------------------------------------------------
    // Verificamos si la orden realmente existe en la base de datos
    const existeOrden = (orden && orden.Estado) ? true : false;

    // Si no existe la orden o las reglas dicen que se bloquee, deshabilitamos
    const disabledFechasOrden = (!existeOrden || reglas.control) ? 'disabled' : '';

    // Clases dinámicas: Gris si está bloqueado, color original si está habilitado
    const classInputRefPago = (!existeOrden || reglas.control)
        ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 shadow-none'
        : 'bg-blue-50 border-blue-200';

    const classInputPagoReal = (!existeOrden || reglas.control)
        ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 shadow-none'
        : 'bg-green-50 border-green-200';
    // ------------------------------------------------------------------------

    // 2. PREPARACIÓN DE SELECT PROVEEDORES
    let idProveedorActual = sol.ID_Proveedor;
    if (!idProveedorActual && data.proveedor) idProveedorActual = data.proveedor.ID_Proveedor;

    let htmlProv = `<option value="" data-credito="0">-- Seleccionar --</option>`;
    listaProveedores.forEach(p => {
        const selected = (p.ID_Proveedor == idProveedorActual) ? 'selected' : '';
        const diasRaw = p.Dias_Credito !== undefined ? p.Dias_Credito : (p.dias_credito || 0);
        const dias = parseFloat(diasRaw);
        const tieneCredito = (dias > 0) ? '1' : '0';
        htmlProv += `<option value="${p.ID_Proveedor}" data-credito="${tieneCredito}" ${selected}>${p.RazonSocial}</option>`;
    });

    let idRazonActual = sol.ID_RazonSocial;
    let htmlRazon = `<option value="">-- Seleccionar Proyecto --</option>`;
    listaRazones.forEach(r => {
        const selected = (r.ID_RazonSocial == idRazonActual) ? 'selected' : '';
        htmlRazon += `<option value="${r.ID_RazonSocial}" ${selected}>${r.Nombre} (${r.RFC || ''})</option>`;
    });

    const tieneIva = (sol.IVA == 1 || sol.IVA === 't' || sol.IVA === true);
    const checkedIva = tieneIva ? 'checked' : '';
    const productos = Array.isArray(data.productos) ? data.productos : (Array.isArray(data.servicios) ? data.servicios : []);

    // VARIABLES DE FECHAS
    const fechaRegistroVista = sol.Fecha ? sol.Fecha.split(' ')[0] : '';
    const valorFechaAprobacion = orden.Fecha ? orden.Fecha.split(' ')[0] : '';
    const valorFechaRefPago = orden.FechaRefPago ? orden.FechaRefPago.split(' ')[0] : '';
    const valorFechaPagoReal = orden.FechaPagoRealizado ? orden.FechaPagoRealizado.split(' ')[0] : '';

    const baseInputClass = "mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-1.5 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-xs text-gray-700";
    const labelClass = "block text-xs font-bold text-gray-700 mb-1 uppercase tracking-wide";
    const sectionClass = "bg-white shadow rounded-lg p-6 border border-gray-200 mb-6";

    // Adjuntos
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

    container.innerHTML = `
    <div class="${sectionClass}">
        <div class="flex justify-between items-center border-b pb-2 mb-4">
            <h4 class="text-sm font-bold text-gray-800">Control del Sistema</h4>
            ${reglas.financiero ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded border border-yellow-200">🔒 Edición restringida por Estado</span>' : ''}
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label class="block text-xs font-bold text-red-600 mb-1">Estado</label>
                <select name="Estado" class="${baseInputClass} bg-red-50 border-red-300 text-red-900 font-bold">
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
                <select name="MetodoPago" id="select-metodo-pago" ${disabledControl} class="${baseInputClass} ${classControl}">
                    <option value="${sol.MetodoPago}" selected>Actual: ${sol.MetodoPago == 1 ? 'Crédito' : 'Contado'}</option>
                    <option value="0">Contado</option>
                    <option value="1">Crédito</option>
                </select>
            </div>
            <div>
                <label class="${labelClass}">Fecha Registro (Solicitud)</label>
                <div class="${baseInputClass} bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 shadow-none font-mono">
                    ${fechaRegistroVista || 'N/A'}
                </div>
                <input type="hidden" name="Fecha" value="${fechaRegistroVista}">
            </div>
            <div>
                <label class="${labelClass}">Fecha Aprobación (Orden)</label>
                <div class="${baseInputClass} bg-gray-100 text-gray-500 cursor-not-allowed border-gray-200 shadow-none font-mono">
                    ${valorFechaAprobacion || 'Aún no generada'}
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4 pt-4 border-t border-gray-100">
            <div>
                <label class="${labelClass} text-blue-700">Fecha Ref. Pago</label>
                <input type="date" name="FechaRefPago" value="${valorFechaRefPago}" ${disabledFechasOrden} class="${baseInputClass} ${classInputRefPago}">
            </div>
            <div>
                <label class="${labelClass} text-green-700">Fecha Pago Realizado</label>
                <input type="date" name="FechaPagoRealizado" value="${valorFechaPagoReal}" ${disabledFechasOrden} class="${baseInputClass} ${classInputPagoReal}">
            </div>
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

    <div class="${sectionClass}">
        <div class="flex justify-between items-end mb-4 border-b pb-2">
            <h4 class="text-sm font-bold text-gray-800">Detalle de Productos / Servicios</h4>
            <div class="text-right"><span class="text-xs font-bold text-gray-500">Total:</span> <span id="span-total-editor" class="text-sm font-bold text-gray-800"></span></div>
        </div>
        <table id="tabla-productos-editor" class="w-full text-xs text-left">
            <tbody class="divide-y">
                ${productos.map((prod, index) => `
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-2"><input type="hidden" name="productos[${index}][id]" value="${prod.ID_SolicitudProd || prod.ID_SolicitudServ || prod.ID_Detalle}"><input type="text" name="productos[${index}][nombre]" value="${prod.Nombre || ''}" ${disabledFinanciero} class="${baseInputClass} ${classFinanciero}"></td>
                    <td class="p-2 w-24"><input type="number" step="1.00" min="1" name="productos[${index}][cantidad]" value="${parseFloat(prod.Cantidad)||1}" ${disabledFinanciero} class="${baseInputClass} text-center input-cantidad ${classFinanciero}" oninput="calcularTotalesUI()"></td>
                    <td class="p-2 w-32"><input type="number" step="0.01" min="0" name="productos[${index}][precio]" value="${parseFloat(prod.Importe||prod.Precio)||0}" ${disabledFinanciero} class="${baseInputClass} text-right input-precio ${classFinanciero}" oninput="calcularTotalesUI()"></td>
                    <td class="p-2 text-right td-subtotal font-mono text-gray-500">$0.00</td>
                </tr>`).join('')}
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
        <div class="${classArchivos} grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-100">
            <div><label class="block text-xs font-bold text-green-600 mb-2">Cargar Cotización</label><div id="preview-cotizacion" class="hidden mb-2 p-2 border border-dashed rounded-lg bg-gray-50"></div><input type="file" name="cotizacion_files[]" id="file-cotizacion" class="hidden" accept="image/*,.pdf" multiple onchange="handleFileSelect(this, 'cotizacion')"><button type="button" onclick="document.getElementById('file-cotizacion').click()" class="w-full bg-white border border-green-300 text-green-600 hover:bg-green-50 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button></div>
            <div><label class="block text-xs font-bold text-blue-600 mb-2">Cargar Ficha De Pago</label><div id="preview-comprobante" class="hidden mb-2 p-2 border border-dashed rounded-lg bg-gray-50"></div><input type="file" name="File_Comprobante" id="file-comprobante" class="hidden" accept="image/*,.pdf,.xml" onchange="handleFileSelect(this, 'comprobante')"><button type="button" onclick="document.getElementById('file-comprobante').click()" class="w-full bg-white border border-blue-300 text-blue-600 hover:bg-blue-50 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button></div>
            <div><label class="block text-xs font-bold text-indigo-600 mb-2">Cargar Factura</label><div id="preview-factura" class="hidden mb-2 p-2 border border-dashed rounded-lg bg-gray-50"></div><input type="file" name="File_Factura" id="file-factura" class="hidden" accept="image/*,.pdf,.xml" onchange="handleFileSelect(this, 'factura')"><button type="button" onclick="document.getElementById('file-factura').click()" class="w-full bg-white border border-indigo-300 text-indigo-600 hover:bg-indigo-50 text-xs font-bold py-2 px-4 rounded shadow-sm">📂 Seleccionar</button></div>
        </div>
    </div>

    <input type="hidden" id="original-id-proveedor" value="${idProveedorActual}">
    <input type="hidden" id="flag-existe-cotizacion" value="${existeCotizacion}">
    <input type="hidden" id="flag-existe-ficha" value="${existeFicha}">
    <input type="hidden" id="flag-existe-factura" value="${existeFactura}">
    `;

    calcularTotalesUI();

    setTimeout(() => {
        window.validarCreditoProveedor();
    }, 100);
}

/**
 * FUNCIÓN GLOBAL: Validar si el proveedor seleccionado tiene crédito
 */
window.validarCreditoProveedor = function() {
    const selectProv = document.getElementById('select-proveedor-maestro');
    const selectMetodo = document.getElementById('select-metodo-pago');

    if (!selectProv || !selectMetodo) return;

    // Obtener la opción seleccionada
    const optionSelected = selectProv.options[selectProv.selectedIndex];

    // Validar con seguridad (getAttribute puede ser null si es placeholder)
    const dataCredito = optionSelected ? optionSelected.getAttribute('data-credito') : '0';
    const tieneCredito = (dataCredito === '1');

    // Buscar la opción de Crédito (value="1")
    const optionCredito = selectMetodo.querySelector('option[value="1"]');

    if (optionCredito) {
        if (tieneCredito) {
            // HABILITAR
            optionCredito.disabled = false;
            // Restaurar texto limpio
            optionCredito.textContent = "Crédito";
        } else {
            // BLOQUEAR
            optionCredito.disabled = true;
            optionCredito.textContent = "Crédito (No disponible)";

            // Si estaba seleccionado Crédito, lo pasamos a Contado
            if (selectMetodo.value === "1") {
                selectMetodo.value = "0";
            }
        }
    }
};

/**
 * HELPER PARA SELECCIÓN DE ARCHIVOS (Estilo pago.js)
 */
window.handleFileSelect = function(input, type) {
    const file = input.files[0];
    if (!file) {
        window.removeFile(type);
        return;
    }

    const previewContainer = document.getElementById(`preview-${type}`);
    // Determinar icono
    let icon = '📄';
    if (file.type.startsWith('image/')) icon = '🖼️';
    else if (file.type === 'application/pdf') icon = '📕';
    else if (file.type.includes('xml')) icon = '🔗';

    const fileSize = (file.size / 1024).toFixed(2) + ' KB';

    previewContainer.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">${icon}</span>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-gray-700 truncate w-32">${file.name}</p>
                    <p class="text-[10px] text-gray-500">${fileSize}</p>
                </div>
            </div>
            <button type="button" onclick="removeFile('${type}')" class="text-red-400 hover:text-red-600 font-bold ml-2">&times;</button> 
        </div>
    `;
    previewContainer.classList.remove('hidden');

    // Cambiar estilo del botón para indicar éxito
    const btn = input.nextElementSibling;
    if(btn) {
        btn.classList.remove('bg-white', 'text-blue-600', 'text-indigo-600');
        btn.classList.add('bg-green-50', 'text-green-700', 'border-green-300');
        btn.innerText = '✅ Archivo Listo';
    }
}

window.removeFile = function(type) {
    const input = document.getElementById(`file-${type}`);
    if(input) input.value = '';

    const previewContainer = document.getElementById(`preview-${type}`);
    if(previewContainer) {
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
    }

    // Restaurar botón
    const btn = input.nextElementSibling;
    if(btn) {
        btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-300');
        btn.classList.add('bg-white');
        if(type === 'comprobante') btn.classList.add('text-blue-600', 'border-blue-300');
        else btn.classList.add('text-indigo-600', 'border-indigo-300');

        btn.innerText = `📂 Seleccionar ${type.charAt(0).toUpperCase() + type.slice(1)}`;
    }
}

function calcularTotalesUI() {
    const filas = document.querySelectorAll('#tabla-productos-editor tbody tr');
    const chkIva = document.getElementById('chk_iva_maestro');
    const spanTotal = document.getElementById('span-total-editor');

    let subtotalGlobal = 0;

    filas.forEach(fila => {
        const inputCant = fila.querySelector('.input-cantidad');
        const inputPrecio = fila.querySelector('.input-precio');
        const tdSubtotal = fila.querySelector('.td-subtotal');

        if (inputCant && inputPrecio) {
            const cant = parseFloat(inputCant.value) || 0;
            const precio = parseFloat(inputPrecio.value) || 0;
            const subtotalFila = cant * precio;

            subtotalGlobal += subtotalFila;
            if(tdSubtotal) {
                tdSubtotal.innerText = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(subtotalFila);
            }
        }
    });

    let totalFinal = subtotalGlobal;
    if (chkIva && chkIva.checked) {
        totalFinal = subtotalGlobal * 1.16;
    }

    if (spanTotal) {
        spanTotal.innerText = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(totalFinal);
    }
}

/**
 * ACCIÓN DE GUARDADO CON VALIDACIONES DE NIVEL
 */
async function guardarCambiosMaestros() {
    const form = document.getElementById('form-editor-maestro');
    const formData = new FormData(form);
    const idSolicitud = document.getElementById('maestro_id_solicitud').value;

    // --- MAPA DE NIVELES (Para lógica de validación) ---
    const NIVELES = {
        'En espera': 1, 'Cotizando': 2, 'En revision': 3, 'Aprobada': 4,
        'Espera_Programacion': 5, 'Programada': 6, 'Por Pagar': 7, 'Pagada': 8
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

    const tieneCotizacionBD = elCoti ? elCoti.value === '1' : false;
    const tieneFichaBD      = elFicha ? elFicha.value === '1' : false;
    const tieneFacturaBD    = elFactu ? elFactu.value === '1' : false;

    // Archivos nuevos
    const inputCoti = document.getElementById('file-cotizacion');
    const inputFicha = document.getElementById('file-comprobante');
    const inputFactura = document.getElementById('file-factura');

    const subiendoCoti = inputCoti && inputCoti.files.length > 0;
    const subiendoFicha = inputFicha && inputFicha.files.length > 0;
    const subiendoFactura = inputFactura && inputFactura.files.length > 0;

    const tieneCoti = tieneCotizacionBD || subiendoCoti;
    const tieneFicha = tieneFichaBD || subiendoFicha;
    const tieneFactura = tieneFacturaBD || subiendoFactura;

    // ========================================================================
    // REGLA 1: CAMBIO DE PROVEEDOR (Prioridad Alta)
    // ========================================================================
    const originalProveedorID = document.getElementById('original-id-proveedor').value;
    const nuevoProveedorID = selectProveedor.value;

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

    // Validación de Proveedor para niveles que generan Orden (Nivel 4+)
    const estadosConOrden = ['Aprobada', 'Espera_Programacion', 'Programada', 'Por Pagar', 'Pagada'];
    if (estadosConOrden.includes(estadoSeleccionado)) {
        if (!selectProveedor || !selectProveedor.value) {
            errorValidacion = `El estado "${estadoSeleccionado}" requiere un Proveedor asignado para la Orden de Compra.`;
        }
    }

    if (errorValidacion) {
        alert(`⚠️ REQUISITO NO CUMPLIDO\n\n${errorValidacion}`);
        return;
    }

    // Confirmación final
    if(!confirm(`¿Confirmar cambios y establecer estado como: ${estadoSeleccionado}?`)) return;

    // ENVÍO
    const btnGuardar = document.querySelector('#div-editor-maestro button.bg-blue-600');
    if(btnGuardar) { btnGuardar.disabled = true; btnGuardar.innerText = "Guardando..."; }

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
        if(btnGuardar) { btnGuardar.disabled = false; btnGuardar.innerText = "Guardar Cambios"; }
    }
}

function regresarMaestro() {
    document.getElementById('div-editor-maestro').classList.add('hidden');
    document.getElementById('div-control-maestro').classList.remove('hidden');
}

// FUNCIONES GLOBALES PARA ARCHIVOS (Necesarias para el onclick en el HTML generado)
window.handleFileSelect = function(input, type) {
    const files = input.files;
    const previewId = 'preview-' + type; // ID dinámico corregido
    const previewContainer = document.getElementById(previewId);

    if (!files || files.length === 0) {
        window.removeFile(type);
        return;
    }

    let html = "";
    // Iteración para soportar múltiples archivos (especialmente para cotizaciones)
    Array.from(files).forEach(file => {
        let icon = '📄';
        if (file.type.startsWith('image/')) icon = '🖼️';
        else if (file.type === 'application/pdf') icon = '📕';
        else if (file.type.includes('xml')) icon = '🔗';

        const fileSize = (file.size / 1024).toFixed(2) + ' KB';

        html += `
            <div class="flex items-center justify-between bg-white p-2 mb-1 rounded border border-gray-200 shadow-sm">
                <div class="flex items-center gap-2 overflow-hidden">
                    <span class="text-xl">${icon}</span>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-gray-700 truncate w-40">${file.name}</p>
                        <p class="text-[10px] text-gray-500">${fileSize}</p>
                    </div>
                </div>
                <button type="button" onclick="window.removeFile('${type}')" class="text-red-400 hover:text-red-600 font-bold ml-2 text-lg">&times;</button> 
            </div>`;
    });

    previewContainer.innerHTML = html;
    previewContainer.classList.remove('hidden');

    const btn = input.nextElementSibling;
    if(btn) {
        btn.classList.remove('bg-white', 'text-blue-600', 'text-indigo-600', 'text-green-600', 'border-blue-300', 'border-indigo-300', 'border-green-300');
        btn.classList.add('bg-green-50', 'text-green-700', 'border-green-400');
        btn.innerHTML = files.length > 1 ? `✅ ${files.length} Archivos Listos` : '✅ Archivo Listo';
    }
}

window.removeFile = function(type) {
    const inputIds = { 'cotizacion': 'file-cotizacion', 'comprobante': 'file-comprobante', 'factura': 'file-factura' };
    const input = document.getElementById(inputIds[type]);
    if(input) input.value = '';

    const previewContainer = document.getElementById('preview-' + type);
    if(previewContainer) {
        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
    }

    const btn = input ? input.nextElementSibling : null;
    if(btn) {
        btn.classList.remove('bg-green-50', 'text-green-700', 'border-green-400');
        btn.classList.add('bg-white');
        if(type === 'cotizacion') { btn.classList.add('text-green-600', 'border-green-300'); btn.innerHTML = '📂 Seleccionar Cotizaciones'; }
        else if(type === 'comprobante') { btn.classList.add('text-blue-600', 'border-blue-300'); btn.innerHTML = '📂 Seleccionar Comprobante'; }
        else { btn.classList.add('text-indigo-600', 'border-indigo-300'); btn.innerHTML = '📂 Seleccionar Factura'; }
    }
}