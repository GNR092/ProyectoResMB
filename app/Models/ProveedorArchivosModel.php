<?php

namespace App\Models;

use CodeIgniter\Model;

class ProveedorArchivosModel extends Model
{
    protected $table            = 'proveedor_archivos';
    protected $primaryKey       = 'id_archivo';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_proveedor',
        'nombre_archivo',
        'fecha_subida',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'fecha_subida';
    protected $updatedField  = '';
    protected $deletedField  = '';

    /**
     * Obtiene el siguiente índice para el nombre del archivo de un proveedor.
     */
    public function getNextIndex(int $idProveedor): int
    {
        $lastFile = $this->where('id_proveedor', $idProveedor)
                         ->orderBy('id_archivo', 'DESC')
                         ->first();

        if (!$lastFile) {
            return 1;
        }

        $nombre = $lastFile['nombre_archivo'];
        $parts = explode('_', pathinfo($nombre, PATHINFO_FILENAME));
        $lastIndex = (int) end($parts);

        return $lastIndex + 1;
    }
}
