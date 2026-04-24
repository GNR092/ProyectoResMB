<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class ProductoModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Catálogos';

    protected $table            = 'Producto';
    protected $primaryKey       = 'ID_Producto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Codigo', 'Nombre', 'Existencia'];

    // Callbacks
    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterDelete  = ['auditDelete'];
}
