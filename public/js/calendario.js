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
    let lastTouchStart = 0;
    // Permisos Agenda: solo Compras (+Administración) puede crear/editar/cancelar/evidencia.
    // Contaduría y resto quedan en solo lectura (el backend es la autoridad final).
    const normDeptoAgenda = (s) => String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const deptoAgendaNorm = normDeptoAgenda(typeof USER_DEPT_NAME !== 'undefined' ? USER_DEPT_NAME : (window.USER_DEPT_NAME || ''));
    const esAdminAgenda = deptoAgendaNorm.includes('administraci');
    const esComprasAgenda = deptoAgendaNorm.includes('compras');
    const puedeEditarAgenda = esAdminAgenda || esComprasAgenda;
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
    const toApiDate = (v) => {
        if (!v) return v;
        let s = String(v).trim().replace('T', ' ');
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(s)) s += ':00';
        return s;
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
            estatus: 'pendiente',
        },
        showEventModal: false,
        showDetalle: false,
        soloLectura: false,
        returnToEventModal: false,
        isWeekView: false,
        isListView: false,
        busquedaModal: '',
        filtrosModal: { estado: '', tipo: '', proveedor: '', complejo: '', departamento: '', fecha: '' },
        filtrosLista: { folio: '', estado: '', tipo: '', proveedor: '', complejo: '', departamento: '' },
        solicitudesCache: [],
        colors: COLORS,
        filtrosAvanzadosCargados: false,
        filtrosListaInicializados: false,
        choicesProveedor: null,
        choicesComplejo: null,
        choicesDepartamento: null,
        choicesProveedorLista: null,
        choicesComplejoLista: null,
        choicesDepartamentoLista: null,
        proveedoresList: [],
        complejosList: [],
        departamentosList: [],
        eventosCache: [],
        eventosCacheOriginal: [],
        lastViewType: null,
        archivos: [],
        subiendoArchivos: false,

        async init() {
            this.renderCalendar();
            this.setupResponsiveView();
            await this.cargarSolicitudes();
        },

        async cargarSolicitudes() {
            try {
                const params = new URLSearchParams();
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

        onToggleFiltrosAvanzados(event) {
            if (event.target.open && !this.filtrosAvanzadosCargados) {
                this.cargarFiltrosAvanzados().then(() => {
                    this.$nextTick(() => {
                        this.populateSelects();
                        this.initChoicesFiltros();
                        if (this.isListView) { this.initChoicesFiltrosLista(); this.filtrosListaInicializados = true; }
                    });
                });
                this.filtrosAvanzadosCargados = true;
            }
        },

        async cargarFiltrosAvanzados() {
            try {
                const [prov, dept, places] = await Promise.all([
                    fetch(`${BASE_URL}api/providers/all`, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()),
                    fetch(`${BASE_URL}api/departments/all`, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()),
                    fetch(`${BASE_URL}api/places/all`, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()),
                ]);
                const norm = (res) => Array.isArray(res) ? res : (res.data || res.result || []);
                this.proveedoresList = norm(prov).map(p => ({value: p.RazonSocial, label: p.RazonSocial}));
                // Departamentos: usar formato "Nombre|PlaceNombre" que espera la API — robusto a alias Place/PlaceNombre/Nombre_Corto
                this.departamentosList = norm(dept).map(d => {
                    const placeVal = d.Place || d.PlaceNombre || d.Nombre_Corto || d.place_nombre || '';
                    return {
                        value: `${d.Nombre}|${placeVal}`,
                        label: `${d.Nombre} - ${placeVal || 'Sin complejo'}`,
                        placeNombre: placeVal
                    };
                });
                // Complejos (Places): cargar desde nuevo endpoint — robusto a alias
                this.complejosList = norm(places).map(c => {
                    const val = c.Nombre_Corto || c.Nombre || c.Place || '';
                    return {value: val, label: val};
                }).filter(c => c.value);
            } catch(e) { console.error('cargarFiltrosAvanzados', e); }
        },

        populateSelects() {
            const fill = (selectEl, list, placeholder) => {
                if (!selectEl) return;
                selectEl.innerHTML = `<option value="">${placeholder}</option>` + 
                    list.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
            };
            fill(this.$refs.filtroProveedor, this.proveedoresList, 'Todos los proveedores');
            fill(this.$refs.filtroComplejo, this.complejosList, 'Todos los complejos');
            fill(this.$refs.filtroDepartamento, this.departamentosList, 'Todos los departamentos');
            fill(this.$refs.filtroProveedorLista, this.proveedoresList, 'Todos los proveedores');
            fill(this.$refs.filtroComplejoLista, this.complejosList, 'Todos los complejos');
            fill(this.$refs.filtroDepartamentoLista, this.departamentosList, 'Todos los departamentos');
        },

        initChoicesFiltros() {
            if (typeof Choices === 'undefined') return;
            [this.choicesProveedor, this.choicesComplejo, this.choicesDepartamento].forEach(c => c?.destroy());
            const cfg = {removeItemButton:true, placeholder:true, searchPlaceholderValue:'Buscar...', itemSelectText:'', noResultsText:'Sin resultados', noChoicesText:'Sin opciones'};
            this.choicesProveedor = new Choices(this.$refs.filtroProveedor, {...cfg, placeholderValue:'Todos los proveedores'});
            this.choicesComplejo = new Choices(this.$refs.filtroComplejo, {...cfg, placeholderValue:'Todos los complejos'});
            this.choicesDepartamento = new Choices(this.$refs.filtroDepartamento, {...cfg, placeholderValue:'Todos los departamentos'});

            // Complejo → filtra Departamentos (único fetch dentro de filtrarDepartamentosPorComplejo para evitar carrera con proveedor)
            this.$refs.filtroComplejo?.addEventListener('choice', (e) => {
                this.filtrosModal.complejo = e.detail.value;
                this.filtrarDepartamentosPorComplejo(e.detail.value);
            });
            this.$refs.filtroComplejo?.addEventListener('removeItem', () => {
                this.filtrosModal.complejo = '';
                this.filtrarDepartamentosPorComplejo('');
            });

            // Proveedor/Departamento sync
            [['filtroProveedor','proveedor'], ['filtroDepartamento','departamento']].forEach(([ref,key])=>{
                const el=this.$refs[ref]; if(!el) return;
                el.addEventListener('change',()=>{ this.filtrosModal[key]=el.value; this.filtrarModal(); });
                el.addEventListener('choice',(e)=>{ this.filtrosModal[key]=e.detail.value; this.filtrarModal(); });
                el.addEventListener('removeItem',()=>{ this.filtrosModal[key]=el.value||''; this.filtrarModal(); });
            });
        },

        filtrarDepartamentosPorComplejo(placeNombre) {
            const select = this.$refs.filtroDepartamento;
            if (!select || !this.choicesDepartamento) return;
            const prevVal = this.filtrosModal.departamento;
            const filtered = this.departamentosList.filter(d => !placeNombre || d.placeNombre === placeNombre);
            const opts = filtered.map(d => `<option value="${d.value}">${d.label}</option>`).join('');
            select.innerHTML = `<option value="">Todos los departamentos</option>` + opts;
            const stillExists = filtered.some(d => d.value === prevVal);
            const nextVal = stillExists ? prevVal : '';
            // Guardar referencia a handlers para re-atachar tras destroy (asegura compatibilidad departamento+proveedor+complejo)
            this.choicesDepartamento.destroy();
            this.choicesDepartamento = new Choices(select, {removeItemButton:true, placeholder:true, placeholderValue:'Todos los departamentos', searchPlaceholderValue:'Buscar...', itemSelectText:'', noResultsText:'Sin resultados', noChoicesText:'Sin opciones'});
            if (nextVal) {
                this.choicesDepartamento.setChoiceByValue(nextVal);
                this.filtrosModal.departamento = nextVal;
            } else {
                this.filtrosModal.departamento = '';
            }
            // Re-atachar listeners de departamento (destroy limpia los del select)
            if (this._deptChoiceHandler) select.removeEventListener('choice', this._deptChoiceHandler);
            if (this._deptChangeHandler) select.removeEventListener('change', this._deptChangeHandler);
            if (this._deptRemoveHandler) select.removeEventListener('removeItem', this._deptRemoveHandler);
            this._deptChoiceHandler = (e) => { this.filtrosModal.departamento = e.detail.value; this.filtrarModal(); };
            this._deptChangeHandler = () => { this.filtrosModal.departamento = select.value; this.filtrarModal(); };
            this._deptRemoveHandler = () => { this.filtrosModal.departamento = select.value || ''; this.filtrarModal(); };
            select.addEventListener('choice', this._deptChoiceHandler);
            select.addEventListener('change', this._deptChangeHandler);
            select.addEventListener('removeItem', this._deptRemoveHandler);
            this.filtrarModal();
        },

        initChoicesFiltrosLista() {
            if (typeof Choices === 'undefined') return;
            [this.choicesProveedorLista, this.choicesComplejoLista, this.choicesDepartamentoLista].forEach(c => c?.destroy());
            const cfg = {removeItemButton:true, placeholder:true, searchPlaceholderValue:'Buscar...', itemSelectText:'', noResultsText:'Sin resultados', noChoicesText:'Sin opciones'};
            this.choicesProveedorLista = new Choices(this.$refs.filtroProveedorLista, {...cfg, placeholderValue:'Todos los proveedores'});
            this.choicesComplejoLista = new Choices(this.$refs.filtroComplejoLista, {...cfg, placeholderValue:'Todos los complejos'});
            this.choicesDepartamentoLista = new Choices(this.$refs.filtroDepartamentoLista, {...cfg, placeholderValue:'Todos los departamentos'});
            this.$refs.filtroComplejoLista?.addEventListener('choice', (e) => {
                if (this._suppressListaFilter) return;
                this.filtrosLista.complejo = e.detail.value;
                this.filtrarDepartamentosListaPorComplejo(e.detail.value);
            });
            this.$refs.filtroComplejoLista?.addEventListener('removeItem', () => {
                if (this._suppressListaFilter) return;
                this.filtrosLista.complejo = '';
                this.filtrarDepartamentosListaPorComplejo('');
            });
            [['filtroProveedorLista','proveedor'], ['filtroDepartamentoLista','departamento']].forEach(([ref,key])=>{
                const el=this.$refs[ref]; if(!el) return;
                el.addEventListener('change',()=>{ if(this._suppressListaFilter) return; this.filtrosLista[key]=el.value; this.filtrarLista(); });
                el.addEventListener('choice',(e)=>{ if(this._suppressListaFilter) return; this.filtrosLista[key]=e.detail.value; this.filtrarLista(); });
                el.addEventListener('removeItem',()=>{ if(this._suppressListaFilter) return; this.filtrosLista[key]=el.value||''; this.filtrarLista(); });
            });
        },

        filtrarDepartamentosListaPorComplejo(placeNombre) {
            const select = this.$refs.filtroDepartamentoLista;
            if (!select || !this.choicesDepartamentoLista) return;
            const prevVal = this.filtrosLista.departamento;
            const filtered = this.departamentosList.filter(d => !placeNombre || d.placeNombre === placeNombre);
            const opts = filtered.map(d => `<option value="${d.value}">${d.label}</option>`).join('');
            select.innerHTML = `<option value="">Todos los departamentos</option>` + opts;
            const stillExists = filtered.some(d => d.value === prevVal);
            const nextVal = stillExists ? prevVal : '';
            this.choicesDepartamentoLista.destroy();
            this.choicesDepartamentoLista = new Choices(select, {removeItemButton:true, placeholder:true, placeholderValue:'Todos los departamentos', searchPlaceholderValue:'Buscar...', itemSelectText:'', noResultsText:'Sin resultados', noChoicesText:'Sin opciones'});
            if (nextVal) { this.choicesDepartamentoLista.setChoiceByValue(nextVal); this.filtrosLista.departamento = nextVal; } else { this.filtrosLista.departamento = ''; }
            if (this._deptListaChoiceHandler) select.removeEventListener('choice', this._deptListaChoiceHandler);
            if (this._deptListaChangeHandler) select.removeEventListener('change', this._deptListaChangeHandler);
            if (this._deptListaRemoveHandler) select.removeEventListener('removeItem', this._deptListaRemoveHandler);
            this._deptListaChoiceHandler = (e) => { this.filtrosLista.departamento = e.detail.value; this.filtrarLista(); };
            this._deptListaChangeHandler = () => { this.filtrosLista.departamento = select.value; this.filtrarLista(); };
            this._deptListaRemoveHandler = () => { this.filtrosLista.departamento = select.value || ''; this.filtrarLista(); };
            select.addEventListener('choice', this._deptListaChoiceHandler);
            select.addEventListener('change', this._deptListaChangeHandler);
            select.addEventListener('removeItem', this._deptListaRemoveHandler);
            this.filtrarLista();
        },

        filtrarLista() {
            if (!calendar) return;
            let src = this.eventosCacheOriginal.length ? this.eventosCacheOriginal : this.eventosCache;
            if (!src.length && calendar.getEvents().length) {
                src = calendar.getEvents().map(ev => ({
                    id: ev.id, title: ev.title, start: ev.startStr, end: ev.endStr,
                    backgroundColor: ev.backgroundColor, borderColor: ev.borderColor,
                    extendedProps: {...ev.extendedProps}
                }));
                this.eventosCacheOriginal = src.map(e => ({...e, extendedProps:{...e.extendedProps}}));
            }
            const f = this.filtrosLista;
            const hasAny = f.folio || f.estado || f.tipo || f.proveedor || f.complejo || f.departamento;
            if (!hasAny) {
                calendar.removeAllEvents();
                calendar.addEventSource(src);
                this.eventosCache = src.map(e => ({...e, extendedProps:{...e.extendedProps}}));
                return;
            }
            const norm = v => String(v||'').trim().toLowerCase();
            const folioNorm = norm(f.folio).replace(/^mbsp-?/i,'');
            const filtrados = src.filter(ev => {
                const ext = ev.extendedProps || {};
                const noFolio = norm(ext.No_Folio);
                const noFolioRaw = String(ext.No_Folio||'').toLowerCase();
                const estado = norm(ext.EstadoSolicitud);
                const tipo = ext.TipoSolicitud;
                const prov = norm(ext.Proveedor);
                const comp = norm(ext.Complejo);
                const depto = norm(ext.Departamento);
                const deptoKey = depto ? `${depto}|${comp}` : '';
                const fProv = norm(f.proveedor);
                const fComp = norm(f.complejo);
                const fDepto = norm(f.departamento);
                const fDeptoKeyNorm = fDepto;
                if (f.folio) {
                    const idStr = String(ext.ID_Solicitud||'');
                    if (!noFolio.includes(folioNorm) && !noFolioRaw.includes(folioNorm) && !idStr.includes(folioNorm)) return false;
                }
                if (f.estado && estado !== norm(f.estado)) return false;
                if (f.tipo) {
                    const tipoStr = String(tipo);
                    if (f.tipo === 'Producto' && !['0','1'].includes(tipoStr)) return false;
                    if (f.tipo === 'Servicio' && tipoStr !== '2') return false;
                }
                if (f.proveedor && prov !== fProv) return false;
                if (f.complejo && comp !== fComp) return false;
                if (f.departamento) {
                    if (deptoKey !== fDeptoKeyNorm && depto !== fDeptoKeyNorm) return false;
                }
                return true;
            });
            calendar.removeAllEvents();
            calendar.addEventSource(filtrados);
            this.eventosCache = filtrados.map(e => ({...e, extendedProps:{...e.extendedProps}}));
        },

        limpiarFiltrosLista() {
            this._suppressListaFilter = true;
            this.filtrosLista = { folio:'', estado:'', tipo:'', proveedor:'', complejo:'', departamento:'' };
            const clearChoice = (choice) => { try { choice.setChoiceByValue(''); } catch(e){} };
            if (this.choicesProveedorLista) clearChoice(this.choicesProveedorLista);
            if (this.choicesComplejoLista) clearChoice(this.choicesComplejoLista);
            if (this.choicesDepartamentoLista) clearChoice(this.choicesDepartamentoLista);
            // Restaurar lista completa de deptos tras limpiar complejo
            if (this.choicesDepartamentoLista) {
                const select = this.$refs.filtroDepartamentoLista;
                if (select) {
                    select.innerHTML = `<option value="">Todos los departamentos</option>` + this.departamentosList.map(d => `<option value="${d.value}">${d.label}</option>`).join('');
                    try { this.choicesDepartamentoLista.destroy(); } catch(e){}
                    this.choicesDepartamentoLista = new Choices(select, {removeItemButton:true, placeholder:true, placeholderValue:'Todos los departamentos', searchPlaceholderValue:'Buscar...', itemSelectText:'', noResultsText:'Sin resultados', noChoicesText:'Sin opciones'});
                    // re-atachar listeners lista
                    if (this._deptListaChoiceHandler) select.removeEventListener('choice', this._deptListaChoiceHandler);
                    if (this._deptListaChangeHandler) select.removeEventListener('change', this._deptListaChangeHandler);
                    if (this._deptListaRemoveHandler) select.removeEventListener('removeItem', this._deptListaRemoveHandler);
                    this._deptListaChoiceHandler = (e) => { if(this._suppressListaFilter) return; this.filtrosLista.departamento = e.detail.value; this.filtrarLista(); };
                    this._deptListaChangeHandler = () => { if(this._suppressListaFilter) return; this.filtrosLista.departamento = select.value; this.filtrarLista(); };
                    this._deptListaRemoveHandler = () => { if(this._suppressListaFilter) return; this.filtrosLista.departamento = select.value || ''; this.filtrarLista(); };
                    select.addEventListener('choice', this._deptListaChoiceHandler);
                    select.addEventListener('change', this._deptListaChangeHandler);
                    select.addEventListener('removeItem', this._deptListaRemoveHandler);
                }
            }
            this._suppressListaFilter = false;
            this.filtrarLista();
        },

        get solicitudesFiltradasModal() {
            if (!this.busquedaModal || !this.busquedaModal.trim()) return this.solicitudesCache;
            const q = this.busquedaModal.trim().replace(/^MBSP-?/i, '').toLowerCase();
            return this.solicitudesCache.filter(s => {
                const folio = (s.No_Folio || '').toLowerCase();
                const folioNum = folio.replace(/^mbsp-?/i, '');
                return folio.includes(q) || folioNum.includes(q) || String(s.ID_Solicitud).includes(q);
            });
        },

        formatoOpcionSolicitud(sol) {
            const folio = sol.No_Folio || `ID ${sol.ID_Solicitud}`;
            const estado = sol.Estado || sol.EstadoOrden || '';
            const prov = sol.Proveedor || sol.RazonSocial || '';
            const monto = sol.Monto ? ` — $${Number(sol.Monto).toLocaleString('es-MX', {minimumFractionDigits:2})}` : '';
            const provPart = prov ? ` — ${prov}` : '';
            return `${folio} — ${estado}${provPart}${monto}`;
        },

        async filtrarModal() {
            const q = (this.busquedaModal || '').trim().replace(/^MBSP-?/i, '');
            const hasAdv = this.filtrosModal.estado || this.filtrosModal.tipo || this.filtrosModal.proveedor || this.filtrosModal.complejo || this.filtrosModal.departamento;
            try {
                const params = new URLSearchParams();
                if (q) params.set('folio', q);
                if (this.filtrosModal.estado) params.set('estado', this.filtrosModal.estado);
                if (this.filtrosModal.tipo) params.set('tipo', this.filtrosModal.tipo);
                if (this.filtrosModal.proveedor) params.set('proveedores', this.filtrosModal.proveedor);
                // Complejo y departamento se envían como filtro departamentos "Nombre|Complejo"
                // Corregido: evitar duplicar Place y manejar solo-complejo correctamente
                if (this.filtrosModal.departamento && this.filtrosModal.complejo) {
                    const nombreOnly = this.filtrosModal.departamento.split('|')[0].trim();
                    params.set('departamentos', `${nombreOnly}|${this.filtrosModal.complejo.trim()}`);
                } else if (this.filtrosModal.departamento) {
                    params.set('departamentos', this.filtrosModal.departamento.trim());
                } else if (this.filtrosModal.complejo) {
                    params.set('departamentos', `|${this.filtrosModal.complejo.trim()}`);
                }
                params.set('per_page', '50');
                // Solo buscar si hay algún filtro, si no recargar lista base
                if (!q && !hasAdv) {
                    await this.cargarSolicitudes();
                    return;
                }
                const res = await fetch(`${BASE_URL}api/calendario/solicitudes?${params.toString()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (json.success) this.solicitudesCache = json.data || [];
            } catch (e) { console.error('filtrarModal', e); }
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
            const isMobile = window.innerWidth < 640;
            const isMobileSel = isMobile;
            calendar = new Calendar(el, {
                initialView: 'timeGridWeek',
                locale: esLocale,
                height: isMobile ? 'auto' : '100%',
                contentHeight: isMobile ? 'auto' : undefined,
                expandRows: isMobile ? false : true,
                themeSystem: 'standard',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día', list: 'Lista' },
                slotMinTime: '06:00:00',
                slotMaxTime: '21:00:00',
                slotDuration: '00:30:00',
                slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                allDaySlot: false,
                navLinks: true,
                editable: puedeEditarAgenda,
                selectable: puedeEditarAgenda,
                selectMirror: true,
                selectMinDistance: isMobileSel ? 12 : 5,
                selectLongPressDelay: isMobileSel ? 400 : 0,
                eventLongPressDelay: isMobileSel ? 400 : 0,
                selectAllow: (info) => {
                    if (window.innerWidth >= 640) return true;
                    return (info.end - info.start) >= 15*60*1000;
                },
                unselectAuto: false,
                dayMaxEvents: 0,
                moreLinkContent: (args) => {
                    // dayMaxEvents:0 => args.num = total del día (todos colapsados)
                    // Texto exacto "# Salidas" sin signo +
                    const n = args.num;
                    return `${n} Salidas`;
                },
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
                    const isDayView = info.view.type === 'timeGridDay';
                    const calEl = document.getElementById('calendar');
                    if (calEl) calEl.style.overflow = isDayView ? 'auto' : 'hidden';
                    if (this.lastViewType && this.lastViewType !== info.view.type) {
                        this.eventosCache = [];
                        // No vaciar eventosCacheOriginal para que filtrarLista no quede src 0 mientras carga nueva semana
                    }
                    this.lastViewType = info.view.type;
                    this.applyCarbonTheme();
                    setTimeout(() => calendar.updateSize(), 50);
                    if (this.isListView && !this.filtrosListaInicializados) {
                        if (!this.filtrosAvanzadosCargados) {
                            this.cargarFiltrosAvanzados().then(()=>{ this.$nextTick(()=>{ this.populateSelects(); this.initChoicesFiltrosLista(); }); });
                            this.filtrosAvanzadosCargados = true;
                        } else {
                            this.$nextTick(()=>{ this.populateSelects(); this.initChoicesFiltrosLista(); });
                        }
                        this.filtrosListaInicializados = true;
                    }
                },
            });
            calendar.render();
            this.applyCarbonTheme();
            // Track touch start para gatear dateClick con 400ms en móvil
            const calElForTouch = document.getElementById('calendar');
            if (calElForTouch) {
                calElForTouch.addEventListener('touchstart', () => { lastTouchStart = Date.now(); }, {passive: true});
                // Exponer para handleDateClick
                calendar._agendaTouchStart = () => lastTouchStart;
            }
        },

        handleSelect(info) {
            if (!puedeEditarAgenda) {
                if (calendar) calendar.unselect();
                return;
            }
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
            if (!puedeEditarAgenda) return;
            if (window.innerWidth < 640) {
                const startTs = (calendar && calendar._agendaTouchStart) ? calendar._agendaTouchStart() : 0;
                if (Date.now() - startTs < 400) return;
            }
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
            const isMobile = window.innerWidth < 640;

            el.querySelectorAll('.fc-toolbar').forEach(tb => {
                tb.classList.add('bg-carbon-800', 'text-carbon-200', 'rounded-t-xl', 'px-3', 'sm:px-4', 'py-2', 'sm:py-3', 'flex', 'flex-wrap', 'gap-2', 'items-center', 'justify-between');
                // Título fluido
                tb.querySelectorAll('.fc-toolbar-title').forEach(t => {
                    t.style.fontSize = 'clamp(0.875rem, 4vw, 1.125rem)';
                    t.style.lineHeight = '1.2';
                });
                tb.querySelectorAll('.fc-button').forEach(btn => {
                    btn.classList.remove('fc-button-primary');
                    // Touch target 44px en móvil
                    if (isMobile) {
                        btn.classList.add('bg-carbon-700', 'hover:bg-carbon-600', 'text-carbon-100', 'border-0', 'rounded-lg', 'px-3.5', 'py-2.5', 'text-xs', 'font-medium', 'transition-colors', 'min-h-[44px]', 'min-w-[44px]');
                        btn.style.fontSize = 'clamp(0.75rem, 3vw, 0.8125rem)';
                    } else {
                        btn.classList.add('bg-carbon-700', 'hover:bg-carbon-600', 'text-carbon-100', 'border-0', 'rounded-lg', 'px-3', 'py-1.5', 'text-sm', 'font-medium', 'transition-colors');
                    }
                });
                const todayBtn = tb.querySelector('.fc-today-button');
                if (todayBtn) {
                    todayBtn.classList.remove('bg-carbon-700', 'hover:bg-carbon-600');
                    todayBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-700', 'text-white');
                }
            });
            // Footer toolbar también
            el.querySelectorAll('.fc-footer-toolbar').forEach(tb => {
                tb.classList.add('bg-carbon-800', 'text-carbon-200', 'rounded-b-xl', 'px-3', 'sm:px-4', 'py-2', 'flex', 'flex-wrap', 'gap-2', 'justify-center');
                tb.querySelectorAll('.fc-button').forEach(btn => {
                    if (isMobile) btn.classList.add('min-h-[44px]', 'min-w-[44px]');
                });
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
            const isMobile = window.innerWidth < 640;
            info.el.style.backgroundColor = color;
            info.el.style.borderColor = color;
            info.el.style.borderRadius = '6px';
            info.el.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            info.el.classList.add('fc-event-custom');
            // Touch target mínimo y fuente fluida en móvil
            if (isMobile) {
                info.el.style.minHeight = '28px';
                info.el.style.fontSize = 'clamp(0.7rem, 2.8vw, 0.8125rem)';
                info.el.style.padding = '4px 6px';
            } else {
                info.el.style.fontSize = '';
                info.el.style.minHeight = '';
            }
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
            const cloneDeep = (arr) => (arr||[]).map(e => ({...e, extendedProps:{...(e.extendedProps||{})}}));
            this.eventosCacheOriginal = cloneDeep(json.data);
            this.eventosCache = cloneDeep(json.data);
            const f = this.filtrosLista;
            const hasLista = f && (f.folio || f.estado || f.tipo || f.proveedor || f.complejo || f.departamento);
            if (this.isListView && hasLista) {
                const norm = v => String(v||'').trim().toLowerCase();
                const folioNorm = norm(f.folio).replace(/^mbsp-?/i,'');
                const filtrados = this.eventosCacheOriginal.filter(ev => {
                    const ext = ev.extendedProps || {};
                    const noFolio = norm(ext.No_Folio);
                    const noFolioRaw = String(ext.No_Folio||'').toLowerCase();
                    const estado = norm(ext.EstadoSolicitud);
                    const tipo = ext.TipoSolicitud;
                    const prov = norm(ext.Proveedor);
                    const comp = norm(ext.Complejo);
                    const depto = norm(ext.Departamento);
                    const deptoKey = depto ? `${depto}|${comp}` : '';
                    const fProv = norm(f.proveedor); const fComp = norm(f.complejo); const fDepto = norm(f.departamento);
                    if (f.folio) { const idStr = String(ext.ID_Solicitud||''); if (!noFolio.includes(folioNorm) && !noFolioRaw.includes(folioNorm) && !idStr.includes(folioNorm)) return false; }
                    if (f.estado && estado !== norm(f.estado)) return false;
                    if (f.tipo) { const ts = String(tipo); if (f.tipo === 'Producto' && !['0','1'].includes(ts)) return false; if (f.tipo === 'Servicio' && ts !== '2') return false; }
                    if (f.proveedor && prov !== fProv) return false;
                    if (f.complejo && comp !== fComp) return false;
                    if (f.departamento && deptoKey !== fDepto && depto !== fDepto) return false;
                    return true;
                });
                this.eventosCache = cloneDeep(filtrados);
                return filtrados;
            }
            return cloneDeep(json.data);
        },

        openEventModal(event, selectInfo = null) {
            this.eventModalMode = event ? 'edit' : 'create';
            this.soloLectura = !puedeEditarAgenda;
            selectedEvent = event;
            this.busquedaModal = '';
            this.filtrosModal = { estado: '', tipo: '', proveedor: '', complejo: '', departamento: '', fecha: '' };

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
                    estatus: event.extendedProps.estatus || 'pendiente',
                };
                this.archivos = event.extendedProps.archivos || [];
                this.subiendoArchivos = false;
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
                    estatus: 'pendiente',
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
            this.returnToEventModal = false;
            this.showEventModal = false;
            this.soloLectura = false;
            this.eventForm = { id: '', title: '', start: '', end: '', color: randomColor(), ID_Solicitud: '', estatus: 'pendiente' };
            this.archivos = [];
            this.subiendoArchivos = false;
            document.body.style.overflow = '';
            if (calendar) calendar.unselect();
        },

        async verDetalleDesdeModal() {
            const id = this.eventForm.ID_Solicitud;
            if (!id) return;
            this.returnToEventModal = true;
            this.showEventModal = false;
            document.body.style.overflow = '';
            await this.verDetalleSolicitud(id);
        },

        async verDetalleSolicitud(idSolicitud) {
            if (!idSolicitud) idSolicitud = this.eventForm.ID_Solicitud;
            if (!idSolicitud) return;
            this.showDetalle = true;
            const container = document.getElementById('detalles-calendario-solicitud');
            if (container) container.innerHTML = '<p class="text-center text-gray-500 py-8">Cargando detalles...</p>';
            setTimeout(() => { if (calendar) calendar.updateSize(); }, 50);
            try {
                const data = await SendDataEnd(`api/solicitud/details/${idSolicitud}`);
                let html = '';
                if (typeof generarDetallesSolicitudHTML === 'function') html += generarDetallesSolicitudHTML(data);
                if (typeof generarComentariosHtml === 'function') html += generarComentariosHtml(data);
                if (typeof generarProductosServiciosHTML === 'function') html += generarProductosServiciosHTML(data);
                if (data.ComentariosUser) {
                    html += `<div class="mt-6 p-3 sm:p-4 border rounded-lg bg-gray-100 overflow-x-auto"><h4 class="text-sm sm:text-md font-bold text-gray-700 mb-2">Comentarios del solicitante</h4><p class="text-gray-800 whitespace-pre-wrap break-words text-sm">${data.ComentariosUser}</p></div>`;
                }
                if (typeof generarSeccionAdjuntos === 'function') html += generarSeccionAdjuntos(data);
                html = html || '<p class="text-red-500">Sin datos</p>';
                // Wrapper responsive para tablas anchas inyectadas (sin tocar utils.js)
                if (container) {
                    container.innerHTML = `<div class="min-w-0 overflow-x-auto -mx-3 sm:mx-0 px-3 sm:px-0">${html}</div>`;
                    // Asegurar que tablas internas sean scrollables en móvil
                    container.querySelectorAll('table').forEach(t => {
                        t.style.minWidth = '600px';
                        t.classList.add('w-full');
                        const wrap = t.parentElement;
                        if (wrap && !wrap.classList.contains('overflow-x-auto')) {
                            wrap.classList.add('overflow-x-auto');
                        }
                    });
                }
            } catch (e) {
                if (container) container.innerHTML = `<p class="text-red-500">Error cargando detalles: ${e.message || e}</p>`;
            }
        },

        regresarCalendario() {
            this.showDetalle = false;
            const container = document.getElementById('detalles-calendario-solicitud');
            if (container) container.innerHTML = '';
            this.$nextTick(() => { if (calendar) { calendar.updateSize(); calendar.render(); } });
            if (this.returnToEventModal) {
                this.returnToEventModal = false;
                this.showEventModal = true;
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => this.$refs.titleInput?.focus());
            }
        },

        async saveEvent() {
            if (!puedeEditarAgenda) {
                alert('Solo el departamento de Compras puede crear o editar eventos');
                return;
            }
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
                    fecha_inicio: toApiDate(this.eventForm.start),
                    fecha_fin: toApiDate(this.eventForm.end),
                    color_evento: this.eventForm.color,
                    ID_Solicitud: this.eventForm.ID_Solicitud,
                    estatus: this.eventForm.estatus,
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
                selectedEvent.setExtendedProp('estatus', json.data.extendedProps.estatus);
                selectedEvent.setStart(json.data.start);
                selectedEvent.setEnd(json.data.end);
            }
            this.closeEventModal();
        },

        async moveEvent(event) {
            if (!puedeEditarAgenda) {
                try { event.revert(); } catch (e) {}
                return;
            }
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

        async cancelarEvento() {
            if (!puedeEditarAgenda) {
                alert('Solo el departamento de Compras puede cancelar eventos');
                return;
            }
            if (!confirm('¿Cancelar este evento? El evento se marcará como cancelado pero no se eliminará.')) return;
            const res = await fetch(`${BASE_URL}api/calendario/eventos/${this.eventForm.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token-name"]')?.content || '',
                },
                body: JSON.stringify({
                    estatus: 'cancelado',
                }),
            });
            const json = await res.json();
            if (json.success) {
                selectedEvent.setExtendedProp('estatus', 'cancelado');
                this.eventForm.estatus = 'cancelado';
                // Repintar evento con clase de cancelado (gris + tachado)
                selectedEvent.setProp('classNames', json.data.classNames || []);
                selectedEvent.setProp('backgroundColor', json.data.backgroundColor);
                selectedEvent.setProp('borderColor', json.data.borderColor);
                this.closeEventModal();
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Evento cancelado', 'success');
                }
            } else {
                const msg = json.messages ? JSON.stringify(json.messages) : (json.message || 'Error cancelando');
                alert(msg);
            }
        },

        async cargarArchivos() {
            if (!this.eventForm.id) return;
            try {
                const res = await fetch(`${BASE_URL}api/calendario/eventos/${this.eventForm.id}/archivos`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (json.success) {
                    this.archivos = json.data || [];
                } else {
                    this.archivos = [];
                }
            } catch (e) {
                console.error('cargarArchivos', e);
                this.archivos = [];
            }
        },

        async subirArchivos() {
            if (!puedeEditarAgenda) {
                alert('Solo el departamento de Compras puede adjuntar evidencias');
                return;
            }
            const input = this.$refs.archivosInput;
            if (!input || !input.files.length) return;
            this.subiendoArchivos = true;
            const formData = new FormData();
            for (const file of input.files) {
                formData.append('archivos[]', file);
            }
            try {
                const res = await fetch(`${BASE_URL}api/calendario/eventos/${this.eventForm.id}/archivos`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token-name"]')?.content || '',
                    },
                    body: formData,
                });
                const json = await res.json();
                if (json.success) {
                    this.archivos = json.data.evento.extendedProps.archivos || [];
                    this.eventForm.estatus = json.data.evento.extendedProps.estatus;
                    if (typeof mostrarNotificacion === 'function') {
                        if (json.data.guardados?.length) {
                            mostrarNotificacion(`${json.data.guardados.length} archivo(s) subido(s)`, 'success');
                        }
                        if (json.data.errores?.length) {
                            mostrarNotificacion(json.data.errores.join('; '), 'warning');
                        }
                    }
                } else {
                    const msg = json.messages ? JSON.stringify(json.messages) : (json.message || 'Error subiendo archivos');
                    alert(msg);
                }
            } catch (e) {
                console.error('subirArchivos', e);
                alert('Error de conexión al subir archivos');
            } finally {
                this.subiendoArchivos = false;
                input.value = '';
            }
        },

        async descargarArchivo(archivo) {
            window.open(archivo.url_descarga, '_blank');
        },

        formatoFecha(fecha) {
            if (!fecha) return '';
            const d = new Date(fecha);
            if (isNaN(d)) return fecha;
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },
    };
}

window.calendarioApp = calendarioApp;
