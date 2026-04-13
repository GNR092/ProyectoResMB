<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MantenimientoFilter implements FilterInterface
{
    private const CONFIG_FILE = WRITEPATH . 'mantenimiento.json';

    public function before(RequestInterface $request, $arguments = null)
    {
        $currentUri = $request->getUri()->getPath();

        if (str_starts_with($currentUri, '/mantenimiento')) {
            return;
        }

        if (str_starts_with($currentUri, '/auth')) {
            return;
        }

        if (str_starts_with($currentUri, '/installer')) {
            return;
        }

        $config = $this->getConfig();

        if (!$config || !($config['activado'] ?? false)) {
            return;
        }

        $session = session();

        if (!$session->get('isLoggedIn')) {
            return redirect()->to(base_url('auth'));
        }

        $userRole = $session->get('departamento_usuario') ?? '';
        $loginType = $session->get('login_type') ?? '';

        $rolesPermitidos = $config['roles_permitidos'] ?? [];

        if (empty($rolesPermitidos)) {
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion'    => 'MANTENIMIENTO_ACCESO',
                'modulo'         => 'Sistema',
                'estado'         => 'fallido',
                'valores_nuevos' => json_encode(['mensaje' => 'Sin roles permitidos en mantenimiento', 'url' => $currentUri])
            ]);
            $session->destroy();
            return redirect()->to(base_url('mantenimiento'));
        }

        if ($this->hasAccess($userRole, $loginType, $rolesPermitidos)) {
            return;
        }

        \CodeIgniter\Events\Events::trigger('auditoria', [
            'tipo_accion'    => 'MANTENIMIENTO_ACCESO',
            'modulo'         => 'Sistema',
            'estado'         => 'fallido',
            'valores_nuevos' => json_encode(['mensaje' => 'Usuario no autorizado en mantenimiento', 'url' => $currentUri])
        ]);
        $session->destroy();

        return redirect()->to(base_url('mantenimiento'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }

    private function getConfig(): ?array
    {
        if (!file_exists(self::CONFIG_FILE)) {
            return null;
        }

        $content = file_get_contents(self::CONFIG_FILE);
        $config = json_decode($content, true);

        return $config;
    }

    private function hasAccess(string $userRole, string $loginType, array $rolesPermitidos): bool
    {
        foreach ($rolesPermitidos as $rol) {
            if ($loginType === $rol) {
                return true;
            }

            if (str_contains($userRole, $rol)) {
                return true;
            }
        }

        return false;
    }
}
