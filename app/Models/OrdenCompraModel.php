<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdenCompraModel extends Model
{
    protected $table            = 'ordencompra';
    protected $primaryKey       = 'id_ordencompra';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['id_cotizacion', 'id_proveedor'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}