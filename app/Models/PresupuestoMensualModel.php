<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class PresupuestoMensualModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Finanzas';
    protected $auditIdentifyingFields = ['ID_UnidadOperativa', 'ID_GrupoPresupuestal', 'Anio', 'Mes'];

    protected $table            = 'PresupuestoMensual';
    protected $primaryKey       = 'ID_PresupuestoMensual';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'ID_UnidadOperativa',
        'ID_GrupoPresupuestal',
        'Anio',
        'Mes',
        'Monto_Asignado',
        'Monto_Comprometido',
        'Monto_Ejecutado'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];

    // Helper para obtener datos de la unidad operativa
    public function withUnidad()
    {
        return $this->select('PresupuestoMensual.*, UnidadOperativa.Nombre as Unidad_Nombre')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = PresupuestoMensual.ID_UnidadOperativa', 'left');
    }
}