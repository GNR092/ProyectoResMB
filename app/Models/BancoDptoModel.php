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
        'ID_Dpto',
        'Clabe',
        'Banco'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Helper para traer nombre del departamento
    public function withDepartamento()
    {
        return $this->select('BancoDpto.*, Departamentos.Nombre as Dpto_Nombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = BancoDpto.ID_Dpto', 'left');
    }
}
