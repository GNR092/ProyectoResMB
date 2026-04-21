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
        catalogos: {
            modulos: ['Solicitud', 'OrdenCompra', 'Cotizacion', 'Usuarios', 'Productos', 'Presupuesto', 'Auth', 'Sistema']
        },

        init() {
            this.fetchData();
            
            // Watchers para filtros automáticos (resetean a página 1)
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

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr; // Si no es una fecha válida, mostrar el raw
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
                'LOGIN': 'bg-blue-50 text-blue-600 border-blue-100',
                'INSERT': 'bg-green-50 text-green-600 border-green-100',
                'INSERTAR': 'bg-green-50 text-green-600 border-green-100',
                'UPDATE': 'bg-amber-50 text-amber-600 border-amber-100',
                'ACTUALIZAR': 'bg-amber-50 text-amber-600 border-amber-100',
                'DELETE': 'bg-red-50 text-red-600 border-red-100',
                'ELIMINAR': 'bg-red-50 text-red-600 border-red-100',
                'SISTEMA_ERROR': 'bg-red-600 text-white border-red-700'
            };
            return classes[action] || 'bg-slate-50 text-slate-600 border-slate-100';
        },

        formatJSON(jsonStr) {
            if (!jsonStr) return '{}';
            try {
                const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                return JSON.stringify(obj, null, 2);
            } catch (e) {
                return jsonStr;
            }
        }
    };
}
