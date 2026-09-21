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

    public function create()
    {
        $userId = session('id');
        $payload = $this->request->getJSON(true) ?? $this->request->getVar();
        $rules = [
            'evento'       => 'required|max_length[250]',
            'fecha_inicio' => 'required|valid_date[Y-m-d H:i:s]',
            'fecha_fin'    => 'required|valid_date[Y-m-d H:i:s]',
            'color_evento' => 'required|max_length[20]',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();
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
        $evento = $this->model->delUsuario($userId)->find($id);
        if (!$evento) {
            return $this->failNotFound('Evento no encontrado');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getVar();
        $rules = [
            'evento'       => 'max_length[250]',
            'fecha_inicio' => 'valid_date[Y-m-d H:i:s]',
            'fecha_fin'    => 'valid_date[Y-m-d H:i:s]',
            'color_evento' => 'max_length[20]',
        ];
        if (!$this->validateData($payload, $rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $data = $this->validator->getValidated();
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
        $evento = $this->model->delUsuario($userId)->find($id);
        if (!$evento) {
            return $this->failNotFound('Evento no encontrado');
        }

        if ($this->model->delete($id)) {
            return $this->respondDeleted(['success' => true, 'message' => 'Evento eliminado']);
        }
        return $this->failServerError('No se pudo eliminar');
    }

    public function move($id = null)
    {
        $userId = session('id');
        $evento = $this->model->delUsuario($userId)->find($id);
        if (!$evento) {
            return $this->failNotFound('Evento no encontrado');
        }

        $payload = $this->request->getJSON(true) ?? $this->request->getVar();
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
        return [
            'id'              => (string)$e['id'],
            'title'           => $e['evento'],
            'start'           => $e['fecha_inicio'],
            'end'             => $e['fecha_fin'],
            'backgroundColor' => $e['color_evento'],
            'borderColor'     => $e['color_evento'],
            'allDay'          => false,
        ];
    }
}