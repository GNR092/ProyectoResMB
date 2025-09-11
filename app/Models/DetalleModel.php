<?php

namespace App\Models;

use CodeIgniter\Model;

class DetalleModel extends Model
{
    protected $table            = 'Detalle_Producto';
    protected $primaryKey       = 'ID_DetalleProd';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Solicitud', 'Nombre_Producto', 'Cantidad', 'Costo'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}