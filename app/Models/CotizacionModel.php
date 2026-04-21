<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class CotizacionModel extends Model
{
    use AuditTrait;

    protected $table            = 'Cotizacion';
    protected $primaryKey       = 'ID_Cotizacion';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Solicitud', 'ID_Proveedor', 'Cotizacion_Files', 'Total', 'ID_Usuario_Cotiza'];

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
    protected $afterDelete  = ['auditDelete'];
}