<?php

namespace App\Controllers;

use App\Libraries\Rest;
use App\Models\DepartamentosModel;
use App\Models\TokenModel;
use App\Models\UsuariosModel;
use CodeIgniter\RESTful\ResourceController;

/**
 * ApiExternal - Réplica de solo lectura de la vista "ver historial" para app
 * híbrida móvil externa (solo pruebas).
 *
 * Autenticación: Header Authorization: Bearer <token> (token obtenido en
 * POST api/auth/login). NO usa sesión web.
 *
 * Endpoints públicos (todos GET, solo lectura, no escriben nada):
 *   GET /api/ext/historial              Lista paginada (filtros por query string)
 *   GET /api/ext/solicitud/details/{id} Detalle de una solicitud
 *   GET /api/ext/providers              Catálogo corto de proveedores (filtros)
 *   GET /api/ext/razones                Catálogo de razones sociales (filtros)
 *   GET /api/ext/departments            Catálogo de departamentos (filtros)
 *
 * Regla por usuario (igual que la web): si el departamento del usuario NO está
 * en la lista de excepciones, el servidor fuerza dept_id a su departamento
 * (ve su depto + sus propias solicitudes); si SÍ está, ve todo.
 */
class ApiExternal extends ResourceController
{
    protected $format = 'json';

    /**
     * Departamentos que ven todo el historial (misma lista del frontend web).
     */
    private const DEPT_EXCEPTIONS = [
        'Compras',
        'Administración',
        'Direccion',
        'Tesoreria',
        'Direccion Campus',
        'Contaduría',
    ];

    private function cors()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
    }

    public function options($any = null)
    {
        $this->cors();
        return $this->respond(null, 204);
    }

    private function bearerToken(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if ($header === '') {
            $header = $this->request->getServer('HTTP_AUTHORIZATION') ?? '';
        }
        if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
            return $m[1];
        }
        $q = $this->request->getGet('token');
        return is_string($q) && $q !== '' ? $q : null;
    }

    /**
     * Valida el Bearer token y devuelve [user, deptName] o null.
     *
     * @return array|null [array $user, string $deptName]
     */
    private function authUser(): ?array
    {
        $token = $this->bearerToken();
        if ($token === null) {
            return null;
        }

        $row = (new TokenModel())->where('token', $token)->first();
        if (! $row || empty($row['ID_Usuario'])) {
            return null;
        }

        $user = (new UsuariosModel())->find($row['ID_Usuario']);
        if (! $user) {
            return null;
        }

        $deptName = '';
        if (! empty($user['ID_Dpto'])) {
            $dept = (new DepartamentosModel())->find($user['ID_Dpto']);
            $deptName = $dept['Nombre'] ?? '';
        }

        return [$user, $deptName];
    }

    private function unauthorized()
    {
        $this->cors();
        return $this->respond([
            'status'  => 'error',
            'message' => 'Token no válido o no proporcionado. Usa: Authorization: Bearer <token>.',
        ], 401);
    }

    /**
     * GET /api/ext/historial?page=1&per_page=10&vista=&estado=&fecha=&por_mes=
     *     &folio=&tipo=&metodo=&proveedores=&razones_sociales=&departamentos=
     */
    public function historial()
    {
        $this->cors();

        $auth = $this->authUser();
        if ($auth === null) {
            return $this->unauthorized();
        }
        [$user, $deptName] = $auth;

        $filters = [
            'vista'            => $this->request->getGet('vista'),
            'estado'           => $this->request->getGet('estado'),
            'fecha'            => $this->request->getGet('fecha'),
            'por_mes'          => $this->request->getGet('por_mes'),
            'folio'            => $this->request->getGet('folio'),
            'tipo'             => $this->request->getGet('tipo'),
            'metodo'           => $this->request->getGet('metodo'),
            'proveedores'      => $this->request->getGet('proveedores'),
            'razones_sociales' => $this->request->getGet('razones_sociales'),
            'departamentos'    => $this->request->getGet('departamentos'),
        ];

        // Regla por usuario: fuera de excepciones, se fuerza su departamento.
        if (! in_array($deptName, self::DEPT_EXCEPTIONS, true)) {
            $filters['dept_id'] = $user['ID_Dpto'];
        }

        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = max(1, (int) ($this->request->getGet('per_page') ?? 10));

        $result = (new Rest())->getSolicitudPaginated($page, $perPage, $filters, (int) $user['ID_Usuario']);

        return $this->respond($result, 200);
    }

    /**
     * GET /api/ext/solicitud/details/{id}
     */
    public function solicitudDetails($id = null)
    {
        $this->cors();

        if ($this->authUser() === null) {
            return $this->unauthorized();
        }

        if ($id === null || ! is_numeric($id)) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'Se requiere un ID de solicitud numérico.',
            ], 422);
        }

        $details = (new Rest())->getSolicitudWithProducts((int) $id);

        if (empty($details)) {
            return $this->respond([
                'status'  => 'error',
                'message' => 'No se encontraron detalles para la solicitud con ID: ' . $id,
            ], 404);
        }

        return $this->respond($details, 200);
    }

    /**
     * GET /api/ext/providers
     */
    public function providers()
    {
        $this->cors();

        if ($this->authUser() === null) {
            return $this->unauthorized();
        }

        return $this->respond((new Rest())->getProveedorIdAndRazonSocial(), 200);
    }

    /**
     * GET /api/ext/razones
     */
    public function razones()
    {
        $this->cors();

        if ($this->authUser() === null) {
            return $this->unauthorized();
        }

        $data = \Config\Database::connect()
            ->table('Razon_Social')
            ->select('ID_RazonSocial, Nombre, RFC, Nombre_Comercial, Direccion')
            ->get()
            ->getResultArray();

        return $this->respond($data, 200);
    }

    /**
     * GET /api/ext/departments
     */
    public function departments()
    {
        $this->cors();

        if ($this->authUser() === null) {
            return $this->unauthorized();
        }

        return $this->respond((new Rest())->getAllDepartments(), 200);
    }
}
