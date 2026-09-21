<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EventoCalendarioModel;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;

class Calendario extends ResourceController
{
    protected $format = 'json';
    protected $model;
    protected $api;

    public function __construct()
    {
        $this->model = new EventoCalendarioModel();
        $this->api = new Rest();
    }

    public function index()
    {
        $userId = session('id');
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end   = $this->request->getGet('end')   ?? date('Y-m-t');

        $eventos = $this->model->delUsuario($userId)
                               ->enRango($start, $end)
                               ->findAll();

        $data = array_map(fn($e) => $this->formatEvent($e), $eventos);

        return $this->respond(['success' => true, 'data' => $data], HttpStatus::OK);
    }

    /**
     * Listado ligero de solicitudes para selector del calendario.
     * Todos los estados, busqueda por folio, filtros avanzados solo si se proveen.
     * GET api/calendario/solicitudes?search=&folio=&estado=&tipo=&fecha=&proveedores=&razones_sociales=&departamentos=&page=&per_page=
     */
    public function solicitudes()
    {
        $search = trim((string)($this->request->getGet('search') ?? $this->request->getGet('folio') ?? ''));
        $folio = trim((string)($this->request->getGet('folio') ?? $search));
        // Normalizar folio: aceptar solo números (ej. 123 -> MBSP-123, MBSP-123 -> 123)
        if ($folio !== '') {
            $folioNorm = preg_replace('/^MBSP-?/i', '', $folio);
            // Si es solo números, buscar por substring numérico (like %123%)
            // Mantener folio normalizado para like flexible
            $folio = $folioNorm !== '' ? $folioNorm : $folio;
        }
        $estado = $this->request->getGet('estado');
        $tipo = $this->request->getGet('tipo');
        $fecha = $this->request->getGet('fecha');
        $porMes = $this->request->getGet('por_mes');
        $proveedores = $this->request->getGet('proveedores');
        $razones = $this->request->getGet('razones_sociales');
        $departamentos = $this->request->getGet('departamentos');
        $page = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = max(1, min(50, (int)($this->request->getGet('per_page') ?? 20)));

        $filters = [
            'folio' => $folio,
            'estado' => $estado,
            'tipo' => $tipo,
            'fecha' => $fecha,
            'por_mes' => $porMes,
            'proveedores' => $proveedores,
            'razones_sociales' => $razones,
            'departamentos' => $departamentos,
        ];
        // limpiar vacios
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '' && $v !== []);

        // Soporte search generico: si search y no folio, mapear a folio
        if ($search !== '' && empty($filters['folio'])) {
            $filters['folio'] = $search;
        }

        $userId = session('id');
        $deptId = session('id_departamento_usuario');

        try {
            $result = $this->api->getSolicitudPaginated($page, $perPage, $filters, $userId);
            // Mapear a formato ligero para Choices - incluye Monto y Proveedor para mostrar en selector
            $data = array_map(function ($item) {
                // Monto puede venir como Total (Cotizacion), Monto, o calculado
                $monto = $item['Total'] ?? $item['Monto'] ?? $item['CotizacionTotal'] ?? $item['total'] ?? '';
                if ($monto === '' && isset($item['cotizacion']['Total'])) $monto = $item['cotizacion']['Total'];
                $proveedor = $item['Proveedor'] ?? $item['ProveedorNombre'] ?? $item['RazonSocialProveedor'] ?? $item['RazonSocial'] ?? '';
                // Fallback para nombre proveedor desde cotización
                if ($proveedor === '' && isset($item['cotizacion']['ProveedorNombre'])) $proveedor = $item['cotizacion']['ProveedorNombre'];
                return [
                    'ID_Solicitud' => $item['ID_Solicitud'] ?? $item['id_solicitud'] ?? null,
                    'No_Folio' => $item['No_Folio'] ?? $item['no_folio'] ?? '',
                    'Fecha' => $item['Fecha'] ?? '',
                    'Estado' => $item['Estado'] ?? $item['EstadoOrden'] ?? '',
                    'EstadoOrden' => $item['EstadoOrden'] ?? null,
                    'Tipo' => $item['Tipo'] ?? null,
                    'RazonSocial' => $item['RazonSocial'] ?? $item['RazonSocialNombre'] ?? '',
                    'Proveedor' => $proveedor,
                    'Departamento' => $item['Departamento'] ?? $item['DepartamentoNombre'] ?? '',
                    'Monto' => $monto,
                ];
            }, $result['data'] ?? []);

            return $this->respond([
                'success' => true,
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
            ], HttpStatus::OK);
        } catch (\Throwable $e) {
            log_message('error', 'Calendario::solicitudes ' . $e->getMessage());
            return $this->failServerError('Error cargando solicitudes');
        }
    }

    public function create()
    {
        $userId = session('id');
        $payload = $this->request->getJSON(true) ?? $this->request->getVar();
        $rules = [
            'evento'       => 'required|max_length[250]',
            'fecha_inicio' => 'required|valid_date[Y-m-d H:i:s]',
            'fecha_fin'    => 'required|valid_date[Y-m-d H:i:s]',
            'color_evento' => 'required|max_length[20]',
            'ID_Solicitud' => 'required|is_natural_no_zero',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();
        // Verificar que la solicitud exista
        $solExists = $this->api->getSolicitudWithProducts((int)$data['ID_Solicitud']);
        if (!$solExists) {
            return $this->failValidationErrors(['ID_Solicitud' => 'La solicitud seleccionada no existe']);
        }
        $data['ID_Usuario'] = $userId;
        if ($this->model->insert($data)) {
            $evento = $this->model->find($this->model->getInsertID());
            return $this->respondCreated(['success' => true, 'data' => $this->formatEvent($evento)]);
        }
        return $this->failServerError('No se pudo crear el evento');
    }

    public function update($id = null)
    {
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        $evento = $this->model->delUsuario((int)$userId)->find($id);
        if (!$evento) {
            $evento = $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) return $this->failNotFound('Evento no encontrado');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getVar();
        $rules = [
            'evento'       => 'max_length[250]',
            'fecha_inicio' => 'valid_date[Y-m-d H:i:s]',
            'fecha_fin'    => 'valid_date[Y-m-d H:i:s]',
            'color_evento' => 'max_length[20]',
            'ID_Solicitud' => 'permit_empty|is_natural_no_zero',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();
        if (isset($data['ID_Solicitud']) && $data['ID_Solicitud'] !== '' && $data['ID_Solicitud'] !== null) {
            $solExists = $this->api->getSolicitudWithProducts((int)$data['ID_Solicitud']);
            if (!$solExists) {
                return $this->failValidationErrors(['ID_Solicitud' => 'La solicitud seleccionada no existe']);
            }
        }
        if (empty($data)) {
            return $this->failValidationErrors(['evento' => 'Nada que actualizar']);
        }

        if ($this->model->update($id, $data)) {
            return $this->respond(['success' => true, 'data' => $this->formatEvent($this->model->find($id))]);
        }
        return $this->failServerError('No se pudo actualizar');
    }

    public function delete($id = null)
    {
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        $evento = $this->model->delUsuario((int)$userId)->find($id);
        if (!$evento) {
            $evento = $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) return $this->failNotFound('Evento no encontrado');
        }

        if ($this->model->delete($id)) {
            return $this->respondDeleted(['success' => true, 'message' => 'Evento eliminado']);
        }
        return $this->failServerError('No se pudo eliminar');
    }

    public function move($id = null)
    {
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        $evento = $this->model->delUsuario((int)$userId)->find($id);
        if (!$evento) {
            // Fallback con tipo string por si el driver trata id como string
            $evento = $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) {
                return $this->failNotFound('Evento no encontrado');
            }
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload) || empty($payload)) {
            $payload = $this->request->getVar();
            if (!is_array($payload)) $payload = [];
        }
        // Normalizar payload por compatibilidad JS (start/end ya vienen Y-m-d H:i:s desde toLocalStr)
        if (isset($payload['start'])) $payload['start'] = trim($payload['start']);
        if (isset($payload['end'])) $payload['end'] = trim($payload['end']);
        $rules = [
            'start' => 'required|valid_date[Y-m-d H:i:s]',
            'end'   => 'required|valid_date[Y-m-d H:i:s]',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();

        if ($this->model->update($id, [
            'fecha_inicio' => $data['start'],
            'fecha_fin'    => $data['end'],
        ])) {
            return $this->respond(['success' => true, 'data' => $this->formatEvent($this->model->find($id))]);
        }
        return $this->failServerError('No se pudo mover');
    }

    private function formatEvent(array $e): array
    {
        $folio = null;
        $estadoSol = null;
        if (!empty($e['ID_Solicitud'])) {
            try {
                $sol = $this->api->getSolicitudWithProducts((int)$e['ID_Solicitud']);
                if ($sol) {
                    $folio = $sol['No_Folio'] ?? null;
                    $estadoSol = $sol['EstadoOrden'] ?? $sol['Estado'] ?? null;
                }
            } catch (\Throwable $ex) {}
        }
        $title = $e['evento'];
        if ($folio) {
            $title = $title . ' — ' . $folio;
        }
        return [
            'id'              => (string)$e['id'],
            'title'           => $title,
            'start'           => $e['fecha_inicio'],
            'end'             => $e['fecha_fin'],
            'backgroundColor' => $e['color_evento'],
            'borderColor'     => $e['color_evento'],
            'extendedProps'   => [
                'color' => $e['color_evento'],
                'ID_Solicitud' => $e['ID_Solicitud'] ?? null,
                'No_Folio' => $folio,
                'EstadoSolicitud' => $estadoSol,
                'evento_raw' => $e['evento'],
            ],
            'allDay'          => false,
        ];
    }
}