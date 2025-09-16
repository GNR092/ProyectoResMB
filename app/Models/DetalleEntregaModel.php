<?php

namespace App\Models;

use CodeIgniter\Model;

class DetalleEntregaModel extends Model
{
    protected $table            = 'DetalleEntrega';
    protected $primaryKey       = 'ID_DetalleEntrega';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_Entrega',
        'ID_Producto',
        'Cantidad',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}