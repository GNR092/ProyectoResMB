<?php
$iconPath = FCPATH . 'icons/icons.svg';
$version = file_exists($iconPath) ? filemtime($iconPath) : time();
$iconUrl = "/icons/icons.svg?v=$version";
?>
<div class="p-4" x-data="crudUsuarios()">
    <h2 class="text-2xl font-bold mb-4">Administrar Usuarios</h2>

    <!-- Pantalla de Lista -->
    <div id="div-lista-usuarios">

        <div class="flex items-center mb-4">
            <button onclick="abrirModal('ajustes')"
                    class="text-sm text-gray-600 hover:text-gray-900 transition">
                &larr; Regresar
            </button>
        </div>

        <div class="flex justify-between items-center mb-4">
            <button @click="mostrarFormularioCrear" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                Crear Nuevo Usuario
            </button>
        </div>
        <!-- Barra de búsqueda -->
        <div class="mb-4">
            <label for="buscarUsuario" class="sr-only">Buscar usuario por nombre o correo</label>
            <input type="text" id="buscarUsuario" name="buscar_usuario" @input="filtrarUsuarios" placeholder="Buscar por nombre o correo..." class="w-full px-4 py-2 border rounded-md">
        </div>

        <!-- Tabla de usuarios -->
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">Nombre</th>
                        <th class="py-2 px-4 text-left">Correo</th>
                        <th class="py-2 px-4 text-left">Departamento</th>
                        <th class="py-2 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaCrudUsuarios">
                    <?php if (!empty($usuarios)) : ?>
                        <?php foreach ($usuarios as $usuario) : ?>
                            <tr data-id="<?= $usuario['ID_Usuario'] ?>" class="usuario-row" data-numero="<?= esc($usuario['Numero']) ?>" data-id-razonsocial="<?= esc($usuario['ID_RazonSocial']) ?>">
                                <td class="py-2 px-4 border-b nombre"><?= esc($usuario['Nombre']) ?></td>
                                <td class="py-2 px-4 border-b correo"><?= esc($usuario['Correo']) ?></td>
                                <td class="py-2 px-4 border-b departamento" data-id-dpto="<?= esc($usuario['ID_Dpto']) ?>">
                                    <?= esc($usuario['departamento_nombre'] ?? 'N/A') ?> (<?= esc($usuario['place_nombre'] ?? 'N/A') ?>)
                                </td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button @click="editarUsuario(<?= $usuario['ID_Usuario'] ?>)" class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor">
                                            <use xlink:href="<?= $iconUrl ?>#editar"></use>
                                        </svg>
                                    </button>
                                    <?php if (($usuario['departamento_nombre'] ?? '') !== 'Administración') : ?>
                                        <button @click="eliminarUsuario(<?= $usuario['ID_Usuario'] ?>)" class="text-red-600 hover:text-red-800 ml-2" title="Eliminar">
                                            <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor">
                                                <use xlink:href="<?= $iconUrl ?>#eliminar-fila"></use>
                                            </svg>
                                        </button>
                                    <?php else : ?>
                                        <button class="text-gray-400 ml-2 cursor-not-allowed" title="No se puede eliminar a un administrador" disabled>
                                            <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor">
                                                <use xlink:href="<?= $iconUrl ?>#eliminar-fila"></use>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="4" class="py-4 px-4 text-center text-gray-500">No hay usuarios registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pantalla de Creación -->
    <div id="div-crear-usuario" class="hidden" x-data="{ showPasswords: false }">
        <div class="flex items-center mb-4">
            <button @click="regresarALista" class="text-blue-600 hover:underline">
                &larr; Regresar a la lista
            </button>
        </div>

        <h3 class="text-xl font-bold mb-4">Crear Nuevo Usuario</h3>

        <form id="form-crear-usuario" @submit.prevent="guardarNuevoUsuario" class="space-y-4">
            <div>
                <label for="crear-Nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" id="crear-Nombre" name="Nombre" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="crear-Correo" class="block text-sm font-medium text-gray-700">Correo</label>
                <input type="email" id="crear-Correo" name="Correo" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="crear-ID_Dpto" class="block text-sm font-medium text-gray-700">Departamento</label>
                <select id="crear-ID_Dpto" name="ID_Dpto" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Seleccione un departamento</option>
                    <?php if (!empty($departamentos)) : ?>
                        <?php foreach ($departamentos as $depto) : ?>
                            <option value="<?= $depto['ID_Dpto'] ?>"><?= esc($depto['Nombre']) ?> (<?= esc($depto['Place']) ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <input type="text" id="crear-ID_RazonSocial" name="ID_RazonSocial" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="crear-Numero" class="block text-sm font-medium text-gray-700">Número de Teléfono</label>
                <input type="tel" id="crear-Numero" placeholder="Ej. 5512345678" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
            </div>

            <div>
                <label for="crear-ContrasenaP" class="block text-sm font-medium text-gray-700">Contraseña Jefe (Personal)</label>
                <input :type="showPasswords ? 'text' : 'password'" id="crear-ContrasenaP" required minlength="8" placeholder="Mínimo 8 caracteres" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="crear-ContrasenaP_confirm" class="block text-sm font-medium text-gray-700">Confirmar Contraseña Jefe</label>
                <input :type="showPasswords ? 'text' : 'password'" id="crear-ContrasenaP_confirm" required minlength="8" placeholder="Repetir contraseña" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="crear-ContrasenaG" class="block text-sm font-medium text-gray-700">Contraseña Empleado (opcional)</label>
                <input :type="showPasswords ? 'text' : 'password'" id="crear-ContrasenaG" minlength="8" placeholder="Dejar en blanco si no aplica" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="crear-ContrasenaG_confirm" class="block text-sm font-medium text-gray-700">Confirmar Contraseña Empleado (opcional)</label>
                <input :type="showPasswords ? 'text' : 'password'" id="crear-ContrasenaG_confirm" minlength="8" placeholder="Dejar en blanco si no aplica" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex items-center">
                <input type="checkbox" x-model="showPasswords" id="crear-show-pass" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="crear-show-pass" class="ml-2 block text-sm text-gray-900">Mostrar contraseñas</label>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="regresarALista" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">
                    Cancelar
                </button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Guardar Usuario
                </button>
            </div>
        </form>
    </div>

    <!-- Pantalla de Edición -->
    <div id="div-editar-usuario" class="hidden" x-data="{ showPasswords: false }">
        <div class="flex items-center mb-4">
            <button @click="regresarALista" class="text-blue-600 hover:underline">
                &larr; Regresar a la lista
            </button>
        </div>

        <h3 class="text-xl font-bold mb-4">Editar Usuario</h3>

        <form id="form-editar-usuario" @submit.prevent="guardarCambiosUsuario" class="space-y-4">
            <input type="hidden" id="editar-ID_Usuario">

            <div>
                <label for="editar-Nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                <input type="text" id="editar-Nombre" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="editar-Correo" class="block text-sm font-medium text-gray-700">Correo</label>
                <input type="email" id="editar-Correo" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="editar-ID_Dpto" class="block text-sm font-medium text-gray-700">Departamento</label>
                <select id="editar-ID_Dpto" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Seleccione un departamento</option>
                    <?php if (!empty($departamentos)) : ?>
                        <?php foreach ($departamentos as $depto) : ?>
                            <option value="<?= $depto['ID_Dpto'] ?>"><?= esc($depto['Nombre']) ?> (<?= esc($depto['Place']) ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="editar-ID_RazonSocial" class="block text-sm font-medium text-gray-700">Razón Social</label>
                <select id="editar-ID_RazonSocial" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Seleccione una razón social</option>
                    <?php if (!empty($razones_sociales)) : ?>
                        <?php foreach ($razones_sociales as $rs) : ?>
                            <option value="<?= $rs['ID_RazonSocial'] ?>"><?= esc($rs['Nombre']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="editar-Numero" class="block text-sm font-medium text-gray-700">Número de Teléfono (opcional)</label>
                <input type="tel" id="editar-Numero" placeholder="Ej. 5512345678" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="editar-ContrasenaP" class="block text-sm font-medium text-gray-700">Nueva Contraseña Jefe (opcional)</label>
                <input :type="showPasswords ? 'text' : 'password'" id="editar-ContrasenaP" placeholder="Dejar en blanco para no cambiar" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="editar-ContrasenaP_confirm" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña Jefe</label>
                <input :type="showPasswords ? 'text' : 'password'" id="editar-ContrasenaP_confirm" placeholder="Repetir contraseña" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="editar-ContrasenaG" class="block text-sm font-medium text-gray-700">Nueva Contraseña Empleado (opcional)</label>
                <input :type="showPasswords ? 'text' : 'password'" id="editar-ContrasenaG" placeholder="Dejar en blanco para no cambiar" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="editar-ContrasenaG_confirm" class="block text-sm font-medium text-gray-700">Confirmar Nueva Contraseña Empleado</label>
                <input :type="showPasswords ? 'text' : 'password'" id="editar-ContrasenaG_confirm" placeholder="Repetir contraseña" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="flex items-center">
                <input type="checkbox" x-model="showPasswords" id="editar-show-pass" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="editar-show-pass" class="ml-2 block text-sm text-gray-900">Mostrar contraseñas</label>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="regresarALista" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-md mr-2 hover:bg-gray-300">
                    Cancelar
                </button>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>