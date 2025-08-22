<?php

namespace App\Models;

use CodeIgniter\Model;

class PlacesModel extends Model
{
    protected $table            = 'Places';
    protected $primaryKey       = 'ID_Place';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Nombre_Corto', 'Nombre_Completo'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}