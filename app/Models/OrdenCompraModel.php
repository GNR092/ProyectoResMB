<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class OrdenCompraModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Compras';

    protected $table            = 'OrdenCompra';
    protected $primaryKey       = 'ID_OrdenCompra';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

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
        'File_Complemento',
        'FechaRefPago',
        'FechaPagoRealizado',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeUpdate = ['captureOldData'];
    protected $beforeDelete = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];
}