<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class ProveedorModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Catálogos';

    protected $table            = 'Proveedor';
    protected $primaryKey       = 'ID_Proveedor';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'RazonSocial',
        'Correo',
        'RFC',
        'Banco',
        'Cuenta',
        'Clabe',
        'Tel_Contacto',
        'Nombre_Contacto',
        'Servicio',
        'Dias_Credito',
        'Monto_Credito',
    ];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [
        'ID_Proveedor' => 'permit_empty|is_natural_no_zero',
        'RazonSocial' => 'required|is_unique[Proveedor.RazonSocial,ID_Proveedor,{ID_Proveedor}]',
    ];
    protected $validationMessages   = [
        'RazonSocial' => [
            'is_unique' => 'Ya existe un proveedor registrado con esta Razón Social.',
            'required'  => 'La Razón Social es obligatoria.'
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeUpdate   = ['captureOldData'];
    protected $afterUpdate    = ['auditUpdate'];
    protected $afterInsert    = ['auditInsert', 'duplicarCuentaPrincipal'];
    protected $beforeDelete   = ['captureOldData'];
    protected $afterDelete    = ['auditDelete'];

    /**
     * Duplica la cuenta bancaria principal del proveedor en la tabla Cuentas
     * después de un insert exitoso, para que aparezca en los selectores de servicios.
     */
    protected function duplicarCuentaPrincipal(array $data)
    {
        // El ID del proveedor insertado está en $data['id']
        $idProveedor = $data['id'] ?? null;
        
        // Los datos insertados están en $data['data']
        $cuenta = $data['data']['Cuenta'] ?? null;
        $clabe = $data['data']['Clabe'] ?? null;

        // Si hay una cuenta o clabe definida, la insertamos en la tabla Cuentas
        if ($idProveedor && ($cuenta || $clabe)) {
            $cuentasModel = new \App\Models\CuentasModel();
            
            // Preferimos Clabe si existe, si no Cuenta
            $valorCuenta = !empty($clabe) ? $clabe : $cuenta;

            $cuentasModel->insert([
                'ID_Proveedor' => $idProveedor,
                'Cuenta'       => $valorCuenta
            ]);
        }

        return $data;
    }
}
