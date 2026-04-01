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
use App\Models\RazonSocialModel;
use App\Models\UnidadOperativaModel;
use App\Models\SegmentoNegocioModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PresupuestoApiController extends ResourceController
{
    protected $format = 'json';

    public function __construct()
    {
        // Constructor
    }

    /**
     * Auxiliar para inicializar totales en cero
     */
    private function getTotalesCero() { 
        return [
            'asignado' => 0, 'comprometido' => 0, 'ejecutado' => 0, 
            'disponible' => 0, 'excedido' => 0, 'porcentaje' => 0
        ]; 
    }

    /**
     * REPORTE COMPARATIVO (PANTALLA 2)
     */
    public function getComparativo($idPlace, $anio, $mes)
    {
        try {
            $unidadModel = new UnidadOperativaModel();
            $presupuestoMensualModel = new PresupuestoMensualModel();
            $grupoModel = new GrupoPresupuestalModel();

            if (empty($mes)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);
            $meses = array_map('intval', explode(',', $mes));

            // 1. Obtener Unidades
            $builder = $unidadModel->select('UnidadOperativa.ID_UnidadOperativa, UnidadOperativa.Nombre, UnidadOperativa.ID_Place, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
                ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
                ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
                ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

            if (!empty($idPlace) && $idPlace != '0') {
                $placeIds = array_filter(array_map('intval', explode(',', (string)$idPlace)));
                if (!empty($placeIds)) $builder->whereIn('UnidadOperativa.ID_Place', $placeIds);
            }
            
            $unidadesRaw = $builder->orderBy('Razon_Social.Nombre', 'ASC')
                ->orderBy('Places.Nombre_Corto', 'ASC')
                ->orderBy('UnidadOperativa.Nombre', 'ASC')
                ->findAll();

            if (empty($unidadesRaw)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);

            $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');

            // 2. Cargar Grupos Activos
            $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->findAll();

            // 3. Cargar Presupuestos Agregados
            $presupuestosRaw = $presupuestoMensualModel
                ->select('ID_UnidadOperativa, ID_GrupoPresupuestal')
                ->selectSum('Monto_Asignado', 'Monto_Asignado')
                ->selectSum('Monto_Comprometido', 'Monto_Comprometido')
                ->selectSum('Monto_Ejecutado', 'Monto_Ejecutado')
                ->whereIn('ID_UnidadOperativa', $unidadIds)
                ->where('Anio', (int)$anio)
                ->whereIn('Mes', $meses)
                ->groupBy('ID_UnidadOperativa, ID_GrupoPresupuestal')
                ->findAll();

            // Indexar presupuestos para acceso rápido O(1)
            $presIndex = [];
            foreach ($presupuestosRaw as $p) {
                $presIndex[$p['ID_UnidadOperativa'] . '_' . $p['ID_GrupoPresupuestal']] = $p;
            }

            $estructura = [];
            $gtAsignado = 0; $gtComprometido = 0; $gtEjecutado = 0;

            foreach ($unidadesRaw as $uni) {
                $idU = (int)$uni['ID_UnidadOperativa'];
                $analisis = [];
                $tUniAsignado = 0; $tUniComprometido = 0; $tUniEjecutado = 0;

                foreach ($gruposAll as $g) {
                    if ((int)$g['ID_UnidadOperativa'] === $idU) {
                        $p = $presIndex[$idU . '_' . $g['ID_GrupoPresupuestal']] ?? null;

                        $asig = (float)($p['Monto_Asignado'] ?? 0);
                        $comp = (float)($p['Monto_Comprometido'] ?? 0);
                        $ejec = (float)($p['Monto_Ejecutado'] ?? 0);
                        $gasto = $comp + $ejec;

                        $disp = $asig - $gasto;
                        $exce = 0;
                        if ($disp < 0) { $exce = abs($disp); $disp = 0; }

                        $analisis[] = [
                            'etiqueta'     => $g['Nombre'],
                            'asignado'     => $asig,
                            'comprometido' => $comp,
                            'ejecutado'    => $ejec,
                            'disponible'   => $disp,
                            'excedido'     => $exce,
                            'porcentaje'   => $asig > 0 ? round(($gasto / $asig) * 100, 2) : 0
                        ];

                        $tUniAsignado += $asig; $tUniComprometido += $comp; $tUniEjecutado += $ejec;
                    }
                }

                $tUniGasto = $tUniComprometido + $tUniEjecutado;
                $uDisp = $tUniAsignado - $tUniGasto;
                $uExce = 0;
                if ($uDisp < 0) { $uExce = abs($uDisp); $uDisp = 0; }

                $uni['detalles'] = $analisis;
                $uni['totales'] = [
                    'asignado'     => $tUniAsignado,
                    'comprometido' => $tUniComprometido,
                    'ejecutado'    => $tUniEjecutado,
                    'disponible'   => $uDisp,
                    'excedido'     => $uExce,
                    'porcentaje'   => $tUniAsignado > 0 ? round(($tUniGasto / $tUniAsignado) * 100, 2) : 0
                ];
                
                $estructura[] = $uni;
                $gtAsignado += $tUniAsignado; $gtComprometido += $tUniComprometido; $gtEjecutado += $tUniEjecutado;
            }

            $gtTotalGasto = $gtComprometido + $gtEjecutado;
            $gtDisp = $gtAsignado - $gtTotalGasto;
            $gtExce = 0;
            if ($gtDisp < 0) { $gtExce = abs($gtDisp); $gtDisp = 0; }

            return $this->respond([
                'departamentos' => $estructura,
                'totales_generales' => [
                    'asignado'     => $gtAsignado,
                    'comprometido' => $gtComprometido,
                    'ejecutado'    => $gtEjecutado,
                    'disponible'   => $gtDisp,
                    'excedido'     => $gtExce,
                    'porcentaje'   => $gtAsignado > 0 ? round(($gtTotalGasto / $gtAsignado) * 100, 2) : 0
                ]
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[getComparativo] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * REPORTE CONSOLIDADO MAESTRO (PANTALLA 4)
     */
    public function getReporteCompleto($idPlace, $anio, $mes)
    {
        try {
            $unidadModel = new UnidadOperativaModel();
            $presupuestoMensualModel = new PresupuestoMensualModel();
            $grupoModel = new GrupoPresupuestalModel();
            $bancoModel = new BancoDptoModel();
            $saldosModel = new SaldosBancariosModel();

            if (empty($mes)) return $this->respond(['departamentos' => []]);
            $meses = array_map('intval', explode(',', $mes));

            // 1. Unidades
            $builder = $unidadModel->select('UnidadOperativa.ID_UnidadOperativa, UnidadOperativa.Nombre, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
                ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
                ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
                ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

            if (!empty($idPlace) && $idPlace != '0') {
                $placeIds = array_filter(array_map('intval', explode(',', (string)$idPlace)));
                if (!empty($placeIds)) $builder->whereIn('UnidadOperativa.ID_Place', $placeIds);
            }
            $unidadesRaw = $builder->orderBy('Razon_Social.Nombre', 'ASC')->orderBy('Places.Nombre_Corto', 'ASC')->orderBy('UnidadOperativa.Nombre', 'ASC')->findAll();

            if (empty($unidadesRaw)) return $this->respond(['departamentos' => []]);

            $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');
            $rsIds = array_unique(array_filter(array_column($unidadesRaw, 'ID_RazonSocial')));

            // 2. Grupos y Presupuestos
            $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->findAll();
            $presRaw = $presupuestoMensualModel->select('ID_UnidadOperativa, ID_GrupoPresupuestal')->selectSum('Monto_Asignado','asig')->selectSum('Monto_Comprometido','comp')->selectSum('Monto_Ejecutado','ejec')
                ->whereIn('ID_UnidadOperativa', $unidadIds)->where('Anio', (int)$anio)->whereIn('Mes', $meses)->groupBy('ID_UnidadOperativa, ID_GrupoPresupuestal')->findAll();

            $presIndex = [];
            foreach ($presRaw as $p) { $presIndex[$p['ID_UnidadOperativa'] . '_' . $p['ID_GrupoPresupuestal']] = $p; }

            // 3. Bancos
            $bancos = !empty($rsIds) ? $bancoModel->whereIn('ID_RazonSocial', $rsIds)->findAll() : [];
            $bancoIds = array_column($bancos, 'ID_BancoDpto');
            $saldos = !empty($bancoIds) ? $saldosModel->whereIn('id_bancodpto', $bancoIds)->where('anio', (int)$anio)->whereIn('mes', $meses)->findAll() : [];

            $saldosIndex = [];
            foreach ($saldos as $s) { $saldosIndex[$s['id_bancodpto']][] = $s; }

            $estructura = [];
            $rsProcesadasBancos = [];

            foreach ($unidadesRaw as $uni) {
                $idU = (int)$uni['ID_UnidadOperativa'];
                $idRS = (int)$uni['ID_RazonSocial'];

                $analisisGrupos = [];
                $pAsig = 0; $pComp = 0; $pEjec = 0;

                foreach ($gruposAll as $g) {
                    if ((int)$g['ID_UnidadOperativa'] === $idU) {
                        $p = $presIndex[$idU . '_' . $g['ID_GrupoPresupuestal']] ?? null;
                        $gasig = (float)($p['asig'] ?? 0);
                        $gcomp = (float)($p['comp'] ?? 0);
                        $gejec = (float)($p['ejec'] ?? 0);
                        $ggast = $gcomp + $gejec;

                        $gdisp = $gasig - $ggast;
                        $gexce = 0;
                        if ($gdisp < 0) { $gexce = abs($gdisp); $gdisp = 0; }

                        $analisisGrupos[] = [
                            'etiqueta' => $g['Nombre'], 'asignado' => $gasig, 'comprometido' => $gcomp, 'ejecutado' => $gejec,
                            'gastado' => $ggast, 'disponible' => $gdisp, 'excedido' => $gexce,
                            'porcentaje' => $gasig > 0 ? round(($ggast / $gasig) * 100, 2) : 0
                        ];
                        $pAsig += $gasig; $pComp += $gcomp; $pEjec += $gejec;
                    }
                }

                $bIni = 0; $bFin = 0;
                if (!in_array($idRS, $rsProcesadasBancos)) {
                    foreach ($bancos as $b) {
                        if ((int)$b['ID_RazonSocial'] === $idRS) {
                            $seB = $saldosIndex[(int)$b['ID_BancoDpto']] ?? [];
                            if (!empty($seB)) {
                                usort($seB, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                                $bIni += (float)reset($seB)['saldo_inicial'];
                                $fVal = (float)end($seB)['saldo_final'];
                                if ($fVal == 0 && (float)end($seB)['saldo_inicial'] != 0) $fVal = (float)end($seB)['saldo_inicial'];
                                $bFin += $fVal;
                            }
                        }
                    }
                    $rsProcesadasBancos[] = $idRS;
                }

                $totalGasto = $pComp + $pEjec;
                $pDisp = $pAsig - $totalGasto;
                $pExce = 0;
                if ($pDisp < 0) { $pExce = abs($pDisp); $pDisp = 0; }

                $estructura[] = [
                    'ID_UnidadOperativa' => $idU,
                    'Nombre' => $uni['Nombre'],
                    'PlaceNombre' => $uni['PlaceNombre'],
                    'RazonSocialNombre' => $uni['RazonSocialNombre'],
                    'SegmentoNombre' => $uni['SegmentoNombre'] ?? 'Sin Segmento',
                    'detalles' => $analisisGrupos,
                    'presupuesto' => [
                        'asignado' => $pAsig, 'comprometido' => $pComp, 'ejecutado' => $pEjec,
                        'gastado' => $totalGasto, 'disponible' => $pDisp, 'excedido' => $pExce,
                        'porcentaje' => $pAsig > 0 ? round(($totalGasto / $pAsig) * 100, 2) : 0
                    ],
                    'bancos' => [
                        'inicial' => $bIni, 'final' => $bFin, 'uso' => $bIni - $bFin,
                        'porcentaje' => $bIni > 0 ? round((($bIni - $bFin) / $bIni) * 100, 2) : 0
                    ]
                ];
            }

            return $this->respond(['departamentos' => $estructura]);
        } catch (\Throwable $e) {
            log_message('error', '[getReporteCompleto] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * ESTRUCTURA PARA EDICIÓN (PANTALLA ASIGNACIÓN)
     */
    public function getEstructura($idPlace, $anio, $mes)
    {
        try {
            $unidadModel = new UnidadOperativaModel();
            $grupoModel = new GrupoPresupuestalModel();
            $presupuestoMensualModel = new PresupuestoMensualModel();

            $placeIds = array_filter(array_map('intval', explode(',', (string)$idPlace)));
            if (empty($placeIds)) return $this->respond(['departamentos' => []]);

            $unidadesRaw = $unidadModel->select('UnidadOperativa.ID_UnidadOperativa, UnidadOperativa.Nombre, Places.Nombre_Corto as PlaceNombre')
                ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
                ->whereIn('UnidadOperativa.ID_Place', $placeIds)
                ->orderBy('Places.Nombre_Corto', 'ASC')
                ->orderBy('UnidadOperativa.Nombre', 'ASC')
                ->findAll();

            if (empty($unidadesRaw)) return $this->respond(['departamentos' => []]);

            $unidades = array_map(function($u) use ($placeIds) {
                if (count($placeIds) > 1) $u['Nombre'] = "({$u['PlaceNombre']}) {$u['Nombre']}";
                return $u;
            }, $unidadesRaw);

            $unidadIds = array_column($unidades, 'ID_UnidadOperativa');
            $presGuardados = $presupuestoMensualModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('Anio', (int)$anio)->where('Mes', (int)$mes)->findAll();
            
            $presIndex = [];
            foreach ($presGuardados as $p) { $presIndex[$p['ID_UnidadOperativa'] . '_' . $p['ID_GrupoPresupuestal']] = $p; }

            $idsG = array_unique(array_column($presGuardados, 'ID_GrupoPresupuestal'));
            $queryG = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->groupStart()->where('activo', true);
            if (!empty($idsG)) $queryG->orWhereIn('ID_GrupoPresupuestal', $idsG);
            $grupos = $queryG->groupEnd()->orderBy('Nombre', 'ASC')->findAll();

            $estructura = [];
            foreach ($unidades as $uni) {
                $idU = (int)$uni['ID_UnidadOperativa'];
                $gruposDeLaUnidad = [];
                foreach ($grupos as $grupo) {
                    if ((int)$grupo['ID_UnidadOperativa'] === $idU) {
                        $p = $presIndex[$idU . '_' . $grupo['ID_GrupoPresupuestal']] ?? null;
                        $valActivo = $grupo['activo'];
                        $esActivo = ($valActivo === true || $valActivo === 't' || $valActivo === 1 || $valActivo === '1');

                        if (!$esActivo && !$p) continue;
                        $grupo['Monto_Asignado'] = $p ? $p['Monto_Asignado'] : '';
                        $grupo['ID_PresupuestoMensual'] = $p ? $p['ID_PresupuestoMensual'] : null;
                        $gruposDeLaUnidad[] = $grupo;
                    }
                }
                $uni['grupos'] = $gruposDeLaUnidad;
                $estructura[] = $uni;
            }

            // Bloqueo por revisión
            $solicitudModel = new \App\Models\SolicitudesCambioPresupuestoModel();
            $revisiones = $solicitudModel->where(['Modulo' => 'PresupuestoMensual', 'Estado' => 'Pendiente', 'ID_Afectado' => "{$anio}-{$mes}"])->findAll();
            $bloqueado = false;
            if (!empty($revisiones)) {
                foreach ($revisiones as $rev) {
                    $payload = json_decode($rev['Datos_Payload'], true);
                    if (isset($payload['grupos'])) {
                        foreach ($payload['grupos'] as $pg) {
                            if (in_array((int)($pg['id_unidad'] ?? $pg['id_dpto']), $unidadIds)) { $bloqueado = true; break 2; }
                        }
                    }
                }
            }

            return $this->respond(['departamentos' => $estructura, 'bloqueadoPorRevision' => $bloqueado]);
        } catch (\Throwable $e) {
            log_message('error', '[getEstructura] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * BANCOS COMPARATIVO (PANTALLA 3)
     */
    public function getComparativoBancos($idPlace, $anio, $mes) {
        try {
            $uniModel = new UnidadOperativaModel(); $bModel = new BancoDptoModel(); $sModel = new SaldosBancariosModel();
            if (empty($mes)) return $this->respond(['razones' => []]);
            $meses = array_map('intval', explode(',', $mes));

            $unidadesB = $uniModel->select('UnidadOperativa.ID_Place, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre')
                ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial');
            if (!empty($idPlace) && $idPlace != '0') {
                $pIds = array_filter(array_map('intval', explode(',', (string)$idPlace)));
                if(!empty($pIds)) $unidadesB->whereIn('UnidadOperativa.ID_Place', $pIds);
            }
            $unidades = $unidadesB->findAll();
            if (empty($unidades)) return $this->respond(['razones' => []]);

            $rsIds = array_unique(array_column($unidades, 'ID_RazonSocial'));
            $bancos = $bModel->whereIn('ID_RazonSocial', $rsIds)->findAll();
            $bancoIds = array_column($bancos, 'ID_BancoDpto');
            $saldos = !empty($bancoIds) ? $sModel->whereIn('id_bancodpto', $bancoIds)->where('anio', (int)$anio)->whereIn('mes', $meses)->findAll() : [];

            $sIndex = [];
            foreach ($saldos as $s) { $sIndex[$s['id_bancodpto']][] = $s; }

            $resultado = [];
            foreach ($rsIds as $idRS) {
                $bRS = []; $ti = 0; $tf = 0; $nomRS = '';
                foreach ($bancos as $b) {
                    if ((int)$b['ID_RazonSocial'] === (int)$idRS) {
                        $seB = $sIndex[(int)$b['ID_BancoDpto']] ?? [];
                        if (!empty($seB)) {
                            usort($seB, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                            $ini = (float)reset($seB)['saldo_inicial'];
                            $fin = (float)end($seB)['saldo_final'];
                            if ($fin == 0 && (float)end($seB)['saldo_inicial'] != 0) $fin = (float)end($seB)['saldo_inicial'];
                            $bRS[] = ['banco' => $b['Banco'], 'clabe' => $b['Clabe'], 'inicial' => $ini, 'final' => $fin, 'usado' => $ini - $fin, 'porcentaje' => $ini > 0 ? round((($ini - $fin) / $ini) * 100, 2) : 0];
                            $ti += $ini; $tf += $fin;
                        }
                    }
                }
                foreach ($unidades as $u) { if ((int)$u['ID_RazonSocial'] === (int)$idRS) $nomRS = $u['RazonSocialNombre']; }
                $resultado[] = ['ID_RazonSocial' => $idRS, 'Nombre' => $nomRS, 'bancos' => $bRS, 'totales' => ['inicial' => $ti, 'final' => $tf, 'usado' => $ti - $tf, 'porcentaje' => $ti > 0 ? round((($ti - $tf) / $ti) * 100, 2) : 0]];
            }
            return $this->respond(['razones' => $resultado]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function saveMasivo()
    {
        $json = $this->request->getJSON(true);
        if (!isset($json['anio']) || !isset($json['mes']) || !isset($json['grupos']) || !is_array($json['grupos'])) {
            return $this->failValidationErrors('Datos incompletos.');
        }

        $anio = (int) $json['anio'];
        $mes = (int) $json['mes'];
        $comentarios = $json['comentarios'] ?? null;
        $usoCopia = $json['uso_copia'] ?? false;
        $restoAnio = $json['resto_anio'] ?? false;

        $mesesAGuardar = [$mes];
        if ($restoAnio) {
            $mesesAGuardar = [];
            for ($m = $mes; $m <= 12; $m++) {
                $mesesAGuardar[] = $m;
            }
        }

        if ($usoCopia) {
            $db = \Config\Database::connect();
            $db->transStart();
            try {
                $pmModel = new PresupuestoMensualModel();
                $paModel = new PresupuestoAnualModel();
                $uniModel = new UnidadOperativaModel();
                $placesModel = new PlacesModel();
                
                $rsAfectadas = [];

                foreach ($mesesAGuardar as $mActual) {
                    foreach ($json['grupos'] as $g) {
                        $idUnidad = (int) ($g['id_unidad'] ?? $g['id_dpto']);
                        $idGrupo  = (int) $g['id_grupo'];
                        $data = ['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => $idGrupo, 'Anio' => $anio, 'Mes' => $mActual, 'Monto_Asignado' => (float) $g['monto_asignado']];

                        $exists = $pmModel->where(['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => $idGrupo, 'Anio' => $anio, 'Mes' => $mActual])->first();
                        if ($exists) {
                            $pmModel->update($exists['ID_PresupuestoMensual'], $data);
                        } else {
                            $this->syncPresupuestoMensualSequenceIfNeeded($db);
                            $pmModel->insert($data);
                        }

                        $unidad = $uniModel->find($idUnidad);
                        if ($unidad) {
                            $place = $placesModel->find($unidad['ID_Place']);
                            if ($place) $rsAfectadas[] = (int) $place['ID_RazonSocial'];
                        }
                    }
                }

                foreach (array_unique($rsAfectadas) as $idRS) {
                    $q = $db->table('PresupuestoMensual')->selectSum('PresupuestoMensual.Monto_Asignado', 'total')->join('UnidadOperativa u', 'u.ID_UnidadOperativa = PresupuestoMensual.ID_UnidadOperativa')->join('Places p', 'p.ID_Place = u.ID_Place')->join('GrupoPresupuestal gp', 'gp.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')->where(['PresupuestoMensual.Anio' => $anio, 'p.ID_RazonSocial' => $idRS, 'gp.activo' => true])->get()->getRow();
                    $total = $q ? (float) $q->total : 0.0;
                    $pa = $paModel->where(['Anio' => $anio, 'ID_RazonSocial' => $idRS])->first();
                    if ($pa) $paModel->update($pa['ID_PresupuestoAnual'], ['Monto' => $total]);
                    else $paModel->insert(['ID_RazonSocial' => $idRS, 'Anio' => $anio, 'Monto' => $total]);
                }

                $db->transComplete();
                if ($db->transStatus() === false) throw new \Exception('Error al guardar copia.');
                return $this->respond(['success' => true, 'pending_review' => false, 'message' => 'Presupuesto guardado correctamente ✅']);
            } catch (\Exception $e) {
                $db->transRollback();
                return $this->failServerError($e->getMessage());
            }
        }

        $solModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        foreach ($mesesAGuardar as $mActual) {
            $solModel->insert(['ID_Usuario' => session('id'), 'Modulo' => 'PresupuestoMensual', 'Accion' => 'Masivo', 'ID_Afectado' => "{$anio}-{$mActual}", 'Datos_Payload' => json_encode($json), 'Estado' => 'Pendiente', 'Comentarios_Solicitante' => $comentarios]);
        }
        
        return $this->respondCreated(['success' => true, 'pending_review' => true, 'message' => 'Cambios enviados a revisión.']);
    }

    /**
     * EXPORTAR EXCEL DESDE JSON (PANTALLA 2)
     */
    public function exportarDatosJson()
    {
        try {
            $json = $this->request->getJSON(true); $datos = $json['datos'] ?? []; $hayExcedidos = $json['hayExcedidos'] ?? false;
            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Presupuesto');
            $headers = ['Departamento / Partida', 'Importe Asignado', 'Importe Comprometido', 'Importe Pagado', 'Compras del mes', 'Importe Disponible'];
            if ($hayExcedidos) $headers[] = 'Importe Excedido';
            $headers[] = '% Ejecución';

            $cols = ['A','B','C','D','E','F','G','H'];
            $headerStyle = ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F2937']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            foreach ($headers as $i => $h) { $sheet->setCellValue($cols[$i].'1', $h); $sheet->getStyle($cols[$i].'1')->applyFromArray($headerStyle); $sheet->getColumnDimension($cols[$i])->setAutoSize(true); }

            $row = 2;
            foreach ($datos as $rs) {
                $this->excelWriteJsonRow($sheet, $row, $rs['nombre'], $rs['totales'], $hayExcedidos, 'E5E7EB', true); $row++;
                foreach ($rs['segmentos'] as $seg) {
                    $this->excelWriteJsonRow($sheet, $row, '  '.$seg['nombre'], $seg['totales'], $hayExcedidos, 'DBEAFE', true, '1E3A8A'); $row++;
                    foreach ($seg['complejos'] as $comp) {
                        $this->excelWriteJsonRow($sheet, $row, '    '.$comp['nombre'], $comp['totales'], $hayExcedidos, null, false, null, true); $row++;
                        foreach ($comp['departamentos'] as $un) {
                            $this->excelWriteJsonRow($sheet, $row, '      '.$un['Nombre'], $un['totales'], $hayExcedidos, null, true); $row++;
                            foreach ($un['detalles'] as $dt) { $this->excelWriteJsonRow($sheet, $row, '        '.$dt['etiqueta'], $dt, $hayExcedidos, null, false, '6B7280'); $row++; }
                        }
                    }
                }
            }
            $this->excelFinalStyle($sheet, $row-1, 'presupuesto_ui', $hayExcedidos ? 'H' : 'G');
        } catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    /**
     * EXPORTAR REPORTE COMPLETO DESDE JSON (PANTALLA 4)
     */
    public function exportarReporteCompletoJson()
    {
        try {
            $json = $this->request->getJSON(true); $datos = $json['datos'] ?? []; $hayExcedidos = $json['hayExcedidos'] ?? false;
            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Consolidado');
            $headers = ['Departamento / Partida', 'Importe Asignado', 'Importe Comprometido', 'Importe Pagado', 'Compras del mes', 'Importe Disponible'];
            if ($hayExcedidos) $headers[] = 'Importe Excedido';
            $headers = array_merge($headers, ['% Ejec.', 'B. Inicial', 'B. Final', 'B. Diferencia']);

            $cols = ['A','B','C','D','E','F','G','H','I','J','K'];
            $lastCol = $cols[count($headers)-1];
            $headerStyle = ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F2937']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            foreach ($headers as $i => $h) { $sheet->setCellValue($cols[$i].'1', $h); $sheet->getStyle($cols[$i].'1')->applyFromArray($headerStyle); $sheet->getColumnDimension($cols[$i])->setAutoSize(true); }

            $row = 2;
            foreach ($datos as $rs) {
                $this->excelWriteJsonRow($sheet, $row, $rs['nombre'], $rs['totales'], $hayExcedidos, 'E5E7EB', true, '000000', false, true); $row++;
                foreach ($rs['segmentos'] as $seg) {
                    $this->excelWriteJsonRow($sheet, $row, '  '.$seg['nombre'], $seg['totales'], $hayExcedidos, 'DBEAFE', true, '1E3A8A', false, true); $row++;
                    foreach ($seg['complejos'] as $comp) {
                        $this->excelWriteJsonRow($sheet, $row, '    '.$comp['nombre'], $comp['totales'], $hayExcedidos, null, false, null, true, true); $row++;
                        foreach ($comp['departamentos'] as $un) {
                            $this->excelWriteJsonRow($sheet, $row, '      '.$un['Nombre'], $un['totales'], $hayExcedidos, null, true, null, false, true); $row++;
                            foreach ($un['detalles'] as $dt) { $this->excelWriteJsonRow($sheet, $row, '        '.$dt['etiqueta'], $dt, $hayExcedidos, null, false, '6B7280', false, false); $row++; }
                        }
                    }
                }
            }
            $this->excelFinalStyle($sheet, $row-1, 'consolidado_ui', $lastCol);
        } catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    private function excelWriteJsonRow($sheet, $row, $label, $t, $hayExcedidos, $bgColor=null, $bold=false, $fColor=null, $italic=false, $isFull=false)
    {
        $asig = (float)($t['asignado'] ?? $t['pAsignado'] ?? 0);
        $comp = (float)($t['comprometido'] ?? $t['pComprometido'] ?? 0);
        $ejec = (float)($t['ejecutado'] ?? $t['pEjecutado'] ?? 0);
        $disp = (float)($t['disponible'] ?? $t['pDisponible'] ?? 0);
        $exced = (float)($t['excedido'] ?? $t['pExcedido'] ?? 0);
        $perc = (float)($t['porcentaje'] ?? $t['pPorcentaje'] ?? 0);
        $compras = $comp + $ejec;

        $sheet->setCellValue('A'.$row, $label); $sheet->setCellValue('B'.$row, $asig); $sheet->setCellValue('C'.$row, $comp); $sheet->setCellValue('D'.$row, $ejec); $sheet->setCellValue('E'.$row, $compras); $sheet->setCellValue('F'.$row, $disp);
        $idx = 6;
        if ($hayExcedidos) { $sheet->setCellValue('G'.$row, $exced); if ($exced>0) $sheet->getStyle('G'.$row)->getFont()->getColor()->setRGB('FF0000'); $idx++; }
        $colP = chr(65 + $idx); $sheet->setCellValue($colP.$row, $perc / 100); $idx++;
        $last = $colP;
        if ($isFull && isset($t['bInicial'])) {
            $colI = chr(65 + $idx); $sheet->setCellValue($colI.$row, $t['bInicial']); $idx++;
            $colF = chr(65 + $idx); $sheet->setCellValue($colF.$row, $t['bFinal']); $idx++;
            $colD = chr(65 + $idx); $sheet->setCellValue($colD.$row, $t['bInicial'] - $t['bFinal']);
            $last = $colD;
        }
        $style = $sheet->getStyle('A'.$row.':'.$last.$row);
        if($bold) $style->getFont()->setBold(true); if($italic) $style->getFont()->setItalic(true); if($fColor) $style->getFont()->getColor()->setRGB($fColor);
        if($bgColor) $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
    }

    private function excelFinalStyle($sheet, $lastRow, $name, $lastCol)
    {
        $sheet->getStyle('B2:'.chr(ord($lastCol)-1).$lastRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        $headers = $sheet->rangeToArray('A1:'.$lastCol.'1')[0];
        $percCol = 'H';
        foreach($headers as $i=>$h) { if($h == '% Ejec.' || $h == '% Ejecución') $percCol = chr(65+$i); }
        $sheet->getStyle($percCol.'2:'.$percCol.$lastRow)->getNumberFormat()->setFormatCode('0.0%');
        $sheet->getStyle('A1:'.$lastCol.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $writer = new Xlsx($sheet->getParent());
        $filename = $name.'_'.date('Ymd_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        $writer->save('php://output'); exit();
    }

    public function exportarBancosJson()
    {
        try {
            $json = $this->request->getJSON(true); $datos = $json['datos'] ?? [];
            if (empty($datos)) return $this->fail('No hay datos');
            $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Bancos');
            $headers = ['Razón Social / Cuenta Bancaria', 'Saldo Inicial', 'Saldo Final', 'Diferencia (Uso)', '% Variación'];
            $cols = ['A', 'B', 'C', 'D', 'E'];
            $headerStyle = ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F2937']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            foreach ($headers as $i => $h) { $sheet->setCellValue($cols[$i].'1', $h); $sheet->getStyle($cols[$i].'1')->applyFromArray($headerStyle); $sheet->getColumnDimension($cols[$i])->setAutoSize(true); }
            $row = 2;
            foreach ($datos as $rs) {
                $sheet->setCellValue('A'.$row, $rs['Nombre']); $sheet->setCellValue('B'.$row, $rs['totales']['inicial']); $sheet->setCellValue('C'.$row, $rs['totales']['final']); $sheet->setCellValue('D'.$row, $rs['totales']['usado']); $sheet->setCellValue('E'.$row, $rs['totales']['porcentaje']/100);
                $sheet->getStyle('A'.$row.':E'.$row)->getFont()->setBold(true); $sheet->getStyle('A'.$row.':E'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB'); $row++;
                foreach ($rs['bancos'] as $b) {
                    $sheet->setCellValue('A'.$row, '  '.$b['banco'].' (CLABE: '.$b['clabe'].')'); $sheet->setCellValue('B'.$row, $b['inicial']); $sheet->setCellValue('C'.$row, $b['final']); $sheet->setCellValue('D'.$row, $b['usado']); $sheet->setCellValue('E'.$row, $b['porcentaje']/100);
                    if ($b['final'] < $b['inicial']) $sheet->getStyle('D'.$row)->getFont()->getColor()->setRGB('FF0000'); $row++;
                }
            }
            $sheet->getStyle('B2:D'.($row-1))->getNumberFormat()->setFormatCode('$#,##0.00'); $sheet->getStyle('E2:E'.($row-1))->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('A1:E'.($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $writer = new Xlsx($spreadsheet); $filename = 'reporte_bancos_'.date('Ymd_His').'.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); header('Content-Disposition: attachment;filename="'.$filename.'"');
            $writer->save('php://output'); exit();
        } catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    /**
     * EXPORTAR ASIGNACIÓN DE PRESUPUESTO (PANTALLA 1)
     */
    public function exportarAsignacion()
    {
        try {
            $json = $this->request->getJSON(true);
            $datos = $json['datos'] ?? [];
            $mesAnio = $json['mesAnio'] ?? '';

            if (empty($datos)) {
                return $this->fail('No hay datos para exportar');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Asignación Presupuesto');

            // Encabezados
            $headers = ['Complejo', 'Departamento De Operación / Partida Presupuestal', 'Monto Asignado'];
            $cols = ['A', 'B', 'C'];

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ];

            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i] . '1', $h);
                $sheet->getStyle($cols[$i] . '1')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = 2;
            foreach ($datos as $uni) {
                // Fila de la Unidad (Departamento)
                $sheet->setCellValue('A' . $row, $uni['place']);
                $sheet->setCellValue('B' . $row, $uni['unidad']);
                
                // Calculamos el total de la unidad
                $totalUnidad = 0;
                foreach ($uni['grupos'] as $g) {
                    $totalUnidad += (float)$g['monto'];
                }
                $sheet->setCellValue('C' . $row, $totalUnidad);

                // Estilo para la fila de la unidad
                $sheet->getStyle("A$row:C$row")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
                ]);
                $row++;

                // Filas de los grupos (partidas)
                foreach ($uni['grupos'] as $g) {
                    $sheet->setCellValue('B' . $row, '    ' . $g['nombre']);
                    $sheet->setCellValue('C' . $row, (float)$g['monto']);
                    $row++;
                }
            }

            // Fila de Gran Total
            $sheet->setCellValue('B' . $row, 'GRAN TOTAL');
            $sheet->setCellValue('C' . $row, "=SUM(C2:C" . ($row - 1) . ")/2"); // Dividido entre 2 porque las filas de unidad ya incluyen la suma de sus grupos
            
            // Alternativamente, si queremos evitar errores con la fórmula, podemos sumar solo las filas de "Unidad"
            // Pero como las unidades y grupos están intercalados, usaremos una lógica de estilo para el total:
            $sheet->getStyle("A$row:C$row")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ]);
            $row++;

            // Formato de moneda
            $sheet->getStyle('C2:C' . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            
            // Bordes
            $sheet->getStyle('A1:C' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $writer = new Xlsx($spreadsheet);
            $filename = "asignacion_presupuesto_{$mesAnio}_" . date('Ymd_His') . ".xlsx";

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit();
        } catch (\Throwable $e) {
            log_message('error', '[exportarAsignacion] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * EXPORTAR PRESUPUESTO ANUAL
     */
    public function exportarAnual()
    {
        try {
            $json = $this->request->getJSON(true);
            $anio = $json['anio'] ?? date('Y');
            $idsPlaces = $json['idsPlaces'] ?? [];
            
            if (empty($idsPlaces)) {
                return $this->fail('No se seleccionaron complejos');
            }

            $unidadModel = new UnidadOperativaModel();
            $grupoModel = new GrupoPresupuestalModel();
            $presupuestoMensualModel = new PresupuestoMensualModel();

            // 1. Obtener Unidades
            $unidadesRaw = $unidadModel->select('UnidadOperativa.ID_UnidadOperativa, UnidadOperativa.Nombre, Places.Nombre_Corto as PlaceNombre')
                ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
                ->whereIn('UnidadOperativa.ID_Place', $idsPlaces)
                ->orderBy('Places.Nombre_Corto', 'ASC')
                ->orderBy('UnidadOperativa.Nombre', 'ASC')
                ->findAll();

            if (empty($unidadesRaw)) {
                return $this->fail('No hay datos para exportar');
            }

            $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');

            // 2. Cargar Grupos Activos
            $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->orderBy('Nombre', 'ASC')->findAll();

            // 3. Cargar Presupuestos de todo el año
            $presupuestosRaw = $presupuestoMensualModel
                ->whereIn('ID_UnidadOperativa', $unidadIds)
                ->where('Anio', (int)$anio)
                ->findAll();

            // Indexar presupuestos: [ID_UnidadOperativa_ID_GrupoPresupuestal_Mes]
            $presIndex = [];
            foreach ($presupuestosRaw as $p) {
                $presIndex[$p['ID_UnidadOperativa'] . '_' . $p['ID_GrupoPresupuestal'] . '_' . $p['Mes']] = $p['Monto_Asignado'];
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Presupuesto $anio");

            // Encabezados
            $mesesNombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $headers = ['Complejo', 'Departamento / Partida'];
            $headers = array_merge($headers, $mesesNombres);
            $headers[] = 'Total Anual';
            
            $cols = [];
            for ($i = 0; $i < count($headers); $i++) {
                $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            }
            $lastCol = end($cols);

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ];

            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i] . '1', $h);
                $sheet->getStyle($cols[$i] . '1')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = 2;
            foreach ($unidadesRaw as $uni) {
                $idU = (int)$uni['ID_UnidadOperativa'];
                
                // Fila de la Unidad
                $sheet->setCellValue('A' . $row, $uni['PlaceNombre']);
                $sheet->setCellValue('B' . $row, $uni['Nombre']);
                
                $startUnidadRow = $row + 1;
                $gruposCount = 0;

                // Filas de los grupos
                foreach ($gruposAll as $g) {
                    if ((int)$g['ID_UnidadOperativa'] === $idU) {
                        $currentRow = $startUnidadRow + $gruposCount;
                        $sheet->setCellValue('B' . $currentRow, '    ' . $g['Nombre']);
                        
                        // Llenar meses
                        for ($mes = 1; $mes <= 12; $mes++) {
                            $monto = $presIndex[$idU . '_' . $g['ID_GrupoPresupuestal'] . '_' . $mes] ?? 0;
                            $colMes = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($mes + 2);
                            $sheet->setCellValue($colMes . $currentRow, (float)$monto);
                        }
                        
                        // Total Anual de la fila (Partida)
                        $firstMesCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3);
                        $lastMesCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(14);
                        $sheet->setCellValue($lastCol . $currentRow, "=SUM({$firstMesCol}{$currentRow}:{$lastMesCol}{$currentRow})");
                        
                        $gruposCount++;
                    }
                }

                // Sumatorias de la unidad por mes en la fila principal del departamento
                $firstMesCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(3);
                $lastMesCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(14);
                
                if ($gruposCount > 0) {
                    for ($mes = 1; $mes <= 12; $mes++) {
                        $colMes = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($mes + 2);
                        $sheet->setCellValue($colMes . $row, "=SUM({$colMes}{$startUnidadRow}:{$colMes}" . ($startUnidadRow + $gruposCount - 1) . ")");
                    }
                    // Total Anual de la Unidad
                    $sheet->setCellValue($lastCol . $row, "=SUM({$firstMesCol}{$row}:{$lastMesCol}{$row})");
                } else {
                    for ($mes = 1; $mes <= 12; $mes++) {
                        $colMes = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($mes + 2);
                        $sheet->setCellValue($colMes . $row, 0);
                    }
                    $sheet->setCellValue($lastCol . $row, 0);
                }

                // Estilo unidad
                $sheet->getStyle("A$row:{$lastCol}$row")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]
                ]);

                $row = $startUnidadRow + $gruposCount;
            }

            // Fila de Gran Total
            $sheet->setCellValue('B' . $row, 'TOTALES POR MES');
            for ($mes = 1; $mes <= 12; $mes++) {
                $colMes = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($mes + 2);
                $sheet->setCellValue($colMes . $row, "=SUM({$colMes}2:{$colMes}" . ($row - 1) . ")/2");
            }
            // Gran Total Anual
            $sheet->setCellValue($lastCol . $row, "=SUM({$lastCol}2:{$lastCol}" . ($row - 1) . ")/2");
            
            $sheet->getStyle("A$row:{$lastCol}$row")->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                'font' => ['color' => ['rgb' => 'FFFFFF']]
            ]);
            $row++;

            // Formato de moneda
            $sheet->getStyle("C2:{$lastCol}" . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            
            // Bordes
            $sheet->getStyle("A1:{$lastCol}" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $writer = new Xlsx($spreadsheet);
            $filename = "presupuesto_anual_{$anio}_" . date('Ymd_His') . ".xlsx";

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit();
        } catch (\Throwable $e) {
            log_message('error', '[exportarAnual] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    private function syncPresupuestoMensualSequenceIfNeeded($db): void
    {
        if (($db->DBDriver ?? '') !== 'Postgre') {
            return;
        }

        try {
            $db->query(
                'SELECT setval(pg_get_serial_sequence(\'"PresupuestoMensual"\', \'ID_PresupuestoMensual\'), COALESCE((SELECT MAX("ID_PresupuestoMensual") FROM "PresupuestoMensual"), 1), true)'
            );
        } catch (\Throwable $e) {
            log_message('warning', '[PresupuestoApiController::syncPresupuestoMensualSequenceIfNeeded] ' . $e->getMessage());
        }
    }

    /**
     * Rutas API Presupuesto Dictamen
     */
    public function getCambiosPendientes()
    {
        try {
            $solicitudModel = new \App\Models\SolicitudesCambioPresupuestoModel();
            return $this->respond($solicitudModel->getPendientes());
        } catch (\Throwable $e) {
            log_message('error', '[getCambiosPendientes] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    public function dictaminarCambio()
    {
        try {
            $json = $this->request->getJSON(true);
            $id = $json['ID_SolicitudCambio'] ?? null;
            $estado = $json['Estado'] ?? null;
            $comentarios = $json['Comentarios'] ?? null;

            if (!$id || !$estado) return $this->failValidationErrors('Faltan datos.');

            $solModel = new \App\Models\SolicitudesCambioPresupuestoModel();
            $solicitud = $solModel->find($id);
            if (!$solicitud) return $this->failNotFound('Solicitud no encontrada.');

            if ($estado === 'Aprobado') {
                $db = \Config\Database::connect();
                $db->transStart();
                
                $modulo = $solicitud['Modulo'];
                $accion = $solicitud['Accion'];
                $payload = json_decode($solicitud['Datos_Payload'], true);
                $idAfectado = $solicitud['ID_Afectado'];

                if ($accion === 'Masivo') {
                    if ($modulo === 'PresupuestoMensual') {
                        $this->ejecutarPresupuestoMasivo($payload);
                    } else if ($modulo === 'SaldosBancarios') {
                        $this->ejecutarSaldosMasivo($payload);
                    }
                } else {
                    $this->ejecutarCambioIndividual($modulo, $accion, $idAfectado, $payload);
                }

                $solModel->update($id, [
                    'Estado' => 'Aprobado',
                    'Comentarios_Revisor' => $comentarios,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $db->transComplete();
                if ($db->transStatus() === false) throw new \Exception('Error al procesar la transacción.');
                return $this->respond(['success' => true, 'message' => 'Cambio aprobado y ejecutado correctamente ✅']);
            } else {
                $solModel->update($id, [
                    'Estado' => 'Rechazado',
                    'Comentarios_Revisor' => $comentarios,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return $this->respond(['success' => true, 'message' => 'Cambio rechazado ❌']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[dictaminarCambio] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    private function ejecutarPresupuestoMasivo($json)
    {
        $pmModel = new PresupuestoMensualModel();
        $paModel = new PresupuestoAnualModel();
        $uniModel = new UnidadOperativaModel();
        $placesModel = new PlacesModel();
        $db = \Config\Database::connect();

        $anio = (int) $json['anio'];
        $mes = (int) $json['mes'];
        $restoAnio = $json['resto_anio'] ?? false;

        $mesesAGuardar = [$mes];
        if ($restoAnio) {
            for ($m = $mes + 1; $m <= 12; $m++) $mesesAGuardar[] = $m;
        }

        $rsAfectadas = [];
        foreach ($mesesAGuardar as $mActual) {
            foreach ($json['grupos'] as $g) {
                $idUnidad = (int) ($g['id_unidad'] ?? $g['id_dpto']);
                $idGrupo  = (int) $g['id_grupo'];
                $data = ['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => $idGrupo, 'Anio' => $anio, 'Mes' => $mActual, 'Monto_Asignado' => (float) $g['monto_asignado']];

                $exists = $pmModel->where(['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => $idGrupo, 'Anio' => $anio, 'Mes' => $mActual])->first();
                if ($exists) $pmModel->update($exists['ID_PresupuestoMensual'], $data);
                else {
                    $this->syncPresupuestoMensualSequenceIfNeeded($db);
                    $pmModel->insert($data);
                }

                $unidad = $uniModel->find($idUnidad);
                if ($unidad) {
                    $place = $placesModel->find($unidad['ID_Place']);
                    if ($place) $rsAfectadas[] = (int) $place['ID_RazonSocial'];
                }
            }
        }

        foreach (array_unique($rsAfectadas) as $idRS) {
            $q = $db->table('PresupuestoMensual')->selectSum('PresupuestoMensual.Monto_Asignado', 'total')->join('UnidadOperativa u', 'u.ID_UnidadOperativa = PresupuestoMensual.ID_UnidadOperativa')->join('Places p', 'p.ID_Place = u.ID_Place')->join('GrupoPresupuestal gp', 'gp.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')->where(['PresupuestoMensual.Anio' => $anio, 'p.ID_RazonSocial' => $idRS, 'gp.activo' => true])->get()->getRow();
            $total = $q ? (float) $q->total : 0.0;
            $pa = $paModel->where(['Anio' => $anio, 'ID_RazonSocial' => $idRS])->first();
            if ($pa) $paModel->update($pa['ID_PresupuestoAnual'], ['Monto' => $total]);
            else $paModel->insert(['ID_RazonSocial' => $idRS, 'Anio' => $anio, 'Monto' => $total]);
        }
    }

    private function ejecutarSaldosMasivo($json)
    {
        $sModel = new SaldosBancariosModel();
        foreach ($json['saldos'] as $s) {
            $data = ['id_bancodpto' => (int)$s['id_bancodpto'], 'anio' => (int)$json['anio'], 'mes' => (int)$json['mes'], 'saldo_inicial' => (float)$s['saldo_inicial'], 'saldo_final' => (float)$s['saldo_final']];
            $exists = $sModel->where(['id_bancodpto' => $data['id_bancodpto'], 'anio' => $data['anio'], 'mes' => $data['mes']])->first();
            if ($exists) $sModel->update($exists['id'], $data);
            else $sModel->insert($data);
        }
    }

    private function ejecutarCambioIndividual($modulo, $accion, $idAfectado, $payload)
    {
        $model = null;
        switch ($modulo) {
            case 'GrupoPresupuestal': $model = new GrupoPresupuestalModel(); break;
            case 'BancoDpto': $model = new BancoDptoModel(); break;
            case 'RazonSocial': $model = new RazonSocialModel(); break;
            case 'Place': $model = new PlacesModel(); break;
            case 'SegmentoNegocio': $model = new SegmentoNegocioModel(); break;
            case 'UnidadOperativa': $model = new UnidadOperativaModel(); break;
            case 'Departamento': $model = new DepartamentosModel(); break;
        }

        if (!$model) throw new \Exception("Módulo '$modulo' no reconocido.");

        if ($accion === 'Insertar') {
            $model->insert($payload);
        } else if ($accion === 'Editar') {
            if (!$idAfectado) throw new \Exception("ID de afectado no proporcionado para Editar.");
            $model->update($idAfectado, $payload);
        } else if ($accion === 'Eliminar') {
            if (!$idAfectado) throw new \Exception("ID de afectado no proporcionado para Eliminar.");
            $model->delete($idAfectado);
        }
    }

    /**
     * Rutas API Saldos Bancarios
     */
    public function getEstructuraSaldos($idRS, $anio, $mes)
    {
        try {
            $bModel = new BancoDptoModel();
            $sModel = new SaldosBancariosModel();
            $solModel = new \App\Models\SolicitudesCambioPresupuestoModel();

            $bancos = $bModel->where('ID_RazonSocial', (int)$idRS)->findAll();
            if (empty($bancos)) return $this->respond(['razones' => []]);

            $bancoIds = array_column($bancos, 'ID_BancoDpto');
            $saldos = $sModel->whereIn('id_bancodpto', $bancoIds)->where('anio', (int)$anio)->where('mes', (int)$mes)->findAll();
            
            $sIndex = [];
            foreach ($saldos as $s) { $sIndex[$s['id_bancodpto']] = $s; }

            foreach ($bancos as &$b) {
                $s = $sIndex[$b['ID_BancoDpto']] ?? null;
                $b['saldo_inicial'] = $s ? (float)$s['saldo_inicial'] : 0;
                $b['saldo_final'] = $s ? (float)$s['saldo_final'] : 0;
                $b['id_saldo'] = $s ? $s['id'] : null;
            }

            // Bloqueo por revisión
            $revisiones = $solModel->where(['Modulo' => 'SaldosBancarios', 'Estado' => 'Pendiente', 'ID_Afectado' => "{$anio}-{$mes}"])->findAll();
            return $this->respond(['razones' => [['ID_RazonSocial' => (int)$idRS, 'bancos' => $bancos]], 'bloqueadoPorRevision' => !empty($revisiones)]);
        } catch (\Throwable $e) {
            log_message('error', '[getEstructuraSaldos] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    public function saveSaldosMasivo()
    {
        try {
            $json = $this->request->getJSON(true);
            if (!isset($json['anio']) || !isset($json['mes']) || !isset($json['saldos'])) {
                return $this->failValidationErrors('Datos incompletos.');
            }

            $solModel = new \App\Models\SolicitudesCambioPresupuestoModel();
            $solModel->insert([
                'ID_Usuario' => session('id'),
                'Modulo' => 'SaldosBancarios',
                'Accion' => 'Masivo',
                'ID_Afectado' => "{$json['anio']}-{$json['mes']}",
                'Datos_Payload' => json_encode($json),
                'Estado' => 'Pendiente',
                'Comentarios_Solicitante' => $json['comentarios'] ?? null
            ]);

            return $this->respondCreated(['success' => true, 'pending_review' => true, 'message' => 'Saldos enviados a revisión.']);
        } catch (\Throwable $e) {
            log_message('error', '[saveSaldosMasivo] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }
}
