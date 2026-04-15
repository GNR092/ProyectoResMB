<?php

namespace App\Models;

use CodeIgniter\Model;

class ProveedorModel extends Model
{
    protected $table            = 'Proveedor';
    protected $primaryKey       = 'ID_Proveedor';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'RazonSocial',
        'Correo',
        'RFC',
        'Banco',
        'Cuenta',
        'Clabe',
        'Tel_Contacto',
        'Nombre_Contacto',
        'Servicio',
        'Dias_Credito',
        'Monto_Credito',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'RazonSocial' => 'required|is_unique[Proveedor.RazonSocial,ID_Proveedor,{ID_Proveedor}]',
    ];
    protected $validationMessages   = [
        'RazonSocial' => [
            'is_unique' => 'Ya existe un proveedor registrado con esta Razón Social.',
            'required'  => 'La Razón Social es obligatoria.'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
    }