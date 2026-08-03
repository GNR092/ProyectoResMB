<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\PresupuestoMensualModel;
use App\Models\GrupoPresupuestalModel;
use App\Models\BancoDptoModel;
use App\Models\SaldosBancariosModel;
use App\Models\RazonSocialModel;
use App\Models\UnidadOperativaModel;
use App\Models\PlacesModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

use App\Models\DepartamentosModel;
use App\Models\ProveedorModel;
use App\Libraries\Rest;

class ReportesController extends ResourceController
{
    protected $format = 'json';
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }

    /**
     * Renderiza el modal principal de Reporte de Presupuesto
     */
    public function index()
    {
        $session = session();
        $data = [
            'departamentos_usuario' => $session->get('departamentos'), // Evitar colisión con la lista completa de dptos
            'nombre_usuario' => $session->get('nombre_usuario'),
            'departamento_usuario' => $session->get('departamento_usuario'),
        ];

        $razonSocialModel = new RazonSocialModel();
        $placesModel      = new PlacesModel();
        $departamentoModel = new DepartamentosModel();

        $data['razones_sociales'] = $razonSocialModel->orderBy('Nombre', 'ASC')->findAll();
        $data['places']           = $placesModel->orderBy('Nombre_Corto', 'ASC')->findAll();

        // Datos para el Reporte de Pagado/Por Pagar (Pantalla 6)
        $data['departamentos'] = $departamentoModel
            ->select('ID_Dpto, Nombre')
            ->orderBy('Nombre', 'ASC')
            ->findAll();

        $data['proveedores'] = $this->api->getProveedorIdAndRazonSocial();

        $ocs = $this->api->getAllOrdenCompraData();
        $tabledata = [];

        foreach ($ocs as $oc) {
            $o = $this->api->getOrdenCompra($oc['ID_Solicitud']);
            if ($o && !empty($o['OrdenCompra'])) {
                $estado = $o['OrdenCompra']['Estado'] ?? '';
                if (in_array($estado, ['Por Pagar', 'Pagada'])) {
                    $o['EstadoOrden'] = $estado;
                    $o['MontoTotal'] = $o['cotizacion']['Total'] ?? 0;
                    $tabledata[] = $o;
                }
            }
        }
        $data['tabledata'] = $tabledata;

        return view('modales/control/ReportePresupuesto', $data);
    }

    /**
     * REPORTE COMPARATIVO MENSUAL (PANTALLA 1 - SOLO PRESUPUESTO)
     */
    public function getComparativoMensual($idPlace, $anio, $mes)
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

            $unidadesRaw = $builder->orderBy('Razon_Social.Nombre', 'ASC')->orderBy('Places.Nombre_Corto', 'ASC')->orderBy('UnidadOperativa.Nombre', 'ASC')->findAll();
            if (empty($unidadesRaw)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);

            $unidadesIds = array_column($unidadesRaw, 'ID_UnidadOperativa');

            // 2. Obtener Grupos (Partidas)
            $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadesIds)->where('activo', true)->findAll();

            // 3. Obtener Presupuestos para esos meses
            $presupuestosRaw = $presupuestoMensualModel->whereIn('ID_UnidadOperativa', $unidadesIds)->where('Anio', $anio)->whereIn('Mes', $meses)->findAll();

            // Indexar presupuestos
            $presIndex = [];
            foreach ($presupuestosRaw as $p) {
                $presIndex[$p['ID_UnidadOperativa'] . '_' . $p['ID_GrupoPresupuestal'] . '_' . $p['Mes']] = $p;
            }

            $gtAsignado = 0; $gtEjecutado = 0; $estructura = [];

            foreach ($unidadesRaw as $uni) {
                $idU = (int)$uni['ID_UnidadOperativa'];
                $detallesMensuales = [];
                $tUniAsignado = 0; $tUniEjecutado = 0;

                foreach ($gruposAll as $g) {
                    if ((int)$g['ID_UnidadOperativa'] === $idU) {
                        $idG = (int)$g['ID_GrupoPresupuestal'];
                        foreach ($meses as $m) {
                            $p = $presIndex[$idU . '_' . $idG . '_' . $m] ?? null;
                            if (!$p) continue;
                            
                            $asig = (float)($p['Monto_Asignado'] ?? 0);
                            $ejec = (float)($p['Monto_Ejecutado'] ?? 0);
                            
                            if ($asig == 0 && $ejec == 0) continue;

                            $detallesMensuales[] = [
                                'etiqueta' => $g['Nombre'], 
                                'es_manual' => $g['es_manual'],
                                'mes'      => $m, 
                                'asignado' => $asig,
                                'ejecutado' => $ejec
                            ];
                            $tUniAsignado += $asig;
                            $tUniEjecutado += $ejec;
                        }
                    }
                }

                $uni['detalles'] = $detallesMensuales;
                $uni['totales']  = [
                    'asignado'  => $tUniAsignado,
                    'ejecutado' => $tUniEjecutado
                ];
                $estructura[] = $uni;
                $gtAsignado  += $tUniAsignado;
                $gtEjecutado += $tUniEjecutado;
            }

            return $this->respond([
                'departamentos' => $estructura,
                'totales_generales' => [
                    'asignado'  => $gtAsignado,
                    'ejecutado' => $gtEjecutado
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * EXPORTAR COMPARATIVO (PANTALLA 2 - GET)
     */
    public function exportarComparativo($idPlace, $anio, $mes)
    {
        // Este método suele redirigir a la lógica de exportación JSON o procesar directamente
        // Por consistencia con las rutas, lo dejamos disponible aunque el JS use el POST
        return $this->getComparativo($idPlace, $anio, $mes);
    }

    /**
     * EXPORTAR REPORTE COMPLETO (PANTALLA 4 - GET)
     */
    public function exportarReporteCompleto($idPlace, $anio, $mes)
    {
        return $this->getReporteCompleto($idPlace, $anio, $mes);
    }

    /**
     * Auxiliar para inicializar totales en cero
     */
    private function getMesNombre($m) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[(int)$m] ?? 'N/A';
    }

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
                            'es_manual'    => $g['es_manual'],
                            'asignado'     => $asig,
                            'comprometido' => $comp,
                            'ejecutado'    => $ejec,
                            'disponible'   => $disp,
                            'exce'         => $exce, // Re-named to exce as per your pattern or consistency
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
                            'etiqueta' => $g['Nombre'], 'es_manual' => $g['es_manual'], 'asignado' => $gasig, 'comprometido' => $gcomp, 'ejecutado' => $gejec,
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

    /**
     * EXPORTAR EXCEL DESDE JSON (PANTALLA 2)
     */
    public function exportarDatosJson()
    {
        try {
            $json = $this->request->getJSON(true); 
            $datos = $json['datos'] ?? []; 
            $hayExcedidos = $json['hayExcedidos'] ?? false;
            $pantalla = $json['pantalla'] ?? 'presupuesto';
            $mesesSeleccionados = $json['mesesSeleccionados'] ?? [];

            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet(); 
            $sheet = $spreadsheet->getActiveSheet(); 
            $sheet->setTitle('Presupuesto');

            $isMensual = ($pantalla === 'solo_presupuesto' && count($mesesSeleccionados) > 1);
            
            if ($isMensual) {
                $headers = ['Departamento / Partida'];
                foreach ($mesesSeleccionados as $m) { $headers[] = $m['nombre']; }
                $headers[] = 'Total Asignado';
            } else {
                $headers = ['Departamento / Partida', 'Importe Asignado', 'Importe Comprometido', 'Importe Pagado', 'Compras del mes', 'Importe Disponible'];
                if ($hayExcedidos) $headers[] = 'Importe Excedido';
                $headers[] = '% Ejecución';
            }

            $headerStyle = ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F2937']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            foreach ($headers as $i => $h) { 
                $colLetter = $this->getColumnLetter($i);
                $sheet->setCellValue($colLetter.'1', $h); 
                $sheet->getStyle($colLetter.'1')->applyFromArray($headerStyle); 
                $sheet->getColumnDimension($colLetter)->setAutoSize(true); 
            }

            $row = 2;
            foreach ($datos as $rs) {
                $this->excelWriteDynamicRow($sheet, $row, $rs['nombre'], $rs['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, 'E5E7EB', true); $row++;
                foreach ($rs['segmentos'] as $seg) {
                    $this->excelWriteDynamicRow($sheet, $row, '  '.$seg['nombre'], $seg['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, 'DBEAFE', true, '1E3A8A'); $row++;
                    foreach ($seg['complejos'] as $comp) {
                        $this->excelWriteDynamicRow($sheet, $row, '    '.$comp['nombre'], $comp['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, null, false, null, true); $row++;
                        foreach ($comp['departamentos'] as $un) {
                            $this->excelWriteDynamicRow($sheet, $row, '      '.$un['Nombre'], $un['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, null, true); $row++;
                            foreach ($un['detalles'] as $dt) { 
                                $isIndirecto = !empty($dt['es_manual']) && ($dt['es_manual'] == 1 || $dt['es_manual'] === true || $dt['es_manual'] === 't');
                                $this->excelWriteDynamicRow($sheet, $row, '        '.$dt['etiqueta'], $dt, $isMensual, $mesesSeleccionados, $hayExcedidos, $isIndirecto ? 'FEF3C7' : null, false, '6B7280'); $row++; 
                            }
                        }
                    }
                }
            }
            $lastCol = $this->getColumnLetter(count($headers) - 1);
            $this->excelFinalStyleDynamic($sheet, $row-1, 'presupuesto_export', $lastCol, $isMensual);
        } catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    /**
     * EXPORTAR REPORTE MENSUAL (PANTALLA 1 y SOLO EJECUTADO)
     */
    public function exportarMensualJson()
    {
        try {
            $json = $this->request->getJSON(true);
            $datos = $json['datos'] ?? [];
            $mesesSeleccionados = $json['mesesSeleccionados'] ?? [];
            $titulo = $json['titulo'] ?? 'Reporte Mensual';
            $campo  = $json['campo'] ?? 'asignado'; // 'asignado' o 'ejecutado'

            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte');

            $headers = ['Departamento / Partida'];
            $countMeses = count($mesesSeleccionados);

            $labelCampo = ($campo === 'asignado') ? 'Asignado' : 'Ejecutado';

            if ($countMeses > 1) {
                foreach ($mesesSeleccionados as $m) {
                    $headers[] = $m['nombre'];
                }
                $headers[] = 'Total ' . $labelCampo;
            } else {
                $headers[] = 'Importe ' . $labelCampo;
            }

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ];

            foreach ($headers as $i => $h) {
                $colLetter = $this->getColumnLetter($i);
                $sheet->setCellValue($colLetter . '1', $h);
                $sheet->getStyle($colLetter . '1')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            $row = 2;
            foreach ($datos as $rs) {
                $this->excelWriteMensualRow($sheet, $row, $rs['nombre'], $rs['totales'], $mesesSeleccionados, $campo, 'E5E7EB', true);
                $row++;
                foreach ($rs['segmentos'] as $seg) {
                    $this->excelWriteMensualRow($sheet, $row, '  ' . $seg['nombre'], $seg['totales'], $mesesSeleccionados, $campo, 'DBEAFE', true, '1E3A8A');
                    $row++;
                    foreach ($seg['complejos'] as $comp) {
                        $this->excelWriteMensualRow($sheet, $row, '    ' . $comp['nombre'], $comp['totales'], $mesesSeleccionados, $campo, null, false, null, true);
                        $row++;
                        foreach ($comp['departamentos'] as $un) {
                            $unTotales = $un['totales'] ?? [];
                            $this->excelWriteMensualRow($sheet, $row, '      ' . $un['Nombre'], $unTotales, $mesesSeleccionados, $campo, null, true);
                            $row++;
                            foreach ($un['detalles'] as $dt) {
                                $isIndirecto = !empty($dt['es_manual']) && ($dt['es_manual'] == 1 || $dt['es_manual'] === true || $dt['es_manual'] === 't');
                                $this->excelWriteMensualRow($sheet, $row, '        ' . $dt['etiqueta'], $dt, $mesesSeleccionados, $campo, $isIndirecto ? 'FEF3C7' : null, false, '6B7280');
                                $row++;
                            }
                        }
                    }
                }
            }

            $lastCol = $this->getColumnLetter(count($headers) - 1);
            $sheet->getStyle('B2:' . $lastCol . ($row - 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('A1:' . $lastCol . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $writer = new Xlsx($spreadsheet);
            $filename = str_replace(' ', '_', strtolower($titulo)) . '_' . date('Ymd_His') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            $writer->save('php://output');
            exit();

        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function excelWriteMensualRow($sheet, $row, $label, $t, $meses, $campo, $bgColor = null, $bold = false, $fColor = null, $italic = false)
    {
        $sheet->setCellValue('A' . $row, $label);
        $colIdx = 1;

        if (count($meses) > 1) {
            foreach ($meses as $m) {
                $val = (float)($t['importesPorMes'][$m['id']] ?? 0);
                $sheet->setCellValue($this->getColumnLetter($colIdx++) . $row, $val);
            }
        }
        
        $sheet->setCellValue($this->getColumnLetter($colIdx++) . $row, (float)($t[$campo] ?? 0));

        $lastCol = $this->getColumnLetter($colIdx - 1);
        $style = $sheet->getStyle('A' . $row . ':' . $lastCol . $row);
        if ($bold) $style->getFont()->setBold(true);
        if ($italic) $style->getFont()->setItalic(true);
        if ($fColor) $style->getFont()->getColor()->setRGB($fColor);
        if ($bgColor) $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
    }

    /**
     * EXPORTAR REPORTE COMPLETO DESDE JSON (PANTALLA 4)
     */
    public function exportarReporteCompletoJson()
    {
        try {
            $json = $this->request->getJSON(true); 
            $datos = $json['datos'] ?? []; 
            $hayExcedidos = $json['hayExcedidos'] ?? false;
            $pantalla = $json['pantalla'] ?? 'completo';
            $mesesSeleccionados = $json['mesesSeleccionados'] ?? [];

            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet(); $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Consolidado');

            $isMensual = ($pantalla === 'solo_presupuesto' && count($mesesSeleccionados) > 1);

            if ($isMensual) {
                $headers = ['Departamento / Partida'];
                foreach ($mesesSeleccionados as $m) { $headers[] = $m['nombre']; }
                $headers[] = 'Total Asignado';
            } else {
                $headers = ['Departamento / Partida', 'Importe Asignado', 'Importe Comprometido', 'Importe Pagado', 'Compras del mes', 'Importe Disponible'];
                if ($hayExcedidos) $headers[] = 'Importe Excedido';
                $headers = array_merge($headers, ['% Ejec.', 'B. Inicial', 'B. Final', 'B. Diferencia']);
            }

            $headerStyle = ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'1F2937']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]];
            foreach ($headers as $i => $h) { 
                $colLetter = $this->getColumnLetter($i);
                $sheet->setCellValue($colLetter.'1', $h); 
                $sheet->getStyle($colLetter.'1')->applyFromArray($headerStyle); 
                $sheet->getColumnDimension($colLetter)->setAutoSize(true); 
            }

            $row = 2;
            foreach ($datos as $rs) {
                $this->excelWriteDynamicRow($sheet, $row, $rs['nombre'], $rs['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, 'E5E7EB', true, '000000', false, true); $row++;
                foreach ($rs['segmentos'] as $seg) {
                    $this->excelWriteDynamicRow($sheet, $row, '  '.$seg['nombre'], $seg['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, 'DBEAFE', true, '1E3A8A', false, true); $row++;
                    foreach ($seg['complejos'] as $comp) {
                        $this->excelWriteDynamicRow($sheet, $row, '    '.$comp['nombre'], $comp['totales'], $isMensual, $mesesSeleccionados, $hayExcedidos, null, false, null, true, true); $row++;
                        foreach ($comp['departamentos'] as $un) {
                            $unTotales = $un['presupuesto'] ?? $un['totales'] ?? [];
                            $this->excelWriteDynamicRow($sheet, $row, '      '.$un['Nombre'], $unTotales, $isMensual, $mesesSeleccionados, $hayExcedidos, null, true, null, false, true); $row++;
                            foreach ($un['detalles'] as $dt) { 
                                $isIndirecto = !empty($dt['es_manual']) && ($dt['es_manual'] == 1 || $dt['es_manual'] === true || $dt['es_manual'] === 't');
                                $this->excelWriteDynamicRow($sheet, $row, '        '.$dt['etiqueta'], $dt, $isMensual, $mesesSeleccionados, $hayExcedidos, $isIndirecto ? 'FEF3C7' : null, false, '6B7280', false, false); $row++; 
                            }
                        }
                    }
                }
            }
            $lastCol = $this->getColumnLetter(count($headers) - 1);
            $this->excelFinalStyleDynamic($sheet, $row-1, 'consolidado_export', $lastCol, $isMensual, !$isMensual);
        } catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
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
     * EXPORTAR REPORTE DE VENCIMIENTOS DESDE JSON
     */
    public function exportarVencimientosJson()
    {
        try {
            $json = $this->request->getJSON(true);
            $datos = $json['datos'] ?? [];
            $esDetallado = $json['reporteDetallado'] ?? false;
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Corporativo MBM';
            $fechaHoy = date('d/m/Y H:i:s');
            
            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Vencimientos');

            // --- DISEÑO DEL ENCABEZADO ---
            $sheet->setCellValue('A1', $nombreEmpresa);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            
            $sheet->setCellValue('A2', 'REPORTE DE VENCIMIENTOS (' . ($esDetallado ? 'DETALLADO' : 'AGRUPADO') . ')');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

            $sheet->setCellValue('A3', 'Fecha de creación: ' . $fechaHoy);
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

            $headers = ['Cód.', 'RFC', 'Razón Social'];
            if ($esDetallado) {
                array_splice($headers, 1, 0, ['Folio', 'Empresa Origen']);
            }
            
            if (!$esDetallado) {
                $headers[] = 'Importe Crédito';
            }
            
            $headers[] = 'Importe Por Pagar';

            // Detectar si hay excedidos para añadir la columna
            $hayExcedidos = false;
            foreach ($datos as $v) {
                if ((float)($v['importeExcedido'] ?? 0) > 0) {
                    $hayExcedidos = true;
                    break;
                }
            }

            if ($hayExcedidos) {
                $headers[] = 'Importe Excedido';
            }
            
            if (!$esDetallado) {
                $headers[] = 'Saldo Crédito';
            }
            
            $headers[] = 'Días Créd.';
            
            if ($esDetallado) {
                $headers[] = 'Fecha Aprobacion.';
                $headers[] = 'Fecha Venc.';
            }
            
            $headers[] = 'Estatus';
            $headers[] = 'Días Vencido';

            $cols = [];
            for ($i = 0; $i < count($headers); $i++) {
$cols[] = chr(65 + $i);
            }
            
            $headerStyle = [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ];
            
            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i].'5', $h);
                $sheet->getStyle($cols[$i].'5')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = 6;
            
            // Si es detallado, ordenar por proveedor y luego por días vencidos (más urgentes primero)
            $datosOrdenados = $datos;
            if ($esDetallado) {
                usort($datosOrdenados, function($a, $b) {
                    $provA = $a['ID_Proveedor'] ?? 0;
                    $provB = $b['ID_Proveedor'] ?? 0;
                    if ($provA != $provB) {
                        return $provA - $provB;
                    }
                    return ($b['diasVencidos'] ?? 0) - ($a['diasVencidos'] ?? 0);
                });
            }
            
            $currentProveedor = null;
            $subtotalPorPagar = 0;
            $subtotalExcedido = 0;
            $subtotalRows = []; // Para aplicar formato de moneda después
            
            foreach ($datosOrdenados as $v) {
                $proveedorId = $v['ID_Proveedor'] ?? null;
                
                // Si cambió de proveedor y no es el primero, insertar subtotal
                if ($currentProveedor !== null && $proveedorId !== $currentProveedor) {
                    $c = 0;
                    $sheet->setCellValue($cols[$c++].$row, '');
                    if ($esDetallado) {
                        $sheet->setCellValue($cols[$c++].$row, '');
                        $sheet->setCellValue($cols[$c++].$row, '');
                    }
                    $sheet->setCellValue($cols[$c++].$row, '');
                    $sheet->setCellValue($cols[$c++].$row, 'Subtotal ' . $proveedorAnteriorNombre);
                    
                    if (!$esDetallado) {
                        $sheet->setCellValue($cols[$c++].$row, '');
                    }
                    
                    $sheet->setCellValue($cols[$c++].$row, $subtotalPorPagar);
                    
                    if ($hayExcedidos) {
                        $sheet->setCellValue($cols[$c++].$row, $subtotalExcedido);
                    }
                    
                    if (!$esDetallado) {
                        $sheet->setCellValue($cols[$c++].$row, '');
                    }
                    
                    $sheet->setCellValue($cols[$c++].$row, '');
                    
                    if ($esDetallado) {
                        $sheet->setCellValue($cols[$c++].$row, '');
                        $sheet->setCellValue($cols[$c++].$row, '');
                    }
                    
                    $sheet->setCellValue($cols[$c++].$row, '');
                    $sheet->setCellValue($cols[$c++].$row, '');
                    
                    // Estilo subtotal: solo negrita y bordes (sin color de fondo)
                    $lastCol = $cols[count($headers)-1];
                    $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getFont()->setBold(true);
                    $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    // Doble borde inferior para separar visualmente del siguiente grupo
                    $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
                    
                    // Guardar fila de subtotal para formato de moneda
                    $subtotalRows[] = $row;
                    
                    // Reset subtotales
                    $subtotalPorPagar = 0;
                    $subtotalExcedido = 0;
                    $row++;
                }
                
                $currentProveedor = $proveedorId;
                $proveedorAnteriorNombre = $v['RazonSocial'];
                
                $c = 0;
                $sheet->setCellValue($cols[$c++].$row, $v['ID_Proveedor']);
                if ($esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, $v['No_Folio']);
                    $sheet->setCellValue($cols[$c++].$row, $v['Complejo'] ?? 'N/A');
                }
                $sheet->setCellValue($cols[$c++].$row, $v['RFC'] ?: 'N/A');
                $sheet->setCellValue($cols[$c++].$row, $v['RazonSocial']);
                
                if (!$esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, (float)$v['Monto_Credito']);
                }
                
                $importePorPagar = (float)$v['importePorPagar'];
                $sheet->setCellValue($cols[$c++].$row, $importePorPagar);
                $subtotalPorPagar += $importePorPagar;

                if ($hayExcedidos) {
                    $importeExcedido = (float)($v['importeExcedido'] ?? 0);
                    $sheet->setCellValue($cols[$c++].$row, $importeExcedido);
                    $subtotalExcedido += $importeExcedido;
                }
                
                if (!$esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, (float)$v['saldoCredito']);
                }
                
                $sheet->setCellValue($cols[$c++].$row, (int)$v['Dias_Credito']);
                
                if ($esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, $v['fechaReferenciaStr']);
                    $sheet->setCellValue($cols[$c++].$row, $v['fechaVencimientoStr'] ?? 'N/A');
                }
                
                $sheet->setCellValue($cols[$c++].$row, $v['estatusVencimiento'] ?? 'N/A');
                $sheet->setCellValue($cols[$c++].$row, $v['textoVencimiento']);

                // Estilos de semáforo - solo color para vencidos
                if (isset($v['claseSemaforo'])) {
                    if (strpos($v['claseSemaforo'], 'bg-gray-900') !== false || strpos($v['claseSemaforo'], 'bg-red-100') !== false) {
                        // Vencidos: fondo rojo tenue, letra roja oscura
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFont()
                            ->getColor()->setRGB('991B1B');
                    }
                    // Quitados: amarillo (por vencer) y otros - sin color
                }
                $row++;
            }
            
            // Subtotal del último proveedor
            if ($currentProveedor !== null) {
                $c = 0;
                $sheet->setCellValue($cols[$c++].$row, '');
                if ($esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, '');
                    $sheet->setCellValue($cols[$c++].$row, '');
                }
                $sheet->setCellValue($cols[$c++].$row, '');
                $sheet->setCellValue($cols[$c++].$row, 'Subtotal ' . $proveedorAnteriorNombre);
                
                if (!$esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, '');
                }
                
                $sheet->setCellValue($cols[$c++].$row, $subtotalPorPagar);
                
                if ($hayExcedidos) {
                    $sheet->setCellValue($cols[$c++].$row, $subtotalExcedido);
                }
                
                if (!$esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, '');
                }
                
                $sheet->setCellValue($cols[$c++].$row, '');
                
                if ($esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, '');
                    $sheet->setCellValue($cols[$c++].$row, '');
                }
                
                $sheet->setCellValue($cols[$c++].$row, '');
                $sheet->setCellValue($cols[$c++].$row, '');
                
                // Estilo subtotal
                $lastCol = $cols[count($headers)-1];
                $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getFont()->setBold(true);
                $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                // Doble borde inferior para separar visualmente
                $sheet->getStyle('A'.$row.':'.$lastCol.$row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
                
                $subtotalRows[] = $row;
                $row++; // Fila en blanco después del último subtotal
            }

            // Formato de moneda para columnas de dinero
            if ($esDetallado) {
                // Si es detallado, el dinero está en D (Folio no, RFC no...)
                // Vamos a usar un bucle para detectar qué letras son moneda
                foreach($headers as $idx => $h) {
                    if (in_array($h, ['Importe Crédito', 'Importe Por Pagar', 'Importe Excedido', 'Saldo Crédito'])) {
                        $colLetter = $cols[$idx];
                        $sheet->getStyle($colLetter.'6:'.$colLetter.($row-1))->getNumberFormat()->setFormatCode('$#,##0.00');
                    }
                }
                // Aplicar formato de moneda a las filas de subtotal
                foreach ($subtotalRows as $subRow) {
                    foreach($headers as $idx => $h) {
                        if (in_array($h, ['Importe Crédito', 'Importe Por Pagar', 'Importe Excedido', 'Saldo Crédito'])) {
                            $colLetter = $cols[$idx];
                            $sheet->getStyle($colLetter.$subRow)->getNumberFormat()->setFormatCode('$#,##0.00');
                        }
                    }
                }
            } else {
                foreach($headers as $idx => $h) {
                    if (in_array($h, ['Importe Crédito', 'Importe Por Pagar', 'Importe Excedido', 'Saldo Crédito'])) {
                        $colLetter = $cols[$idx];
                        $sheet->getStyle($colLetter.'6:'.$colLetter.($row-1))->getNumberFormat()->setFormatCode('$#,##0.00');
                    }
                }
            }

            $sheet->getStyle('A5:'.$cols[count($headers)-1].($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // --- BLOQUE DE RESUMEN FINAL ---
            $totalVencido = 0; $totalPagoHoy = 0; $totalPorVencer = 0; $totalGeneral = 0;
            foreach ($datos as $v) {
                $monto = (float)($v['importePorPagar'] ?? 0);
                $totalGeneral += $monto;
                $est = $v['estatusVencimiento'] ?? '';
                if ($est === 'Vencido') $totalVencido += $monto;
                elseif ($est === 'Pago Hoy') $totalPagoHoy += $monto;
                elseif ($est === 'Por Vencer') $totalPorVencer += $monto;
            }

            $row += 2;
            $resumenRows = [
                ['Total Vencido', $totalVencido, 'FEE2E2', '991B1B'],
                ['Pago Hoy', $totalPagoHoy, 'DBEAFE', '1E40AF'],
                ['Por Vencer', $totalPorVencer, 'DCFCE7', '166534'],
                ['Total General', $totalGeneral, '1F2937', 'FFFFFF']
            ];

            foreach ($resumenRows as $res) {
                $sheet->setCellValue('B'.$row, $res[0]);
                $sheet->setCellValue('C'.$row, $res[1]);
                $sheet->getStyle('B'.$row.':C'.$row)->getFont()->setBold(true);
                $sheet->getStyle('B'.$row.':C'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($res[2]);
                $sheet->getStyle('B'.$row.':C'.$row)->getFont()->getColor()->setRGB($res[3]);
                $sheet->getStyle('C'.$row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('B'.$row.':C'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $row++;
            }

            $writer = new Xlsx($spreadsheet);
            $filename = 'reporte_vencimientos_'.($esDetallado ? 'detallado_' : 'agrupado_').date('Ymd_His').'.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit();

        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function getColumnLetter($index) {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = floor($index / 26) - 1;
        }
        return $letter;
    }

    private function excelWriteDynamicRow($sheet, $row, $label, $t, $isMensual, $meses, $hayExcedidos, $bgColor=null, $bold=false, $fColor=null, $italic=false, $isFull=false) {
        $sheet->setCellValue('A'.$row, $label);
        $colIdx = 1;

        if ($isMensual) {
            foreach ($meses as $m) {
                $val = (float)($t['importesPorMes'][$m['id']] ?? 0);
                $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, $val);
            }
            $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, (float)($t['asignado'] ?? 0));
        } else {
            $asig = (float)($t['asignado'] ?? $t['pAsignado'] ?? 0);
            $comp = (float)($t['comprometido'] ?? $t['pComprometido'] ?? 0);
            $ejec = (float)($t['ejecutado'] ?? $t['pEjecutado'] ?? 0);
            $disp = (float)($t['disponible'] ?? $t['pDisponible'] ?? 0);
            $exced = (float)($t['excedido'] ?? $t['pExcedido'] ?? 0);
            $perc = (float)($t['porcentaje'] ?? $t['pPorcentaje'] ?? 0);
            $compras = $comp + $ejec;

            $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, $asig);
            $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, $comp);
            $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, $ejec);
            $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, $compras);
            $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, $disp);
            if ($hayExcedidos) { 
                $colLetter = $this->getColumnLetter($colIdx++);
                $sheet->setCellValue($colLetter.$row, $exced); 
                if ($exced > 0) $sheet->getStyle($colLetter.$row)->getFont()->getColor()->setRGB('FF0000'); 
            }
            
            $percCol = $this->getColumnLetter($colIdx++);
            $sheet->setCellValue($percCol.$row, $perc / 100);

            if ($isFull) {
                $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, (float)($t['bInicial'] ?? 0));
                $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, (float)($t['bFinal'] ?? 0));
                $sheet->setCellValue($this->getColumnLetter($colIdx++).$row, (float)(($t['bInicial'] ?? 0) - ($t['bFinal'] ?? 0)));
            }
        }

        $lastCol = $this->getColumnLetter($colIdx - 1);
        $style = $sheet->getStyle('A'.$row.':'.$lastCol.$row);
        if($bold) $style->getFont()->setBold(true); 
        if($italic) $style->getFont()->setItalic(true); 
        if($fColor) $style->getFont()->getColor()->setRGB($fColor);
        if($bgColor) $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColor);
    }

    private function excelFinalStyleDynamic($sheet, $lastRow, $name, $lastCol, $isMensual, $isFull=false) {
        $sheet->getStyle('B2:'.$lastCol.$lastRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        
        if (!$isMensual) {
            $headers = $sheet->rangeToArray('A1:'.$lastCol.'1')[0];
            $percCol = 'H';
            foreach($headers as $i=>$h) { if($h == '% Ejec.' || $h == '% Ejecución') $percCol = $this->getColumnLetter($i); }
            $sheet->getStyle($percCol.'2:'.$percCol.$lastRow)->getNumberFormat()->setFormatCode('0.0%');
        }

        $sheet->getStyle('A1:'.$lastCol.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $writer = new Xlsx($sheet->getParent());
        $filename = $name.'_'.date('Ymd_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        $writer->save('php://output'); exit();
    }
}
