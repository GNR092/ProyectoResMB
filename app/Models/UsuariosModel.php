<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class UsuariosModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Catálogos';

    protected $table            = 'Usuarios';
    protected $primaryKey       = 'ID_Usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Dpto', 'ID_RazonSocial', 'Nombre', 'Correo', 'ContrasenaP', 'ContrasenaG', 'Numero', 'Firma_digital'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Callbacks
    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];
}