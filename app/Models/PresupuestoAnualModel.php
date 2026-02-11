<?php

namespace App\Models;

use CodeIgniter\Model;

class PresupuestoAnualModel extends Model
{
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

    // Helper para obtener datos de la Razón Social
    public function withRazonSocial()
    {
        return $this->select('PresupuestoAnual.*, Razon_Social.Nombre as RazonSocial_Nombre')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = PresupuestoAnual.ID_RazonSocial', 'left');
    }
}