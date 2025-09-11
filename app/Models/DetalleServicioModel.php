<?php

namespace App\Models;

use CodeIgniter\Model;

class DetalleServicioModel extends Model
{
    protected $table            = 'Detalle_Servicio';
    protected $primaryKey       = 'ID_DetalleServ';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_SolicitudServ', 'Nombre_Servicio', 'Costo'];

    // Dates
    protected $useTimestamps = false;
}