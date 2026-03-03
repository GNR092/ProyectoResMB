<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\PresupuestoMensualModel;
use App\Models\PresupuestoAnualModel;
use App\Models\DepartamentosModel;
use App\Models\PlacesModel;
use App\Models\GrupoPresupuestalModel;
use App\Models\BancoDptoModel;
use App\Models\SaldosBancariosModel;

class PresupuestoApiController extends ResourceController
{
    protected $format = 'json';

    public function __construct()
    {
        // Any common setup for budget API can go here
    }

    // --- MÉTODOS PARA SALDOS BANCARIOS ---

    public function getEstructuraSaldos($idPlace, $anio, $mes)
    {
        $dptoModel = new DepartamentosModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        // 1. Departamentos del Place
        $departamentos = $dptoModel->where('ID_Place', $idPlace)
            ->orderBy('Nombre', 'ASC')
            ->findAll();

        if (empty($departamentos)) {
            return $this->respond(['departamentos' => []]);
        }

        $dptoIds = array_column($departamentos, 'ID_Dpto');

        // 2. Bancos de esos departamentos
        $bancos = $bancoModel->whereIn('ID_Dpto', $dptoIds)
            ->orderBy('Banco', 'ASC')
            ->findAll();

        $bancoIds = array_column($bancos, 'ID_BancoDpto');

        // 3. Saldos guardados
        $saldosGuardados = [];
        if (!empty($bancoIds)) {
            $saldosGuardados = $saldosModel->whereIn('id_bancodpto', $bancoIds)
                ->where('anio', $anio)
                ->where('mes', $mes)
                ->findAll();
        }

        $estructura = [];

        foreach ($departamentos as $dpto) {
            $bancosDelDpto = [];

            foreach ($bancos as $banco) {
                if ((int)$banco['ID_Dpto'] === (int)$dpto['ID_Dpto']) {
                    
                    $saldo_inicial = '';
                    $saldo_final = '';
                    $idExistente = null;

                    foreach ($saldosGuardados as $saldo) {
                        if ((int)$saldo['id_bancodpto'] === (int)$banco['ID_BancoDpto']) {
                            $saldo_inicial = $saldo['saldo_inicial'];
                            $saldo_final = $saldo['saldo_final'];
                            $idExistente = $saldo['id'];
                            break;
                        }
                    }

                    $banco['saldo_inicial'] = $saldo_inicial;
                    $banco['saldo_final'] = $saldo_final;
                    $banco['id_saldo_existente'] = $idExistente;

                    $bancosDelDpto[] = $banco;
                }
            }

            // Solo incluimos departamentos que tengan cuentas bancarias configuradas
            if (!empty($bancosDelDpto)) {
                $dpto['bancos'] = $bancosDelDpto;
                $estructura[] = $dpto;
            }
        }

        return $this->respond(['departamentos' => $estructura]);
    }

    public function saveSaldosMasivo()
    {
        $json = $this->request->getJSON(true);

        if (!isset($json['anio']) || !isset($json['mes']) || !isset($json['saldos']) || !is_array($json['saldos'])) {
            return $this->failValidationErrors('Datos incompletos.');
        }

        $anio = (int) $json['anio'];
        $mes = (int) $json['mes'];
        $saldos = $json['saldos'];

        $saldosModel = new SaldosBancariosModel();
        $db = \Config\Database::connect();

        $db->transStart();

        try {
            foreach ($saldos as $s) {
                if (!isset($s['id_bancodpto'])) continue;

                $dataToSave = [
                    'id_bancodpto'  => (int) $s['id_bancodpto'],
                    'mes'           => $mes,
                    'anio'          => $anio,
                    'saldo_inicial' => (float) ($s['saldo_inicial'] ?? 0),
                    'saldo_final'   => (float) ($s['saldo_final'] ?? 0)
                ];

                if (!empty($s['id_existente'])) {
                    $saldosModel->update((int) $s['id_existente'], $dataToSave);
                } else {
                    // Check again just in case
                    $exists = $saldosModel->where('id_bancodpto', $s['id_bancodpto'])
                        ->where('mes', $mes)
                        ->where('anio', $anio)
                        ->first();
                    
                    if ($exists) {
                        $saldosModel->update($exists['id'], $dataToSave);
                    } else {
                        $saldosModel->insert($dataToSave);
                    }
                }
            }

            $db->transComplete();
            return $this->respondCreated(['success' => true, 'message' => 'Saldos guardados correctamente.']);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->failServerError('Error al guardar: ' . $e->getMessage());
        }
    }

    // --- MÉTODOS EXISTENTES ---

    // NUEVA FUNCIÓN PARA GUARDAR MÚLTIPLES DEPARTAMENTOS
    public function saveMasivo()
    {
        $json = $this->request->getJSON(true);

        // 1. Validar datos principales (ya no pedimos id_dpto a nivel general)
        if (!isset($json['anio']) || !isset($json['mes']) || !isset($json['grupos']) || !is_array($json['grupos'])) {
            return $this->failValidationErrors('Datos incompletos o inválidos.');
        }

        $anio = (int) $json['anio'];
        $mes = (int) $json['mes'];
        $grupos = $json['grupos'];

        $presupuestoMensualModel = new PresupuestoMensualModel();
        $presupuestoAnualModel = new PresupuestoAnualModel();
        $departamentosModel = new DepartamentosModel();
        $placesModel = new PlacesModel();
        $db = \Config\Database::connect();

        $db->transException(true)->transStart();

        try {
            // Arreglo para llevar registro de qué Razones Sociales fueron afectadas
            // y así recalcular su presupuesto anual al final.
            $razonesSocialesAfectadas = [];

            // 2. Guardar o Actualizar los grupos
            foreach ($grupos as $grupo) {
                // Ahora validamos que el id_dpto venga dentro de cada iteración
                if (!isset($grupo['id_grupo']) || !isset($grupo['monto_asignado']) || !isset($grupo['id_dpto'])) {
                    throw new \Exception('Datos de grupo presupuestal incompletos.');
                }

                $idDptoActual = (int) $grupo['id_dpto'];

                $dataToSave = [
                    'ID_Dpto' => $idDptoActual,
                    'ID_GrupoPresupuestal' => (int) $grupo['id_grupo'],
                    'Anio' => $anio,
                    'Mes' => $mes,
                    'Monto_Asignado' => (float) $grupo['monto_asignado']
                ];

                if (!empty($grupo['id_existente'])) {
                    // Actualizar registro existente
                    $presupuestoMensualModel->update((int) $grupo['id_existente'], $dataToSave);
                } else {
                    // Si no tiene ID existente, comprobamos por seguridad en BD
                    $existingMonthlyBudget = $presupuestoMensualModel
                        ->where('ID_Dpto', $idDptoActual)
                        ->where('ID_GrupoPresupuestal', (int) $grupo['id_grupo'])
                        ->where('Anio', $anio)
                        ->where('Mes', $mes)
                        ->first();

                    if ($existingMonthlyBudget) {
                        $presupuestoMensualModel->update($existingMonthlyBudget['ID_PresupuestoMensual'], $dataToSave);
                    } else {
                        $presupuestoMensualModel->insert($dataToSave);
                    }
                }

                // Averiguar a qué Razón Social pertenece este departamento para el cálculo anual
                $departamento = $departamentosModel->find($idDptoActual);
                if ($departamento && isset($departamento['ID_Place'])) {
                    $place = $placesModel->find($departamento['ID_Place']);
                    if ($place && isset($place['ID_RazonSocial'])) {
                        $idRazonSocial = (int) $place['ID_RazonSocial'];

                        // Lo guardamos en el arreglo si no existe aún (para no calcularlo 100 veces repetidas)
                        if (!in_array($idRazonSocial, $razonesSocialesAfectadas)) {
                            $razonesSocialesAfectadas[] = $idRazonSocial;
                        }
                    }
                }
            }

            // 3. Recalcular el Presupuesto Anual SOLO de las Razones Sociales afectadas
            foreach ($razonesSocialesAfectadas as $idRazonSocial) {
                $queryResult = $presupuestoMensualModel
                    ->selectSum('PresupuestoMensual.Monto_Asignado', 'total')
                    ->join('Departamentos d', 'd.ID_Dpto = PresupuestoMensual.ID_Dpto')
                    ->join('Places p', 'p.ID_Place = d.ID_Place')
                    ->where('PresupuestoMensual.Anio', $anio)
                    ->where('p.ID_RazonSocial', $idRazonSocial)
                    ->get()
                    ->getRow();

                $totalMontoAnual = $queryResult ? (float) $queryResult->total : 0.0;

                $presupuestoAnual = $presupuestoAnualModel
                    ->where('Anio', $anio)
                    ->where('ID_RazonSocial', $idRazonSocial)
                    ->first();

                $annualData = [
                    'ID_RazonSocial' => $idRazonSocial,
                    'Anio' => $anio,
                    'Monto' => (float) $totalMontoAnual
                ];

                if ($presupuestoAnual) {
                    $presupuestoAnualModel->update($presupuestoAnual['ID_PresupuestoAnual'], $annualData);
                } else {
                    $presupuestoAnualModel->insert($annualData);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos para presupuesto anual.');
            }

            return $this->respondCreated([
                'success' => true,
                'message' => 'Presupuestos guardados correctamente.'
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[saveMasivo] ' . $e->getMessage());
            return $this->failServerError('Error al guardar: ' . $e->getMessage());
        }
    }

    public function getEstructura($idPlace, $anio, $mes)
    {
        $dptoModel = new DepartamentosModel();
        $grupoModel = new GrupoPresupuestalModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();

        // 1. Traer TODOS los departamentos que pertenecen a este ID_Place
        $departamentos = $dptoModel->where('ID_Place', $idPlace)
            ->orderBy('Nombre', 'ASC')
            ->findAll();

        if (empty($departamentos)) {
            return $this->respond(['departamentos' => []]);
        }

        $dptoIds = array_column($departamentos, 'ID_Dpto');

        // 2. Traer TODOS los grupos presupuestales que pertenecen a esos departamentos
        $grupos = $grupoModel->whereIn('ID_Dpto', $dptoIds)
            ->orderBy('Nombre', 'ASC')
            ->findAll();

        // 3. Traer los presupuestos que ya han sido guardados para ese Mes/Año
        $presupuestosGuardados = $presupuestoMensualModel->whereIn('ID_Dpto', $dptoIds)
            ->where('Anio', $anio)
            ->where('Mes', $mes)
            ->findAll();

        $estructura = [];

        foreach ($departamentos as $dpto) {
            $gruposDelDpto = [];

            foreach ($grupos as $grupo) {
                if ((int)$grupo['ID_Dpto'] === (int)$dpto['ID_Dpto']) {

                    $montoAsignado = '';
                    $idExistente = null;

                    foreach ($presupuestosGuardados as $presupuesto) {
                        if ((int)$presupuesto['ID_GrupoPresupuestal'] === (int)$grupo['ID_GrupoPresupuestal'] &&
                            (int)$presupuesto['ID_Dpto'] === (int)$dpto['ID_Dpto']) {

                            $montoAsignado = $presupuesto['Monto_Asignado'];
                            $idExistente = $presupuesto['ID_PresupuestoMensual'];
                            break;
                        }
                    }

                    $grupo['Monto_Asignado'] = $montoAsignado;
                    $grupo['ID_PresupuestoMensual'] = $idExistente;

                    $gruposDelDpto[] = $grupo;
                }
            }

            $dpto['grupos'] = $gruposDelDpto;
            $estructura[] = $dpto;
        }

        return $this->respond(['departamentos' => $estructura]);
    }

    /**
     * Obtiene los saldos presupuestales para un departamento y grupo específicos.
     * La fecha se determina por la aprobación de la solicitud o el mes actual.
     */
    public function getSaldos()
    {
        $idDpto = $this->request->getVar('id_dpto');
        $idGrupo = $this->request->getVar('id_grupo');
        $idSolicitud = $this->request->getVar('id_solicitud');

        if (!$idDpto || !$idGrupo) {
            return $this->failValidationErrors('id_dpto e id_grupo son requeridos.');
        }

        // Determinar Mes y Año
        $mes = (int)date('n');
        $anio = (int)date('Y');

        if (!empty($idSolicitud)) {
            $solicitudModel = new \App\Models\SolicitudModel();
            $solicitud = $solicitudModel->find($idSolicitud);
            
            if ($solicitud && !empty($solicitud['Fecha_Aprobacion'])) {
                $fechaAprob = strtotime($solicitud['Fecha_Aprobacion']);
                $mes = (int)date('n', $fechaAprob);
                $anio = (int)date('Y', $fechaAprob);
            }
        }

        $presupuestoModel = new PresupuestoMensualModel();
        $presupuesto = $presupuestoModel
            ->where('ID_Dpto', $idDpto)
            ->where('ID_GrupoPresupuestal', $idGrupo)
            ->where('Anio', $anio)
            ->where('Mes', $mes)
            ->first();

        if (!$presupuesto) {
            return $this->respond([
                'success' => true,
                'data' => [
                    'Monto_Asignado' => 0,
                    'Monto_Comprometido' => 0,
                    'Monto_Ejecutado' => 0,
                    'Saldos_Disponibles' => 0,
                    'Mes' => $mes,
                    'Anio' => $anio,
                    'found' => false
                ]
            ]);
        }

        $asignado = (float)$presupuesto['Monto_Asignado'];
        $comprometido = (float)$presupuesto['Monto_Comprometido'];
        $ejecutado = (float)$presupuesto['Monto_Ejecutado'];
        $disponible = $asignado - ($comprometido + $ejecutado);

        return $this->respond([
            'success' => true,
            'data' => [
                'Monto_Asignado' => $asignado,
                'Monto_Comprometido' => $comprometido,
                'Monto_Ejecutado' => $ejecutado,
                'Saldos_Disponibles' => $disponible,
                'Mes' => $mes,
                'Anio' => $anio,
                'found' => true
            ]
        ]);
    }
}