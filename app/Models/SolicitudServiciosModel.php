<?php

namespace App\Models;

use CodeIgniter\Model;

class SolicitudServiciosModel extends Model
{
    protected $table            = 'Solicitud_Servicios';
    protected $primaryKey       = 'ID_SolicitudServ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Solicitud', 'Nombre', 'Importe'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
