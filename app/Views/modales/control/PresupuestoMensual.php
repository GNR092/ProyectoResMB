<?php
$gruposJson      = json_encode($grupos ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$presupuestosJson = json_encode($presupuestos ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="presupuesto-mensual-main-div"
     class="p-6 bg-white rounded-xl shadow-md"
     x-data="PresupuestoMensualData()"
     x-init="init()"
     data-grupos-json="<?= esc($gruposJson) ?>"
     data-presupuestos-json="<?= esc($presupuestosJson) ?>">

    <!-- Filtros: Departamento + Mes y Año -->
    <div class="flex flex-wrap items-center gap-x-10 gap-y-4 mb-8">

        <div class="flex items-center gap-3">
            <label for="pm-departamento" class="text-sm font-medium text-gray-700">Departamentos</label>
            <select id="pm-departamento"
                    x-model="idDpto"
                    @change="filtrarGrupos()"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300 min-w-55">
                <option value="">—</option>
                <?php foreach ($departamentos as $dpto): ?>
                    <option value="<?= esc($dpto['ID_Dpto']) ?>"><?= esc($dpto['Nombre']) ?> (<?= esc($dpto['PlaceNombre']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <label for="pm-mes-anio" class="text-sm font-medium text-gray-700">Mes y año</label>
            <input type="month"
                   id="pm-mes-anio"
                   x-model="mesAnio"
                   @change="filtrarGrupos()"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-300">
        </div>

    </div>

    <!-- Tab + Tabla -->
    <div>

        <!-- Tab header -->
        <div class="flex">
            <div class="px-4 py-2 text-sm font-medium border border-b-0 border-gray-300 rounded-t-lg bg-white text-gray-800 relative z-10">
                Grupos
            </div>
        </div>

        <!-- Tabla -->
        <div class="border border-gray-300 rounded-b-lg rounded-tr-lg overflow-hidden">
            <table class="min-w-full">
                <thead class="bg-white">
                    <tr>
                        <th class="w-1/2 px-6 py-3 border-b border-gray-300 text-center font-medium text-gray-700">Grupo</th>
                        <th class="w-1/2 px-6 py-3 border-b border-gray-300 text-center font-medium text-gray-700 border-l border-l-gray-300">Asignar monto</th>
                    </tr>
                </thead>
                <tbody>

                    <template x-if="gruposFiltrados.length === 0">
                        <tr>
                            <td colspan="2" class="px-4 py-16 text-center text-gray-400 text-sm">
                                Seleccione un departamento y un mes/año para ver los grupos
                            </td>
                        </tr>
                    </template>

                    <template x-for="(grupo, i) in gruposFiltrados" :key="grupo.ID_GrupoPresupuestal">
                        <tr :class="i % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
                            <td class="px-6 py-3 border-b border-gray-200 text-gray-800" x-text="grupo.Nombre"></td>
                            <td class="px-6 py-3 border-b border-gray-200 border-l border-l-gray-200">
                                <div class="flex items-center gap-1">
                                    <span class="text-gray-500 text-sm">$</span>
                                    <input type="number"
                                           min="0"
                                           step="0.01"
                                           x-model="grupo.Monto_Asignado"
                                           placeholder="0.00"
                                           class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-right focus:outline-none focus:ring focus:ring-blue-300 text-sm">
                                </div>
                            </td>
                        </tr>
                    </template>

                </tbody>
            </table>
        </div>

        <!-- Botón guardar -->
        <div class="flex justify-end mt-4" x-show="gruposFiltrados.length > 0">
            <button @click="guardar()"
                    :disabled="guardando"
                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed">
                <span x-show="!guardando">Guardar</span>
                <span x-show="guardando">Guardando...</span>
            </button>
        </div>

    </div>

    <!-- Mensaje de feedback -->
    <p class="mt-4 text-center text-sm font-medium"
       x-show="mensaje !== ''"
       :class="error ? 'text-red-600' : 'text-green-600'"
       x-text="mensaje">
    </p>

</div>

<script>
window.PresupuestoMensual = function(todosGrupos, presupuestosExistentes) {
    return {
        idDpto: '',
        mesAnio: '',
        todosGrupos: todosGrupos,
        presupuestosExistentes: presupuestosExistentes,
        gruposFiltrados: [],
        guardando: false,
        mensaje: '',
        error: false,

        init() {
            const now   = new Date();
            const anio  = now.getFullYear();
            const mes   = String(now.getMonth() + 1).padStart(2, '0');
            this.mesAnio = `${anio}-${mes}`;
            this.filtrarGrupos(); // Call filter on init
        },

        filtrarGrupos() {
            if (!this.idDpto || !this.mesAnio) {
                this.gruposFiltrados = [];
                return;
            }

            const [anio, mes] = this.mesAnio.split('-');

            console.log('Selected idDpto:', this.idDpto);
            const gruposDpto = this.todosGrupos.filter(
                g => {
                    console.log('Group ID_Dpto:', g.ID_Dpto);
                    return g.ID_Dpto !== null && g.ID_Dpto !== undefined && String(g.ID_Dpto) === String(this.idDpto);
                }
            );

            this.gruposFiltrados = gruposDpto.map(g => {
                const existente = this.presupuestosExistentes.find(
                    p => String(p.ID_GrupoPresupuestal) === String(g.ID_GrupoPresupuestal)
                      && String(p.ID_Dpto)              === String(this.idDpto)
                      && String(p.Anio)                 === String(anio)
                      && String(p.Mes)                  === String(parseInt(mes))
                );
                return {
                    ...g,
                    ID_PresupuestoMensual: existente ? existente.ID_PresupuestoMensual : null,
                    Monto_Asignado: existente ? existente.Monto_Asignado : ''
                };
            });
        },

        async guardar() {
            if (!this.idDpto || !this.mesAnio || this.gruposFiltrados.length === 0) return;

            const [anio, mes] = this.mesAnio.split('-');
            this.guardando    = true;
            this.mensaje      = '';

            const payload = {
                id_dpto: this.idDpto,
                anio: parseInt(anio),
                mes: parseInt(mes),
                grupos: this.gruposFiltrados.map(g => ({
                    id_grupo:          g.ID_GrupoPresupuestal,
                    id_existente:      g.ID_PresupuestoMensual,
                    monto_asignado:    parseFloat(g.Monto_Asignado) || 0
                }))
            };

            try {
                const res = await fetch(`${BASE_URL}api/presupuesto-mensual/guardar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    const json = await res.json();
                    this.presupuestosExistentes = json.presupuestos ?? this.presupuestosExistentes;
                    this.filtrarGrupos();
                    this.mensaje = 'Presupuesto guardado correctamente';
                    this.error   = false;
                } else {
                    this.mensaje = 'Error al guardar el presupuesto';
                    this.error   = true;
                }
            } catch (e) {
                this.mensaje = 'Error de conexión';
                this.error   = true;
            } finally {
                this.guardando = false;
                setTimeout(() => { this.mensaje = ''; }, 3000);
            }
        }
    };
}
</script>
