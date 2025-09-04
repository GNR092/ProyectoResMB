<?php

namespace App\Models;

use CodeIgniter\Model;

class SolicitudModel extends Model
{
    protected $table = 'Solicitud';
    protected $primaryKey = 'ID_SolicitudProd';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'ID_Usuario',
        'ID_Dpto',
        'ID_Proveedor',
        'IVA',
        'Fecha',
        'Estado',
        'No_Folio',
        'Archivo'

    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}