<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class EventoCalendarioModel extends Model
{
    use AuditTrait;
    protected $auditClasificacion = 'Calendario';

    protected $table = 'eventos_calendario';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = ['ID_Usuario', 'ID_Solicitud', 'evento', 'color_evento', 'fecha_inicio', 'fecha_fin'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function delUsuario(int $userId)
    {
        return $this->where('ID_Usuario', $userId);
    }

    public function enRango(string $start, string $end)
    {
        return $this->where('fecha_inicio <=', $end)
                    ->where('fecha_fin >=', $start);
    }
}