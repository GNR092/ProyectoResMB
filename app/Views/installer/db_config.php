<!DOCTYPE html>
<html>

<head>
    <title>Asistente de Instalación</title>
    <link rel="stylesheet" type="text/css" href="<?= base_url(); ?>css/styless.css">
</head>

<body class="bg-gray-100 font-montserrat flex items-center justify-center min-h-screen" x-data="installerData()">

    <div class="bg-white p-8 md:p-12 rounded-lg shadow-xl w-full max-w-2xl">

        <h1 class="text-3xl font-bold font-doulos text-center mb-6">Paso 1: Configuración de la Base de Datos</h1>

        <div class="space-y-4 mb-6">
            <!-- Muestra un mensaje de error general si existe -->
            <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-md border border-red-400">
                <?= esc($error) ?>
            </div>
            <?php endif; ?>

            <!-- Muestra errores de validación si existen -->
            <?php if (isset($validation)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-md border border-red-400">
                <?= $validation->listErrors() ?>
            </div>
            <?php endif; ?>

            <!-- Muestra el resultado de la prueba de conexión a la base de datos -->
            <?php if (isset($testResult)): ?>
            <!-- Si la prueba fue exitosa, muestra un mensaje verde -->
            <?php if ($testResult['success']): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-md border border-green-400">
                <?= esc($testResult['message']) ?>
            </div>
            <!-- Si la prueba falló, muestra un mensaje rojo -->
            <?php else: ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-md border border-red-400">
                <?= esc($testResult['message']) ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <!-- Selector de tipo de base de datos -->
        <div class="space-y-4 mb-6">
            <h2 class="text-2xl font-bold text-gray-700">Tipo de Base de Datos</h2>
            <div>
                <label for="db_driver" class="block text-gray-700 font-medium">Selecciona el motor de base de
                    datos:</label>
                <select id="db_driver" name="db_driver" x-model="db_driver" @change="updateDbConfig()"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="Postgre">PostgreSQL</option>
                    <option value="MySQLi">MySQL/MariaDB</option>
                </select>
            </div>
        </div>


        <form id="installerForm" @submit.prevent="processInstallation" action="<?= url_to('Installer::process') ?>"
            method="post" class="space-y-6">
            <?= csrf_field() ?>
            <input type="hidden" name="db_driver" x-model="db_driver">
            
            <!-- Configuración del Superusuario -->
            <div x-data="{ showSuperuser: false }" class="space-y-4" x-show="db_driver === 'Postgre'" x-transition>
                <h2 class="text-2xl font-bold text-gray-700">Acceso de Superusuario (<span
                        x-text="db_driver === 'Postgre' ? 'PostgreSQL' : 'MySQL/MariaDB'"></span>)</h2>

                <div>
                    <label for="superuser_name" class="block text-gray-700 font-medium">Nombre del Superusuario:</label>
                    <input type="text" id="superuser_name" name="superuser_name" x-model="superuser_name"
                        :required="db_driver === 'Postgre'"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <p class="text-gray-600">Contraseña para el superusuario `<span x-text="superuser_name"></span>`.</p>

                <div>
                    <label for="superuser_password" class="block text-gray-700 font-medium">Contraseña de Superusuario:</label>
                    <input :type="showSuperuser ? 'text' : 'password'" id="superuser_password" name="superuser_password"
                        :required="db_driver === 'Postgre'"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ingresa una contraseña">
                </div>
                <!-- Mostrar contraseña -->
                <div class="flex items-center">
                    <input type="checkbox" x-model="showSuperuser" id="show_superuser_password"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="show_superuser_password" class="ml-2 block text-sm text-gray-900">Mostrar
                        contraseña</label>
                </div>
            </div>
            <!-- Configuración del Servidor de Base de Datos -->
            <div class="space-y-4">
                <h2 class="text-2xl font-bold text-gray-700">Configuración del Servidor de Base de Datos</h2>
                <div>
                    <label for="db_hostname" class="block text-gray-700 font-medium">Hostname:</label>
                    <input type="text" id="db_hostname" name="db_hostname"
                        value="<?= old('db_hostname', 'localhost') ?>" required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="db_port" class="block text-gray-700 font-medium">Puerto:</label>
                    <input type="number" id="db_port" name="db_port" x-model.number="db_port" required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="button" @click="testConnection"
                    class="w-full px-4 py-2 bg-green-200 text-gray-800 rounded-md hover:bg-green-400 transition-colors duration-200">
                    Probar Conexión
                </button>
            </div>

            <!-- Configuración del Usuario de la Aplicación -->
            <div x-data="{show_ci_password: false}" class="space-y-4">
                <h2 class="text-2xl font-bold text-gray-700">Configuración del Usuario de la Aplicación</h2>
                <p class="text-gray-600">Estos serán los datos que usará CodeIgniter para conectarse.</p>
                
                <div>
                    <label for="ci_username" class="block text-gray-700 font-medium">Nombre de Usuario para la
                        Aplicación:</label>
                    <input type="text" 
                          id="ci_username" 
                          name="ci_username" 
                          value="<?= old('ci_username', '') ?>"
                          oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '')"
                          required
                          class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                          placeholder="letras minúsculas, números y guiones bajos">
               </div>
                <div>
                    <!-- Contraseña para el usuario de la aplicación -->
                    <label for="ci_user_password" class="block text-gray-700 font-medium">Contraseña para el usuario de
                        la aplicación:</label>
                    <input :type="show_ci_password ? 'text' : 'password'" id="ci_user_password" name="ci_user_password"
                        required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Ingresa una contraseña">
                </div>
                <div>
                    <!-- Confirmar contraseña para el usuario de la aplicación -->
                    <label for="ci_user_password_confirm" class="block text-gray-700 font-medium">Confirmar
                        Contraseña:</label>
                    <input :type="show_ci_password ? 'text' : 'password'" id="ci_user_password_confirm"
                        name="ci_user_password_confirm" required
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Repite la contraseña">
                </div>
                <!-- Mostrar contraseña -->
                <div class="flex items-center">
                    <input type="checkbox" x-model="show_ci_password" id="show_ci_password"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="show_ci_password" class="ml-2 block text-sm text-gray-900">Mostrar contraseña</label>
                </div>
            </div>

            <!-- Campo para el nombre de la DB en MySQL -->
            <div x-show="db_driver === 'MySQLi'" x-transition>
                <label for="db_name" class="block text-gray-700 font-medium">Nombre de la Base de Datos:</label>
                <input type="text" id="db_name" name="db_name" value="MBSPCompras" :required="db_driver === 'MySQLi'"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>

            <p class="text-gray-600 text-sm" x-show="db_driver === 'Postgre'" x-transition>El nombre de la base de datos (`MBSPCompras`) se creará por defecto.</p>

            <button type="submit" id="processBtn"
                class="w-full px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 transition-colors duration-200">
                Instalar y Configurar
            </button>
        </form>

        <form id="testForm" action="<?= url_to('Installer::testConnection') ?>" method="post" class="hidden">
            <?= csrf_field() ?>
            <input type="hidden" name="db_driver" value="">
            <input type="hidden" name="superuser_name" value="">
            <input type="hidden" name="superuser_password" value="">
            <input type="hidden" name="db_hostname" value="">
            <input type="hidden" name="db_port" value="">
            <input type="hidden" name="ci_username" value="">
            <input type="hidden" name="ci_user_password" value="">
            <input type="hidden" name="db_name" value="">
        </form>

    </div>
    <script>
        function installerData() {
            return {
                db_driver: 'Postgre',
                db_port: 5432,
                superuser_name: 'postgres',
                updateDbConfig() {
                    if (this.db_driver === 'Postgre') {
                        this.db_port = 5432;
                        this.superuser_name = 'postgres';
                    } else {
                        this.db_port = 3306;
                        this.superuser_name = 'root';
                    }
                },
                processInstallation() {
                    const installerForm = document.getElementById('installerForm');
                    const ciPassword = document.getElementById('ci_user_password').value;
                    const ciPasswordConfirm = document.getElementById('ci_user_password_confirm').value;

                    if (ciPassword !== ciPasswordConfirm) {
                        alert('Las contraseñas de la aplicación no coinciden.');
                        return;
                    }
                    installerForm.submit();
                },
                testConnection() {
                    const testForm = document.getElementById('testForm');
                    // Common fields
                    testForm.querySelector('input[name="db_driver"]').value = this.db_driver;
                    testForm.querySelector('input[name="db_hostname"]').value = document.getElementById('db_hostname').value;
                    testForm.querySelector('input[name="db_port"]').value = document.getElementById('db_port').value;

                    // PostgreSQL specific fields
                    if (this.db_driver === 'Postgre') {
                        testForm.querySelector('input[name="superuser_name"]').value = this.superuser_name;
                        testForm.querySelector('input[name="superuser_password"]').value = document.getElementById('superuser_password').value;
                    }

                    // MySQL specific fields
                    if (this.db_driver === 'MySQLi') {
                        testForm.querySelector('input[name="ci_username"]').value = document.getElementById('ci_username').value;
                        testForm.querySelector('input[name="ci_user_password"]').value = document.getElementById('ci_user_password').value;
                        testForm.querySelector('input[name="db_name"]').value = document.getElementById('db_name').value;
                    }
                    
                    testForm.submit();
                }
            }
        }
    </script>
    <script src="<?= base_url(); ?>js/alpine@3.14.8.js" defer></script>
</body>

</html>