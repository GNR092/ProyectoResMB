<!-- Pantalla 1: Lista del Catálogo De Productos Y Servicios -->
<div id="pantalla-lista-catalogo" class="p-6 bg-white rounded-xl shadow-md">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Catálogo De Productos Y Servicios</h2>
        <button id="btn-agregar-catalogo" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 transition shadow-sm">
            + AGREGAR PRODUCTO
        </button>
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
