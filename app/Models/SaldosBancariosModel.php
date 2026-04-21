<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Traits\AuditTrait;

class SaldosBancariosModel extends Model
{
    use AuditTrait;

    protected $auditClasificacion = 'Finanzas';
    protected $auditIdentifyingFields = ['id_bancodpto', 'anio', 'mes'];

    protected $table            = 'SaldosBancarios';

    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'id_bancodpto',
        'mes',
        'anio',
        'saldo_inicial',
        'saldo_final'
    ];

    // Dates
    protected $useTimestamps = false; // Activado ya que la migración incluye created_at y updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Callbacks
    protected $beforeUpdate = ['captureOldData'];
    protected $afterUpdate  = ['auditUpdate'];
    protected $afterInsert  = ['auditInsert'];
    protected $afterDelete  = ['auditDelete'];

    /**
     * Obtiene los saldos con la información del banco y razón social relacionada
     */
    public function getSaldosConDetalle()
    {
        return $this->select('SaldosBancarios.*, BancoDpto.Banco, BancoDpto.Clabe, Razon_Social.Nombre as RazonSocial')
            ->join('BancoDpto', 'BancoDpto.ID_BancoDpto = SaldosBancarios.id_bancodpto')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = BancoDpto.ID_RazonSocial', 'left');
    }
}
