<?php

namespace App\Models;

use CodeIgniter\Model;

class RazonSocialModel extends Model
{
    protected $table            = 'RazonSocial';
    protected $primaryKey       = 'ID_RazonSocial';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Nombre'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
