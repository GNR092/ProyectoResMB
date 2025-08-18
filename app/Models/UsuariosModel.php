<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModels extends Model
{
    protected $table            = 'Usuarios';
    protected $primaryKey       = 'ID_Usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ID_Dpto', 'ID_RazonSocial', 'Nombre', 'Correo', 'Contrasena', 'Numero'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}