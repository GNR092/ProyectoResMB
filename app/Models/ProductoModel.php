<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table            = 'Producto';
    protected $primaryKey       = 'ID_Producto';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['Codigo', 'Nombre', 'Existencia'];
}
