let choicesDeptoMaestro = null;

function initControlMaestro() {
    const tabla = document.getElementById('tabla-maestro');
    if (!tabla) return;

    // 1. Configurar Choices (Igual que historial)
    const filtroEl = document.getElementById('filtroDepartamentoMaestro');
    if (filtroEl) {
        const originalSelect = document.getElementById('filtroDepartamento');
        if(originalSelect && filtroEl.options.length <= 1) {
            filtroEl.innerHTML = originalSelect.innerHTML;
        }

        choicesDeptoMaestro = new Choices(filtroEl, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Todos los departamentos',
            itemSelectText: '',
            noResultsText: 'No se encontraron resultados',
        });
        document.getElementById('wrapper-depto-maestro').classList.remove('hidden');
    }

    // 2. Determinar URL (Igual que historial)
    let urlEndpoint = 'api/historic';
    const exceptions = ['Compras', 'Administración', 'Direccion', 'Tesoreria', 'Direccion Campus', 'Contaduría'];
    if (typeof USER_DEPT_NAME !== 'undefined' && USER_DEPT_ID && !exceptions.includes(USER_DEPT_NAME)) {
        urlEndpoint = `api/historic/department/${USER_DEPT_ID}`;
    }

    // 3. INICIALIZAR TABLA (AQUÍ ESTABA EL DETALLE)
    createPaginatedTable({
        // CORRECCIÓN: Apuntamos al TBODY para no borrar el header
        tableSelector: '#tabla-maestro tbody',
        paginationSelector: 'paginacion-maestro',
        endpoint: urlEndpoint,
        filterFormSelector: '#filtros-maestro-container', // El contenedor de inputs

        renderRow: (item) => {
            // Helpers existentes en tu sistema
            const status = typeof getStatusText === 'function' ? getStatusText(item.Estado) : item.Estado;
            const svg = typeof getStatusSVG === 'function' ? getStatusSVG(item.Estado) : '';
            const metodo = typeof getMetodoPago === 'function' ? getMetodoPago(item.MetodoPago) : item.MetodoPago;

            const totalRaw = parseFloat(item.MontoTotal) || 0;
            const montoFormateado = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(totalRaw);

            // Renderizado EXACTO a tu historial, solo cambia el botón final
            return `
            <tr class="text-center hover:bg-gray-50 transition border-b border-gray-100">
                <td class="hidden border-r px-4 py-2">${item.ID_Solicitud}</td>
                <td class="border-r px-4 py-2 font-medium text-gray-800">${item.No_Folio || 'N/A'}</td>
                <td class="border-r px-4 py-2 text-sm text-gray-600">${item.Fecha}</td>
                <td class="border-r px-4 py-2 text-xs text-gray-500">${item.DepartamentoNombre || ''}</td>
                <td class="border-r px-4 py-2 text-sm text-left px-4">${item.ProveedorNombre || 'N/A'}</td>
                <td class="border-r px-4 py-2 font-bold text-gray-800">${totalRaw > 0 ? montoFormateado : '$0.00'}</td>
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
            const deptosSeleccionados = choicesDeptoMaestro ? choicesDeptoMaestro.getValue(true) : [];

            return allData.filter((item) => {
                const coincideEstado = !estadoFiltro || item.Estado === estadoFiltro;

                let coincideDepto = true;
                if (deptosSeleccionados.length > 0) {
                    const deptoFull = `${item.DepartamentoNombre}|${item.PlaceNombre || ''}`;
                    coincideDepto = deptosSeleccionados.includes(deptoFull);
                } else if (choicesDeptoMaestro && choicesDeptoMaestro.getValue(true).length === 0) {
                    coincideDepto = true;
                }

                if (!fechaFiltro) return coincideEstado && coincideDepto;

                const fechaItem = item.Fecha;
                if (filtrarPorMes) {
                    return fechaItem.slice(0, 7) === fechaFiltro.slice(0, 7) && coincideEstado && coincideDepto;
                }
                return fechaItem === fechaFiltro && coincideEstado && coincideDepto;
            });
        }
    });
}

/**
 * CARGA Y RENDERIZA EL EDITOR
 */
async function cargarEditorMaestro(idSolicitud, folio) {
    document.getElementById('div-control-maestro').classList.add('hidden');
    document.getElementById('div-editor-maestro').classList.remove('hidden');
    document.getElementById('titulo-editor').innerText = `Editando: ${folio || 'ID '+idSolicitud}`;

    const container = document.getElementById('contenido-editor-maestro');
    container.innerHTML = '<div class="text-center py-10 text-gray-500">Cargando datos completos...</div>';

    try {
        const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`);
        if (data.error) throw new Error(data.error);

        document.getElementById('maestro_id_solicitud').value = idSolicitud;
        renderizarInputsDios(data, container);

    } catch (error) {
        console.error(error);
        container.innerHTML = `<div class="text-center text-red-500 py-10">Error al cargar: ${error.message}</div>`;
    }
}

/**
 * CONSTRUYE LOS INPUTS (Esta es la parte visual del formulario)
 */

function renderizarInputsDios(data, container) {
    const sol = data.solicitud || data;
    const prov = data.proveedor || {};
    const cot = data.cotizacion || {};

    // Estilos de inputs (Gris/Blanco estándar)
    const inputClass = "mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm";
    const labelClass = "block text-sm font-medium text-gray-700";

    // 1. Intentamos generar la vista previa de archivos usando tu función existente
    let htmlAdjuntosExistentes = '';
    if (typeof generarSeccionAdjuntos === 'function') {
        try {
            // Aseguramos compatibilidad de IDs por si tu función lo requiere
            if(!data.ID_Solicitud && sol.ID_Solicitud) data.ID_Solicitud = sol.ID_Solicitud;

            htmlAdjuntosExistentes = generarSeccionAdjuntos(data);
        } catch (e) {
            console.warn("Error al generar sección adjuntos:", e);
            htmlAdjuntosExistentes = '<p class="text-xs text-red-500">No se pudo cargar la vista previa de archivos.</p>';
        }
    }

    container.innerHTML = `
    <div class="bg-white shadow rounded-lg p-6 border border-gray-200 mb-6">
        <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Datos de Control</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-bold text-red-600">Estado del Sistema</label>
                <select name="Estado" class="${inputClass} bg-red-50 border-red-300 text-red-900">
                    <option value="${sol.Estado}" selected class="font-bold">➡ ${sol.Estado} (Actual)</option>
                    <option value="En espera">En espera</option>
                    <option value="Aprobada">Aprobada</option>
                    <option value="Rechazada">Rechazada</option>
                    <option value="Cotizando">Cotizando</option>
                    <option value="Aprobacion pendiente">Aprobación Pendiente</option>
                    <option value="En revision">En revisión</option>
                    <option value="Espera_Programacion">Espera Programación</option>
                    <option value="Programada">Programada</option>
                    <option value="Por Pagar">Por Pagar</option>
                    <option value="Pagada">Pagada</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Cambio directo sin notificaciones.</p>
            </div>
            <div>
                <label class="${labelClass}">Método de Pago</label>
                <select name="MetodoPago" class="${inputClass}">
                    <option value="${sol.MetodoPago}" selected>Conservar (${sol.MetodoPago})</option>
                    <option value="0">Contado (0)</option>
                    <option value="1">Crédito (1)</option>
                    <option value="9">En Espera (9)</option>
                </select>
            </div>
            <div>
                <label class="${labelClass}">Fecha Registro</label>
                <input type="date" name="Fecha" value="${sol.Fecha ? sol.Fecha.split(' ')[0] : ''}" class="${inputClass}">
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 border border-gray-200 mb-6">
        <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Información y Costos</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="${labelClass}">Proveedor</label>
                <input type="text" name="ProveedorNombre" value="${prov.RazonSocial || sol.ProveedorNombre || ''}" class="${inputClass}">
            </div>
            <div>
                <label class="${labelClass}">Monto Total ($)</label>
                <input type="number" step="0.01" name="MontoTotal" value="${cot.Total || sol.MontoTotal || 0}" class="${inputClass} font-bold text-gray-800">
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 border border-gray-200 mb-6">
        <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Archivos Adjuntos Actuales</h4>
        <div class="p-2 bg-gray-50 rounded border border-gray-100">
            ${htmlAdjuntosExistentes || '<p class="text-sm text-gray-400 italic">No hay adjuntos visualizables mediante la función estándar.</p>'}
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
        <h4 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Reemplazar / Subir Archivos</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-dashed border-gray-300 p-4 rounded bg-gray-50 hover:bg-gray-100 transition">
                <p class="font-bold text-sm text-gray-700 mb-2">Comprobante de Pago</p>
                <input type="file" name="File_Comprobante" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-[10px] text-gray-400 mt-1">Subir aquí reemplazará el archivo actual.</p>
            </div>
            <div class="border border-dashed border-gray-300 p-4 rounded bg-gray-50 hover:bg-gray-100 transition">
                <p class="font-bold text-sm text-gray-700 mb-2">Factura (PDF/XML)</p>
                <input type="file" name="File_Factura" class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-[10px] text-gray-400 mt-1">Subir aquí reemplazará el archivo actual.</p>
            </div>
        </div>
    </div>
    `;
}

/**
 * ACCIÓN DE GUARDADO (SILENCIOSO)
 */
async function guardarCambiosMaestros() {
    const form = document.getElementById('form-editor-maestro');
    const formData = new FormData(form);
    const idSolicitud = document.getElementById('maestro_id_solicitud').value;

    // LA CLAVE: Bandera silenciosa
    formData.append('silent_mode', 'true');

    if(!confirm('¿Confirmar cambios directos? No se notificará a nadie.')) return;

    try {
        const result = await SendDataEnd(`api/solicitudes/update_master/${idSolicitud}`, {
            method: 'POST',
            body: formData
        });

        if (result.success) {
            alert('Datos actualizados correctamente.');
            regresarMaestro();
            initControlMaestro(); // Recargar tabla
        } else {
            alert('Error: ' + (result.message || 'Desconocido'));
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión');
    }
}

function regresarMaestro() {
    document.getElementById('div-editor-maestro').classList.add('hidden');
    document.getElementById('div-control-maestro').classList.remove('hidden');
}