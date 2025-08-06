<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use PDO;
use PDOException;
use CodeIgniter\Validation\Exceptions\ValidationException;

class Installer extends Controller
{
    public function index()
    {
        return view('installer/db_config');
    }

    // Nueva función para probar la conexión
    public function testConnection()
    {
        $post = $this->request->getPost();

        // Validación solo para los campos de conexión
        $rules = [
            'db_hostname' => 'required',
            'db_port' => 'required|is_natural_no_zero',
        ];

        // La contraseña de superusuario puede ser opcional para una simple prueba de conexión
        // Esto permite probar si el servidor está en línea sin necesidad de credenciales válidas
        // si el usuario 'postgres' no tiene contraseña, por ejemplo.
        $superuserPass = $post['superuser_password'] ?? '';

        if (!$this->validate($rules)) {
            return view('installer/db_config', [
                'validation' => $this->validator
            ]);
        }

        $dbHost = $post['db_hostname'];
        $dbPort = $post['db_port'];

        try {
            // Se intenta conectar a la base de datos 'postgres' (por defecto)
            $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=postgres";
            $pdo = new PDO($dsn, 'postgres', $superuserPass);

            // Si llegamos aquí, la conexión es exitosa.
            return view('installer/db_config', [
                'testResult' => [
                    'success' => true,
                    'message' => '¡Conexión exitosa al servidor de PostgreSQL!'
                ]
            ]);
        } catch (PDOException $e) {
            $message = 'Error al intentar conectar: ' . $e->getMessage();
            return view('installer/db_config', [
                'testResult' => [
                    'success' => false,
                    'message' => $message
                ]
            ]);
        }
    }

    public function process()
    {
        helper('form');
        $rules = [
            'superuser_password' => 'required',
            'ci_username' => 'required|alpha_dash|min_length[3]',
            'ci_user_password' => 'required|min_length[8]',
            'db_hostname' => 'required',
            'db_port' => 'required|is_natural_no_zero',
        ];

        if (!$this->validate($rules)) {
            return view('installer/db_config', [
                'validation' => $this->validator
            ]);
        }

        $post = $this->request->getPost();

        $dbHost = $post['db_hostname'];
        $dbPort = $post['db_port'];
        $dbUser = $post['ci_username'];
        $dbPass = $post['ci_user_password'];
        $dbName = 'MBSPCompras';

        $superuserPass = $post['superuser_password'];

        try {
            // --- PASO 1: Conexión como superusuario para CREAR el usuario de la aplicación ---
            $dsnSuperuser = "pgsql:host=$dbHost;port=$dbPort;dbname=postgres";
            $pdoSuperuser = new PDO($dsnSuperuser, 'postgres', $superuserPass);
            $pdoSuperuser->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // SQL para crear el usuario si no existe
            $sqlCreateUser = "
                DO \$do\$
                BEGIN
                    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$dbUser') THEN
                        CREATE USER $dbUser WITH PASSWORD '$dbPass' CREATEDB;
                    END IF;
                END
                \$do\$;
            ";
            $pdoSuperuser->exec($sqlCreateUser);

            // Cierra la conexión del superusuario. Esto es crucial.
            $pdoSuperuser = null;

            // --- PASO 2: Conexión como el NUEVO usuario para CREAR la base de datos ---

            // Intentamos conectar a 'postgres' primero, ya que la nueva DB aún no existe.
            $dsnAppUser = "pgsql:host=$dbHost;port=$dbPort;dbname=postgres";
            $pdoAppUser = new PDO($dsnAppUser, $dbUser, $dbPass);
            $pdoAppUser->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificamos si la base de datos ya existe. Esto es una consulta de SELECT segura.
            $sqlCheckDb = "SELECT 1 FROM pg_database WHERE datname = :dbname";
            $stmt = $pdoAppUser->prepare($sqlCheckDb);
            $stmt->execute([':dbname' => $dbName]);
            $dbExists = $stmt->fetch();

            if (!$dbExists) {
                // Si la base de datos NO existe, la creamos con una sentencia SQL simple.
                // Ya no usamos el bloque DO.
                $sqlCreateDb = "CREATE DATABASE \"$dbName\";";
                $pdoAppUser->exec($sqlCreateDb);
            }

            // Cierra la conexión del usuario de la aplicación.
            $pdoAppUser = null;

            // --- PASO 3: Verificar y actualizar el archivo .env ---
            $envPath = ROOTPATH . '.env';
            $envTemplatePath = ROOTPATH . 'env';

            // VERIFICA SI EL ARCHIVO .env EXISTE
            if (!file_exists($envPath) && file_exists($envTemplatePath)) {
                // Si no existe, copia el archivo 'env' a '.env'
                if (!copy($envTemplatePath, $envPath)) {
                    throw new \Exception("No se pudo copiar el archivo de plantilla 'env' a '.env'. Verifique los permisos de la carpeta del proyecto.");
                }
            }

            // AHORA QUE SABEMOS QUE EL ARCHIVO EXISTE, LO ACTUALIZAMOS
            $this->updateEnvFile($dbHost, $dbPort, $dbName, $dbUser, $dbPass);

            // Crear el archivo de bloqueo
            file_put_contents(WRITEPATH . 'installer.lock', 'Installation successful.');

            return redirect()->to('installer/success');

        } catch (PDOException $e) {
            $error_message = 'Error en la conexión o en la creación de la base de datos: ';

            if (strpos($e->getMessage(), 'password authentication failed') !== false) {
                $error_message .= 'La contraseña de superusuario es incorrecta.';
            } elseif (strpos($e->getMessage(), 'could not connect to server') !== false) {
                $error_message .= 'No se pudo conectar al servidor de PostgreSQL. Verifique que el hostname y el puerto sean correctos.';
            } else {
                $error_message .= $e->getMessage();
            }

            return view('installer/db_config', [
                'error' => $error_message,
                'validation' => $this->validator
            ]);
        } catch (\Exception $e) {
            // Manejar errores de copia de archivos
            return view('installer/db_config', [
                'error' => 'Error de archivos: ' . $e->getMessage(),
                'validation' => $this->validator
            ]);
        }
    }

    public function success()
    {

        return view('installer/success');
    }

    private function updateEnvFile($host, $port, $dbname, $user, $pass)
    {
        $envPath = ROOTPATH . '.env';
        $envContent = file_get_contents($envPath);
        $envLines = explode("\n", $envContent);

        $newEnvLines = [];
        foreach ($envLines as $line) {
            if (str_starts_with($line, 'database.default.hostname')) {
                $newEnvLines[] = "database.default.hostname = $host";
            } elseif (str_starts_with($line, 'database.default.database')) {
                $newEnvLines[] = "database.default.database = $dbname";
            } elseif (str_starts_with($line, 'database.default.username')) {
                $newEnvLines[] = "database.default.username = $user";
            } elseif (str_starts_with($line, 'database.default.password')) {
                $newEnvLines[] = "database.default.password = $pass";
            } elseif (str_starts_with($line, 'database.default.port')) {
                $newEnvLines[] = "database.default.port = $port";
            } else {
                $newEnvLines[] = $line;
            }
        }

        $newEnvContent = implode("\n", $newEnvLines);
        file_put_contents($envPath, $newEnvContent);
    }
}