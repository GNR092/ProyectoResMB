<?php

namespace App\Models;

use CodeIgniter\Model;

class BancoDptoModel extends Model
{
    protected $table            = 'BancoDpto';
    protected $primaryKey       = 'ID_BancoDpto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'ID_RazonSocial',
        'Clabe',
        'Banco'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Helper para traer nombre de la razón social
    public function withRazonSocial()
    {
        return $this->select('BancoDpto.*, Razon_Social.Nombre as RazonSocial_Nombre')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = BancoDpto.ID_RazonSocial', 'left');
    }
}
