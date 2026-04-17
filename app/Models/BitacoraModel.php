<?php

namespace App\Models;

use CodeIgniter\Model;

class BitacoraModel extends Model
{
    protected $table            = 'bitacora';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'usuario_id',
        'nombre_usuario',
        'departamento_id',
        'complejo_id',
        'razon_social_id',
        'tipo_accion',
        'clasificacion',
        'usuario_autoriza_id',
        'modulo',
        'solicitud_id',
        'orden_compra_id',
        'cotizacion_id',
        'ip_address',
        'valores_antiguos',
        'valores_nuevos',
        'estado'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
}
