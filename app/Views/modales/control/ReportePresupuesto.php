<?php
// Codificamos los datos iniciales enviados por el controlador
$razonesJson = json_encode($razones_sociales ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
$placesJson  = json_encode($places ?? [], JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<div id="reporte-presupuesto-main-div"
     class="p-6 bg-white rounded-xl shadow-md min-h-[400px]"
     x-data="reportePresupuestoComponent"
     data-razones-json='<?= esc($razonesJson) ?>'
     data-places-json='<?= esc($placesJson) ?>'>

    <!-- Pantalla 1: Menú Principal de Reportes -->
    <div x-show="pantalla === 'menu'" x-cloak class="animate-fadeIn">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <span class="text-blue-600">📊</span> Central de Reportes
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Botón Reporte Presupuesto -->
            <button @click="irAPantalla('presupuesto')" 
                    class="flex flex-col items-center p-8 border-2 border-blue-100 rounded-2xl hover:border-blue-500 hover:bg-blue-50 transition-all group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">📉</div>
                <span class="font-bold text-gray-700 group-hover:text-blue-700">Presupuesto vs Ejecutado</span>
                <p class="text-xs text-gray-500 mt-2 text-center">Análisis de gastos comprometidos y ejecutados mes a mes.</p>
            </button>

            <!-- Botón Reporte Cuentas Bancarias -->
            <button @click="irAPantalla('cuentas')" 
                    class="flex flex-col items-center p-8 border-2 border-green-100 rounded-2xl hover:border-green-500 hover:bg-green-50 transition-all group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">🏦</div>
                <span class="font-bold text-gray-700 group-hover:text-green-700">Cuentas Bancarias</span>
                <p class="text-xs text-gray-500 mt-2 text-center">Resumen de saldos iniciales y finales por departamento.</p>
            </button>

            <!-- Botón Reporte Completo -->
            <button @click="irAPantalla('completo')" 
                    class="flex flex-col items-center p-8 border-2 border-purple-100 rounded-2xl hover:border-purple-500 hover:bg-purple-50 transition-all group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">📋</div>
                <span class="font-bold text-gray-700 group-hover:text-purple-700">Reporte Completo</span>
                <p class="text-xs text-gray-500 mt-2 text-center">Visión integral de todas las operaciones financieras.</p>
            </button>
        </div>
    </div>

    <!-- Pantalla 2: Reporte Presupuesto vs Ejecutado -->
    <div x-show="pantalla === 'presupuesto'" x-cloak class="animate-fadeIn">
        <div class="flex items-center justify-between mb-6">
            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-blue-600 flex items-center gap-1 font-medium">
                &larr; Volver al menú
            </button>
            <h2 class="text-xl font-bold text-gray-800 text-right">Presupuesto vs Ejecutado</h2>
        </div>

        <!-- Filtros -->
        <div class="flex flex-wrap items-start gap-x-6 gap-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Razón Social</label>
                <select x-model="idRazonSocial" @change="idPlace = ''; departamentos = []"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 min-w-[200px] text-sm">
                    <option value="">Seleccione Razón Social</option>
                    <template x-for="rs in razonesSociales" :key="rs.ID_RazonSocial">
                        <option :value="rs.ID_RazonSocial" x-text="rs.Nombre"></option>
                    </template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Place</label>
                <select x-model="idPlace" @change="cargarComparativo()" :disabled="!idRazonSocial"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 min-w-[200px] text-sm disabled:bg-gray-100">
                    <option value="">Seleccione Place</option>
                    <template x-for="place in placesFiltrados" :key="place.ID_Place">
                        <option :value="place.ID_Place" x-text="place.Nombre_Corto"></option>
                    </template>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-gray-500 uppercase">Mes y año</label>
                <input type="month" x-model="mesAnio" @change="cargarComparativo()"
                       class="px-3 py-2 border border-gray-300 rounded-lg focus:ring focus:ring-blue-300 text-sm">
            </div>
        </div>

        <!-- Tabla Comparativa -->
        <div class="border border-gray-300 rounded-lg overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-800 text-white text-[10px] uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Departamento / Grupo</th>
                        <th class="px-4 py-3 text-right">Presp. Asignado</th>
                        <th class="px-4 py-3 text-right">Comprometido</th>
                        <th class="px-4 py-3 text-right">Ejecutado</th>
                        <th class="px-4 py-3 text-right">Disponible</th>
                        <th class="px-4 py-3 text-center">% Ejecución</th>
                    </tr>
                </thead>
                <tbody x-show="cargando || departamentos.length === 0">
                    <tr x-show="cargando">
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500 italic">Cargando datos...</td>
                    </tr>
                    <tr x-show="!cargando && departamentos.length === 0">
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">Seleccione filtros para ver el reporte.</td>
                    </tr>
                </tbody>
                <template x-for="dpto in departamentos" :key="dpto.ID_Dpto">
                    <tbody class="border-b border-gray-300">
                        <tr class="bg-blue-50">
                            <td colspan="6" class="px-6 py-2 font-bold text-blue-900 border-b border-blue-100">
                                🏢 <span x-text="dpto.Nombre"></span>
                            </td>
                        </tr>
                        <template x-for="item in dpto.analisis" :key="item.grupo">
                            <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100">
                                <td class="px-6 py-3 pl-10 text-gray-700 font-medium" x-text="item.grupo"></td>
                                <td class="px-4 py-3 text-right text-gray-900" x-text="formatearMoneda(item.asignado)"></td>
                                <td class="px-4 py-3 text-right text-orange-600 italic" x-text="formatearMoneda(item.comprometido)"></td>
                                <td class="px-4 py-3 text-right text-blue-700 font-semibold" x-text="formatearMoneda(item.ejecutado)"></td>
                                <td class="px-4 py-3 text-right font-bold" :class="item.disponible < 0 ? 'text-red-600' : 'text-green-700'" x-text="formatearMoneda(item.disponible)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getClaseSemaforo(item.porcentaje)" x-text="item.porcentaje + '%'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </template>
            </table>
        </div>
    </div>

    <!-- Pantalla 3: Reporte Cuentas Bancarias (Placeholder) -->
    <div x-show="pantalla === 'cuentas'" x-cloak class="animate-fadeIn">
        <div class="flex items-center justify-between mb-6">
            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-green-600 flex items-center gap-1 font-medium">
                &larr; Volver al menú
            </button>
            <h2 class="text-xl font-bold text-gray-800">Reporte de Cuentas Bancarias</h2>
        </div>
        <div class="bg-green-50 border-2 border-dashed border-green-200 rounded-2xl p-20 text-center">
            <span class="text-5xl mb-4 block">🛠️</span>
            <h3 class="text-green-800 font-bold text-lg">Módulo en construcción</h3>
            <p class="text-green-600 text-sm">Este reporte mostrará el histórico de saldos bancarios muy pronto.</p>
        </div>
    </div>

    <!-- Pantalla 4: Reporte Completo (Placeholder) -->
    <div x-show="pantalla === 'completo'" x-cloak class="animate-fadeIn">
        <div class="flex items-center justify-between mb-6">
            <button @click="irAPantalla('menu')" class="text-sm text-gray-600 hover:text-purple-600 flex items-center gap-1 font-medium">
                &larr; Volver al menú
            </button>
            <h2 class="text-xl font-bold text-gray-800">Reporte Completo</h2>
        </div>
        <div class="bg-purple-50 border-2 border-dashed border-purple-200 rounded-2xl p-20 text-center">
            <span class="text-5xl mb-4 block">⚙️</span>
            <h3 class="text-purple-800 font-bold text-lg">Módulo en construcción</h3>
            <p class="text-purple-600 text-sm">Integración completa de presupuesto, bancos y flujos de caja.</p>
        </div>
    </div>

</div>

<style>
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    [x-cloak] { display: none !important; }
</style>
