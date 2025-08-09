<?php

namespace App\Controllers;

use PDO;
use PDOException;

class Installer extends BaseController
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
            'superuser_password' => [
                'label' => 'Contraseña de Superusuario',
                'rules' => 'required',
                'errors' => [
                    'required' => 'La contraseña de superusuario es obligatoria.'
                ]
            ],
            'db_hostname' => [
                'label' => 'Hostname del Servidor PostgreSQL',
                'rules' => 'required',
                'errors' => [
                    'required' => 'El hostname es obligatorio.'
                ]
            ],
            'db_port' => [
                'label' => 'Contraseña de Superusuario',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El puerto es obligatorio.',
                    'is_natural_no_zero' => 'El puerto debe ser un número natural mayor que cero.'
                ]
            ],
        ];

        if (!$this->validate($rules)) {
            return view('installer/db_config', [
                'validation' => $this->validator
            ]);
        }
        $superuserPass = $post['superuser_password'];
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
            'superuser_password' => [
                'label' => 'Contraseña de Superusuario',
                'rules' => 'required',
                'errors' => [
                    'required' => 'La contraseña de superusuario es obligatoria.'
                ]
            ],
            'ci_username' => [
                'label' => 'Nombre de Usuario',
                'rules' => 'required|alpha_dash|min_length[3]',
                'errors' => [
                    'required' => 'El {field} es obligatorio.',
                    'alpha_dash' => 'El {field} solo puede contener letras, números y guiones bajos.',
                    'min_length' => 'El {field} debe tener al menos {param} caracteres'
                ]
            ],
            'ci_user_password' => [
                'label' => 'Contraseña de Usuario',
                'rules' => 'required|min_length[8]',
                'errors' => [
                    'required' => 'La contraseña es obligatoria.',
                    'min_length' => 'La contraseña debe tener al menos {param} caracteres.'
                ]
            ],
            'db_hostname' => [
                'label' => 'Hostname del Servidor PostgreSQL',
                'rules' => 'required',
                'errors' => [
                    'required' => 'El hostname es obligatorio.'
                ]
            ],
            'db_port' => [
                'label' => 'Contraseña de Superusuario',
                'rules' => 'required|is_natural_no_zero',
                'errors' => [
                    'required' => 'El puerto es obligatorio.',
                    'is_natural_no_zero' => 'El puerto debe ser un número natural mayor que cero.'
                ]
            ],
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
        return redirect()->to('installer/success');
    }

    public function success()
    {

        return view('installer/success');
    }

    private function updateEnvFile($host, $port, $dbname, $user, $pass)
    {
        // Define las líneas de PostgreSQL que se deben añadir
        $linesToAdd = [
            'database.default.DBDriver = Postgre',
            'database.default.schema = public',
            'database.default.charset = utf8',
        ];

        $envPath = ROOTPATH . '.env';
        // Leer el archivo .env de forma segura
        $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $newEnvLines = [];
        $addedPostgreLines = false;

        foreach ($envLines as $line) {
            // Limpiamos los espacios y el '#' al inicio de cada línea
            $trimmedLine = trim($line, "# \t\n\r\0\x0B");

            if (str_starts_with($trimmedLine, 'database.default.hostname')) {
                $newEnvLines[] = "database.default.hostname = $host";
            } elseif (str_starts_with($trimmedLine, 'database.default.database')) {
                $newEnvLines[] = "database.default.database = $dbname";
            } elseif (str_starts_with($trimmedLine, 'database.default.username')) {
                $newEnvLines[] = "database.default.username = $user";
            } elseif (str_starts_with($trimmedLine, 'database.default.password')) {
                $newEnvLines[] = "database.default.password = $pass";

                // Lógica para añadir las líneas de PostgreSQL si no existen
                if (!$addedPostgreLines) {
                    // Verificar si las líneas ya están presentes
                    $foundAll = true;
                    foreach ($linesToAdd as $lineCheck) {
                        $key = explode('=', $lineCheck)[0];
                        $keyFound = false;
                        foreach ($envLines as $originalLine) {
                            if (str_starts_with(trim($originalLine, "# \t"), $key)) {
                                $keyFound = true;
                                break;
                            }
                        }
                        if (!$keyFound) {
                            $foundAll = false;
                            break;
                        }
                    }

                    // Si no existen, las añade en este punto
                    if (!$foundAll) {
                        foreach ($linesToAdd as $newLine) {
                            $newEnvLines[] = $newLine;
                        }
                    }
                    $addedPostgreLines = true;
                }
            } elseif (str_starts_with($trimmedLine, 'database.default.port')) {
                $newEnvLines[] = "database.default.port = $port";
            } else {
                $newEnvLines[] = $line;
            }
        }

        $newEnvContent = implode("\n", $newEnvLines);
        file_put_contents($envPath, $newEnvContent);
        $this->migrate();
    }
    public function migrate()
{
    // Ahora, al ser una nueva petición, CodeIgniter ya ha cargado
    // los nuevos datos del .env
    try {
        $migrate = \Config\Services::migrations();
        $migrate->latest();
        
        // Crear el archivo de bloqueo si la migración fue exitosa
        file_put_contents(WRITEPATH . 'installer.lock', 'Installation successful.');
        
    } catch (\Throwable $e) {
        // En caso de error en la migración
        echo "Error al ejecutar las migraciones: " . $e->getMessage();
        // Puedes agregar lógica para mostrar una vista de error
    }
}
}