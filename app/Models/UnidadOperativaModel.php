<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class UnidadOperativaModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Configuración';

    protected $table            = 'UnidadOperativa';
    protected $primaryKey       = 'ID_UnidadOperativa';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Nombre', 'ID_Place', 'activo'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeUpdate = ['captureOldData'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];
}
