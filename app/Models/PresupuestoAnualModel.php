<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class PresupuestoAnualModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Finanzas';
    protected $auditIdentifyingFields = ['ID_RazonSocial', 'Anio'];

    protected $table            = 'PresupuestoAnual';
    protected $primaryKey       = 'ID_PresupuestoAnual';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'ID_RazonSocial',
        'Anio',
        'Monto'
    ];

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

    // Helper para obtener datos de la Razón Social
    public function withRazonSocial()
    {
        return $this->select('PresupuestoAnual.*, Razon_Social.Nombre as RazonSocial_Nombre')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = PresupuestoAnual.ID_RazonSocial', 'left');
    }
}