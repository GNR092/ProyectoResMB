<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class SolicitudModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Operaciones';

    protected $table = 'Solicitud';
    protected $primaryKey = 'ID_Solicitud';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'ID_Usuario',
        'ID_Dpto',
        'ID_UnidadOperativa',
        'ID_Proveedor',
        'ID_Cuenta',
        'ID_RazonSocial',
        'IVA',
        'Fecha',
        'Estado',
        'No_Folio',
        'Archivo',
        'ComentariosAdmin',
        'TipoComentarioAdmin',
        'ComentariosUser',
        'Tipo',
        'MetodoPago',
        'Fecha_Aprobacion',
        'ID_Usuario_Autoriza',
        'ComentarioCotizacion',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = ['normalizeUnidadOperativa'];
    protected $beforeUpdate = ['normalizeUnidadOperativaOnUpdate', 'captureOldData'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];

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
