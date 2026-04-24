<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class DepartamentosModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Catálogos';

    protected $table            = 'Departamentos';
    protected $primaryKey       = 'ID_Dpto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_UnidadOperativa', 'ID_Place', 'Nombre'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Callbacks
    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterDelete  = ['auditDelete'];
}