<?php

namespace App\Controllers;

use App\Models\TokenModel;
use App\Models\UsuariosModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * ApiAuth - Autenticación externa para app híbrida móvil (solo pruebas).
 *
 * Endpoints públicos (NO requieren sesión web):
 *   POST /api/auth/login   {email, password} -> {token, login_type, user}
 *   GET  /api/auth/me      Header Authorization: Bearer <token> -> {user}
 *   POST /api/auth/logout  Header Authorization: Bearer <token> -> {message}
 *
 * Diseño de impacto cero:
 * - No toca Auth, Api, ni filtros existentes.
 * - No borra ni actualiza tokens existentes: inserta UNA fila nueva por login
 *   móvil, así las sesiones web abiertas no se invalidan.
 * - Logout móvil elimina SOLO la fila del token presentado.
 * - CORS se maneja dentro del controlador (Access-Control-Allow-Origin: *),
 *   sin modificar la config global.
 */
class ApiAuth extends ResourceController
{
    protected $format = 'json';

    /**
     * CORS mínimo para app externa (WebView / fetch).
     */
    private function cors()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
    }

    /**
     * Respuesta a preflight OPTIONS.
     */
    public function options($any = null)
    {
        $this->cors();
        return $this->respond(null, 204);
    }

    /**
     * POST /api/auth/login
     * Body JSON: {"email": "...", "password": "..."}
     * Acepta también form-data / x-www-form-urlencoded con los mismos campos.
     */
    public function login()
    {
        $this->cors();

        $data = $this->request->getJSON(true);
        if (empty($data) || ! is_array($data)) {
            $data = [
                'email'    => $this->request->getVar('email'),
                'password' => $this->request->getVar('password'),
            ];
        }

        $email    = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Se requiere email y password.',
            ], 422);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Email no válido.',
            ], 422);
        }

        $userModel = new UsuariosModel();
        $user      = $userModel->where('Correo', $email)->first();

        if (! $user) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        // Mismo esquema que el login web: ContrasenaP = jefe, ContrasenaG = empleado.
        // Para la app se acepta cualquiera de las dos (sin forzar tipo).
        $loginType = null;
        if (! empty($user['ContrasenaP']) && password_verify($password, $user['ContrasenaP'])) {
            $loginType = 'boss';
        } elseif (! empty($user['ContrasenaG']) && password_verify($password, $user['ContrasenaG'])) {
            $loginType = 'employee';
        }

        if ($loginType === null) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        // Token nuevo SIN borrar los existentes (no invalida sesiones web).
        do {
            $token = bin2hex(random_bytes(32));
        } while ((new TokenModel())->where('token', $token)->first());

        $tokenModel = new TokenModel();
        $tokenModel->insert([
            'ID_Usuario' => $user['ID_Usuario'],
            'token'      => $token,
        ]);

        unset($user['ContrasenaP'], $user['ContrasenaG']);

        return $this->respond([
            'status'     => 'success',
            'token'      => $token,
            'token_type' => 'Bearer',
            'login_type' => $loginType,
            'user'       => $user,
        ], 200);
    }

    /**
     * Extrae el token del header Authorization: Bearer <token>.
     */
    private function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if ($header === '') {
            $header = $this->request->getServer('HTTP_AUTHORIZATION') ?? '';
        }
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        // Permite también ?token=... para pruebas rápidas en navegador.
        $q = $this->request->getGet('token');
        return is_string($q) && $q !== '' ? $q : null;
    }

    /**
     * GET /api/auth/me
     * Header: Authorization: Bearer <token>
     */
    public function me()
    {
        $this->cors();

        $token = $this->bearerToken();
        if ($token === null) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Token no proporcionado. Usa: Authorization: Bearer <token>.',
            ], 401);
        }

        $row = (new TokenModel())->where('token', $token)->first();
        if (! $row || empty($row['ID_Usuario'])) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Token inválido.',
            ], 401);
        }

        $user = (new UsuariosModel())->find($row['ID_Usuario']);
        if (! $user) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Usuario no encontrado.',
            ], 401);
        }

        unset($user['ContrasenaP'], $user['ContrasenaG']);

        return $this->respond([
            'status' => 'success',
            'user'   => $user,
        ], 200);
    }

    /**
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     * Elimina SOLO ese token (no afecta otras sesiones del mismo usuario).
     */
    public function logout()
    {
        $this->cors();

        $token = $this->bearerToken();
        if ($token === null) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Token no proporcionado.',
            ], 401);
        }

        $tokenModel = new TokenModel();
        $row        = $tokenModel->where('token', $token)->first();
        if ($row) {
            $tokenModel->delete($row['ID_Token']);
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'Sesión móvil cerrada.',
        ], 200);
    }
}
