<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class SolicitudesCambioPresupuestoModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Finanzas';
    protected $table            = 'SolicitudesCambioPresupuesto';
    protected $primaryKey       = 'ID_SolicitudCambio';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_Usuario',
        'Modulo',
        'Accion',
        'ID_Afectado',
        'Datos_Payload',
        'Datos_Antiguos',
        'Estado',
        'Comentarios_Solicitante',
        'Comentarios_Revisor',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];

    // Opcional: Join con usuario para vistas
    public function getPendientes()
    {
        return $this->select('SolicitudesCambioPresupuesto.*, Usuarios.Nombre as NombreUsuario')
                    ->join('Usuarios', 'Usuarios.ID_Usuario = SolicitudesCambioPresupuesto.ID_Usuario', 'left')
                    ->where('Estado', 'Pendiente')
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
