<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class UsuarioProductoFavoritoModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Preferencias Usuario';

    protected $table            = 'usuarios_productos_favoritos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_usuario',
        'id_catalogoprod',
        'alias_personal',
        'frecuencia'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Callbacks
    protected $beforeUpdate = ['captureOldData'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];

    /**
     * Obtiene los productos favoritos de un usuario con información del catálogo
     */
    public function getFavoritosUsuario($id_usuario)
    {
        return $this->select('usuarios_productos_favoritos.*, Catalogo_Productos.Nombre, Catalogo_Productos.ID_GrupoPresupuestal, UnidadOperativa.Nombre as UnidadNombre')
            ->join('Catalogo_Productos', 'Catalogo_Productos.ID_CatalogoProd = usuarios_productos_favoritos.id_catalogoprod')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left')
            ->where('usuarios_productos_favoritos.id_usuario', $id_usuario)
            ->orderBy('usuarios_productos_favoritos.frecuencia', 'DESC')
            ->orderBy('Catalogo_Productos.Nombre', 'ASC')
            ->findAll();
    }
}
