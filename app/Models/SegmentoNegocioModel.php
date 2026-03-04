<?php

namespace App\Models;

use CodeIgniter\Model;

class SegmentoNegocioModel extends Model
{
    protected $table            = 'segmento_negocio';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'id_razon_social',
        'nombre',
        'descripcion'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene los segmentos con la información de la Razón Social relacionada
     */
    public function withRazonSocial()
    {
        return $this->select('segmento_negocio.*, Razon_Social.Nombre as RazonSocial_Nombre')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = segmento_negocio.id_razon_social', 'left');
    }
}
