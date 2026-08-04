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
use App\Models\SolicitudModel;
use App\Models\SolicitudProductModel;
use App\Models\SolicitudServiciosModel;
use App\Models\BitacoraModel;
use App\Libraries\PDF;
use App\Libraries\Rest;
use App\Libraries\Status;
use App\Libraries\SolicitudTipo;

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

    /**
     * Exporta el reporte "Vencimientos" (Créditos/Vencimientos) a PDF.
     * Recibe los datos filtrados del frontend (mismo patrón que exportarVencimientosJson).
     * Soporta modo detallado y agrupado, subtotales por proveedor y bloque resumen 4 colores.
     */
    public function exportarVencimientosPdf()
    {
        try {
            $json   = $this->request->getJSON(true);
            $datos  = $json['datos'] ?? [];
            $esDetallado = $json['reporteDetallado'] ?? false;
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Corporativo MBM';
            $fechaHoy = date('d/m/Y H:i:s');

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el PDF');
            }

            $pdf = new PDF('L', 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->SetAutoPageBreak(false);
            $pdf->setHeaderTitle('REPORTE DE VENCIMIENTOS (' . ($esDetallado ? 'DETALLADO' : 'AGRUPADO') . ')');
            $pdf->AddPage();

            // --- Bloque informativo (título + metadata) ---
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(18, 18, 18);
            $pdf->Cell(0, 7, $this->_iso('REPORTE DE VENCIMIENTOS (' . ($esDetallado ? 'DETALLADO' : 'AGRUPADO') . ')'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 4, $this->_iso('Empresa: ' . $nombreEmpresa), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Tipo: ' . ($esDetallado ? 'Detallado' : 'Agrupado')), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Generado: ' . $fechaHoy), 0, 1, 'L');
            $pdf->Ln(2);

            // Construir encabezados dinámicos (idéntico a exportarVencimientosJson)
            $headers = ['Cod.', 'RFC', 'Razon Social'];
            if ($esDetallado) {
                array_splice($headers, 1, 0, ['Folio', 'Empresa Origen']);
            }
            if (!$esDetallado) {
                $headers[] = 'Importe Credito';
            }
            $headers[] = 'Importe Por Pagar';

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
                $headers[] = 'Saldo Credito';
            }
            $headers[] = 'Dias Cred.';
            
            if ($esDetallado) {
                $headers[] = 'Fecha Aprobacion.';
                $headers[] = 'Fecha Venc.';
            }
            
            $headers[] = 'Estatus';
            $headers[] = 'Dias Vencido';

            $nCols = count($headers);
            $colW = [];
            // Anchos base (ajustados para que quepan en ~259mm)
            $baseWidths = [
                'Cod.' => 12, 'RFC' => 22, 'Razon Social' => 38,
                'Folio' => 18, 'Empresa Origen' => 22,
                'Importe Credito' => 24, 'Importe Por Pagar' => 24,
                'Importe Excedido' => 24, 'Saldo Credito' => 22,
                'Dias Cred.' => 14, 'Fecha Aprobacion.' => 20, 'Fecha Venc.' => 20,
                'Estatus' => 16, 'Dias Vencido' => 20
            ];
            foreach ($headers as $h) {
                $colW[] = $baseWidths[$h] ?? 18;
            }

            $lineH = 5;

            $pdf->SetWidths($colW);

            $drawHeader = function($pdf) use ($headers, $colW, $lineH) {
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetFillColor(31, 41, 55);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetDrawColor(26, 26, 26);
                $pdf->SetX(8);
                $pdf->drawTableRow($colW, array_map(fn($h) => $this->_iso($h), $headers), array_fill(0, count($headers), 'C'), $lineH, true);
            };

            $drawHeader($pdf);

            // Ordenar datos: detallado por proveedor + diasVencidos desc
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

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(220);
            $currentProveedor = null;
            $subtotalPorPagar = 0;
            $subtotalExcedido = 0;
            $proveedorAnteriorNombre = '';

            foreach ($datosOrdenados as $v) {
                $proveedorId = $v['ID_Proveedor'] ?? null;
                
                // Subtotal cuando cambia proveedor (modo detallado)
                if ($esDetallado && $currentProveedor !== null && $proveedorId !== $currentProveedor) {
                    $pdf->SetFont('Arial', 'B', 8);
                    $pdf->SetTextColor(0, 0, 0);
                    $sy = $pdf->GetY();
                    $x = 8;
                    $c = 0;
                    $pdf->SetXY($x, $sy);
                    $pdf->MultiCell($colW[$c++], $lineH, $this->_iso('Subtotal ' . $proveedorAnteriorNombre), 1, 'L', false);
                    // Solo subtotales en columnas de importe
                    $totalPorPagarW = 0;
                    $totalExcedidoW = 0;
                    // No dibujar celdas vacías, solo el texto "Subtotal..."
                    // Para simplicidad, una fila completa con el label
                    $totalW = array_sum($colW);
                    $pdf->SetX(8);
                    $pdf->Cell($totalW, $lineH, $this->_iso('Subtotal ' . $proveedorAnteriorNombre), 1, 0, 'L', false);
                    $pdf->SetFont('Arial', '', 8);
                    $subtotalPorPagar = 0;
                    $subtotalExcedido = 0;
                }
                
                $currentProveedor = $proveedorId;
                $proveedorAnteriorNombre = $v['RazonSocial'] ?? 'N/A';
                
                $cells = [];
                $c = 0;
                $cells[$c++] = $v['ID_Proveedor'];                                 // Cod.
                if ($esDetallado) {
                    $cells[$c++] = $v['No_Folio'];                                 // Folio
                    $cells[$c++] = $v['Complejo'] ?? 'N/A';                        // Empresa Origen
                }
                $cells[$c++] = $v['RFC'] ?: 'N/A';                                 // RFC
                $cells[$c++] = $v['RazonSocial'];                                  // Razon Social
                if (!$esDetallado) {
                    $cells[$c++] = '$' . number_format((float)($v['Monto_Credito'] ?? 0), 2); // Importe Credito
                }
                $cells[$c++] = '$' . number_format((float)($v['importePorPagar'] ?? 0), 2); // Importe Por Pagar
                $subtotalPorPagar += (float)($v['importePorPagar'] ?? 0);
                
                if ($hayExcedidos) {
                    $cells[$c++] = '$' . number_format((float)($v['importeExcedido'] ?? 0), 2); // Importe Excedido
                    $subtotalExcedido += (float)($v['importeExcedido'] ?? 0);
                }
                
                if (!$esDetallado) {
                    $cells[$c++] = '$' . number_format((float)($v['saldoCredito'] ?? 0), 2); // Saldo Credito
                }
                $cells[$c++] = $v['Dias_Credito'];                                 // Dias Cred.
                if ($esDetallado) {
                    $cells[$c++] = $v['fechaReferenciaStr'];                       // Fecha Aprobacion.
                    $cells[$c++] = $v['fechaVencimientoStr'] ?? 'N/A';             // Fecha Venc.
                }
                $cells[$c++] = $v['estatusVencimiento'] ?? 'N/A';                  // Estatus
                $cells[$c++] = $v['textoVencimiento'];                             // Dias Vencido

                // Calcular altura de fila
                $lineCounts = [];
                foreach ($cells as $i => $c) {
                    $lineCounts[$i] = $pdf->NbLines($colW[$i], $this->_iso($c));
                }
                $h = max($lineCounts) * $lineH;

                if ($pdf->GetY() + $h > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage();
                    $drawHeader($pdf);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetDrawColor(220);
                }

                $nCells = count($cells);
                $aligns = [];
                for ($i = 0; $i < $nCells; $i++) {
                    $align = ($i === 0 || $i >= $nCells - 2) ? 'C' : 'L';
                    if ($i >= 4 && $i <= 10) $align = 'R'; // Columnas monetarias a la derecha
                    $aligns[$i] = $align;
                }
                $pdf->SetX(8);
                $pdf->drawTableRow($colW, array_map(fn($v) => $this->_iso((string)$v), $cells), $aligns, $lineH, false);
            }

            // --- Último subtotal (modo detallado) + Footer Total General ---
            if ($esDetallado && $currentProveedor !== null) {
                $totalW = array_sum($colW);
                if ($pdf->GetY() + $lineH > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage();
                    $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);
                }
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetX(8);
                    $pdf->Cell($totalW, $lineH, $this->_iso('SUBTOTAL ' . $proveedorAnteriorNombre), 1, 1, 'L', false);
            }

            // --- Calcular totales ---
            $totalGeneral = 0;
            $totalVencido = 0;
            $totalPagoHoy = 0;
            $totalPorVencer = 0;
            foreach ($datos as $v) {
                $imp = (float)($v['importePorPagar'] ?? 0);
                $totalGeneral += $imp;
                $estatus = $v['estatusVencimiento'] ?? '';
                if ($estatus === 'Vencido') $totalVencido += $imp;
                elseif ($estatus === 'Pago Hoy') $totalPagoHoy += $imp;
                elseif ($estatus === 'Por Vencer') $totalPorVencer += $imp;
            }

            // --- Footer: Total General (estilo oscuro) ---
            $this->_dibujarTotalGeneral($pdf, $colW, 'TOTAL GENERAL: $' . number_format($totalGeneral, 2));

            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('D', 'reporte_vencimientos_' . ($esDetallado ? 'detallado' : 'agrupado') . '_' . date('Ymd') . '.pdf');
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarVencimientosPdf] ' . $e->getMessage());
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

    /**
     * EXPORTAR REPORTE MENSUAL EN PDF (pantallas solo_presupuesto / solo_ejecutado).
     * Reproduce la misma jerarquía y estilo visual que exportarMensualJson,
     * pero con salida en formato documento PDF (FPDF/FPDI + setasign).
     *
     * @return mixed
     */
    public function exportarMensualPdf()
    {
        try {
            $json   = $this->request->getJSON(true);
            $datos  = $json['datos'] ?? [];
            $mesesSeleccionados = $json['mesesSeleccionados'] ?? [];
            $titulo = $json['titulo'] ?? 'Presupuesto Asignado';
            $campo  = (($json['campo'] ?? 'asignado') === 'asignado') ? 'asignado' : 'ejecutado';
            $mesAnio = $json['mesAnio'] ?? (date('Y') . '-' . date('n'));
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el PDF');
            }

            $isMensual  = count($mesesSeleccionados) > 1;
            $orient     = $isMensual ? 'L' : 'P';
            $labelCampo = $campo === 'asignado' ? 'Asignado' : 'Ejecutado';

            $pdf = new PDF($orient, 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->SetAutoPageBreak(false);
            $pdf->setHeaderTitle('REPORTE ' . mb_strtoupper($titulo));

            $pdf->AddPage();

            // --- Bloque informativo (encabezado del reporte) ---
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(18, 18, 18);
            $pdf->Cell(0, 7, $this->_iso('REPORTE ' . strtoupper($titulo)), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            $mesesStr = $isMensual
                ? implode(', ', array_column($mesesSeleccionados, 'nombre'))
                : (count($mesesSeleccionados) ? $mesesSeleccionados[0]['nombre'] : '-');
            $pdf->Cell(0, 4, $this->_iso('Empresa: ' . $nombreEmpresa), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Periodo: ' . $mesAnio . '   Mes(es): ' . $mesesStr), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Campo: ' . $labelCampo . '   Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'L');
            $pdf->Ln(2);

            // --- Anchos de columnas ---
            $contentW = $orient === 'L' ? 259.4 : 195.9;
            $denomW   = $isMensual ? 66 : 108;
            $totalW   = $isMensual ? 26 : 30;
            $nMeses   = max(count($mesesSeleccionados), 1);
            $mesW     = ($contentW - $denomW - $totalW) / $nMeses;
            if ($mesW < 15) { $mesW = 15; }

            $x0 = $pdf->GetX();

            $this->_pdfMensualHeaderTabla($pdf, $x0, $denomW, $mesW, $totalW, $mesesSeleccionados, $isMensual, $nMeses);

            $h = 6.2;
            $ctx = [
                'x0' => $x0, 'h' => $h, 'denomW' => $denomW, 'mesW' => $mesW, 'totalW' => $totalW,
                'nMeses' => $nMeses, 'meses' => $mesesSeleccionados, 'isMensual' => $isMensual, 'campo' => $campo,
            ];

            $totalGeneral = 0.0;
            foreach ($datos as $rs) {
                $totalGeneral += (float)(($rs['totales'][$campo] ?? $rs[$campo] ?? 0));
                $this->_pdfRenderNodo($pdf, $rs, 0, $ctx);
            }

            // --- Footer de totales ---
            $y0 = $pdf->GetY();
            $pdf->SetDrawColor(26, 26, 26);
            $pdf->Line($x0, $y0, $x0 + $denomW + ($isMensual ? $mesW * $nMeses : 0) + $totalW, $y0);
            $y0 += 0.5;

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(31, 41, 55);
            $pdf->SetTextColor(255, 255, 255);
            $x = $x0;
            $pdf->SetXY($x, $y0);
            $pdf->Cell($denomW, 7, $this->_iso('TOTAL GENERAL'), 'LR', 0, 'R', true);
            $x += $denomW;
            if ($isMensual) {
                for ($i = 0; $i < $nMeses; $i++) {
                    $pdf->SetXY($x, $y0);
                    $pdf->Cell($mesW, 7, '', 'LR', 0, 'R', true);
                    $x += $mesW;
                }
            }
            $pdf->SetXY($x, $y0);
            $pdf->Cell($totalW, 7, $this->_iso($this->_fmtMoney($totalGeneral)), 'LR', 1, 'R', true);

            $descarga = 'Reporte_' . trim(str_replace(' ', '_', $titulo)) . '_' . $mesAnio . '.pdf';
            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('D', $descarga);
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarMensualPdf] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Convierte UTF-8 a ISO-8859-1 (requerido por las fuentes core de FPDF).
     *
     * @param string|null $s Texto en UTF-8.
     * @return string Texto en ISO-8859-1.
     */
    private function _iso(?string $s): string
    {
        return mb_convert_encoding($s ?? '', 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Formatea un importe monetario.
     *
     * @param float $n Importe.
     * @return string Importe formateado ($ 1,234.56).
     */
    private function _fmtMoney($n): string
    {
        return '$ ' . number_format((float)$n, 2, '.', ',');
    }

    /**
     * Dibuja el encabezado (fila de títulos) de la tabla del reporte mensual.
     *
     * @param PDF   $pdf   Instancia del PDF.
     * @param float $x0    Posición X izquierda de la tabla.
     * @param float $denomW Ancho columna denominación.
     * @param float $mesW   Ancho columna mes.
     * @param float $totalW Ancho columna total.
     * @param array $meses  Meses seleccionados [{id, nombre}].
     * @param bool  $isMensual Si hay más de un mes.
     * @param int   $nMeses Número de meses.
     */
    private function _pdfMensualHeaderTabla(PDF $pdf, float $x0, float $denomW, float $mesW, float $totalW, array $meses, bool $isMensual, int $nMeses): void
    {
        $h = 6.5;
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(31, 41, 55);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(26, 26, 26);

        $x = $x0;
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($denomW, $h, $this->_iso('Departamento / Partida'), 'LR', 0, 'L', true);
        $x += $denomW;
        if ($isMensual) {
            foreach ($meses as $m) {
                $pdf->SetXY($x, $pdf->GetY());
                $pdf->Cell($mesW, $h, $this->_iso($m['nombre'] ?? ''), 'LR', 0, 'C', true);
                $x += $mesW;
            }
        }
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($totalW, $h, $this->_iso($isMensual ? 'Total' : 'Importe'), 'LR', 1, 'C', true);
    }

    /**
     * Renderiza recursivamente la jerarquía de nodos del reporte mensual.
     *
     * @param PDF   $pdf  Instancia del PDF.
     * @param array $nodo Nodo (RazonSocial / Segmento / Complejo / Unidad / Partida).
     * @param int   $level Profundidad (0=Razón Social).
     * @param array $ctx  Contexto de layout.
     */
    private function _pdfRenderNodo(PDF $pdf, $nodo, int $level, array $ctx): void
    {
        $h = $ctx['h'];
        $x0 = $ctx['x0'];
        $denomW = $ctx['denomW'];
        $mesW = $ctx['mesW'];
        $totalW = $ctx['totalW'];
        $nMeses = $ctx['nMeses'];
        $meses = $ctx['meses'];
        $isMensual = $ctx['isMensual'];
        $campo = $ctx['campo'];

        $label = $nodo['etiqueta'] ?? ($nodo['nombre'] ?? ($nodo['Nombre'] ?? ''));
        $label = str_repeat(' ', $level * 3) . $label;

        $totales = isset($nodo['totales']) ? $nodo['totales'] : $nodo;
        $totalVal = (float)($totales[$campo] ?? 0);
        $mesVals = $totales['importesPorMes'] ?? [];

        // Saltos de página
        if ($pdf->GetY() + $h > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage();
            $this->_pdfMensualHeaderTabla($pdf, $x0, $denomW, $mesW, $totalW, $meses, $isMensual, $nMeses);
        }

        // Estilos por nivel
        $niveles = [
            0 => ['fill' => [229, 231, 235], 'txt' => [30, 30, 30], 'style' => 'B'],
            1 => ['fill' => [219, 234, 252], 'txt' => [30, 58, 138], 'style' => 'B'],
            2 => ['fill' => null,           'txt' => [90, 90, 90],   'style' => 'I'],
            3 => ['fill' => [243, 244, 246], 'txt' => [30, 30, 30],  'style' => 'B'],
        ];
        $isIndirecto = !empty($nodo['es_manual']) && in_array($nodo['es_manual'], [1, true, 't'], true);
        if ($level === 4) {
            $estilo = [
                'fill' => $isIndirecto ? [254, 243, 199] : null,
                'txt'  => [107, 114, 128],
                'style' => '',
            ];
        } else {
            $estilo = $niveles[$level] ?? end($niveles);
        }

        $pdf->SetFont('Arial', $estilo['style'], 8);
        $pdf->SetTextColor($estilo['txt'][0], $estilo['txt'][1], $estilo['txt'][2]);
        $pdf->SetDrawColor(229, 231, 235);
        $fill = $estilo['fill'] !== null;
        if ($fill) {
            $pdf->SetFillColor($estilo['fill'][0], $estilo['fill'][1], $estilo['fill'][2]);
        }

        // Celda denominación
        $pdf->Cell($denomW, $h, $this->_iso($label), 'LR', 0, 'L', $fill);
        // Celdas de meses
        if ($isMensual) {
            foreach ($meses as $m) {
                $val = isset($mesVals[$m['id']]) ? (float)$mesVals[$m['id']] : 0;
                $pdf->Cell($mesW, $h, $this->_iso($this->_fmtMoney($val)), 'LR', 0, 'R', $fill);
            }
        }
        // Celda total
        $pdf->Cell($totalW, $h, $this->_iso($this->_fmtMoney($totalVal)), 'LR', 1, 'R', $fill);

        // Hijos (segmentos / complejos / departamentos / detalles)
        foreach (['segmentos', 'complejos', 'departamentos', 'detalles'] as $ck) {
            if (isset($nodo[$ck]) && is_array($nodo[$ck])) {
                foreach ($nodo[$ck] as $child) {
                    $this->_pdfRenderNodo($pdf, $child, $level + 1, $ctx);
                }
            }
        }
    }

    /**
     * EXPORTAR PRESUPUESTO vs EJECUTADO MENSUAL EN PDF (pantalla 'presupuesto').
     * Reproduce la tabla fija del Excel (Asignado, Comprometido, Pagado,
     * Compras del mes, Disponible, [Excedido], % Ejec.) con la jerarquía
     * RS -> Segmento -> Complejo -> Unidad -> Partida.
     */
    public function exportarPresupuestoVsEjecutadoPdf()
    {
        try {
            $json  = $this->request->getJSON(true);
            $datos = $json['datos'] ?? [];
            $hayExcedidos = !empty($json['hayExcedidos']);
            $mesesSeleccionados = $json['mesesSeleccionados'] ?? [];
            $titulo    = $json['titulo'] ?? 'Presupuesto vs Ejecutado';
            $mesAnio   = $json['mesAnio'] ?? (date('Y') . '-' . date('n'));
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el PDF');
            }

            $pdf = new PDF('L', 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->SetAutoPageBreak(false);
            $pdf->setHeaderTitle('REPORTE ' . mb_strtoupper($titulo));
            $pdf->AddPage();

            // --- Bloque informativo ---
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(18, 18, 18);
            $pdf->Cell(0, 7, $this->_iso('REPORTE ' . strtoupper($titulo)), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            $mesesStr = count($mesesSeleccionados)
                ? implode(', ', array_column($mesesSeleccionados, 'nombre'))
                : '-';
            $pdf->Cell(0, 4, $this->_iso('Empresa: ' . $nombreEmpresa), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Periodo: ' . $mesAnio . '   Mes(es): ' . $mesesStr), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'L');
            $pdf->Ln(2);

            // --- Anchos de columnas ---
            $contentW = 259.4;                 // Letter horizontal menos márgenes 10/10
            $pctW     = 18;
            $moneyW   = 26;
            $cols     = ['Asignado', 'Comprometido', 'Pagado', 'Compras del mes', 'Disponible'];
            if ($hayExcedidos) {
                $cols[] = 'Excedido';
            }
            $nMoney   = count($cols);
            $denomW   = $contentW - ($nMoney * $moneyW) - $pctW;
            if ($denomW < 64) {
                $denomW  = 64;
                $moneyW  = ($contentW - $denomW - $pctW) / $nMoney;
                if ($moneyW < 20) {
                    $moneyW = 20;
                }
            }

            $x0 = $pdf->GetX();
            $h  = 6.2;

            $this->_pdfVsHeaderTabla($pdf, $x0, $denomW, $moneyW, $pctW, $cols);

            $ctx = [
                'x0'           => $x0, 'h' => $h,
                'denomW'       => $denomW, 'moneyW' => $moneyW, 'pctW' => $pctW,
                'cols'         => $cols, 'hayExcedidos' => $hayExcedidos,
            ];

            $tg = ['asignado' => 0.0, 'comprometido' => 0.0, 'ejecutado' => 0.0, 'excedido' => 0.0];
            foreach ($datos as $rs) {
                $t = $rs['totales'] ?? $rs;
                $tg['asignado']     += (float)($t['asignado'] ?? 0);
                $tg['comprometido'] += (float)($t['comprometido'] ?? 0);
                $tg['ejecutado']    += (float)($t['ejecutado'] ?? 0);
                $tg['excedido']     += (float)($t['excedido'] ?? 0);
                $this->_pdfVsRenderNodo($pdf, $rs, 0, $ctx);
            }

            $this->_pdfVsFooterTotal($pdf, $x0, $denomW, $moneyW, $pctW, $cols, $hayExcedidos, $tg);

            $descarga = 'Reporte_' . trim(str_replace(' ', '_', $titulo)) . '_' . $mesAnio . '.pdf';
            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('D', $descarga);
            exit;

} catch (\Throwable $e) {
            log_message('error', '[exportarPresupuestoVsEjecutadoPdf] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Exporta el reporte "Compras" (Pagado/Por Pagar Autorizado) a PDF.
     * Recibe los datos filtrados del frontend (mismo patrón que exportarMovimientosExcel).
     */
    public function exportarComprasPdf()
    {
        try {
            $json   = $this->request->getJSON(true);
            $datos  = $json['datos'] ?? [];
            $filtros = $json['filtros'] ?? [];
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';
            $fechaHoy = date('d/m/Y H:i:s');

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el PDF');
            }

            $pdf = new PDF('L', 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->SetAutoPageBreak(false);
            $pdf->setHeaderTitle('REPORTE PAGADO/POR PAGAR AUTORIZADO');
            $pdf->AddPage();

            // --- Bloque informativo (título + metadata) ---
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(18, 18, 18);
            $pdf->Cell(0, 7, $this->_iso('REPORTE PAGADO / POR PAGAR AUTORIZADO'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 4, $this->_iso('Empresa: ' . $nombreEmpresa), 0, 1, 'L');
            
            $filtrosStr = [];
            if (!empty($filtros['fecha'])) {
                $filtrosStr[] = 'Fecha: ' . $filtros['fecha'];
            }
            if (!empty($filtros['estado']) && $filtros['estado'] !== 'Todos') {
                $filtrosStr[] = 'Estado: ' . $filtros['estado'];
            }
            if (!empty($filtros['departamentos']) && $filtros['departamentos'] !== 'Todos') {
                $filtrosStr[] = 'Depto: ' . $filtros['departamentos'];
            }
            if (!empty($filtros['razonesSociales']) && $filtros['razonesSociales'] !== 'Todas') {
                $filtrosStr[] = 'Razón Social: ' . $filtros['razonesSociales'];
            }
            if (!empty($filtros['proveedores']) && $filtros['proveedores'] !== 'Todos') {
                $filtrosStr[] = 'Proveedores: ' . $filtros['proveedores'];
            }
            if (!empty($filtros['metodoPago']) && $filtros['metodoPago'] !== 'Todos') {
                $filtrosStr[] = 'Método: ' . $filtros['metodoPago'];
            }
            $pdf->Cell(0, 4, $this->_iso('Filtros: ' . (empty($filtrosStr) ? 'Ninguno' : implode(' | ', $filtrosStr))), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Generado: ' . $fechaHoy), 0, 1, 'L');
            $pdf->Ln(2);

            // --- Columnas ---
            $headers = ['Folio', 'Fecha', 'Departamento', 'Razón Social', 'Proveedor', 'Método Pago', 'Estado', 'Importe Total'];
            $colW    = [22, 22, 38, 40, 42, 22, 20, 24];
            $lineH   = 5;

            $pdf->SetWidths($colW);

            // --- Encabezado de tabla (estilo oscuro) ---
            $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);

            // --- Filas de datos ---
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(229, 231, 235);
            $totalGeneral = 0;

            foreach ($datos as $item) {
                $proveedorNombre = $item['proveedor']['RazonSocial'] ?? ($item['ProveedorFiltro'] ?? 'N/A');
                $metodoPago = $item['MetodoPago'] ?? 0;
                $metodoTexto = $metodoPago == 0 ? 'Efectivo' : ($metodoPago == 1 ? 'Crédito' : 'En Espera');
                $importe = (float)($item['cotizacion']['Total'] ?? $item['MontoTotal'] ?? 0);
                $totalGeneral += $importe;

                $cells = [
                    $item['No_Folio'] ?? 'N/A',
                    $item['Fecha'] ?? 'N/A',
                    $item['DepartamentoNombre'] ?? 'N/A',
                    $item['Complejo'] ?? 'N/A',
                    $proveedorNombre,
                    $metodoTexto,
                    $item['EstadoOrden'] ?? 'N/A',
                    '$' . number_format($importe, 2),
                ];

                $lineCounts = [];
                foreach ($cells as $i => $c) {
                    $lineCounts[$i] = $pdf->NbLines($colW[$i], $this->_iso($c));
                }
                $h = max($lineCounts) * $lineH;

                if ($pdf->GetY() + $h > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage();
                    $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetDrawColor(229, 231, 235);
                }

                $aligns = [];
                foreach ($cells as $i => $v) {
                    $aligns[$i] = ($i === 0 || $i === 7) ? 'R' : 'L';
                }
                $pdf->SetX(8);
                $pdf->drawTableRow($colW, array_map(fn($v) => $this->_iso((string)$v), $cells), $aligns, $lineH, false);
            }

            // --- Footer: Total General (estilo oscuro) ---
            $this->_dibujarTotalGeneral($pdf, $colW, 'TOTAL: $' . number_format($totalGeneral, 2));

            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('D', 'reporte_compras_' . date('Ymd') . '.pdf');
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarComprasPdf] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Dibuja la fila de encabezado con estilo oscuro (fondo RGB 31,41,55).
     */
    private function _dibujarCabeceraOscura(PDF $pdf, array $colW, array $headers, float $lineH): void
    {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(31, 41, 55);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(26, 26, 26);

        $pdf->SetX(8);
        $pdf->drawTableRow($colW, array_map(fn($h) => $this->_iso($h), $headers), array_fill(0, count($headers), 'C'), $lineH, true);
    }

    /**
     * Dibuja la fila de total general con estilo oscuro.
     */
    private function _dibujarTotalGeneral(PDF $pdf, array $colW, string $label): void
    {
        $totalW = array_sum($colW);

        if ($pdf->GetY() + 7 > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage();
        }

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(31, 41, 55);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(26, 26, 26);
        $pdf->SetX(8);
        $pdf->Cell($totalW, 7, $this->_iso($label), 1, 0, 'R', true);
    }

    /**
     * Devuelve el estilo visual de un nodo seg�n su nivel jer�rquico.
     */
    private function _pdfEstiloNodo(int $level, $nodo): array
    {
        $niveles = [
            0 => ['fill' => [229, 231, 235], 'txt' => [30, 30, 30], 'style' => 'B'],
            1 => ['fill' => [219, 234, 252], 'txt' => [30, 58, 138], 'style' => 'B'],
            2 => ['fill' => null,           'txt' => [90, 90, 90],   'style' => 'I'],
            3 => ['fill' => [243, 244, 246], 'txt' => [30, 30, 30],  'style' => 'B'],
        ];
        $isIndirecto = !empty($nodo['es_manual']) && in_array($nodo['es_manual'], [1, true, 't'], true);
        if ($level === 4) {
            return [
                'fill'  => $isIndirecto ? [254, 243, 199] : null,
                'txt'   => [107, 114, 128],
                'style' => '',
            ];
        }
        return $niveles[$level] ?? end($niveles);
    }

    /**
     * Dibuja la cabecera (fila de títulos) de la tabla del reporte vs ejecutado.
     */
    private function _pdfVsHeaderTabla(PDF $pdf, float $x0, float $denomW, float $moneyW, float $pctW, array $cols): void
    {
        $h = 6.5;
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(31, 41, 55);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(26, 26, 26);

        $x = $x0;
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($denomW, $h, $this->_iso('Departamento / Partida'), 'LR', 0, 'L', true);
        $x += $denomW;
        foreach ($cols as $c) {
            $pdf->SetXY($x, $pdf->GetY());
            $pdf->Cell($moneyW, $h, $this->_iso($c), 'LR', 0, 'C', true);
            $x += $moneyW;
        }
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($pctW, $h, $this->_iso('% Ejec.'), 'LR', 1, 'C', true);
    }

    /**
     * Renderiza recursivamente la jerarquía del reporte vs ejecutado.
     */
    private function _pdfVsRenderNodo(PDF $pdf, $nodo, int $level, array $ctx): void
    {
        $h          = $ctx['h'];
        $x0         = $ctx['x0'];
        $denomW     = $ctx['denomW'];
        $moneyW     = $ctx['moneyW'];
        $pctW       = $ctx['pctW'];
        $cols       = $ctx['cols'];
        $hayExcedidos = $ctx['hayExcedidos'];

        $label = $nodo['etiqueta'] ?? ($nodo['nombre'] ?? ($nodo['Nombre'] ?? ''));
        $label = str_repeat(' ', $level * 3) . $label;

        $totales = isset($nodo['totales']) ? $nodo['totales'] : $nodo;
        $asig   = (float)($totales['asignado'] ?? 0);
        $comp   = (float)($totales['comprometido'] ?? 0);
        $ejec   = (float)($totales['ejecutado'] ?? 0);
        $disp   = (float)($totales['disponible'] ?? 0);
        $exced  = (float)($totales['excedido'] ?? 0);
        $perc   = (float)($totales['porcentaje'] ?? 0);
        $compras = $comp + $ejec;

        // Salto de página
        if ($pdf->GetY() + $h > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage();
            $this->_pdfVsHeaderTabla($pdf, $x0, $denomW, $moneyW, $pctW, $cols);
        }

        $est = $this->_pdfEstiloNodo($level, $nodo);
        $pdf->SetFont('Arial', $est['style'], 8);
        $pdf->SetTextColor($est['txt'][0], $est['txt'][1], $est['txt'][2]);
        $pdf->SetDrawColor(229, 231, 235);
        $fill = $est['fill'] !== null;
        if ($fill) {
            $pdf->SetFillColor($est['fill'][0], $est['fill'][1], $est['fill'][2]);
        }

        $pdf->Cell($denomW, $h, $this->_iso($label), 'LR', 0, 'L', $fill);
        $pdf->Cell($moneyW, $h, $this->_iso($this->_fmtMoney($asig)), 'LR', 0, 'R', $fill);
        $pdf->Cell($moneyW, $h, $this->_iso($this->_fmtMoney($comp)), 'LR', 0, 'R', $fill);
        $pdf->Cell($moneyW, $h, $this->_iso($this->_fmtMoney($ejec)), 'LR', 0, 'R', $fill);
        $pdf->Cell($moneyW, $h, $this->_iso($this->_fmtMoney($compras)), 'LR', 0, 'R', $fill);
        $pdf->Cell($moneyW, $h, $this->_iso($this->_fmtMoney($disp)), 'LR', 0, 'R', $fill);
        if ($hayExcedidos) {
            $pdf->Cell($moneyW, $h, $this->_iso($this->_fmtMoney($exced)), 'LR', 0, 'R', $fill);
        }
        $pdf->Cell($pctW, $h, $this->_iso(number_format($perc, 1) . '%'), 'LR', 1, 'C', $fill);

        foreach (['segmentos', 'complejos', 'departamentos', 'detalles'] as $ck) {
            if (isset($nodo[$ck]) && is_array($nodo[$ck])) {
                foreach ($nodo[$ck] as $child) {
                    $this->_pdfVsRenderNodo($pdf, $child, $level + 1, $ctx);
                }
            }
        }
    }

    /**
     * Dibuja la fila de totales generales del reporte vs ejecutado.
     */
    private function _pdfVsFooterTotal(PDF $pdf, float $x0, float $denomW, float $moneyW, float $pctW, array $cols, bool $hayExcedidos, array $tg): void
    {
        $y0 = $pdf->GetY();
        $pdf->SetDrawColor(26, 26, 26);
        $lineEnd = $x0 + $denomW + (count($cols) * $moneyW) + $pctW;
        $pdf->Line($x0, $y0, $lineEnd, $y0);
        $y0 += 0.5;

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(31, 41, 55);
        $pdf->SetTextColor(255, 255, 255);
        $x = $x0;
        $pdf->SetXY($x, $y0);
        $pdf->Cell($denomW, 7, $this->_iso('TOTAL GENERAL'), 'LR', 0, 'R', true);
        $x += $denomW;

        $compras = $tg['comprometido'] + $tg['ejecutado'];
        $disp    = $tg['asignado'] - $compras;
        $pdf->Cell($moneyW, 7, $this->_iso($this->_fmtMoney($tg['asignado'])), 'LR', 0, 'R', true);
        $x += $moneyW;
        $pdf->Cell($moneyW, 7, $this->_iso($this->_fmtMoney($tg['comprometido'])), 'LR', 0, 'R', true);
        $x += $moneyW;
        $pdf->Cell($moneyW, 7, $this->_iso($this->_fmtMoney($tg['ejecutado'])), 'LR', 0, 'R', true);
        $x += $moneyW;
        $pdf->Cell($moneyW, 7, $this->_iso($this->_fmtMoney($compras)), 'LR', 0, 'R', true);
        $x += $moneyW;
        $pdf->Cell($moneyW, 7, $this->_iso($this->_fmtMoney($disp)), 'LR', 0, 'R', true);
        $x += $moneyW;
        if ($hayExcedidos) {
            $pdf->Cell($moneyW, 7, $this->_iso($this->_fmtMoney($tg['excedido'])), 'LR', 0, 'R', true);
            $x += $moneyW;
        }
        $perc = $tg['asignado'] > 0 ? round(($compras / $tg['asignado']) * 100, 1) : 0;
        $pdf->Cell($pctW, 7, $this->_iso(number_format($perc, 1) . '%'), 'LR', 1, 'C', true);
    }

    // ============================================================
    // REPORTE: SOLICITUDES SIN COTIZAR
    // ============================================================

    /**
     * Obtiene las solicitudes que aún no tienen cotización registrada
     * (sin registro en la tabla Cotizacion) y cuyo estado es anterior
     * a "Cotizando" (Aprobacion Pendiente / En espera).
     */
    public function getSolicitudesSinCotizar()
    {
        $solicitudModel = new SolicitudModel();
        $productoModel  = new SolicitudProductModel();
        $servicioModel  = new SolicitudServiciosModel();
        $bitacoraModel  = new BitacoraModel();

        $solicitudes = $solicitudModel
            ->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as ComplejoNombre, Razon_Social.Nombre as RazonSocialNombre, Usuarios.Nombre as UsuarioNombre')
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->where('Cotizacion.ID_Cotizacion IS NULL')
            ->whereIn('Solicitud.Estado', [
                Status::Aprobacion_pendiente,
                Status::En_espera,
                Status::Rechazada,
                Status::Dept_Rechazada,
                'Cancelada',
            ])
            ->orderBy('Solicitud.ID_Solicitud', 'DESC')
            ->findAll();

        $ids = array_column($solicitudes, 'ID_Solicitud');
        if (empty($ids)) {
            return $this->respond(['datos' => [], 'totales' => ['cantidad' => 0, 'costo_total' => 0]]);
        }

        // --- Costo total estimado por solicitud (sin cotización) ---
        $costos = array_fill_keys($ids, 0.0);

        $rows = $productoModel->select('ID_Solicitud, Cantidad, Importe')->whereIn('ID_Solicitud', $ids)->findAll();
        foreach ($rows as $r) {
            $costos[$r['ID_Solicitud']] += (float) $r['Cantidad'] * (float) $r['Importe'];
        }

        $rows = $servicioModel->select('ID_Solicitud, Importe')->whereIn('ID_Solicitud', $ids)->findAll();
        foreach ($rows as $r) {
            $costos[$r['ID_Solicitud']] += (float) $r['Importe'];
        }

        // --- Fecha de aprobación del jefe (bitácora) ---
        // La transición "Aprobacion Pendiente" -> "En espera" deja un ACTUALIZAR
        // con valores_antiguos que contiene el estado previo.
        $aprobaciones = [];
        $logs = $bitacoraModel->select('solicitud_id, fecha_hora, valores_antiguos')
            ->where('tipo_accion', 'ACTUALIZAR')
            ->whereIn('solicitud_id', $ids)
            ->findAll();
        foreach ($logs as $log) {
            if (stripos((string) ($log['valores_antiguos'] ?? ''), 'Aprobacion Pendiente') === false) {
                continue;
            }
            $sid   = (int) $log['solicitud_id'];
            $fecha = strtotime((string) ($log['fecha_hora'] ?? ''));
            if ($fecha && (!isset($aprobaciones[$sid]) || $fecha < $aprobaciones[$sid])) {
                $aprobaciones[$sid] = $fecha;
            }
        }

        $datos = [];
        $totalGeneral = 0.0;
        foreach ($solicitudes as $sol) {
            $montoBase = $costos[$sol['ID_Solicitud']] ?? 0;
            $ivaVal    = $sol['IVA'] ?? false;
            $ivaOn     = ($ivaVal === 't' || $ivaVal === '1' || $ivaVal === 1 || $ivaVal === true);
            $costo     = round($montoBase * ($ivaOn ? 1.16 : 1.0), 2);
            $totalGeneral += $costo;

            $tipo = (int) ($sol['Tipo'] ?? SolicitudTipo::Cotizacion);
            $datos[] = [
                'ID_Solicitud'        => (int) $sol['ID_Solicitud'],
                'No_Folio'            => $sol['No_Folio'] ?? 'N/A',
                'RazonSocial'         => $sol['RazonSocialNombre'] ?? 'N/A',
                'Complejo'            => $sol['ComplejoNombre'] ?? 'N/A',
                'Departamento'        => $sol['DepartamentoNombre'] ?? 'N/A',
                'Usuario'             => $sol['UsuarioNombre'] ?? 'N/A',
                'FechaSolicitud'      => $sol['Fecha'] ?? null,
                'FechaAprobacionJefe' => isset($aprobaciones[$sol['ID_Solicitud']])
                    ? date('Y-m-d H:i:s', $aprobaciones[$sol['ID_Solicitud']]) : null,
                'Estado'              => $sol['Estado'] ?? 'N/A',
                'Tipo'                => in_array($tipo, [SolicitudTipo::NoCotizacion, SolicitudTipo::Cotizacion], true)
                    ? 'Producto' : 'Servicio',
                'CostoTotal'          => $costo,
            ];
        }

        return $this->respond([
            'datos'   => $datos,
            'totales' => [
                'cantidad'    => count($datos),
                'costo_total' => round($totalGeneral, 2),
            ],
        ]);
    }

    /**
     * Exporta a Excel las solicitudes sin cotizar.
     */
    public function exportarSolicitudesSinCotizarJson()
    {
        try {
            $json = $this->request->getJSON(true);
            $datos = $json['datos'] ?? [];
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';
            $fechaHoy = date('d/m/Y H:i:s');

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el Excel');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Solicitudes Sin Cotizar');

            $sheet->setCellValue('A1', $nombreEmpresa);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->setCellValue('A2', 'REPORTE DE SOLICITUDES SIN COTIZAR');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->setCellValue('A3', 'Fecha de creación: ' . $fechaHoy);
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

            $headers = ['Folio', 'Razón Social', 'Complejo', 'Departamento', 'Usuario Solicitante', 'Fecha Solicitud', 'Fecha Aprob. Jefe', 'Estado', 'Tipo', 'Costo Total'];

            $cols = [];
            for ($i = 0; $i < count($headers); $i++) {
                $cols[] = $this->getColumnLetter($i);
            }

            $headerStyle = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
            ];
            $borderStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ];

            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i] . '5', $h);
                $sheet->getStyle($cols[$i] . '5')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = 6;
            $totalGeneral = 0;
            foreach ($datos as $v) {
                $total = (float) ($v['CostoTotal'] ?? 0);
                $totalGeneral += $total;

                $sheet->setCellValue($cols[0] . $row, $v['No_Folio'] ?? '');
                $sheet->setCellValue($cols[1] . $row, $v['RazonSocial'] ?? '');
                $sheet->setCellValue($cols[2] . $row, $v['Complejo'] ?? '');
                $sheet->setCellValue($cols[3] . $row, $v['Departamento'] ?? '');
                $sheet->setCellValue($cols[4] . $row, $v['Usuario'] ?? '');
                $sheet->setCellValue($cols[5] . $row, $v['FechaSolicitud'] ?? '');
                $sheet->setCellValue($cols[6] . $row, $v['FechaAprobacionJefe'] ?? '');
                $sheet->setCellValue($cols[7] . $row, $v['Estado'] ?? '');
                $sheet->setCellValue($cols[8] . $row, $v['Tipo'] ?? '');
                $sheet->setCellValue($cols[9] . $row, $total);
                $sheet->getStyle($cols[9] . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle($cols[0] . $row . ':' . $cols[9] . $row)->applyFromArray($borderStyle);
                $row++;
            }

            $sheet->setCellValue($cols[8] . ($row + 1), 'TOTAL GENERAL');
            $sheet->getStyle($cols[8] . ($row + 1))->getFont()->setBold(true);
            $sheet->getStyle($cols[8] . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue($cols[9] . ($row + 1), $totalGeneral);
            $sheet->getStyle($cols[9] . ($row + 1))->getFont()->setBold(true);
            $sheet->getStyle($cols[9] . ($row + 1))->getNumberFormat()->setFormatCode('$#,##0.00');

            $writer = new Xlsx($spreadsheet);
            $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="solicitudes_sin_cotizar_' . date('Ymd') . '.xlsx"');
            $writer->save('php://output');
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarSolicitudesSinCotizarJson] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Exporta a PDF las solicitudes sin cotizar.
     */
    public function exportarSolicitudesSinCotizarPdf()
    {
        try {
            $json   = $this->request->getJSON(true);
            $datos  = $json['datos'] ?? [];
            $filtros = $json['filtros'] ?? [];
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';
            $fechaHoy = date('d/m/Y H:i:s');

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el PDF');
            }

            $pdf = new PDF('L', 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->SetAutoPageBreak(false);
            $pdf->setHeaderTitle('REPORTE SOLICITUDES SIN COTIZAR');
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(18, 18, 18);
            $pdf->Cell(0, 7, $this->_iso('REPORTE DE SOLICITUDES SIN COTIZAR'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 4, $this->_iso('Empresa: ' . $nombreEmpresa), 0, 1, 'L');

            $filtrosStr = [];
            if (!empty($filtros['desde']) || !empty($filtros['hasta'])) {
                $filtrosStr[] = 'Fecha: ' . ($filtros['desde'] ?? '...') . ' a ' . ($filtros['hasta'] ?? '...');
            }
            if (!empty($filtros['estados'])) {
                $filtrosStr[] = 'Estados: ' . (is_array($filtros['estados']) ? implode(', ', $filtros['estados']) : $filtros['estados']);
            }
            if (!empty($filtros['razonesSociales'])) {
                $filtrosStr[] = 'Razón Social: ' . (is_array($filtros['razonesSociales']) ? implode(', ', $filtros['razonesSociales']) : $filtros['razonesSociales']);
            }
            if (!empty($filtros['complejos'])) {
                $filtrosStr[] = 'Complejo: ' . (is_array($filtros['complejos']) ? implode(', ', $filtros['complejos']) : $filtros['complejos']);
            }
            if (!empty($filtros['departamentos'])) {
                $filtrosStr[] = 'Departamentos: ' . (is_array($filtros['departamentos']) ? implode(', ', $filtros['departamentos']) : $filtros['departamentos']);
            }
            if (!empty($filtros['tipos'])) {
                $filtrosStr[] = 'Tipo: ' . (is_array($filtros['tipos']) ? implode(', ', $filtros['tipos']) : $filtros['tipos']);
            }
            $pdf->Cell(0, 4, $this->_iso('Filtros: ' . (empty($filtrosStr) ? 'Ninguno' : implode(' | ', $filtrosStr))), 0, 1, 'L');
            $pdf->Cell(0, 4, $this->_iso('Generado: ' . $fechaHoy), 0, 1, 'L');
            $pdf->Ln(2);

            $headers = ['Folio', 'Razón Social', 'Complejo', 'Departamento', 'Usuario', 'Fecha Solic.', 'F. Aprob. Jefe', 'Estado', 'Tipo', 'Costo Total'];
            $colW    = [22, 34, 26, 30, 30, 22, 22, 22, 16, 26];
            $lineH   = 5;

            $pdf->SetWidths($colW);
            $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(229, 231, 235);
            $totalGeneral = 0;

            foreach ($datos as $item) {
                $importe = (float) ($item['CostoTotal'] ?? 0);
                $totalGeneral += $importe;

                $cells = [
                    $item['No_Folio'] ?? 'N/A',
                    $item['RazonSocial'] ?? 'N/A',
                    $item['Complejo'] ?? 'N/A',
                    $item['Departamento'] ?? 'N/A',
                    $item['Usuario'] ?? 'N/A',
                    $item['FechaSolicitud'] ?? 'N/A',
                    $item['FechaAprobacionJefe'] ?? 'N/A',
                    $item['Estado'] ?? 'N/A',
                    $item['Tipo'] ?? 'N/A',
                    '$' . number_format($importe, 2),
                ];

                $lineCounts = [];
                foreach ($cells as $i => $c) {
                    $lineCounts[$i] = $pdf->NbLines($colW[$i], $this->_iso($c));
                }
                $h = max($lineCounts) * $lineH;

                if ($pdf->GetY() + $h > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage();
                    $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetDrawColor(229, 231, 235);
                }

                $aligns = [];
                foreach ($cells as $i => $v) {
                    $aligns[$i] = ($i === 9) ? 'R' : 'L';
                }
                $pdf->SetX(8);
                $pdf->drawTableRow($colW, array_map(fn($v) => $this->_iso((string) $v), $cells), $aligns, $lineH, false);
            }

            $this->_dibujarTotalGeneral($pdf, $colW, 'TOTAL: $' . number_format($totalGeneral, 2));

            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('D', 'solicitudes_sin_cotizar_' . date('Ymd') . '.pdf');
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarSolicitudesSinCotizarPdf] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Reporte de pagos pendientes.
     *
     * Incluye requisiciones en 'En revision' / 'Aprobada' sin OC, y requisiciones
     * con OC en 'Espera_Programacion', 'Programada' o 'Por Pagar'.
     * Calcula por fila la FechaMasReciente (máximo de todas las fechas de la
     * requisición/OC) que se usa como fecha de corte al exportar.
     */
    public function getPagosPendientesReporte()
    {
        $solicitudModel = new SolicitudModel();
        $productoModel  = new SolicitudProductModel();
        $servicioModel  = new SolicitudServiciosModel();
        $bitacoraModel  = new BitacoraModel();

        $solicitudes = $solicitudModel
            ->select("Solicitud.*, Departamentos.Nombre as DepartamentoNombre, Places.Nombre_Corto as ComplejoNombre, Razon_Social.Nombre as RazonSocialNombre, Usuarios.Nombre as UsuarioNombre, Cotizacion.ID_Cotizacion, Cotizacion.Total as CotizacionTotal, Cotizacion.ID_Proveedor as CotizacionProveedor, OrdenCompra.ID_OrdenCompra, OrdenCompra.ID_Proveedor as OCProveedor, OrdenCompra.Estado as OCEstado, OrdenCompra.Fecha as OCFecha, OrdenCompra.FechaPagoRealizado, OrdenCompra.Fecha_Comprobante")
            ->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
            ->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
            ->join('Cotizacion', 'Cotizacion.ID_Solicitud = Solicitud.ID_Solicitud', 'left')
            ->join('OrdenCompra', 'OrdenCompra.ID_Cotizacion = Cotizacion.ID_Cotizacion', 'left')
            ->groupStart()
                ->groupStart()
                    ->whereIn('Solicitud.Estado', [Status::En_Revision, Status::Aprobada])
                    ->where('OrdenCompra.ID_OrdenCompra IS NULL')
                ->groupEnd()
                ->orGroupStart()
                    ->where('OrdenCompra.ID_OrdenCompra IS NOT NULL')
                    ->whereIn('OrdenCompra.Estado', [Status::Espera_Programacion, Status::Programada, Status::Por_Pagar])
                ->groupEnd()
            ->groupEnd()
            ->orderBy('Solicitud.Fecha', 'ASC')
            ->findAll();

        $ids = array_column($solicitudes, 'ID_Solicitud');
        if (empty($ids)) {
            return $this->respond([
                'datos'   => [],
                'totales' => ['cantidad' => 0, 'total_general' => 0, 'saldo_total' => 0],
            ]);
        }

        // --- Proveedores (crédito) ---
        // El proveedor se resuelve por prioridad: OC > Cotización > Solicitud.
        // Se consulta en un segundo paso para evitar COALESCE dentro del JOIN,
        // que el query builder de CI4 no protege correctamente en PostgreSQL.
        $proveedorIds = [];
        foreach ($solicitudes as $sol) {
            $pid = $sol['OCProveedor'] ?? $sol['CotizacionProveedor'] ?? $sol['ID_Proveedor'] ?? null;
            if ($pid !== null && $pid !== '') {
                $proveedorIds[(int) $pid] = true;
            }
        }
        $proveedores = [];
        if (!empty($proveedorIds)) {
            $proveedorModel = new ProveedorModel();
            $rows = $proveedorModel->select('ID_Proveedor, Monto_Credito, Dias_Credito')
                ->whereIn('ID_Proveedor', array_keys($proveedorIds))
                ->findAll();
            foreach ($rows as $r) {
                $proveedores[(int) $r['ID_Proveedor']] = $r;
            }
        }

        // --- Costo estimado por solicitud sin cotización (renglones) ---
        $costos = array_fill_keys($ids, 0.0);

        $rows = $productoModel->select('ID_Solicitud, Cantidad, Importe')->whereIn('ID_Solicitud', $ids)->findAll();
        foreach ($rows as $r) {
            $costos[$r['ID_Solicitud']] += (float) $r['Cantidad'] * (float) $r['Importe'];
        }

        $rows = $servicioModel->select('ID_Solicitud, Importe')->whereIn('ID_Solicitud', $ids)->findAll();
        foreach ($rows as $r) {
            $costos[$r['ID_Solicitud']] += (float) $r['Importe'];
        }

        // --- Fecha de aprobación del jefe (bitácora) ---
        $aprobaciones = [];
        $logs = $bitacoraModel->select('solicitud_id, fecha_hora, valores_antiguos')
            ->where('tipo_accion', 'ACTUALIZAR')
            ->whereIn('solicitud_id', $ids)
            ->findAll();
        foreach ($logs as $log) {
            if (stripos((string) ($log['valores_antiguos'] ?? ''), 'Aprobacion Pendiente') === false) {
                continue;
            }
            $sid   = (int) $log['solicitud_id'];
            $fecha = strtotime((string) ($log['fecha_hora'] ?? ''));
            if ($fecha && (!isset($aprobaciones[$sid]) || $fecha < $aprobaciones[$sid])) {
                $aprobaciones[$sid] = $fecha;
            }
        }

        $datos = [];
        $totalGeneral = 0.0;
        $saldoTotal   = 0.0;

        foreach ($solicitudes as $sol) {
            $tipo      = (int) ($sol['Tipo'] ?? SolicitudTipo::Cotizacion);
            $tipoTexto = in_array($tipo, [SolicitudTipo::NoCotizacion, SolicitudTipo::Cotizacion], true)
                ? 'Producto' : 'Servicio';

            // --- Total de la requisición ---
            $cotizaTotal = $sol['CotizacionTotal'] ?? null;
            if ($cotizaTotal !== null && $cotizaTotal !== '') {
                $total = round((float) $cotizaTotal, 2);
            } else {
                $montoBase = $costos[$sol['ID_Solicitud']] ?? 0;
                $ivaVal    = $sol['IVA'] ?? false;
                $ivaOn     = ($ivaVal === 't' || $ivaVal === '1' || $ivaVal === 1 || $ivaVal === true);
                $total     = round($montoBase * ($ivaOn ? 1.16 : 1.0), 2);
            }

            // --- Fechas normalizadas (Y-m-d) ---
            $fechas = [];
            $fechas['FechaSolicitud']     = ($f = $sol['Fecha'] ?? null) ? date('Y-m-d', strtotime($f)) : null;
            $fechas['FechaAprobacionJefe'] = isset($aprobaciones[$sol['ID_Solicitud']])
                ? date('Y-m-d', $aprobaciones[$sol['ID_Solicitud']]) : null;
            $fechas['FechaAprobacion']    = ($f = $sol['Fecha_Aprobacion'] ?? null) ? date('Y-m-d', strtotime($f)) : null;
            $fechas['FechaOC']            = ($f = $sol['OCFecha'] ?? null) ? date('Y-m-d', strtotime($f)) : null;
            $fechas['FechaPagoRealizado'] = ($f = $sol['FechaPagoRealizado'] ?? null) ? date('Y-m-d', strtotime($f)) : null;
            $fechas['FechaComprobante']   = ($f = $sol['Fecha_Comprobante'] ?? null) ? date('Y-m-d', strtotime($f)) : null;

            $fechaMasReciente = null;
            foreach ($fechas as $fd) {
                if ($fd !== null && ($fechaMasReciente === null || $fd > $fechaMasReciente)) {
                    $fechaMasReciente = $fd;
                }
            }

            // --- Forma de pago y saldo de crédito ---
            $metodo = (int) ($sol['MetodoPago'] ?? 9);
            $formaPago = match ($metodo) {
                0 => 'Contado',
                1 => 'Crédito',
                default => 'En Espera',
            };

            $proveedorId = $sol['OCProveedor'] ?? $sol['CotizacionProveedor'] ?? $sol['ID_Proveedor'] ?? null;
            $proveedor   = $proveedorId !== null ? ($proveedores[(int) $proveedorId] ?? null) : null;

            $montoCredito = null;
            $saldoCredito = null;
            if ($metodo === 1 && $proveedor !== null) {
                $montoCredito = ($proveedor['Monto_Credito'] ?? null) !== null && ($proveedor['Monto_Credito'] ?? '') !== ''
                    ? round((float) $proveedor['Monto_Credito'], 2) : null;
                if ($montoCredito !== null) {
                    $saldoCredito = round($montoCredito - $total, 2);
                    $saldoTotal   += $saldoCredito;
                }
            }

            $totalGeneral += $total;

            $datos[] = [
                'ID_Solicitud'        => (int) $sol['ID_Solicitud'],
                'No_Folio'            => $sol['No_Folio'] ?? 'N/A',
                'RazonSocial'         => $sol['RazonSocialNombre'] ?? 'N/A',
                'Complejo'            => $sol['ComplejoNombre'] ?? 'N/A',
                'Departamento'        => $sol['DepartamentoNombre'] ?? 'N/A',
                'Usuario'             => $sol['UsuarioNombre'] ?? 'N/A',
                'FechaSolicitud'      => $fechas['FechaSolicitud'],
                'FechaAprobacionJefe' => $fechas['FechaAprobacionJefe'],
                'FechaAprobacion'     => $fechas['FechaAprobacion'],
                'FechaOC'             => $fechas['FechaOC'],
                'FechaPagoRealizado'  => $fechas['FechaPagoRealizado'],
                'FechaComprobante'    => $fechas['FechaComprobante'],
                'FechaMasReciente'    => $fechaMasReciente,
                'Estado'              => $sol['Estado'] ?? 'N/A',
                'EstadoOC'            => $sol['OCEstado'] ?? null,
                'Tipo'                => $tipoTexto,
                'MetodoPago'          => $metodo,
                'FormaPago'           => $formaPago,
                'MontoCredito'        => $montoCredito,
                'TotalRequisicion'    => $total,
                'SaldoCredito'        => $saldoCredito,
            ];
        }

        return $this->respond([
            'datos'   => $datos,
            'totales' => [
                'cantidad'      => count($datos),
                'total_general' => round($totalGeneral, 2),
                'saldo_total'   => round($saldoTotal, 2),
            ],
        ]);
    }

    /**
     * Exporta a Excel el reporte de pagos pendientes.
     */
    public function exportarPagosPendientesJson()
    {
        try {
            $json   = $this->request->getJSON(true);
            $datos  = $json['datos'] ?? [];
            $fechaCorte = $json['fechaCorte'] ?? null;
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';
            $fechaHoy = date('d/m/Y H:i:s');

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el Excel');
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Pagos Pendientes');

            $sheet->setCellValue('A1', $nombreEmpresa);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->setCellValue('A2', 'REPORTE DE PAGOS PENDIENTES');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->setCellValue('A3', 'Fecha de creación: ' . $fechaHoy);
            $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);
            if ($fechaCorte) {
                $sheet->setCellValue('A4', 'Fecha de corte: ' . date('d/m/Y', strtotime($fechaCorte)));
                $sheet->getStyle('A4')->getFont()->setItalic(true)->setSize(10);
            }

            $headers = ['Folio', 'Razón Social', 'Complejo', 'Departamento', 'Usuario Solicitante', 'Fecha Solicitud', 'Fecha Aprob. Jefe', 'Fecha Aprob. Dirección', 'Fecha OC', 'Fecha Pago Realizado', 'Fecha Comprobante', 'Estado', 'Forma de Pago', 'Crédito Proveedor', 'Total Requisición', 'Saldo Crédito'];

            $cols = [];
            for ($i = 0; $i < count($headers); $i++) {
                $cols[] = $this->getColumnLetter($i);
            }

            $headerStyle = [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
            ];
            $borderStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ];

            $headerRow = $fechaCorte ? 6 : 5;
            foreach ($headers as $i => $h) {
                $sheet->setCellValue($cols[$i] . $headerRow, $h);
                $sheet->getStyle($cols[$i] . $headerRow)->applyFromArray($headerStyle);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = $headerRow + 1;
            $totalGeneral = 0;
            $saldoTotal   = 0;
            foreach ($datos as $v) {
                $total = (float) ($v['TotalRequisicion'] ?? 0);
                $saldo = (float) ($v['SaldoCredito'] ?? 0);
                $totalGeneral += $total;
                $saldoTotal   += $saldo;

                $sheet->setCellValue($cols[0] . $row, $v['No_Folio'] ?? '');
                $sheet->setCellValue($cols[1] . $row, $v['RazonSocial'] ?? '');
                $sheet->setCellValue($cols[2] . $row, $v['Complejo'] ?? '');
                $sheet->setCellValue($cols[3] . $row, $v['Departamento'] ?? '');
                $sheet->setCellValue($cols[4] . $row, $v['Usuario'] ?? '');
                $sheet->setCellValue($cols[5] . $row, $v['FechaSolicitud'] ?? '');
                $sheet->setCellValue($cols[6] . $row, $v['FechaAprobacionJefe'] ?? '');
                $sheet->setCellValue($cols[7] . $row, $v['FechaAprobacion'] ?? '');
                $sheet->setCellValue($cols[8] . $row, $v['FechaOC'] ?? '');
                $sheet->setCellValue($cols[9] . $row, $v['FechaPagoRealizado'] ?? '');
                $sheet->setCellValue($cols[10] . $row, $v['FechaComprobante'] ?? '');
                $sheet->setCellValue($cols[11] . $row, $v['Estado'] ?? '');
                $sheet->setCellValue($cols[12] . $row, $v['FormaPago'] ?? '');
                $sheet->setCellValue($cols[13] . $row, $v['MontoCredito'] ?? '');
                if ($v['MontoCredito'] !== null) {
                    $sheet->getStyle($cols[13] . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                }
                $sheet->setCellValue($cols[14] . $row, $total);
                $sheet->getStyle($cols[14] . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->setCellValue($cols[15] . $row, $v['SaldoCredito'] ?? '');
                if ($v['SaldoCredito'] !== null) {
                    $sheet->getStyle($cols[15] . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                }
                $sheet->getStyle($cols[0] . $row . ':' . $cols[15] . $row)->applyFromArray($borderStyle);
                $row++;
            }

            $sheet->setCellValue($cols[13] . ($row + 1), 'TOTAL GENERAL');
            $sheet->getStyle($cols[13] . ($row + 1))->getFont()->setBold(true);
            $sheet->getStyle($cols[13] . ($row + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue($cols[14] . ($row + 1), $totalGeneral);
            $sheet->getStyle($cols[14] . ($row + 1))->getFont()->setBold(true);
            $sheet->getStyle($cols[14] . ($row + 1))->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->setCellValue($cols[15] . ($row + 1), $saldoTotal);
            $sheet->getStyle($cols[15] . ($row + 1))->getFont()->setBold(true);
            $sheet->getStyle($cols[15] . ($row + 1))->getNumberFormat()->setFormatCode('$#,##0.00');

            $writer = new Xlsx($spreadsheet);
            $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="pagos_pendientes_' . date('Ymd') . '.xlsx"');
            $writer->save('php://output');
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarPagosPendientesJson] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Exporta a PDF el reporte de pagos pendientes.
     */
    public function exportarPagosPendientesPdf()
    {
        try {
            $json   = $this->request->getJSON(true);
            $datos  = $json['datos'] ?? [];
            $filtros = $json['filtros'] ?? [];
            $fechaCorte = $json['fechaCorte'] ?? null;
            $nombreEmpresa = $json['nombreEmpresa'] ?? 'Grupo MBM';
            $fechaHoy = date('d/m/Y H:i:s');

            if (empty($datos)) {
                return $this->fail('No hay datos para generar el PDF');
            }

            $pdf = new PDF('L', 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->SetAutoPageBreak(false);
            $pdf->setHeaderTitle('REPORTE PAGOS PENDIENTES');
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(18, 18, 18);
            $pdf->Cell(0, 7, $this->_iso('REPORTE DE PAGOS PENDIENTES'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 4, $this->_iso('Empresa: ' . $nombreEmpresa), 0, 1, 'L');

            $filtrosStr = [];
            if (!empty($filtros['razonesSociales'])) {
                $filtrosStr[] = 'Razón Social: ' . (is_array($filtros['razonesSociales']) ? implode(', ', $filtros['razonesSociales']) : $filtros['razonesSociales']);
            }
            if (!empty($filtros['complejos'])) {
                $filtrosStr[] = 'Complejo: ' . (is_array($filtros['complejos']) ? implode(', ', $filtros['complejos']) : $filtros['complejos']);
            }
            if (!empty($filtros['departamentos'])) {
                $filtrosStr[] = 'Departamentos: ' . (is_array($filtros['departamentos']) ? implode(', ', $filtros['departamentos']) : $filtros['departamentos']);
            }
            if (!empty($filtros['usuarios'])) {
                $filtrosStr[] = 'Usuario: ' . (is_array($filtros['usuarios']) ? implode(', ', $filtros['usuarios']) : $filtros['usuarios']);
            }
            if (!empty($filtros['formasPago'])) {
                $filtrosStr[] = 'Forma de Pago: ' . (is_array($filtros['formasPago']) ? implode(', ', $filtros['formasPago']) : $filtros['formasPago']);
            }
            if (!empty($filtros['tipos'])) {
                $filtrosStr[] = 'Tipo: ' . (is_array($filtros['tipos']) ? implode(', ', $filtros['tipos']) : $filtros['tipos']);
            }
            if (!empty($filtros['estados'])) {
                $filtrosStr[] = 'Estado: ' . (is_array($filtros['estados']) ? implode(', ', $filtros['estados']) : $filtros['estados']);
            }
            $pdf->Cell(0, 4, $this->_iso('Filtros: ' . (empty($filtrosStr) ? 'Ninguno' : implode(' | ', $filtrosStr))), 0, 1, 'L');
            if ($fechaCorte) {
                $pdf->Cell(0, 4, $this->_iso('Fecha de corte: ' . date('d/m/Y', strtotime($fechaCorte))), 0, 1, 'L');
            }
            $pdf->Cell(0, 4, $this->_iso('Generado: ' . $fechaHoy), 0, 1, 'L');
            $pdf->Ln(2);

            $headers = ['Folio', 'Razón Social', 'Complejo', 'Departamento', 'Usuario', 'F. Solic.', 'F. Aprob. Jefe', 'F. Aprob. Dir.', 'F. OC', 'F. Pago Real.', 'F. Comprob.', 'Estado', 'Forma Pago', 'Crédito', 'Total Req.', 'Saldo'];
            $colW    = [12, 24, 16, 19, 19, 14, 14, 14, 14, 14, 14, 14, 14, 15, 18, 16];
            $lineH   = 5;

            $pdf->SetWidths($colW);
            $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(229, 231, 235);
            $totalGeneral = 0;
            $saldoTotal   = 0;

            foreach ($datos as $item) {
                $total = (float) ($item['TotalRequisicion'] ?? 0);
                $saldo = (float) ($item['SaldoCredito'] ?? 0);
                $totalGeneral += $total;
                $saldoTotal   += $saldo;

                $cells = [
                    $item['No_Folio'] ?? 'N/A',
                    $item['RazonSocial'] ?? 'N/A',
                    $item['Complejo'] ?? 'N/A',
                    $item['Departamento'] ?? 'N/A',
                    $item['Usuario'] ?? 'N/A',
                    $item['FechaSolicitud'] ?? 'N/A',
                    $item['FechaAprobacionJefe'] ?? 'N/A',
                    $item['FechaAprobacion'] ?? 'N/A',
                    $item['FechaOC'] ?? 'N/A',
                    $item['FechaPagoRealizado'] ?? 'N/A',
                    $item['FechaComprobante'] ?? 'N/A',
                    $item['Estado'] ?? 'N/A',
                    $item['FormaPago'] ?? 'N/A',
                    $item['MontoCredito'] !== null ? '$' . number_format((float) $item['MontoCredito'], 2) : 'N/A',
                    '$' . number_format($total, 2),
                    $item['SaldoCredito'] !== null ? '$' . number_format((float) $item['SaldoCredito'], 2) : 'N/A',
                ];

                $lineCounts = [];
                foreach ($cells as $i => $c) {
                    $lineCounts[$i] = $pdf->NbLines($colW[$i], $this->_iso($c));
                }
                $h = max($lineCounts) * $lineH;

                if ($pdf->GetY() + $h > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage();
                    $this->_dibujarCabeceraOscura($pdf, $colW, $headers, $lineH);
                    $pdf->SetFont('Arial', '', 7);
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetDrawColor(229, 231, 235);
                }

                $aligns = [];
                foreach ($cells as $i => $v) {
                    $aligns[$i] = ($i === 15 || $i === 16) ? 'R' : 'L';
                }
                $pdf->SetX(8);
                $pdf->drawTableRow($colW, array_map(fn($v) => $this->_iso((string) $v), $cells), $aligns, $lineH, false);
            }

            $this->_dibujarTotalGeneral($pdf, $colW, 'TOTAL: $' . number_format($totalGeneral, 2) . '   |   SALDO CRÉDITO: $' . number_format($saldoTotal, 2));

            $this->response->setHeader('Content-Type', 'application/pdf');
            $pdf->Output('D', 'pagos_pendientes_' . date('Ymd') . '.pdf');
            exit;

        } catch (\Throwable $e) {
            log_message('error', '[exportarPagosPendientesPdf] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }
}
