<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class MantenimientoCommand extends BaseCommand
{
    protected $group = 'MBS';

    protected $name = 'maintenance';

    protected $description = 'Gestiona el modo mantenimiento del sistema';

    protected $usage = 'maintenance:on [--roles=Admin,Usuario] [--message=Mensaje] | maintenance:off | maintenance:status';

    protected $arguments = [];

    protected $options = [
        '--roles' => 'Lista de roles separados por coma que tendrán acceso',
        '--role' => 'Igual que --roles',
        '--message' => 'Mensaje personalizado a mostrar',
    ];

    private const CONFIG_FILE = WRITEPATH . 'mantenimiento.json';

    public function run(array $params)
    {
        $action = $params[0] ?? 'status';

        switch ($action) {
            case 'on':
                $this->turnOn($params);
                break;
            case 'off':
                $this->turnOff();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->showUsage();
                break;
        }
    }

    private function turnOn(array $params): void
    {
        $rolesParam = null;
        $message = 'Estamos realizando un mantenimiento programado para darte un mejor servicio. Volveremos en breve.';

        foreach ($params as $key => $value) {
            if (is_string($key) && str_contains($key, '=')) {
                $parts = explode('=', $key, 2);
                $optKey = ltrim($parts[0], '-');
                $optVal = $parts[1] ?? '';
                
                if (in_array($optKey, ['roles', 'role', 'r'])) {
                    $rolesParam = $optVal;
                }
                if (in_array($optKey, ['message', 'msg', 'm'])) {
                    $message = $optVal;
                }
            } elseif (is_string($key) && !str_starts_with($key, '-')) {
                if (in_array($key, ['roles', 'role', 'r'])) {
                    $rolesParam = $value;
                }
                if (in_array($key, ['message', 'msg', 'm'])) {
                    $message = $value;
                }
            }
        }

        $rolesPermitidos = ['Administración'];
        if ($rolesParam) {
            $rolesPermitidos = array_map('trim', explode(',', $rolesParam));
        }

        $config = [
            'activado' => true,
            'roles_permitidos' => $rolesPermitidos,
            'mensaje' => $message,
            'activado_en' => date('Y-m-d H:i:s'),
        ];

        try {
            $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents(self::CONFIG_FILE, $json);

            CLI::write('Modo mantenimiento ACTIVADO', 'green');
            CLI::write('Mensaje: ' . $message);
            
            if (empty($rolesPermitidos)) {
                CLI::write('ACCESO: BLOQUEO TOTAL - Nadie puede acceder', 'red');
            } else {
                CLI::write('Roles con acceso: ' . implode(', ', $rolesPermitidos), 'green');
            }
        } catch (\Throwable $e) {
            CLI::error('Error al activar modo mantenimiento: ' . $e->getMessage());
            log_message('error', '[MAINTENANCE COMMAND] ' . $e->getMessage());
        }
    }

    private function turnOff(): void
    {
        try {
            if (file_exists(self::CONFIG_FILE)) {
                unlink(self::CONFIG_FILE);
            }

            CLI::write('Modo mantenimiento DESACTIVADO', 'green');
            CLI::write('El sistema está accesible para todos los usuarios.');
        } catch (\Throwable $e) {
            CLI::error('Error al desactivar modo mantenimiento: ' . $e->getMessage());
            log_message('error', '[MAINTENANCE COMMAND] ' . $e->getMessage());
        }
    }

    private function showStatus(): void
    {
        if (!file_exists(self::CONFIG_FILE)) {
            CLI::write('Modo mantenimiento: INACTIVO', 'green');
            CLI::write('El sistema está operando normalmente.');
            return;
        }

        $content = file_get_contents(self::CONFIG_FILE);
        $config = json_decode($content, true);

        if (!$config || !($config['activado'] ?? false)) {
            CLI::write('Modo mantenimiento: INACTIVO', 'green');
            return;
        }

        CLI::write('Modo mantenimiento: ACTIVO', 'red');
        CLI::write('Mensaje: ' . ($config['mensaje'] ?? 'N/A'));
        CLI::write('Activado en: ' . ($config['activado_en'] ?? 'N/A'));
        
        $roles = $config['roles_permitidos'] ?? [];
        if (empty($roles)) {
            CLI::write('Acceso: BLOQUEO TOTAL - Nadie puede acceder', 'red');
        } else {
            CLI::write('Roles con acceso: ' . implode(', ', $roles), 'green');
        }
    }

    private function showUsage(): void
    {
        CLI::write('Uso:', 'yellow');
        CLI::write('  php spark maintenance on [--roles=Rol1,Rol2] [--message="Tu mensaje"]', 'white');
        CLI::write('  php spark maintenance off', 'white');
        CLI::write('  php spark maintenance status', 'white');
        CLI::write('');
        CLI::write('Comandos:', 'yellow');
        CLI::write('  maintenance on    Activa el modo mantenimiento', 'white');
        CLI::write('  maintenance off   Desactiva el modo mantenimiento', 'white');
        CLI::write('  maintenance status  Muestra el estado actual', 'white');
        CLI::write('');
        CLI::write('Opciones:', 'yellow');
        CLI::write('  --roles=Rol1,Rol2  Roles que tendrán acceso (separados por coma)', 'white');
        CLI::write('  --message="msg"    Mensaje a mostrar en la página de mantenimiento', 'white');
        CLI::write('');
        CLI::write('Roles disponibles:', 'yellow');
        CLI::write('  Administración, Compras, Dirección, Tesoreria, Almacen, Direccion Campus, Contaduria', 'white');
        CLI::write('  boss (todos los jefes), employee (todos los empleados)', 'white');
    }
}
