<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class SolicitudProductModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Operaciones';

    protected $table            = 'Solicitud_Producto';
    protected $primaryKey       = 'ID_SolicitudProd';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Solicitud', 'ID_CatalogoProd', 'ID_GrupoPresupuestal', 'Codigo', 'Nombre', 'Cantidad', 'Importe', 'Monto_Comprometido_Original'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Callbacks
    protected $beforeUpdate = ['captureOldData'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];
}
