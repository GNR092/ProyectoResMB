<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdenCompraModel extends Model
{
    protected $table            = 'OrdenCompra';
    protected $primaryKey       = 'ID_OrdenCompra';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    // CAMBIO AQUÍ: Se agrega 'ID_GrupoPresupuestal'
    protected $allowedFields    = [
        'ID_Cotizacion',
        'ID_Proveedor',
        'Estado',
        'Fecha',
        'File_Factura',
        'File_Comprobante',
        'File_ReqPag',
        'File_Remision',
        'File_FacturaEntrada',
        'File_FacturaServicioPDF',
        'File_FacturaServicioXML',
        'ID_GrupoPresupuestal',
        'FechaRefPago',
        'FechaPagoRealizado',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}