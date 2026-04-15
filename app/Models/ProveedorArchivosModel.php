<?php

namespace App\Models;

use CodeIgniter\Model;

class ProveedorArchivosModel extends Model
{
    protected $table            = 'Proveedor_Archivos';
    protected $primaryKey       = 'ID_Archivo';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ID_Proveedor',
        'Nombre_Archivo',
        'Fecha_Subida',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'Fecha_Subida';
    protected $updatedField  = ''; // No necesitamos updated_at
    protected $deletedField  = '';

    /**
     * Obtiene el siguiente índice para el nombre del archivo de un proveedor.
     * Nomenclatura: DocumentoProveedor_(IdProveedor)_(Index)
     * 
     * @param int $idProveedor
     * @return int
     */
    public function getNextIndex(int $idProveedor): int
    {
        $lastFile = $this->where('ID_Proveedor', $idProveedor)
                         ->orderBy('ID_Archivo', 'DESC')
                         ->first();

        if (!$lastFile) {
            return 1;
        }

        // Intentar extraer el número del nombre del archivo actual
        // Nombre_Archivo: DocumentoProveedor_5_1.pdf
        $nombre = $lastFile['Nombre_Archivo'];
        $parts = explode('_', pathinfo($nombre, PATHINFO_FILENAME));
        $lastIndex = (int) end($parts);

        return $lastIndex + 1;
    }
}
