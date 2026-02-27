<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\PresupuestoMensualModel;
use App\Models\PresupuestoAnualModel;
use App\Models\DepartamentosModel;
use App\Models\PlacesModel;
use App\Libraries\HttpStatus; // Assuming HttpStatus is needed for failServerError etc.

class PresupuestoApiController extends ResourceController
{
    protected $format = 'json';

    public function __construct()
    {
        // Any common setup for budget API can go here
    }

    public function saveMonthlyBudget()
    {
        $json = $this->request->getJSON(true); // Get JSON as associative array

        // 1. Validate incoming data
        if (!isset($json['id_dpto']) || !isset($json['anio']) || !isset($json['mes']) || !isset($json['grupos']) || !is_array($json['grupos'])) {
            return $this->failValidationErrors('Datos incompletos o inválidos.');
        }


        $idDpto = (int) $json['id_dpto'];
        $anio = (int) $json['anio'];
        $mes = (int) $json['mes'];
        $grupos = $json['grupos'];

        // Instantiate models
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $presupuestoAnualModel = new PresupuestoAnualModel();
        $departamentosModel = new DepartamentosModel();
        $placesModel = new PlacesModel();
        $db = \Config\Database::connect();


        $db->transException(true)->transStart();


        try {
            // 2. Save/Update PresupuestoMensual entries
            foreach ($grupos as $grupo) {
                if (!isset($grupo['id_grupo']) || !isset($grupo['monto_asignado'])) {
                    throw new \Exception('Datos de grupo presupuestal incompletos.');
                }


                $dataToSave = [
                    'ID_Dpto' => $idDpto,
                    'ID_GrupoPresupuestal' => (int) $grupo['id_grupo'],
                    'Anio' => $anio,
                    'Mes' => $mes,
                    'Monto_Asignado' => (float) $grupo['monto_asignado']
                ];

                if (!empty($grupo['id_existente'])) {
                    // Update existing monthly budget
                    $presupuestoMensualModel->update((int) $grupo['id_existente'], $dataToSave);
                } else {
                    // Insert new monthly budget
                    // Check if a record with the same ID_Dpto, ID_GrupoPresupuestal, Anio, Mes already exists
                    $existingMonthlyBudget = $presupuestoMensualModel
                        ->where('ID_Dpto', $idDpto)
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

            }


            // 3. Calculate and Update PresupuestoAnual
            // Get ID_RazonSocial from ID_Dpto
            $departamento = $departamentosModel->find($idDpto);
            if (!$departamento) {
                throw new \Exception('Departamento con ID ' . $idDpto . ' no encontrado.');
            }
            if (!isset($departamento['ID_Place'])) {
                throw new \Exception('Departamento con ID ' . $idDpto . ' no tiene un ID de Place asociado.');
            }


            $place = $placesModel->find($departamento['ID_Place']);
            if (!$place) {
                throw new \Exception('Lugar con ID ' . $departamento['ID_Place'] . ' no encontrado para el departamento ' . $idDpto . '.');
            }
            if (!isset($place['ID_RazonSocial'])) {
                throw new \Exception('Lugar con ID ' . $departamento['ID_Place'] . ' no tiene un ID de Razon Social asociado.');
            }


            $idRazonSocial = (int) $place['ID_RazonSocial'];

            // Sum Monto_Asignado for the current Anio and ID_RazonSocial
            $queryResult = $presupuestoMensualModel
                ->selectSum('PresupuestoMensual.Monto_Asignado', 'total') // Use model's table name directly
                ->join('Departamentos d', 'd.ID_Dpto = PresupuestoMensual.ID_Dpto')
                ->join('Places p', 'p.ID_Place = d.ID_Place')
                ->where('PresupuestoMensual.Anio', $anio)
                ->where('p.ID_RazonSocial', $idRazonSocial)
                ->get()
                ->getRow();

            $totalMontoAnual = $queryResult ? (float) $queryResult->total : 0.0;


            // Check and update/insert PresupuestoAnual
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
                // Update existing annual budget
                $presupuestoAnualModel->update($presupuestoAnual['ID_PresupuestoAnual'], $annualData);
            } else {
                // Insert new annual budget
                $presupuestoAnualModel->insert($annualData);
            }


            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Falla en la transacción de base de datos para presupuesto anual.');
            }


            // Fetch updated monthly budgets to return to frontend
            $updatedMonthlyBudgets = $presupuestoMensualModel
                ->where('ID_Dpto', $idDpto)
                ->where('Anio', $anio)
                ->where('Mes', $mes)
                ->findAll();


            return $this->respondCreated([
                'success' => true,
                'message' => 'Presupuesto mensual y anual actualizados correctamente.',
                'presupuestos' => $updatedMonthlyBudgets // Return updated monthly budgets for frontend to refresh
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', '[saveMonthlyBudget] ' . $e->getMessage());
            return $this->failServerError('Error al guardar el presupuesto: ' . $e->getMessage());
        }
    }
}
