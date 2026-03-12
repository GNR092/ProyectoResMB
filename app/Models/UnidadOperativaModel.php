<?php

namespace App\Models;

use CodeIgniter\Model;

class UnidadOperativaModel extends Model
{
    protected $table            = 'UnidadOperativa';
    protected $primaryKey       = 'ID_UnidadOperativa';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Nombre', 'ID_Place', 'activo'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
