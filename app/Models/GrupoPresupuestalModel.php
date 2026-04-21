<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class GrupoPresupuestalModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Configuración';

    protected $table            = 'GrupoPresupuestal';
    protected $primaryKey       = 'ID_GrupoPresupuestal';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_GrupoPresupuestal', 'Nombre', 'Descripcion','ID_UnidadOperativa', 'activo', 'es_manual'];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];
    }