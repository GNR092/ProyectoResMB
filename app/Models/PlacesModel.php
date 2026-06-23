<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class PlacesModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Catálogos';

    protected $table            = 'Places';
    protected $primaryKey       = 'ID_Place';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Nombre_Corto', 'Nombre_Completo', 'ID_RazonSocial', 'id_segmento', 'activo'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validación
    protected $validationRules      = [
        'Nombre_Corto' => 'required',
        'Nombre_Completo' => 'required',
        'ID_RazonSocial' => 'required'
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $beforeInsert = ['normalizeSegmento', 'normalizeUnidadOperativa'];
    protected $beforeUpdate = ['captureOldData', 'normalizeSegmento', 'normalizeUnidadOperativaOnUpdate'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterDelete  = ['auditDelete'];

    protected function normalizeSegmento(array $data): array
    {
        if (! isset($data['data']['id_segmento'])) {
            return $data;
        }

        $valor = $data['data']['id_segmento'];
        if ($valor === '' || $valor === '0' || $valor === 0 || $valor === null) {
            $data['data']['id_segmento'] = null;
        } else {
            $data['data']['id_segmento'] = (int) $valor;
        }

        return $data;
    }

    protected function normalizeUnidadOperativa(array $data): array
    {
        if (! array_key_exists('ID_UnidadOperativa', $data['data'] ?? [])) {
            return $data;
        }

        $valor = $data['data']['ID_UnidadOperativa'];
        if ($valor === '' || $valor === '0' || $valor === 0 || $valor === null) {
            $data['data']['ID_UnidadOperativa'] = null;
            return $data;
        }

        $unidad = (int) $valor;
        $data['data']['ID_UnidadOperativa'] = $unidad > 0 ? $unidad : null;
        return $data;
    }

    protected function normalizeUnidadOperativaOnUpdate(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        return $this->normalizeUnidadOperativa($data);
    }
}