<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdenCompraModel extends Model
{
    protected $table            = 'Orden_Compra';
    protected $primaryKey       = 'ID_OrdenCompra';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Cotizacion', 'ID_Proveedor'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}