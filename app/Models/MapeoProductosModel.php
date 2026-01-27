<?php

namespace App\Models;

use CodeIgniter\Model;

class MapeoProductosModel extends Model
{
    protected $table            = 'MapeoProductos';
    protected $primaryKey       = 'ID_Mapeo';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array'; // Puedes cambiar a 'object' si prefieres
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    // Campos permitidos para asignación masiva
    protected $allowedFields    = [
        'ID_Proveedor',
        'IdentificadorXML',
        'ID_Producto',
        'FactorConversion'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'ID_Proveedor'     => 'required|integer',
        'IdentificadorXML' => 'required',
        'ID_Producto'      => 'required|integer',
        'FactorConversion' => 'required|decimal'
    ];
}