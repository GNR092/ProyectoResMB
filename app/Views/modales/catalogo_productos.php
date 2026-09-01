<!-- Pantalla 1: Lista del Catálogo De Productos Y Servicios -->
<div id="pantalla-lista-catalogo" class="p-6 bg-white rounded-xl shadow-md">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Catálogo De Productos Y Servicios</h2>
        <div class="flex gap-2">
            <button id="btn-migrar-catalogo" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                Migrar productos/servicios
            </button>
            <button id="btn-agregar-catalogo" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition shadow-sm">
                + AGREGAR PRODUCTO
            </button>
        </div>
    </div>

    <!-- Buscador y Filtros -->
    <div id="form-filtros-catalogo" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 text-sm">
        <input type="text" id="buscar-nombre-catalogo" placeholder="Buscar por nombre..." class="px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-300 outline-none">
        <select id="filtro-departamento-catalogo" class="px-3 py-2 border rounded-lg outline-none">
            <option value="">Todos los Deptos. Op.</option>
            <?php foreach ($departamentos as $d): ?>
                <option value="<?= esc($d['Nombre']) ?>"><?= esc($d['Nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filtro-grupo-catalogo" class="px-3 py-2 border rounded-lg outline-none">
            <option value="">Todas las Partidas</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= esc($g['Nombre']) ?>"><?= esc($g['Nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto border rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-700 uppercase font-bold text-[10px]">
                <tr>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="px-4 py-3 text-left">Razón Social</th>
                    <th class="px-4 py-3 text-left">Lugar / Depto. Op.</th>
                    <th class="px-4 py-3 text-left">Partida Presupuestal</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-catalogo-body" class="bg-white divide-y divide-gray-200">
            <noscript>
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-red-500">
                        Se requiere JavaScript para cargar el catálogo de productos y servicios.
                    </td>
                </tr>
            </noscript>
            <!-- Las filas se cargan vía API: GET api/catalogo/paginated (createPaginatedTableServer) -->
            </tbody>
        </table>
    </div>
    <div id="paginacion-catalogo" class="flex justify-center mt-4 space-x-2"></div>
</div>

<!-- Pantalla 2: Formulario (Agregar/Editar) -->
<div id="pantalla-form-catalogo" class="hidden p-6 bg-white rounded-xl shadow-md max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <button id="btn-regresar-catalogo" class="text-sm text-blue-600 hover:underline flex items-center">
            &larr; Volver al listado
        </button>
    </div>
    
    <h2 id="form-catalogo-titulo" class="text-2xl font-bold text-gray-800 mb-6 text-center">Nuevo Producto</h2>

    <form id="form-catalogo" class="space-y-5">
        <input type="hidden" name="ID_CatalogoProd" id="form-id-cat">
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Producto o Servicio</label>
            <input type="text" name="Nombre" id="form-nombre" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition" placeholder="Ej. Lápiz de grafito HB">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Razón Social</label>
                <select name="ID_RazonSocial" id="form-rs" class="w-full px-3 py-2 border rounded-lg outline-none bg-white">
                    <option value="">Seleccione...</option>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= $rs['ID_RazonSocial'] ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Segmento</label>
                <select name="id_segmento" id="form-seg" class="w-full px-3 py-2 border rounded-lg outline-none bg-white">
                    <option value="">Seleccione...</option>
                    <?php foreach ($segmentos as $s): ?>
                        <option value="<?= $s['id'] ?>" data-rs="<?= $s['id_razon_social'] ?>"><?= esc($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Lugar (Complejo)</label>
                <select name="ID_Place" id="form-place" class="w-full px-3 py-2 border rounded-lg outline-none bg-white">
                    <option value="">Seleccione...</option>
                    <?php foreach ($places as $p): ?>
                        <option value="<?= $p['ID_Place'] ?>" data-rs="<?= $p['ID_RazonSocial'] ?>" data-seg="<?= $p['id_segmento'] ?>"><?= esc($p['Nombre_Corto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Áreas De Operación</label>
                <select name="ID_Dpto" id="form-depto" class="w-full px-3 py-2 border rounded-lg outline-none bg-white">
                    <option value="">Seleccione...</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['ID_UnidadOperativa'] ?>" data-place="<?= $d['ID_Place'] ?>" data-unidad="<?= $d['ID_UnidadOperativa'] ?>"><?= esc($d['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1 italic text-blue-600">Partida Presupuestal Automática</label>
            <select name="ID_GrupoPresupuestal" id="form-grupo" class="w-full px-3 py-2 border border-blue-200 rounded-lg outline-none bg-blue-50 font-bold text-blue-800">
                <option value="">-- Sin asignación automática --</option>
                <?php foreach ($grupos as $g): ?>
                    <option value="<?= $g['ID_GrupoPresupuestal'] ?>" 
                            data-unidad="<?= $g['ID_UnidadOperativa'] ?>" 
                            data-depto="<?= $g['ID_Dpto'] ?>"
                            data-place="<?= $g['ID_Place'] ?>">
                        <?= esc($g['Nombre']) ?> (<?= esc($g['UnidadNombre'] ?? 'S/U') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-lg shadow-lg hover:bg-indigo-700 transform transition hover:scale-105 active:scale-95">
                GUARDAR CAMBIOS
            </button>
        </div>
    </form>
</div>

<style>
#pantalla-migrar-catalogo .choices{width:100%!important;flex:1 1 0%!important;min-width:0!important}
#pantalla-migrar-catalogo .choices__inner{min-height:38px!important;height:38px!important;padding:4px 8px!important;display:flex;align-items:center}
#pantalla-migrar-catalogo .choices__list--single{padding:2px 0}
</style>
<!-- Pantalla 3: Migración -->
<div id="pantalla-migrar-catalogo" class="hidden p-6 bg-white rounded-xl shadow-md max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <button id="btn-regresar-migrar" class="text-sm text-blue-600 hover:underline">&larr; Volver al listado</button>
        <span id="migrar-step-badge" class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">Paso 1: Origen</span>
    </div>
    <h2 class="text-xl font-bold text-gray-800 mb-2 text-center">Migrar productos/servicios</h2>
    <p class="text-xs text-gray-500 text-center mb-6">Seleccione mínimo Razón Social, Segmento y Complejo. Use "Migrar desde este punto" para copiar todo lo que está debajo.</p>

    <!-- STEP 1 ORIGEN -->
    <div id="migrar-step1">
        <h3 class="text-sm font-bold text-indigo-700 uppercase tracking-wide mb-3 border-l-4 border-indigo-500 pl-2">Origen</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Razón Social *</label>
                <select id="mig-orig-rs" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= $rs['ID_RazonSocial'] ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Segmento *</label>
                <select id="mig-orig-seg" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($segmentos as $s): ?>
                        <option value="<?= $s['id'] ?>" data-rs="<?= $s['id_razon_social'] ?>"><?= esc($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Complejo *</label>
                <select id="mig-orig-place" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($places as $p): ?>
                        <option value="<?= $p['ID_Place'] ?>" data-rs="<?= $p['ID_RazonSocial'] ?>" data-seg="<?= $p['id_segmento'] ?>"><?= esc($p['Nombre_Corto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Área de Operación</label>
                <select id="mig-orig-uo" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Seleccione...</option>
                    <option value="*">Migrar desde este punto (todas las áreas)</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= $d['ID_UnidadOperativa'] ?>" data-place="<?= $d['ID_Place'] ?>"><?= esc($d['Nombre']) ?> (<?= esc($d['ID_Place']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-700 mb-1">Partida Presupuestal</label>
                <select id="mig-orig-grupo" class="w-full px-3 py-2 border rounded-lg bg-blue-50 text-sm">
                    <option value="">Seleccione...</option>
                    <option value="*">Migrar desde este punto (todas las partidas)</option>
                    <?php foreach ($grupos as $g): ?>
                        <option value="<?= $g['ID_GrupoPresupuestal'] ?>" data-unidad="<?= $g['ID_UnidadOperativa'] ?>"><?= esc($g['Nombre']) ?> (<?= esc($g['UnidadNombre'] ?? 'S/U') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button id="btn-migrar-a-destino" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Escoger destino &rarr;</button>
        </div>
        <div id="mig-orig-resumen" class="mt-3 text-xs text-gray-500 italic"></div>
    </div>

    <!-- STEP 2 DESTINO -->
    <div id="migrar-step2" class="hidden">
        <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-wide mb-3 border-l-4 border-emerald-500 pl-2">Destino</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Razón Social *</label>
                <select id="mig-dest-rs" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($razones_sociales as $rs): ?>
                        <option value="<?= $rs['ID_RazonSocial'] ?>"><?= esc($rs['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Segmento *</label>
                <select id="mig-dest-seg" class="w-full px-3 py-2 border rounded-lg bg-white text-sm">
                    <option value="">Seleccione...</option>
                    <?php foreach ($segmentos as $s): ?>
                        <option value="<?= $s['id'] ?>" data-rs="<?= $s['id_razon_social'] ?>"><?= esc($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Complejo *</label>
                <div class="flex gap-2 w-full items-center">
                    <select id="mig-dest-place" class="flex-1 min-w-0 w-full px-3 py-2 border rounded-lg bg-white text-sm">
                        <option value="">Seleccione...</option>
                        <?php foreach ($places as $p): ?>
                            <option value="<?= $p['ID_Place'] ?>" data-rs="<?= $p['ID_RazonSocial'] ?>" data-seg="<?= $p['id_segmento'] ?>"><?= esc($p['Nombre_Corto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btn-crear-place-dest" class="shrink-0 h-[38px] px-3 flex items-center justify-center bg-emerald-50 border border-emerald-300 text-emerald-700 rounded text-[10px] font-bold whitespace-nowrap hover:bg-emerald-100">+ Nuevo</button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Área de Operación</label>
                <div class="flex gap-2 w-full items-center">
                    <select id="mig-dest-uo" class="flex-1 min-w-0 w-full px-3 py-2 border rounded-lg bg-white text-sm">
                        <option value="">Seleccione...</option>
                    </select>
                    <button type="button" id="btn-crear-uo-dest" class="shrink-0 h-[38px] px-3 flex items-center justify-center bg-emerald-50 border border-emerald-300 text-emerald-700 rounded text-[10px] font-bold whitespace-nowrap hover:bg-emerald-100">+ Nuevo</button>
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold text-gray-700 mb-1">Partida Presupuestal</label>
                <div class="flex gap-2 w-full items-center">
                    <select id="mig-dest-grupo" class="flex-1 min-w-0 w-full px-3 py-2 border rounded-lg bg-emerald-50 text-sm">
                        <option value="">Seleccione...</option>
                    </select>
                    <button type="button" id="btn-crear-grupo-dest" class="shrink-0 h-[38px] px-3 flex items-center justify-center bg-emerald-50 border border-emerald-300 text-emerald-700 rounded text-[10px] font-bold whitespace-nowrap hover:bg-emerald-100">+ Nuevo</button>
                </div>
            </div>
        </div>
        <div class="flex justify-between mt-6">
            <button id="btn-migrar-volver-origen" class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">&larr; Volver a origen</button>
            <button id="btn-migrar-preview" class="px-4 py-2 bg-amber-500 text-white font-bold rounded-lg hover:bg-amber-600 disabled:opacity-50" disabled>Previsualizar</button>
        </div>
        <div id="mig-dest-resumen" class="mt-3 text-xs text-gray-500 italic"></div>
    </div>

    <!-- PREVIEW -->
    <div id="migrar-preview" class="hidden mt-6 border-t pt-4">
        <h4 class="text-sm font-bold text-gray-800 mb-2">Previsualización</h4>
        <div id="migrar-preview-content" class="text-xs bg-gray-50 p-3 rounded border max-h-60 overflow-auto"></div>
        <div class="flex justify-end gap-2 mt-4">
            <button id="btn-migrar-ejecutar" class="px-6 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 disabled:opacity-50" disabled>Ejecutar migración</button>
        </div>
        <div id="migrar-result" class="mt-3 text-sm"></div>
    </div>
</div>
