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
        $dbDriver = $post['db_driver'];

        // Validación condicional basada en el driver
        $rules = [
            'db_hostname' => ['label' => 'Hostname', 'rules' => 'required'],
            'db_port' => ['label' => 'Puerto', 'rules' => 'required|is_natural_no_zero'],
            'db_driver' => [
                'label' => 'Motor de Base de Datos',
                'rules' => 'required|in_list[Postgre,MySQLi]',
            ],
        ];

        if ($dbDriver === 'Postgre') {
            $rules['superuser_name'] = ['label' => 'Nombre de Superusuario', 'rules' => 'required'];
            $rules['superuser_password'] = [
                'label' => 'Contraseña de Superusuario',
                'rules' => 'required',
            ];
        } elseif ($dbDriver === 'MySQLi') {
            $rules['ci_username'] = [
                'label' => 'Nombre de Usuario de la Aplicación',
                'rules' => 'required',
            ];
            $rules['ci_user_password'] = [
                'label' => 'Contraseña de la Aplicación',
                'rules' => 'required',
            ];
            $rules['db_name'] = ['label' => 'Nombre de la Base de Datos', 'rules' => 'required'];
        }

        if (!$this->validate($rules)) {
            return view('installer/db_config', ['validation' => $this->validator]);
        }

        $dbHost = $post['db_hostname'];
        $dbPort = $post['db_port'];

        try {
            $driverName = '';
            if ($dbDriver === 'Postgre') {
                $driverName = 'PostgreSQL';
                $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname=postgres";
                $user = $post['superuser_name'];
                $pass = $post['superuser_password'];
                $pdo = new PDO($dsn, $user, $pass);
            } elseif ($dbDriver === 'MySQLi') {
                $driverName = 'MySQL/MariaDB';
                $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$post['db_name']}";
                $user = $post['ci_username'];
                $pass = $post['ci_user_password'];
                $pdo = new PDO($dsn, $user, $pass);
            }

            return view('installer/db_config', [
                'testResult' => [
                    'success' => true,
                    'message' => "¡Conexión exitosa al servidor de {$driverName}!",
                ],
            ]);
        } catch (PDOException $e) {
            $message = 'Error al intentar conectar: ' . $e->getMessage();
            return view('installer/db_config', [
                'testResult' => [
                    'success' => false,
                    'message' => $message,
                ],
            ]);
        }
    }

    public function process()
    {
        helper('form');

        $dbDriver = $this->request->getPost('db_driver');

        $rules = [
            'db_driver' => [
                'label' => 'Motor de DB',
                'rules' => 'required|in_list[Postgre,MySQLi]',
            ],
            'ci_username' => [
                'label' => 'Nombre de Usuario',
                'rules' => 'required|alpha_dash|min_length[3]',
            ],
            'ci_user_password' => [
                'label' => 'Contraseña de Usuario',
                'rules' => 'required|min_length[8]',
            ],
            'db_hostname' => ['label' => 'Hostname', 'rules' => 'required'],
            'db_port' => ['label' => 'Puerto', 'rules' => 'required|is_natural_no_zero'],
        ];

        if ($dbDriver === 'Postgre') {
            $rules['superuser_name'] = ['label' => 'Superusuario', 'rules' => 'required'];
            $rules['superuser_password'] = [
                'label' => 'Contraseña de Superusuario',
                'rules' => 'required',
            ];
        }

        if ($dbDriver === 'MySQLi') {
            $rules['db_name'] = ['label' => 'Nombre de Base de Datos', 'rules' => 'required'];
        }

        if (!$this->validate($rules)) {
            return view('installer/db_config', ['validation' => $this->validator]);
        }

        $post = $this->request->getPost();
        $dbHost = $post['db_hostname'];
        $dbPort = $post['db_port'];
        $dbUser = $post['ci_username'];
        $dbPass = $post['ci_user_password'];
        $dbName = $dbDriver === 'MySQLi' ? $post['db_name'] : 'MBSPCompras';
        $superuser = $post['superuser_name'] ?? '';
        $superuserPass = $post['superuser_password'] ?? '';

        try {
            if ($dbDriver === 'Postgre') {
                // --- LÓGICA PARA POSTGRESQL ---
                $dsnSuperuser = "pgsql:host=$dbHost;port=$dbPort;dbname=postgres";
                $pdoSuperuser = new PDO($dsnSuperuser, $superuser, $superuserPass);
                $pdoSuperuser->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sqlCreateUser = "DO \$do\$ BEGIN IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$dbUser') THEN CREATE USER $dbUser WITH PASSWORD '$dbPass' CREATEDB; END IF; END \$do\$;";
                $pdoSuperuser->exec($sqlCreateUser);
                $pdoSuperuser = null;

                $dsnAppUser = "pgsql:host=$dbHost;port=$dbPort;dbname=postgres";
                $pdoAppUser = new PDO($dsnAppUser, $dbUser, $dbPass);
                $pdoAppUser->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $sqlCheckDb = 'SELECT 1 FROM pg_database WHERE datname = :dbname';
                $stmt = $pdoAppUser->prepare($sqlCheckDb);
                $stmt->execute([':dbname' => $dbName]);
                if (!$stmt->fetch()) {
                    $pdoAppUser->exec("CREATE DATABASE \"$dbName\"");
                }
                $pdoAppUser = null;
            } elseif ($dbDriver === 'MySQLi') {
                // --- LÓGICA PARA MYSQL/MARIADB (SÓLO CONECTAR) ---
                // Se asume que la base de datos y el usuario ya existen.
                // Intentamos conectar directamente con las credenciales de la aplicación.
                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName";
                $pdo = new PDO($dsn, $dbUser, $dbPass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo = null; // Cierra la conexión si fue exitosa
            }

            // --- PASO 3: Verificar y actualizar el archivo .env ---
            $this->updateEnvFile($dbDriver, $dbHost, $dbPort, $dbName, $dbUser, $dbPass);
        } catch (PDOException $e) {
            $error_message = 'Error de base de datos: ';

            if (strpos(strtolower($e->getMessage()), 'access denied') !== false) {
                $error_message .=
                    'Acceso denegado. Verifique el nombre de usuario y la contraseña.';
            } elseif (strpos(strtolower($e->getMessage()), 'unknown database') !== false) {
                $error_message .= "La base de datos '$dbName' no fue encontrada en el servidor.";
            } elseif (
                strpos(strtolower($e->getMessage()), 'password authentication failed') !== false
            ) {
                $error_message .= 'La contraseña de superusuario para PostgreSQL es incorrecta.';
            } elseif (
                strpos(strtolower($e->getMessage()), 'could not connect to server') !== false
            ) {
                $error_message .=
                    'No se pudo conectar al servidor. Verifique que el hostname y el puerto sean correctos.';
            } else {
                $error_message .= $e->getMessage();
            }
            return view('installer/db_config', ['error' => $error_message]);
        } catch (\Exception $e) {
            return view('installer/db_config', [
                'error' => 'Error de archivos: ' . $e->getMessage(),
            ]);
        }

        return redirect()->to('installer/success');
    }

    public function success()
    {
        // Ejecuta las migraciones en la primera visita a la página de éxito,
        // después de que la configuración del .env ha sido cargada.
        if (!file_exists(WRITEPATH . 'installer.lock')) {
            $this->migrate();
        }
        return view('installer/success');
    }

    private function updateEnvFile($dbDriver, $host, $port, $dbname, $user, $pass)
    {
        $envPath = ROOTPATH . '.env';
        if (!file_exists($envPath)) {
            $envTemplatePath = ROOTPATH . 'env';
            if (!file_exists($envTemplatePath) || !copy($envTemplatePath, $envPath)) {
                throw new \Exception(
                    "No se pudo crear el archivo '.env' desde la plantilla. Verifique los permisos.",
                );
            }
        }

        $envContent = file_get_contents($envPath);

        $replacements = [
            'database.default.hostname' => $host,
            'database.default.database' => $dbname,
            'database.default.username' => $user,
            'database.default.password' => $pass,
            'database.default.port' => $port,
            'database.default.DBDriver' => $dbDriver,
        ];

        if ($dbDriver === 'MySQLi') {
            $replacements['database.default.charset'] = 'utf8mb4';
            $replacements['database.default.DBCollat'] = 'utf8mb4_general_ci';
            // Nos aseguramos de que la línea de schema de Postgre esté comentada o ausente
            $envContent = preg_replace(
                '/^database\.default\.schema\s*=.*/m',
                '# database.default.schema = public',
                $envContent,
            );
        } elseif ($dbDriver === 'Postgre') {
            $replacements['database.default.schema'] = 'public';
            // Nos aseguramos de que las líneas de charset y collate de MySQL estén comentadas o ausentes
            $envContent = preg_replace(
                '/^database\.default\.charset\s*=.*/m',
                '# database.default.charset = utf8mb4',
                $envContent,
            );
            $envContent = preg_replace(
                '/^database\.default\.DBCollat\s*=.*/m',
                '# database.default.DBCollat = utf8mb4_general_ci',
                $envContent,
            );
        }

        foreach ($replacements as $key => $value) {
            $key = str_replace('.', '\.', $key);
            // Esta expresión regular busca la llave, comentada o no, y la reemplaza por la versión activa.
            $pattern = '/^#*\s*' . $key . '\s*=.*/m';
            $replacement = "{$key} = {$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n" . $replacement;
            }
        }

        file_put_contents($envPath, $envContent);
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

            echo 'Migración completada exitosamente. El archivo de bloqueo ha sido creado.';
        } catch (\Throwable $e) {
            // En caso de error en la migración
            echo 'Error al ejecutar las migraciones: ' . $e->getMessage();
            // Puedes agregar lógica para mostrar una vista de error
        }
    }

    public function rollback()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response
                ->setStatusCode(403)
                ->setBody('Esta acción solo está permitida en el entorno de desarrollo.');
        }

        try {
            $migrate = \Config\Services::migrations();

            // Rollback all migrations to batch 0
            if ($migrate->regress(0)) {
                // Delete the lock file to allow re-installation
                $lockFile = WRITEPATH . 'installer.lock';
                if (file_exists($lockFile)) {
                    unlink($lockFile);
                }

                echo 'Rollback completado. Todas las migraciones han sido revertidas y el instalador está desbloqueado.';
            } else {
                echo 'Error: No se pudieron revertir las migraciones. Es posible que no haya migraciones que revertir.';
            }
        } catch (\Throwable $e) {
            echo 'Error durante el rollback: ' . $e->getMessage();
        }
    }
}