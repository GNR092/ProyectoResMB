<?php

namespace App\Models;

use CodeIgniter\Model;

class HistorialProductosModel extends Model
{
    protected $table            = 'HistorialProductos';
    protected $primaryKey       = 'ID_HistorialP';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_Usuario',
        'ID_Producto',
        'CodigoAnt',
        'NombreAnt',
        'ExistenciaAnt',
        'CodigoNew',
        'NombreNew',
        'ExistenciaNew',
        'Razon',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}

