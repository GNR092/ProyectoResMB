<?php

namespace App\Models;

use CodeIgniter\Model;

class EventoArchivosModel extends Model
{
    protected $table            = 'evento_archivos';
    protected $primaryKey       = 'id_archivo';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_evento',
        'nombre_archivo',
        'nombre_usuario',
        'fecha_subida',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'fecha_subida';
    protected $updatedField  = '';
    protected $deletedField  = '';

    /**
     * Obtiene todos los archivos de un evento (más reciente primero)
     */
    public function getByEvento(int $idEvento): array
    {
        return $this->where('id_evento', $idEvento)
                    ->orderBy('fecha_subida', 'DESC')
                    ->findAll();
    }

    /**
     * Obtiene todos los archivos de un evento (más antiguo primero)
     */
    public function getByEventoAsc(int $idEvento): array
    {
        return $this->where('id_evento', $idEvento)
                    ->orderBy('fecha_subida', 'ASC')
                    ->findAll();
    }
}