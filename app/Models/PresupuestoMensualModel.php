<?php

namespace App\Models;

use CodeIgniter\Model;

class PresupuestoMensualModel extends Model
{
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

    // Helper para obtener datos del departamento
    public function withDepartamento()
    {
        return $this->select('PresupuestoMensual.*, Departamentos.Nombre as Dpto_Nombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = PresupuestoMensual.ID_Dpto', 'left');
    }
}