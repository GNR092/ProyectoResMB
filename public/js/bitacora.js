function bitacoraApp() {
    return {
        items: [],
        total: 0,
        loading: false,
        selectedItem: null,
        pagination: {
            page: 1,
            limit: 50,
            totalPages: 1
        },
        filters: {
            usuario_id: '',
            modulo: '',
            tipo_accion: '',
            fecha_inicio: '',
            fecha_fin: ''
        },
        // Mapeos para transformar datos técnicos a lenguaje humano
        friendly: {
            modulos: {
                'Solicitud': 'Requisiciones',
                'Cotizacion': 'Cotizaciones',
                'OrdenCompra': 'Órdenes de Compra',
                'Usuarios': 'Gestión de Usuarios',
                'Catalogos': 'Configuraciones',
                'Autenticacion': 'Acceso al Sistema',
                'PresupuestoAnual': 'Planeación Anual',
                'PresupuestoMensual': 'Gasto Mensual',
                'Presupuesto': 'Presupuestos',
                'Sistema': 'Errores Críticos',
                'GENERAL': 'General'
            },
            acciones: {
                'INSERTAR': 'Creación de Registro',
                'ACTUALIZAR': 'Modificación de Datos',
                'ELIMINAR': 'Eliminación de Registro',
                'LOGIN_EXITOSO': 'Inicio de Sesión',
                'LOGOUT': 'Cierre de Sesión',
                'LOGIN_FALLIDO': 'Intento Fallido',
                'SISTEMA_ERROR': 'Fallo del Sistema',
                'APROBAR_Y_COTIZAR': 'Aprobación de Solicitud',
                'CREAR_COTIZACION_MASIVA': 'Generación de Cotizaciones',
                'SUBIR_ARCHIVOS_COTIZACION': 'Carga de Documentos',
                'FALLO_LOGIN': 'Error de Autenticación'
            }
        },
        catalogos: {
            modulos: [
                'Solicitud', 'Cotizacion', 'Compras', 'OrdenCompra', 'Usuarios', 
                'Catalogos', 'Autenticacion', 'PresupuestoAnual', 
                'PresupuestoMensual', 'Presupuesto', 'Sistema'
            ]
        },

        init() {
            this.fetchData();
            
            this.$watch('filters.modulo', () => { this.pagination.page = 1; this.fetchData(); });
            this.$watch('filters.tipo_accion', () => { this.pagination.page = 1; this.fetchData(); });
            this.$watch('filters.fecha_inicio', () => { this.pagination.page = 1; this.fetchData(); });
            this.$watch('filters.fecha_fin', () => { this.pagination.page = 1; this.fetchData(); });
        },

        async fetchData() {
            this.loading = true;
            try {
                const queryParams = new URLSearchParams({
                    ...this.filters,
                    page: this.pagination.page,
                    limit: this.pagination.limit
                });
                
                const response = await fetch(`${BASE_URL}api/bitacora?${queryParams.toString()}`);
                const result = await response.json();
                
                this.items = Array.isArray(result.data) ? result.data : [];
                this.total = result.total || 0;
                this.pagination.totalPages = Math.ceil(this.total / this.pagination.limit) || 1;
            } catch (error) {
                console.error('Error fetching bitacora:', error);
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        clearFilters() {
            this.filters = {
                usuario_id: '', modulo: '', tipo_accion: '', fecha_inicio: '', fecha_fin: ''
            };
            this.pagination.page = 1;
            this.fetchData();
        },

        changePage(p) {
            if (p < 1 || p > this.pagination.totalPages) return;
            this.pagination.page = p;
            this.fetchData();
        },

        selectItem(item) {
            this.selectedItem = item;
        },

        closeDetails() {
            this.selectedItem = null;
        },

        getFriendlyModule(mod) {
            return this.friendly.modulos[mod] || mod;
        },

        getFriendlyAction(acc) {
            return this.friendly.acciones[acc] || acc;
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr; 
            return date.toLocaleString('es-MX', { 
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        },

        getInitials(name) {
            if (!name || name === 'SISTEMA') return 'S';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        },

        getActionClass(action) {
            const classes = {
                'LOGIN_EXITOSO': 'bg-blue-100 text-blue-700 border-blue-200',
                'LOGOUT': 'bg-slate-100 text-slate-700 border-slate-200',
                'LOGIN_FALLIDO': 'bg-red-100 text-red-700 border-red-200',
                'INSERTAR': 'bg-green-100 text-green-700 border-green-200',
                'ACTUALIZAR': 'bg-amber-100 text-amber-700 border-amber-200',
                'ELIMINAR': 'bg-red-100 text-red-700 border-red-200',
                'SISTEMA_ERROR': 'bg-red-600 text-white border-red-700',
                'FALLO_LOGIN': 'bg-red-100 text-red-700 border-red-200',
                'APROBAR_Y_COTIZAR': 'bg-indigo-100 text-indigo-700 border-indigo-200',
                'CREAR_COTIZACION_MASIVA': 'bg-emerald-100 text-emerald-700 border-emerald-200',
                'SUBIR_ARCHIVOS_COTIZACION': 'bg-teal-100 text-teal-700 border-teal-200'
            };
            return classes[action] || 'bg-slate-50 text-slate-600 border-slate-100';
        },

        formatJSON(jsonStr) {
            if (!jsonStr) return '{}';
            try {
                const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                return JSON.stringify(obj, null, 4);
            } catch (e) {
                return jsonStr;
            }
        }
    };
}
