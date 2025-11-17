<?php

namespace App\Models;

use CodeIgniter\Model;

class EntregasModel extends Model
{
    protected $table            = 'Entregas';
    protected $primaryKey       = 'ID_Entrega';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_Usuario',
        'NombreEntrega',
        'DepartamentoRecibe',
        'NombreRecibe',
        'Fecha',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}