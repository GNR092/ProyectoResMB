<?php

namespace App\Models;

use CodeIgniter\Model;

class SaldosBancariosModel extends Model
{
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
    protected $useTimestamps = true; // Activado ya que la migración incluye created_at y updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Obtiene los saldos con la información del banco y departamento relacionada
     */
    public function getSaldosConDetalle()
    {
        return $this->select('SaldosBancarios.*, BancoDpto.Banco, BancoDpto.Clabe, Departamentos.Nombre as Departamento')
            ->join('BancoDpto', 'BancoDpto.ID_BancoDpto = SaldosBancarios.id_bancodpto')
            ->join('Departamentos', 'Departamentos.ID_Dpto = BancoDpto.ID_Dpto', 'left');
    }
}
