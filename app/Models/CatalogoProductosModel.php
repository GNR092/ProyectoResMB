<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class CatalogoProductosModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Catálogos';

    protected $table            = 'Catalogo_Productos';
    protected $primaryKey       = 'ID_CatalogoProd';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_RazonSocial',
        'id_segmento',
        'ID_Place',
        'ID_Dpto',
        'ID_GrupoPresupuestal',
        'Nombre'
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
     * Obtiene los productos filtrados por Unidad Operativa e incluye el nombre de la unidad
     */
    public function getProductosPorUnidadOperativa($id_unidad)
    {
        return $this->select('Catalogo_Productos.*, UnidadOperativa.Nombre as UnidadNombre')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left')
            ->where('Catalogo_Productos.ID_Dpto', $id_unidad)
            ->orderBy('Catalogo_Productos.Nombre', 'ASC')
            ->findAll();
    }

    /**
     * Obtiene los productos filtrados por Complejo (Place) e incluye el nombre de la unidad
     */
    public function getProductosPorPlace($id_place)
    {
        return $this->select('Catalogo_Productos.*, UnidadOperativa.Nombre as UnidadNombre')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left')
            ->where('Catalogo_Productos.ID_Place', $id_place)
            ->orderBy('Catalogo_Productos.Nombre', 'ASC')
            ->findAll();
    }

    /**
     * Obtiene el catálogo con información de las tablas relacionadas
     */
    public function getFullCatalogo()
    {
        return $this->select('Catalogo_Productos.*, Razon_Social.Nombre as RazonSocial_Nombre, segmento_negocio.nombre as Segmento_Nombre, Places.Nombre_Corto as Place_Nombre, UnidadOperativa.Nombre as Departamento_Nombre, GrupoPresupuestal.Nombre as GrupoPresupuestal_Nombre')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Catalogo_Productos.ID_RazonSocial', 'left')
            ->join('segmento_negocio', 'segmento_negocio.id = Catalogo_Productos.id_segmento', 'left')
            ->join('Places', 'Places.ID_Place = Catalogo_Productos.ID_Place', 'left')
            ->join('UnidadOperativa', 'UnidadOperativa.ID_UnidadOperativa = Catalogo_Productos.ID_Dpto', 'left')
            ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = Catalogo_Productos.ID_GrupoPresupuestal', 'left')
            ->findAll();
    }
}
