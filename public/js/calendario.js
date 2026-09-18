const { Calendar } = FullCalendar;
const esLocale = 'es';

const COLORS = [
    { value: '#FF5722', label: 'Naranja' },
    { value: '#FFC107', label: 'Ámbar' },
    { value: '#8BC34A', label: 'Lima' },
    { value: '#009688', label: 'Verde azulado' },
    { value: '#2196F3', label: 'Azul' },
    { value: '#9C27B0', label: 'Índigo' },
];

function calendarioApp() {
    let calendar = null;
    let selectedEvent = null;

    return {
        eventModalMode: 'create',
        eventForm: {
            id: '',
            title: '',
            start: '',
            end: '',
            color: '#FF5722',
        },
        showEventModal: false,
        colors: COLORS,

        async init() {
            this.renderCalendar();
            this.setupResponsiveView();
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
                editable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                events: (fetchInfo, successCallback, failureCallback) => {
                    this.loadEvents(fetchInfo.startStr, fetchInfo.endStr)
                        .then(successCallback)
                        .catch(failureCallback);
                },
                eventClick: (info) => this.openEventModal(info.event),
                select: (info) => this.openEventModal(null, info),
                eventDrop: (info) => this.moveEvent(info.event),
                eventResize: (info) => this.moveEvent(info.event),
                eventDidMount: (info) => this.styleEvent(info),
                datesSet: () => this.applyCarbonTheme(),
            });
            calendar.render();
            this.applyCarbonTheme();
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
            const color = info.event.extendedProps.color || '#FF5722';
            info.el.style.backgroundColor = color;
            info.el.style.borderColor = color;
            info.el.style.borderRadius = '6px';
            info.el.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            info.el.classList.add('fc-event-custom');
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
                this.eventForm = {
                    id: event.id,
                    title: event.title,
                    start: event.startStr,
                    end: event.endStr || event.startStr,
                    color: event.extendedProps.color || '#FF5722',
                };
            } else {
                const startStr = selectInfo ? selectInfo.startStr : '';
                const endStr = selectInfo ? (selectInfo.endStr || selectInfo.startStr) : '';
                this.eventForm = {
                    id: '',
                    title: '',
                    start: startStr,
                    end: endStr,
                    color: '#FF5722',
                };
            }

            this.showEventModal = true;
            this.$nextTick(() => {
                this.$refs.titleInput?.focus();
                document.body.style.overflow = 'hidden';
            });
        },

        closeEventModal() {
            this.showEventModal = false;
            this.eventForm = { id: '', title: '', start: '', end: '', color: '#FF5722' };
            document.body.style.overflow = '';
        },

        async saveEvent() {
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
                }),
            });
            const json = await res.json();
            if (!json.success) {
                alert(json.message || 'Error guardando');
                return;
            }
            if (this.eventModalMode === 'create') {
                calendar.addEvent(json.data);
            } else {
                selectedEvent.setProp('title', json.data.title);
                selectedEvent.setProp('backgroundColor', json.data.backgroundColor);
                selectedEvent.setProp('borderColor', json.data.borderColor);
                selectedEvent.setStart(json.data.start);
                selectedEvent.setEnd(json.data.end);
            }
            this.closeEventModal();
        },

        async moveEvent(event) {
            const res = await fetch(`${BASE_URL}api/calendario/eventos/${event.id}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token-name"]')?.content || '',
                },
                body: JSON.stringify({
                    start: event.startStr.replace('T', ' '),
                    end: (event.endStr || event.startStr).replace('T', ' '),
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