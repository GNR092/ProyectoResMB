<?php

namespace App\Models;

use CodeIgniter\Model;

class DetalleIngresoModel extends Model
{
    protected $table            = 'DetalleIngreso';
    protected $primaryKey       = 'ID_DetalleIngreso';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'ID_Ingreso',
        'ID_Producto',
        'CantidadOriginal',
        'CantidadIngresada'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'ID_Ingreso'  => 'required|integer',
        'ID_Producto' => 'required|integer',
        'CantidadIngresada' => 'required|decimal'
    ];
}