<?php

namespace App\Models;

use CodeIgniter\Model;

class IngresosModel extends Model
{
    protected $table            = 'Ingresos';
    protected $primaryKey       = 'ID_Ingreso';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'ID_Proveedor',
        'ID_Usuario',
        'UUID',
        'RFC_Receptor',
        'FechaEmision',
        'NombreArchivoXML'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [
        'ID_Proveedor' => 'required|integer',
        'UUID'         => 'required|is_unique[Ingresos.UUID]', // Validación para evitar duplicados
        'RFC_Receptor' => 'required|max_length[13]'
    ];

    protected $validationMessages   = [
        'UUID' => [
            'is_unique' => 'Esta factura XML ya ha sido registrada anteriormente.'
        ]
    ];
}