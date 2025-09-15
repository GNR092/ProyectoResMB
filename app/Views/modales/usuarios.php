<!-- Registro de Usuario -->
<div class="p-6 max-w-lg mx-auto bg-white rounded-xl shadow-md">
    <h2 class="text-2xl font-semibold mb-4 text-center">Registro de Usuario</h2>

    <form id="form-register" method="post" action="<?= site_url('modales/registrarUsuario') ?>" class="space-y-4">
        <!-- Correo -->
        <div>
            <label for="correo" class="block text-sm font-medium text-gray-700">Correo</label>
            <input type="email" id="correo" name="correo"
                   class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                   required>
        </div>

        <!-- Nombre -->
        <div>
            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
            <input type="text" id="nombre" name="nombre"
                   class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                   required>
        </div>

        <!-- Razón Social -->
        <div>
            <label for="razon_social" class="block text-sm font-medium text-gray-700">Razón Social</label>
            <select id="razon_social" name="ID_RazonSocial"
                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                    required>
                <option value="">Seleccione una razón social</option>
                <?php foreach ($razones_sociales as $razon): ?>
                    <option value="<?= esc($razon['ID_RazonSocial']) ?>"><?= esc($razon['Nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Departamento -->
        <div>
            <label for="departamento" class="block text-sm font-medium text-gray-700">Departamento</label>
            <select id="departamento" name="ID_Dpto"
                    class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                    required>
                <option value="">Seleccione un departamento</option>
            </select>
        </div>

        <!-- Contraseña Personal -->
        <div>
            <label for="contrasenaP" class="block text-sm font-medium text-gray-700">Contraseña Personal</label>
            <input type="password" id="contrasenaP" name="ContrasenaP"
                   class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                   required>
        </div>

        <!-- Contraseña General -->
        <div>
            <label for="contrasenaG" class="block text-sm font-medium text-gray-700">Contraseña General</label>
            <input type="password" id="contrasenaG" name="ContrasenaG"
                   class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                   required>
        </div>

        <!-- Teléfono -->
        <div>
            <label for="telefono" class="block text-sm font-medium text-gray-700">Número de Teléfono</label>
            <input type="tel" id="telefono" name="Numero" pattern="[0-9]{10}" placeholder="10 dígitos"
                   class="mt-1 p-2 block w-full border border-gray-300 rounded-md focus:ring focus:ring-blue-200 focus:border-blue-500"
                   required>
        </div>

        <!-- Botón -->
        <div class="text-center">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md">
                Registrar
            </button>
        </div>
    </form>

    <!-- Aquí puedes mostrar mensajes -->
    <div id="mensaje" class="mt-4 text-center text-sm"></div>
</div>
