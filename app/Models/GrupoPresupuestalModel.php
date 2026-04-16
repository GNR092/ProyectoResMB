<?php

namespace App\Models;

use CodeIgniter\Model;

class GrupoPresupuestalModel extends Model
{
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
}