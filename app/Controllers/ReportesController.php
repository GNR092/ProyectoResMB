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

        // Datos para el Reporte de Compras (Pantalla 6)
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
            
            if (empty($datos)) return $this->fail('No hay datos');

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Vencimientos');

            $headers = ['Cód.', 'RFC', 'Razón Social'];
            if ($esDetallado) {
                array_splice($headers, 1, 0, ['Folio']);
            }
            
            if (!$esDetallado) {
                $headers[] = 'Importe Crédito';
            }
            
            $headers[] = 'Importe Por Pagar';
            
            if (!$esDetallado) {
                $headers[] = 'Saldo Crédito';
            }
            
            $headers[] = 'Días Créd.';
            
            if ($esDetallado) {
                $headers[] = 'Fecha Ref.';
            }
            
            $headers[] = 'Días Vencido';

            $cols = [];
            for ($i = 0; $i < count($headers); $i++) {
                $cols[] = chr(65 + $i);
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
                $sheet->setCellValue($cols[$i].'1', $h);
                $sheet->getStyle($cols[$i].'1')->applyFromArray($headerStyle);
                $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
            }

            $row = 2;
            foreach ($datos as $v) {
                $c = 0;
                $sheet->setCellValue($cols[$c++].$row, $v['ID_Proveedor']);
                if ($esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, $v['No_Folio']);
                }
                $sheet->setCellValue($cols[$c++].$row, $v['RFC'] ?: 'N/A');
                $sheet->setCellValue($cols[$c++].$row, $v['RazonSocial']);
                
                if (!$esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, (float)$v['Monto_Credito']);
                }
                
                $sheet->setCellValue($cols[$c++].$row, (float)$v['importePorPagar']);
                
                if (!$esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, (float)$v['saldoCredito']);
                }
                
                $sheet->setCellValue($cols[$c++].$row, (int)$v['Dias_Credito']);
                
                if ($esDetallado) {
                    $sheet->setCellValue($cols[$c++].$row, $v['fechaReferenciaStr']);
                }
                
                $sheet->setCellValue($cols[$c++].$row, $v['textoVencimiento']);

                // Estilos de semáforo
                if (isset($v['claseSemaforo'])) {
                    if (strpos($v['claseSemaforo'], 'bg-gray-900') !== false) {
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('111827');
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFont()
                            ->getColor()->setRGB('FFFFFF');
                    } else if (strpos($v['claseSemaforo'], 'bg-red-100') !== false) {
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFont()
                            ->getColor()->setRGB('991B1B');
                    } else if (strpos($v['claseSemaforo'], 'bg-yellow-100') !== false) {
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
                        $sheet->getStyle('A'.$row.':'.$cols[count($headers)-1].$row)->getFont()
                            ->getColor()->setRGB('92400E');
                    }
                }
                $row++;
            }

            // Formato de moneda para columnas de dinero
            // Dinero suele estar en:
            // Agrupado: D (Monto_Credito), E (importePorPagar), F (saldoCredito)
            // Detallado: D (importePorPagar)
            if ($esDetallado) {
                $sheet->getStyle('E2:E'.($row-1))->getNumberFormat()->setFormatCode('$#,##0.00');
            } else {
                $sheet->getStyle('D2:F'.($row-1))->getNumberFormat()->setFormatCode('$#,##0.00');
            }

            $sheet->getStyle('A1:'.$cols[count($headers)-1].($row-1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

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
}
