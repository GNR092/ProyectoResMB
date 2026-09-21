const { Calendar } = FullCalendar;
const esLocale = 'es';

const COLORS = [
    { value: '#FF5722', label: 'Naranja' },
    { value: '#FFC107', label: 'Ámbar' },
    { value: '#8BC34A', label: 'Lima' },
    { value: '#009688', label: 'Verde azulado' },
    { value: '#2196F3', label: 'Azul' },
    { value: '#9C27B0', label: 'Índigo' },
    { value: '#E91E63', label: 'Rosa' },
    { value: '#00BCD4', label: 'Cyan' },
    { value: '#FF9800', label: 'Naranja intenso' },
    { value: '#4CAF50', label: 'Verde' },
    { value: '#3F51B5', label: 'Índigo azulado' },
    { value: '#795548', label: 'Marrón' },
];

function calendarioApp() {
    let calendar = null;
    let selectedEvent = null;
    const randomColor = () => COLORS[Math.floor(Math.random() * COLORS.length)].value;
    const toLocalStr = (d) => {
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
    };
    const normalizeToLocalInput = (str) => {
        if (!str) return '';
        if (str.includes('Z') || /[+-]\d{2}:?\d{2}$/.test(str)) {
            const d = new Date(str);
            if (!isNaN(d)) return toLocalStr(d);
        }
        return str.substring(0, 19);
    };

    return {
        eventModalMode: 'create',
        eventForm: {
            id: '',
            title: '',
            start: '',
            end: '',
            color: '#FF5722',
            ID_Solicitud: '',
        },
        showEventModal: false,
        showDetalle: false,
        isWeekView: false,
        isListView: false,
        filtros: {
            folio: '',
            estado: '',
            tipo: '',
            fecha: '',
            por_mes: false,
        },
        solicitudesCache: [],
        colors: COLORS,

        async init() {
            this.renderCalendar();
            this.setupResponsiveView();
            await this.cargarSolicitudes();
        },

        async cargarSolicitudes() {
            try {
                const params = new URLSearchParams();
                if (this.filtros.folio) params.set('folio', this.filtros.folio);
                if (this.filtros.estado) params.set('estado', this.filtros.estado);
                if (this.filtros.tipo) params.set('tipo', this.filtros.tipo);
                if (this.filtros.fecha) {
                    params.set('fecha', this.filtros.fecha);
                    if (this.filtros.por_mes) params.set('por_mes', '1');
                }
                params.set('per_page', '50');
                const qs = params.toString() ? `?${params.toString()}` : '';
                const res = await fetch(`${BASE_URL}api/calendario/solicitudes${qs}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (json.success) {
                    this.solicitudesCache = json.data || [];
                }
            } catch (e) {
                console.error('cargarSolicitudes', e);
            }
        },

        async filtrarSolicitudes() {
            await this.cargarSolicitudes();
        },

        limpiarFiltros() {
            this.filtros = { folio: '', estado: '', tipo: '', fecha: '', por_mes: false };
            this.cargarSolicitudes();
        },

        setupResponsiveView() {
            const checkMobile = () => {
                if (calendar) {
                    const isMobile = window.innerWidth < 640;
                    const currentView = calendar.view.type;
                    if (isMobile && currentView !== 'listWeek') {
                        calendar.changeView('listWeek');
                    } else if (!isMobile && currentView === 'listWeek') {
                        calendar.changeView('timeGridWeek');
                    }
                }
            };
            checkMobile();
            window.addEventListener('resize', checkMobile);
        },

        renderCalendar() {
            const el = document.getElementById('calendar');
            calendar = new Calendar(el, {
                initialView: 'timeGridWeek',
                locale: esLocale,
                height: '100%',
                themeSystem: 'standard',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                slotDuration: '00:30:00',
                slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                allDaySlot: false,
                navLinks: true,
                editable: true,
                selectable: true,
                selectMirror: true,
                selectMinDistance: 5,
                unselectAuto: false,
                dayMaxEvents: true,
                events: (fetchInfo, successCallback, failureCallback) => {
                    this.loadEvents(fetchInfo.startStr, fetchInfo.endStr)
                        .then(successCallback)
                        .catch(failureCallback);
                },
                eventClick: (info) => this.openEventModal(info.event),
                select: (info) => this.handleSelect(info),
                dateClick: (info) => this.handleDateClick(info),
                eventDrop: (info) => this.moveEvent(info.event),
                eventResize: (info) => this.moveEvent(info.event),
                eventDidMount: (info) => this.styleEvent(info),
                datesSet: (info) => {
                    this.isWeekView = info.view.type === 'timeGridWeek';
                    this.isListView = info.view.type === 'listWeek';
                    this.applyCarbonTheme();
                },
            });
            calendar.render();
            this.applyCarbonTheme();
        },

        handleSelect(info) {
            const viewType = calendar.view.type;
            if (viewType === 'dayGridMonth') {
                calendar.changeView('timeGridDay', info.startStr);
                calendar.unselect();
                return;
            }
            if (viewType === 'listWeek') {
                calendar.unselect();
                return;
            }
            this.openEventModal(null, { startStr: toLocalStr(info.start), endStr: toLocalStr(info.end) });
        },

        handleDateClick(info) {
            const viewType = calendar.view.type;
            if (viewType === 'dayGridMonth') {
                calendar.changeView('timeGridDay', info.dateStr);
                return;
            }
            if (viewType === 'listWeek') {
                const start = info.date;
                const end = new Date(start.getTime() + 30 * 60 * 1000);
                this.openEventModal(null, { startStr: toLocalStr(start), endStr: toLocalStr(end) });
                return;
            }
            if (viewType === 'timeGridWeek' || viewType === 'timeGridDay') {
                const start = info.date;
                const end = new Date(start.getTime() + 30 * 60 * 1000);
                this.openEventModal(null, { startStr: toLocalStr(start), endStr: toLocalStr(end) });
                return;
            }
        },

        applyCarbonTheme() {
            if (!calendar) return;
            const el = calendar.el;
            if (!el) return;

            el.querySelectorAll('.fc-toolbar').forEach(tb => {
                tb.classList.add('bg-carbon-800', 'text-carbon-200', 'rounded-t-xl', 'px-4', 'py-3');
                tb.querySelectorAll('.fc-button').forEach(btn => {
                    btn.classList.remove('fc-button-primary');
                    btn.classList.add('bg-carbon-700', 'hover:bg-carbon-600', 'text-carbon-100', 'border-0', 'rounded-lg', 'px-3', 'py-1.5', 'text-sm', 'font-medium', 'transition-colors');
                });
                const todayBtn = tb.querySelector('.fc-today-button');
                if (todayBtn) {
                    todayBtn.classList.remove('bg-carbon-700', 'hover:bg-carbon-600');
                    todayBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700', 'text-white');
                }
            });

            el.querySelectorAll('.fc-col-header-cell').forEach(cell => {
                cell.classList.add('bg-carbon-50', 'text-carbon-700', 'font-semibold', 'py-2', 'border-b', 'border-carbon-200');
            });

            el.querySelectorAll('.fc-timegrid-slot-label').forEach(label => {
                label.classList.add('text-carbon-500', 'text-xs', 'font-medium');
            });

            el.querySelectorAll('.fc-timegrid-slot').forEach(slot => {
                slot.classList.add('border-carbon-100');
            });

            el.querySelectorAll('.fc-daygrid-day').forEach(day => {
                day.classList.add('border-carbon-100');
            });

            el.querySelector('.fc-scrollgrid')?.classList.add('border-carbon-100');
        },

        styleEvent(info) {
            const color = info.event.extendedProps.color || info.event.backgroundColor || '#FF5722';
            info.el.style.backgroundColor = color;
            info.el.style.borderColor = color;
            info.el.style.borderRadius = '6px';
            info.el.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            info.el.classList.add('fc-event-custom');
            // Tooltip con folio/estado si hay espacio
            const folio = info.event.extendedProps.No_Folio;
            const estado = info.event.extendedProps.EstadoSolicitud;
            if ((folio || estado) && info.el.offsetWidth > 80) {
                const tip = [folio, estado].filter(Boolean).join(' — ');
                info.el.setAttribute('title', `${info.event.title} — ${tip}`);
            }
        },

        async loadEvents(start, end) {
            const res = await fetch(`${BASE_URL}api/calendario/eventos?start=${start}&end=${end}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Error cargando eventos');
            return json.data;
        },

        openEventModal(event, selectInfo = null) {
            this.eventModalMode = event ? 'edit' : 'create';
            selectedEvent = event;

            if (event) {
                const rawTitle = event.extendedProps.evento_raw || event.title;
                // Si title ya contiene folio, extraer raw
                const titleClean = rawTitle.includes(' — ') ? rawTitle.split(' — ')[0] : rawTitle;
                this.eventForm = {
                    id: event.id,
                    title: titleClean,
                    start: normalizeToLocalInput(event.startStr),
                    end: normalizeToLocalInput(event.endStr || event.startStr),
                    color: event.extendedProps.color || '#FF5722',
                    ID_Solicitud: event.extendedProps.ID_Solicitud ? String(event.extendedProps.ID_Solicitud) : '',
                };
                // Asegurar que la solicitud del evento esté en cache (por si filtros la ocultaron)
                if (this.eventForm.ID_Solicitud && !this.solicitudesCache.find(s => String(s.ID_Solicitud) === String(this.eventForm.ID_Solicitud))) {
                    const folio = event.extendedProps.No_Folio || '';
                    this.solicitudesCache.unshift({
                        ID_Solicitud: this.eventForm.ID_Solicitud,
                        No_Folio: folio || `ID ${this.eventForm.ID_Solicitud}`,
                        Estado: event.extendedProps.EstadoSolicitud || '',
                        Fecha: ''
                    });
                }
            } else {
                const startStr = normalizeToLocalInput(selectInfo ? selectInfo.startStr : '');
                const endStr = normalizeToLocalInput(selectInfo ? (selectInfo.endStr || selectInfo.startStr) : '');
                this.eventForm = {
                    id: '',
                    title: '',
                    start: startStr,
                    end: endStr,
                    color: randomColor(),
                    ID_Solicitud: '',
                };
            }

            this.showEventModal = true;
            this.$nextTick(() => {
                this.$refs.titleInput?.focus();
                document.body.style.overflow = 'hidden';
            });
        },

        prevWeek() { if (calendar) calendar.prev(); },
        nextWeek() { if (calendar) calendar.next(); },

        closeEventModal() {
            this.showEventModal = false;
            this.eventForm = { id: '', title: '', start: '', end: '', color: randomColor(), ID_Solicitud: '' };
            document.body.style.overflow = '';
            if (calendar) calendar.unselect();
        },

        async verDetalleDesdeModal() {
            const id = this.eventForm.ID_Solicitud;
            if (!id) return;
            this.closeEventModal();
            await this.verDetalleSolicitud(id);
        },

        async verDetalleSolicitud(idSolicitud) {
            if (!idSolicitud) idSolicitud = this.eventForm.ID_Solicitud;
            if (!idSolicitud) return;
            this.showDetalle = true;
            const container = document.getElementById('detalles-calendario-solicitud');
            if (container) container.innerHTML = '<p class="text-center text-gray-500 py-8">Cargando detalles...</p>';
            // Ocultar calendar se hace via x-show
            setTimeout(() => { if (calendar) calendar.updateSize(); }, 50);
            try {
                const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`);
                let html = '';
                if (typeof generarDetallesSolicitudHTML === 'function') html += generarDetallesSolicitudHTML(data);
                if (typeof generarComentariosHtml === 'function') html += generarComentariosHtml(data);
                if (typeof generarProductosServiciosHTML === 'function') html += generarProductosServiciosHTML(data);
                if (data.ComentariosUser) {
                    html += `<div class="mt-6 p-4 border rounded-lg bg-gray-100"><h4 class="text-md font-bold text-gray-700 mb-2">Comentarios del solicitante</h4><p class="text-gray-800 whitespace-pre-wrap">${data.ComentariosUser}</p></div>`;
                }
                if (typeof generarSeccionAdjuntos === 'function') html += generarSeccionAdjuntos(data);
                if (container) container.innerHTML = html || '<p class="text-red-500">Sin datos</p>';
            } catch (e) {
                if (container) container.innerHTML = `<p class="text-red-500">Error cargando detalles: ${e.message || e}</p>`;
            }
        },

        regresarCalendario() {
            this.showDetalle = false;
            const container = document.getElementById('detalles-calendario-solicitud');
            if (container) container.innerHTML = '';
            this.$nextTick(() => { if (calendar) { calendar.updateSize(); calendar.render(); } });
        },

        async saveEvent() {
            if (!this.eventForm.ID_Solicitud) {
                alert('Debe seleccionar una requisición vinculada');
                return;
            }
            const url = this.eventModalMode === 'create'
                ? `${BASE_URL}api/calendario/eventos`
                : `${BASE_URL}api/calendario/eventos/${this.eventForm.id}`;
            const method = this.eventModalMode === 'create' ? 'POST' : 'PUT';

            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token-name"]')?.content || '',
                },
                body: JSON.stringify({
                    evento: this.eventForm.title,
                    fecha_inicio: this.eventForm.start.replace('T', ' '),
                    fecha_fin: this.eventForm.end.replace('T', ' '),
                    color_evento: this.eventForm.color,
                    ID_Solicitud: this.eventForm.ID_Solicitud,
                }),
            });
            const json = await res.json();
            if (!json.success) {
                const msg = json.messages ? JSON.stringify(json.messages) : (json.message || 'Error guardando');
                alert(msg);
                return;
            }
            if (this.eventModalMode === 'create') {
                calendar.addEvent(json.data, true);
            } else {
                selectedEvent.setProp('title', json.data.title);
                selectedEvent.setProp('backgroundColor', json.data.backgroundColor);
                selectedEvent.setProp('borderColor', json.data.borderColor);
                selectedEvent.setExtendedProp('ID_Solicitud', json.data.extendedProps.ID_Solicitud);
                selectedEvent.setExtendedProp('No_Folio', json.data.extendedProps.No_Folio);
                selectedEvent.setExtendedProp('EstadoSolicitud', json.data.extendedProps.EstadoSolicitud);
                selectedEvent.setExtendedProp('evento_raw', json.data.extendedProps.evento_raw);
                selectedEvent.setExtendedProp('color', json.data.extendedProps.color);
                selectedEvent.setStart(json.data.start);
                selectedEvent.setEnd(json.data.end);
            }
            this.closeEventModal();
        },

        async moveEvent(event) {
            const toLocal = (d) => toLocalStr(d).replace('T', ' ');
            const startLocal = event.start ? toLocal(event.start) : normalizeToLocalInput(event.startStr).replace('T', ' ');
            const endLocal = event.end ? toLocal(event.end) : normalizeToLocalInput(event.endStr || event.startStr).replace('T', ' ');
            const res = await fetch(`${BASE_URL}api/calendario/eventos/${event.id}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token-name"]')?.content || '',
                },
                body: JSON.stringify({
                    start: startLocal,
                    end: endLocal,
                }),
            });
            const json = await res.json();
            if (!json.success) {
                event.revert();
                alert(json.message || 'Error moviendo evento');
            }
        },

        async deleteEvent() {
            if (!confirm('¿Eliminar este evento?')) return;
            const res = await fetch(`${BASE_URL}api/calendario/eventos/${this.eventForm.id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token-name"]')?.content || '',
                },
            });
            const json = await res.json();
            if (json.success) {
                selectedEvent.remove();
                this.closeEventModal();
            } else {
                alert(json.message || 'Error eliminando');
            }
        },
    };
}

window.calendarioApp = calendarioApp;
