<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class PagoModel extends Model
{
    use AuditTrait;

    protected $table            = 'Pago';
    protected $primaryKey       = 'ID_Pago';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_OrdenCompra', 'ID_Proveedor', 'Tipo', 'Fecha_Solicitud', 'Fecha_Pago', 'Folio', 'Concepto', 'Forma', 'Fecha_Comprobante'];

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