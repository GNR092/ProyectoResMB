<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class CuentasModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Configuración';

    protected $table            = 'Cuentas';

    protected $primaryKey       = 'ID_Cuenta';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_Proveedor',
        'Cuenta'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = ['auditInsert'];
    protected $beforeUpdate   = ['captureOldData'];
    protected $afterUpdate    = ['auditUpdate'];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = ['captureOldData'];
    protected $afterDelete    = ['auditDelete'];
}