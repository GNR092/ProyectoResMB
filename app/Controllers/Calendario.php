<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\EventoCalendarioModel;
use App\Models\EventoArchivosModel;
use App\Models\DepartamentosModel;
use App\Libraries\Rest;
use App\Libraries\HttpStatus;

class Calendario extends ResourceController
{
    protected $format = 'json';
    protected $model;
    protected $archivosModel;
    protected $api;

    public function __construct()
    {
        $this->model = new EventoCalendarioModel();
        $this->archivosModel = new EventoArchivosModel();
        $this->api = new Rest();
    }

    public function index()
    {
        $rol = $this->rolCalendario();
        if ($rol === 'otro') {
            return $this->failForbidden('Sin acceso a la Agenda de Salidas');
        }
        $userId = session('id');
        $start = $this->request->getGet('start') ?? date('Y-m-01');
        $end   = $this->request->getGet('end')   ?? date('Y-m-t');

        // Contaduría y Administración ven todos los eventos; Compras solo los propios.
        if ($rol === 'contaduria' || $rol === 'admin') {
            $eventos = $this->model->enRango($start, $end)->findAll();
        } else {
            $eventos = $this->model->delUsuario($userId)
                                   ->enRango($start, $end)
                                   ->findAll();
        }

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
        $rol = $this->rolCalendario();
        if ($rol === 'otro') {
            return $this->failForbidden('Sin acceso a la Agenda de Salidas');
        }
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
        if (!$this->puedeEditarAgenda()) {
            return $this->failForbidden('Solo Compras puede crear eventos');
        }
        $userId = session('id');
        $payload = $this->request->getJSON(true) ?? $this->request->getVar();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (array_key_exists($campo, $payload)) {
                $payload[$campo] = $this->normalizarFecha($payload[$campo]);
            }
        }
        $rules = [
            'evento'       => 'required|max_length[250]',
            'fecha_inicio' => 'required|valid_date[Y-m-d H:i:s]',
            'fecha_fin'    => 'required|valid_date[Y-m-d H:i:s]',
            'color_evento' => 'required|max_length[20]',
            'ID_Solicitud' => 'required|is_natural_no_zero',
            'estatus'      => 'permit_empty|in_list[pendiente,cancelado,evidencia]',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();
        // Verificar orden de fechas
        if (strtotime($data['fecha_fin']) <= strtotime($data['fecha_inicio'])) {
            return $this->failValidationErrors(['fecha_fin' => 'La fecha de fin debe ser posterior a la de inicio']);
        }
        // Verificar que la solicitud exista
        $solExists = $this->api->getSolicitudWithProducts((int)$data['ID_Solicitud']);
        if (!$solExists) {
            return $this->failValidationErrors(['ID_Solicitud' => 'La solicitud seleccionada no existe']);
        }
        $data['ID_Usuario'] = $userId;
        $data['estatus']    = $data['estatus'] ?? 'pendiente';
        if ($this->model->insert($data)) {
            $evento = $this->model->find($this->model->getInsertID());
            return $this->respondCreated(['success' => true, 'data' => $this->formatEvent($evento)]);
        }
        return $this->failServerError('No se pudo crear el evento');
    }

    public function update($id = null)
    {
        if (!$this->puedeEditarAgenda()) {
            return $this->failForbidden('Solo Compras puede editar o cancelar eventos');
        }
        $rol = $this->rolCalendario();
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        if ($rol === 'admin') {
            $evento = $this->model->find($id);
        } else {
            $evento = $this->model->delUsuario((int)$userId)->find($id);
        }
        if (!$evento) {
            $evento = $rol === 'admin'
                ? $this->model->find($id)
                : $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) return $this->failNotFound('Evento no encontrado');
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload) || $payload === []) {
            $payload = $this->request->getVar();
        }
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (array_key_exists($campo, $payload)) {
                $payload[$campo] = $this->normalizarFecha($payload[$campo]);
            }
        }

        $rules = [
            'evento'       => 'max_length[250]',
            'color_evento' => 'max_length[20]',
            'ID_Solicitud' => 'permit_empty|is_natural_no_zero',
            'estatus'      => 'permit_empty|in_list[pendiente,cancelado,evidencia]',
        ];
        if (array_key_exists('fecha_inicio', $payload)) {
            $rules['fecha_inicio'] = 'valid_date[Y-m-d H:i:s]';
        }
        if (array_key_exists('fecha_fin', $payload)) {
            $rules['fecha_fin'] = 'valid_date[Y-m-d H:i:s]';
        }

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

        // Validar orden de fechas (usando valores nuevos o existentes)
        $inicio = $data['fecha_inicio'] ?? $evento['fecha_inicio'];
        $fin    = $data['fecha_fin']    ?? $evento['fecha_fin'];
        if (strtotime($fin) <= strtotime($inicio)) {
            return $this->failValidationErrors(['fecha_fin' => 'La fecha de fin debe ser posterior a la de inicio']);
        }

        $estatusActual = (string)($evento['estatus'] ?? 'pendiente');
        $estatusNuevo  = (string)($data['estatus'] ?? $estatusActual);
        $tieneEvidencia = (bool)$this->archivosModel->where('id_evento', $id)->first();

        if ($tieneEvidencia && $estatusNuevo === 'pendiente') {
            return $this->failValidationErrors(['estatus' => 'Un evento con evidencias no puede volver a "pendiente"']);
        }
        if (!$tieneEvidencia && $estatusNuevo === 'evidencia') {
            return $this->failValidationErrors(['estatus' => 'El estatus "evidencia" solo se asigna al adjuntar archivos']);
        }

        if ($this->model->update($id, $data)) {
            return $this->respond(['success' => true, 'data' => $this->formatEvent($this->model->find($id))]);
        }
        return $this->failServerError('No se pudo actualizar');
    }

    public function delete($id = null)
    {
        return $this->respond([
            'success' => false,
            'message' => 'Los eventos no se eliminan, solo se cancelan (cambie el estatus a "cancelado")',
        ], 405);
    }

    public function move($id = null)
    {
        if (!$this->puedeEditarAgenda()) {
            return $this->failForbidden('Solo Compras puede mover eventos');
        }
        $rol = $this->rolCalendario();
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        if ($rol === 'admin') {
            $evento = $this->model->find($id);
        } else {
            $evento = $this->model->delUsuario((int)$userId)->find($id);
        }
        if (!$evento) {
            // Fallback con tipo string por si el driver trata id como string
            $evento = $rol === 'admin'
                ? $this->model->find($id)
                : $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) {
                return $this->failNotFound('Evento no encontrado');
            }
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload) || empty($payload)) {
            $payload = $this->request->getVar();
            if (!is_array($payload)) $payload = [];
        }
        // Normalizar fechas usando el helper (maneja T y segundos faltantes)
        if (isset($payload['start'])) $payload['start'] = $this->normalizarFecha($payload['start']);
        if (isset($payload['end'])) $payload['end'] = $this->normalizarFecha($payload['end']);
        $rules = [
            'start' => 'required|valid_date[Y-m-d H:i:s]',
            'end'   => 'required|valid_date[Y-m-d H:i:s]',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();

        if (strtotime($data['end']) <= strtotime($data['start'])) {
            return $this->failValidationErrors(['end' => 'La fecha de fin debe ser posterior a la de inicio']);
        }

        if ($this->model->update($id, [
            'fecha_inicio' => $data['start'],
            'fecha_fin'    => $data['end'],
        ])) {
            return $this->respond(['success' => true, 'data' => $this->formatEvent($this->model->find($id))]);
        }
        return $this->failServerError('No se pudo mover');
    }

    /**
     * Sube archivos adjuntos a un evento
     * POST api/calendario/{id}/archivos
     */
    public function uploadArchivos($id = null)
    {
        if (!$this->puedeEditarAgenda()) {
            return $this->failForbidden('Solo Compras puede adjuntar evidencias');
        }
        $rol = $this->rolCalendario();
        $userId = session('id');
        $nombreUsuario = trim((string) session('nombre_usuario'));
        if ($nombreUsuario === '') {
            return $this->failValidationErrors(['archivos' => 'No se pudo identificar al usuario']);
        }

        $id = is_numeric($id) ? (int)$id : $id;
        if ($rol === 'admin') {
            $evento = $this->model->find($id);
        } else {
            $evento = $this->model->delUsuario((int)$userId)->find($id);
        }
        if (!$evento) {
            $evento = $rol === 'admin'
                ? $this->model->find($id)
                : $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) return $this->failNotFound('Evento no encontrado');
        }

        $files = $this->request->getFiles();
        if (empty($files) || !isset($files['archivos'])) {
            return $this->failValidationErrors(['archivos' => 'No se enviaron archivos']);
        }

        $uploadedFiles = $files['archivos'];
        if (!is_array($uploadedFiles)) {
            $uploadedFiles = [$uploadedFiles];
        }

        $saved = [];
        $errors = [];

        foreach ($uploadedFiles as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK) {
                $errors[] = $file->getClientName() . ': ' . $file->getErrorString();
                continue;
            }

            // Validar tipo MIME y tamaño (máx 10MB)
            $allowedMimes = [
                'application/pdf',
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ];
            $maxSize = 10 * 1024 * 1024; // 10MB

            // getMimeType() detecta el tipo en el servidor (más seguro que el MIME del cliente)
            if (!in_array($file->getMimeType(), $allowedMimes)) {
                $errors[] = $file->getClientName() . ': Tipo de archivo no permitido';
                continue;
            }
            if ($file->getSize() > $maxSize) {
                $errors[] = $file->getClientName() . ': Archivo excede 10MB';
                continue;
            }

            // Generar nombre único
            $ext = $file->getClientExtension();
            $newName = 'evento_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            // Mover a writable/uploads/eventos/
            $uploadPath = WRITEPATH . 'uploads/eventos/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            if ($file->move($uploadPath, $newName)) {
                $inserted = $this->archivosModel->insert([
                    'id_evento'      => $id,
                    'nombre_archivo' => $newName,
                    'nombre_usuario' => $nombreUsuario,
                ]);
                if ($inserted) {
                    $saved[] = $newName;
                } else {
                    unlink($uploadPath . $newName);
                    $errors[] = $file->getClientName() . ': Error al registrar en BD';
                }
            } else {
                $errors[] = $file->getClientName() . ': Error al guardar';
            }
        }

        // Auto-cambiar estatus a 'evidencia' si se subió al menos un archivo
        if (!empty($saved) && $evento['estatus'] !== 'evidencia') {
            $this->model->update($id, ['estatus' => 'evidencia']);
        }

        return $this->respond([
            'success' => true,
            'data'    => [
                'guardados' => $saved,
                'errores'   => $errors,
                'evento'    => $this->formatEvent($this->model->find($id)),
            ],
        ], HttpStatus::OK);
    }

    /**
     * Lista archivos de un evento
     * GET api/calendario/{id}/archivos
     */
    public function getArchivos($id = null)
    {
        $rol = $this->rolCalendario();
        if ($rol === 'otro') {
            return $this->failForbidden('Sin acceso a la Agenda de Salidas');
        }
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        if ($rol === 'admin' || $rol === 'contaduria') {
            $evento = $this->model->find($id);
        } else {
            $evento = $this->model->delUsuario((int)$userId)->find($id);
        }
        if (!$evento) {
            $evento = ($rol === 'admin' || $rol === 'contaduria')
                ? $this->model->find($id)
                : $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) return $this->failNotFound('Evento no encontrado');
        }

        $archivos = $this->archivosModel->getByEvento($id);

        return $this->respond([
            'success' => true,
            'data'    => $archivos,
        ], HttpStatus::OK);
    }

    /**
     * Rol del usuario actual para la Agenda de Salidas.
     * Resuelve el nombre del departamento desde BD (tolerante a
     * "Contaduría/Contaduria" y al sufijo " (Place)" de sesión).
     * @return 'admin'|'compras'|'contaduria'|'otro'
     */
    private function rolCalendario(): string
    {
        $nombre = '';
        try {
            $idDepto = session('id_departamento_usuario');
            if (!empty($idDepto)) {
                $depto = (new DepartamentosModel())->find($idDepto);
                $nombre = (string)($depto['Nombre'] ?? '');
            }
        } catch (\Throwable $e) {
            $nombre = '';
        }
        if ($nombre === '') {
            $nombre = (string)session('departamento_usuario');
        }
        // Quitar sufijo " (Place)" y normalizar acentos/mayúsculas
        $nombre = preg_replace('/\s*\(.*\)\s*/', '', $nombre) ?? $nombre;
        $norm = mb_strtolower(trim($nombre));
        $norm = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $norm
        );
        if (strpos($norm, 'administraci') !== false) return 'admin';
        if (strpos($norm, 'compras') !== false) return 'compras';
        if (strpos($norm, 'contadur') !== false) return 'contaduria';
        return 'otro';
    }

    private function puedeEditarAgenda(): bool
    {
        $rol = $this->rolCalendario();
        return $rol === 'admin' || $rol === 'compras';
    }

    private function formatEvent(array $e): array
    {
        $folio = null;
        $estadoSol = null;
        $tipoSol = null;
        $proveedorNombre = '';
        $complejoNombre = '';
        $departamentoNombre = '';
        $fechaSolicitud = '';
        if (!empty($e['ID_Solicitud'])) {
            try {
                $sol = $this->api->getSolicitudWithProducts((int)$e['ID_Solicitud']);
                if ($sol) {
                    $folio = $sol['No_Folio'] ?? null;
                    $estadoSol = $sol['EstadoOrden'] ?? $sol['Estado'] ?? null;
                    $tipoSol = $sol['Tipo'] ?? null;
                    $proveedorNombre = $sol['RazonSocialNombre'] ?? $sol['ProveedorNombre'] ?? $sol['Proveedor'] ?? $sol['RazonSocial'] ?? '';
                    $complejoNombre = $sol['PlaceNombre'] ?? $sol['Complejo'] ?? $sol['RazonSocialNombre'] ?? '';
                    $departamentoNombre = $sol['DepartamentoNombre'] ?? $sol['Departamento'] ?? '';
                    $fechaSolicitud = $sol['FechaSolicitud'] ?? $sol['Fecha'] ?? '';
                }
            } catch (\Throwable $ex) {}
        }

        // Obtener archivos del evento
        $archivos = $this->archivosModel->getByEvento((int)$e['id']);
        $archivosData = array_map(function ($a) use ($e) {
            return [
                'id_archivo'      => $a['id_archivo'],
                'nombre_archivo'  => $a['nombre_archivo'],
                'nombre_usuario'  => $a['nombre_usuario'] ?? '',
                'fecha_subida'    => $a['fecha_subida'],
                'url_descarga'    => base_url('api/calendario/' . $e['id'] . '/archivos/' . $a['id_archivo'] . '/download'),
            ];
        }, $archivos);

        $title = $e['evento'];
        if ($folio) {
            $title = $title . ' — ' . $folio;
        }
        $estatus = $e['estatus'] ?? 'pendiente';
        return [
            'id'              => (string)$e['id'],
            'title'           => $title,
            'start'           => $e['fecha_inicio'],
            'end'             => $e['fecha_fin'],
            'backgroundColor' => $e['color_evento'],
            'borderColor'     => $e['color_evento'],
            'classNames'      => $estatus === 'cancelado' ? ['ev-cancelado'] : [],
            'extendedProps'   => [
                'color'            => $e['color_evento'],
                'estatus'          => $e['estatus'] ?? 'pendiente',
                'ID_Solicitud'     => $e['ID_Solicitud'] ?? null,
                'No_Folio'         => $folio,
                'EstadoSolicitud'  => $estadoSol,
                'TipoSolicitud'    => $tipoSol,
                'Proveedor'        => $proveedorNombre,
                'Complejo'         => $complejoNombre,
                'Departamento'     => $departamentoNombre,
                'FechaSolicitud'   => $fechaSolicitud,
                'evento_raw'       => $e['evento'],
                'archivos'         => $archivosData,
            ],
            'allDay'          => false,
        ];
    }

    /**
     * Descarga un archivo de un evento
     * GET api/calendario/{id}/archivos/{id_archivo}/download
     */
    public function downloadArchivo($id = null, $idArchivo = null)
    {
        $rol = $this->rolCalendario();
        if ($rol === 'otro') {
            return $this->failForbidden('Sin acceso a la Agenda de Salidas');
        }
        $userId = session('id');
        $id = is_numeric($id) ? (int)$id : $id;
        if ($rol === 'admin' || $rol === 'contaduria') {
            $evento = $this->model->find($id);
        } else {
            $evento = $this->model->delUsuario((int)$userId)->find($id);
        }
        if (!$evento) {
            $evento = ($rol === 'admin' || $rol === 'contaduria')
                ? $this->model->find($id)
                : $this->model->where('ID_Usuario', (int)$userId)->where('id', $id)->first();
            if (!$evento) return $this->failNotFound('Evento no encontrado');
        }

        $idArchivo = is_numeric($idArchivo) ? (int)$idArchivo : $idArchivo;
        $archivo = $this->archivosModel->find($idArchivo);
        if (!$archivo || $archivo['id_evento'] != $id) {
            return $this->failNotFound('Archivo no encontrado');
        }

        $filePath = WRITEPATH . 'uploads/eventos/' . $archivo['nombre_archivo'];
        if (!file_exists($filePath)) {
            return $this->failNotFound('Archivo físico no encontrado');
        }

        return $this->response->download($filePath, null)->setFileName($archivo['nombre_archivo']);
    }

    /**
     * Normaliza fechas de la API a 'Y-m-d H:i:s'.
     *
     * Los <input type="datetime-local"> entregan 'Y-m-d\TH:i' (sin segundos),
     * formato que valid_date[Y-m-d H:i:s] rechaza. Acepta ambas variantes
     * con o sin separador 'T'.
     */
    private function normalizarFecha($valor): string
    {
        $original = is_string($valor) ? $valor : '';
        $valor    = str_replace('T', ' ', trim((string) $valor));

        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $formato) {
            $fecha   = \DateTime::createFromFormat($formato, $valor);
            $errores = \DateTime::getLastErrors();
            if ($fecha === false) {
                continue;
            }
            if ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0)) {
                continue;
            }
            return $fecha->format('Y-m-d H:i:s');
        }

        // Formato no reconocido: se devuelve intacto para que valid_date
        // genere el mensaje de error al usuario.
        return $original;
    }
}