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
        'RFC',
        'Banco',
        'Cuenta',
        'Clabe',
        'Tel_Contacto',
        'Nombre_Contacto',
        'Servicio',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}