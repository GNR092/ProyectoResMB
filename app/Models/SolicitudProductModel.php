<?php

namespace App\Models;

use CodeIgniter\Model;

class SolicitudProductModel extends Model
{
    protected $table            = 'Solicitud_Producto';
    protected $primaryKey       = 'ID_SolicitudProducto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Solicitud', 'Codigo', 'Nombre', 'Cantidad', 'Importe'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
