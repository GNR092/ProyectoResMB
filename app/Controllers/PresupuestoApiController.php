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
        // Any common setup for budget API can go here
    }

    public function exportarComparativo($idPlace, $anio, $mes)
    {
        if (empty($mes)) return $this->fail('Meses no proporcionados');
        $meses = array_map('intval', explode(',', $mes));

        // 1. Obtener Datos
        $unidadModel = new UnidadOperativaModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $grupoModel = new GrupoPresupuestalModel();

        $query = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if (!empty($idPlace) && $idPlace != '0') {
            $placeIds = array_map('intval', explode(',', (string)$idPlace));
            $query->whereIn('UnidadOperativa.ID_Place', $placeIds);
        }
        $unidadesRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('PlaceNombre', 'ASC')->orderBy('Nombre', 'ASC')->findAll();

        if (empty($unidadesRaw)) return $this->fail('No hay datos para exportar');

        $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');

        // Obtener todos los grupos activos
        $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->findAll();

        // Obtener presupuestos
        $presupuestosRaw = $presupuestoMensualModel
            ->select('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->selectSum('Monto_Asignado', 'Monto_Asignado')
            ->selectSum('Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('Monto_Ejecutado', 'Monto_Ejecutado')
            ->whereIn('ID_UnidadOperativa', $unidadIds)
            ->where('Anio', $anio)
            ->whereIn('Mes', $meses)
            ->groupBy('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->findAll();

        // Agrupar datos para el Excel
        $rsGrupos = [];
        foreach ($unidadesRaw as $uni) {
            $idUnidad = (int)$uni['ID_UnidadOperativa'];
            $rsNombre = $uni['RazonSocialNombre'];
            $segNombre = $uni['SegmentoNombre'] ?? 'Sin Segmento';
            $placeNombre = $uni['PlaceNombre'];

            if (!isset($rsGrupos[$rsNombre])) $rsGrupos[$rsNombre] = ['nombre' => $rsNombre, 'segmentos' => [], 'totales' => ['asignado' => 0, 'comprometido' => 0, 'ejecutado' => 0]];
            if (!isset($rsGrupos[$rsNombre]['segmentos'][$segNombre])) $rsGrupos[$rsNombre]['segmentos'][$segNombre] = ['nombre' => $segNombre, 'complejos' => [], 'totales' => ['asignado' => 0, 'comprometido' => 0, 'ejecutado' => 0]];
            if (!isset($rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre])) $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre] = ['nombre' => $placeNombre, 'unidades' => [], 'totales' => ['asignado' => 0, 'comprometido' => 0, 'ejecutado' => 0]];

            $detalles = [];
            $uAsignado = 0; $uComprometido = 0; $uEjecutado = 0;

            // Iterar sobre TODOS los grupos de la unidad
            foreach ($gruposAll as $g) {
                if ((int)$g['ID_UnidadOperativa'] === $idUnidad) {
                    $monto = array_filter($presupuestosRaw, fn($p) => (int)$p['ID_UnidadOperativa'] === $idUnidad && (int)$p['ID_GrupoPresupuestal'] === (int)$g['ID_GrupoPresupuestal']);
                    $m = reset($monto);

                    $gAsignado = (float)($m['Monto_Asignado'] ?? 0);
                    $gComprometido = (float)($m['Monto_Comprometido'] ?? 0);
                    $gEjecutado = (float)($m['Monto_Ejecutado'] ?? 0);
                    
                    $detalles[] = [
                        'etiqueta' => $g['Nombre'],
                        'asignado' => $gAsignado,
                        'comprometido' => $gComprometido,
                        'ejecutado' => $gEjecutado
                    ];
                    $uAsignado += $gAsignado;
                    $uComprometido += $gComprometido;
                    $uEjecutado += $gEjecutado;
                }
            }

            $rsGrupos[$rsNombre]['totales']['asignado'] += $uAsignado;
            $rsGrupos[$rsNombre]['totales']['comprometido'] += $uComprometido;
            $rsGrupos[$rsNombre]['totales']['ejecutado'] += $uEjecutado;

            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['totales']['asignado'] += $uAsignado;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['totales']['comprometido'] += $uComprometido;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['totales']['ejecutado'] += $uEjecutado;

            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['totales']['asignado'] += $uAsignado;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['totales']['comprometido'] += $uComprometido;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['totales']['ejecutado'] += $uEjecutado;

            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['unidades'][] = [
                'nombre' => $uni['Nombre'],
                'detalles' => $detalles,
                'totales' => ['asignado' => $uAsignado, 'comprometido' => $uComprometido, 'ejecutado' => $uEjecutado]
            ];
        }

        // 2. Generar Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte Presupuesto');

        // Estilos
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // Encabezados
        $headers = ['Unidad / Partida', 'Presp. Asignado', 'Comprometido', 'Ejecutado', 'Disponible', '% Ejecución'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '1', $h);
            $sheet->getStyle($cols[$i] . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        $row = 2;
        $gtAsignado = 0;
        $gtComprometido = 0;
        $gtEjecutado = 0;

        foreach ($rsGrupos as $rs) {
            // Acumular para el total general
            $gtAsignado += $rs['totales']['asignado'];
            $gtComprometido += $rs['totales']['comprometido'];
            $gtEjecutado += $rs['totales']['ejecutado'];

            // Fila Razón Social
            $sheet->setCellValue('A' . $row, $rs['nombre']);
            $sheet->setCellValue('B' . $row, $rs['totales']['asignado']);
            $sheet->setCellValue('C' . $row, $rs['totales']['comprometido']);
            $sheet->setCellValue('D' . $row, $rs['totales']['ejecutado']);
            $rsAsig = $rs['totales']['asignado'];
            $rsGasto = $rs['totales']['comprometido'] + $rs['totales']['ejecutado'];
            $sheet->setCellValue('E' . $row, $rsAsig - $rsGasto);
            $sheet->setCellValue('F' . $row, $rsAsig > 0 ? ($rsGasto / $rsAsig) : 0);
            
            $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
            $row++;

            foreach ($rs['segmentos'] as $seg) {
                // ... (resto del código de segmentos, complejos, etc.)
                // Fila Segmento
                $sheet->setCellValue('A' . $row, '    ' . $seg['nombre']);
                $sheet->setCellValue('B' . $row, $seg['totales']['asignado']);
                $sheet->setCellValue('C' . $row, $seg['totales']['comprometido']);
                $sheet->setCellValue('D' . $row, $seg['totales']['ejecutado']);
                $sAsignado = $seg['totales']['asignado'];
                $sGasto = $seg['totales']['comprometido'] + $seg['totales']['ejecutado'];
                $sheet->setCellValue('E' . $row, $sAsignado - $sGasto);
                $sheet->setCellValue('F' . $row, $sAsignado > 0 ? ($sGasto / $sAsignado) : 0);

                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true)->getColor()->setRGB('1E3A8A');
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
                $row++;

                foreach ($seg['complejos'] as $complex) {
                    // Fila Complejo
                    $sheet->setCellValue('A' . $row, '        ' . $complex['nombre']);
                    $sheet->setCellValue('B' . $row, $complex['totales']['asignado']);
                    $sheet->setCellValue('C' . $row, $complex['totales']['comprometido']);
                    $sheet->setCellValue('D' . $row, $complex['totales']['ejecutado']);
                    $cAsignado = $complex['totales']['asignado'];
                    $cGasto = $complex['totales']['comprometido'] + $complex['totales']['ejecutado'];
                    $sheet->setCellValue('E' . $row, $cAsignado - $cGasto);
                    $sheet->setCellValue('F' . $row, $cAsignado > 0 ? ($cGasto / $cAsignado) : 0);

                    $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setItalic(true);
                    $row++;

                    foreach ($complex['unidades'] as $uni) {
                        // Fila Unidad
                        $sheet->setCellValue('A' . $row, '            ' . $uni['nombre']);
                        $sheet->setCellValue('B' . $row, $uni['totales']['asignado']);
                        $sheet->setCellValue('C' . $row, $uni['totales']['comprometido']);
                        $sheet->setCellValue('D' . $row, $uni['totales']['ejecutado']);
                        $uAsignado = $uni['totales']['asignado'];
                        $uGasto = $uni['totales']['comprometido'] + $uni['totales']['ejecutado'];
                        $sheet->setCellValue('E' . $row, $uAsignado - $uGasto);
                        $sheet->setCellValue('F' . $row, $uAsignado > 0 ? ($uGasto / $uAsignado) : 0);

                        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
                        $row++;

                        foreach ($uni['detalles'] as $det) {
                            // Fila Detalle/Partida
                            $sheet->setCellValue('A' . $row, '                ' . $det['etiqueta']);
                            $sheet->setCellValue('B' . $row, $det['asignado']);
                            $sheet->setCellValue('C' . $row, $det['comprometido']);
                            $sheet->setCellValue('D' . $row, $det['ejecutado']);
                            $dGasto = $det['comprometido'] + $det['ejecutado'];
                            $sheet->setCellValue('E' . $row, $det['asignado'] - $dGasto);
                            $sheet->setCellValue('F' . $row, $det['asignado'] > 0 ? ($dGasto / $det['asignado']) : 0);

                            $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setRGB('6B7280');
                            $row++;
                        }
                    }
                }
            }
        }

        // Fila de TOTAL GENERAL
        $sheet->setCellValue('A' . $row, 'TOTAL GENERAL');
        $sheet->setCellValue('B' . $row, $gtAsignado);
        $sheet->setCellValue('C' . $row, $gtComprometido);
        $sheet->setCellValue('D' . $row, $gtEjecutado);
        $gtGasto = $gtComprometido + $gtEjecutado;
        $sheet->setCellValue('E' . $row, $gtAsignado - $gtGasto);
        $sheet->setCellValue('F' . $row, $gtAsignado > 0 ? ($gtGasto / $gtAsignado) : 0);

        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $row . ':F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
        $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->getColor()->setRGB('FFFFFF');
        $row++;

        // Formato de moneda y porcentaje
        $lastRow = $row - 1;
        $sheet->getStyle('B2:E' . $lastRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('F2:F' . $lastRow)->getNumberFormat()->setFormatCode('0.0%');

        // Formato condicional para porcentajes > 100% (columna F)
        for ($i = 2; $i <= $lastRow; $i++) {
            $valorPorcentaje = $sheet->getCell('F' . $i)->getValue();
            if (is_numeric($valorPorcentaje) && $valorPorcentaje > 1) {
                $sheet->getStyle('F' . $i)->getFont()->getColor()->setRGB('FF0000');
            }
        }

        // Bordes
        $sheet->getStyle('A1:F' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $writer = new Xlsx($spreadsheet);
        $filename = 'reporte_presupuesto_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit();
    }

    // --- MÉTODOS PARA SALDOS BANCARIOS ---

    public function exportarReporteCompleto($idPlace, $anio, $mes)
    {
        if (empty($mes)) return $this->fail('Meses no proporcionados');
        $meses = array_map('intval', explode(',', $mes));

        // 1. Obtener Datos
        $unidadModel = new UnidadOperativaModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $grupoModel = new GrupoPresupuestalModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        $query = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if (!empty($idPlace) && $idPlace != '0') {
            $placeIds = array_map('intval', explode(',', (string)$idPlace));
            $query->whereIn('UnidadOperativa.ID_Place', $placeIds);
        }
        $unidadesRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('UnidadOperativa.Nombre', 'ASC')->findAll();

        if (empty($unidadesRaw)) return $this->fail('No hay datos');

        $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');
        $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->findAll();
        $presupuestosRaw = $presupuestoMensualModel
            ->select('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->selectSum('Monto_Asignado', 'Monto_Asignado')
            ->selectSum('Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('Monto_Ejecutado', 'Monto_Ejecutado')
            ->whereIn('ID_UnidadOperativa', $unidadIds)
            ->where('Anio', $anio)
            ->whereIn('Mes', $meses)
            ->groupBy('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->findAll();

        $rsIds = array_unique(array_filter(array_column($unidadesRaw, 'ID_RazonSocial')));
        $bancos = !empty($rsIds) ? $bancoModel->whereIn('ID_RazonSocial', $rsIds)->findAll() : [];
        $bancoIds = array_column($bancos, 'ID_BancoDpto');
        $saldos = !empty($bancoIds) ? $saldosModel->whereIn('id_bancodpto', $bancoIds)->where('anio', $anio)->whereIn('mes', $meses)->findAll() : [];

        // Agrupación Jerárquica
        $rsGrupos = [];
        $rsProcesadasBancos = [];

        foreach ($unidadesRaw as $uni) {
            $idUnidad = (int)$uni['ID_UnidadOperativa'];
            $idRS = (int)$uni['ID_RazonSocial'];
            $rsNombre = $uni['RazonSocialNombre'];
            $segNombre = $uni['SegmentoNombre'] ?? 'Sin Segmento';
            $placeNombre = $uni['PlaceNombre'];

            if (!isset($rsGrupos[$rsNombre])) {
                $rsGrupos[$rsNombre] = ['nombre' => $rsNombre, 'segmentos' => [], 'presupuesto' => ['asignado' => 0, 'gastado' => 0], 'bancos' => ['inicial' => 0, 'final' => 0]];
            }
            if (!isset($rsGrupos[$rsNombre]['segmentos'][$segNombre])) {
                $rsGrupos[$rsNombre]['segmentos'][$segNombre] = ['nombre' => $segNombre, 'complejos' => [], 'presupuesto' => ['asignado' => 0, 'gastado' => 0]];
            }
            if (!isset($rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre])) {
                $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre] = ['nombre' => $placeNombre, 'unidades' => [], 'presupuesto' => ['asignado' => 0, 'gastado' => 0]];
            }

            $detalles = [];
            $uAsignado = 0; $uGastado = 0;

            foreach ($gruposAll as $g) {
                if ((int)$g['ID_UnidadOperativa'] === $idUnidad) {
                    $monto = array_filter($presupuestosRaw, fn($p) => (int)$p['ID_UnidadOperativa'] === $idUnidad && (int)$p['ID_GrupoPresupuestal'] === (int)$g['ID_GrupoPresupuestal']);
                    $m = reset($monto);
                    $gAsig = (float)($m['Monto_Asignado'] ?? 0);
                    $gGast = (float)($m['Monto_Comprometido'] ?? 0) + (float)($m['Monto_Ejecutado'] ?? 0);
                    
                    $detalles[] = ['etiqueta' => $g['Nombre'], 'asignado' => $gAsig, 'gastado' => $gGast];
                    $uAsignado += $gAsig; $uGastado += $gGast;
                }
            }

            if (!in_array($idRS, $rsProcesadasBancos)) {
                $bIni = 0; $bFin = 0;
                foreach ($bancos as $b) {
                    if ((int)$b['ID_RazonSocial'] === $idRS) {
                        $seB = array_filter($saldos, fn($item) => (int)$item['id_bancodpto'] === (int)$b['ID_BancoDpto'] && in_array((int)$item['mes'], $meses));
                        if (!empty($seB)) {
                            usort($seB, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                            $sMin = reset($seB); $sMax = end($seB);
                            $bIni += (float)$sMin['saldo_inicial'];
                            $fVal = (float)$sMax['saldo_final'];
                            if ($fVal == 0 && (float)$sMax['saldo_inicial'] != 0) $fVal = (float)$sMax['saldo_inicial'];
                            $bFin += $fVal;
                        }
                    }
                }
                $rsGrupos[$rsNombre]['bancos']['inicial'] = $bIni;
                $rsGrupos[$rsNombre]['bancos']['final'] = $bFin;
                $rsProcesadasBancos[] = $idRS;
            }

            $rsGrupos[$rsNombre]['presupuesto']['asignado'] += $uAsignado;
            $rsGrupos[$rsNombre]['presupuesto']['gastado'] += $uGastado;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['presupuesto']['asignado'] += $uAsignado;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['presupuesto']['gastado'] += $uGastado;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['presupuesto']['asignado'] += $uAsignado;
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['presupuesto']['gastado'] += $uGastado;
            
            $rsGrupos[$rsNombre]['segmentos'][$segNombre]['complejos'][$placeNombre]['unidades'][] = [
                'nombre' => $uni['Nombre'],
                'presupuesto' => ['asignado' => $uAsignado, 'gastado' => $uGastado],
                'detalles' => $detalles
            ];
        }

        // 2. Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Consolidado Maestro');

        $headerStyle = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $headers = ['Unidad / Partida', 'P. Asignado', 'P. Gastado', 'P. Disponible', '% Ejec.', 'B. Inicial', 'B. Final', 'B. Diferencia'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '1', $h);
            $sheet->getStyle($cols[$i] . '1')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        $row = 2;
        $gtAsig = 0; $gtGast = 0; $gtBIni = 0; $gtBFin = 0;

        foreach ($rsGrupos as $rs) {
            $gtAsig += $rs['presupuesto']['asignado']; $gtGast += $rs['presupuesto']['gastado'];
            $gtBIni += $rs['bancos']['inicial']; $gtBFin += $rs['bancos']['final'];

            // Fila RS
            $sheet->setCellValue('A' . $row, $rs['nombre']);
            $sheet->setCellValue('B' . $row, $rs['presupuesto']['asignado']);
            $sheet->setCellValue('C' . $row, $rs['presupuesto']['gastado']);
            $sheet->setCellValue('D' . $row, $rs['presupuesto']['asignado'] - $rs['presupuesto']['gastado']);
            $sheet->setCellValue('E' . $row, $rs['presupuesto']['asignado'] > 0 ? ($rs['presupuesto']['gastado'] / $rs['presupuesto']['asignado']) : 0);
            $sheet->setCellValue('F' . $row, $rs['bancos']['inicial']);
            $sheet->setCellValue('G' . $row, $rs['bancos']['final']);
            $sheet->setCellValue('H' . $row, $rs['bancos']['inicial'] - $rs['bancos']['final']);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E5E7EB');
            $row++;

            foreach ($rs['segmentos'] as $seg) {
                $sheet->setCellValue('A' . $row, '    ' . $seg['nombre']);
                $sheet->setCellValue('B' . $row, $seg['presupuesto']['asignado']);
                $sheet->setCellValue('C' . $row, $seg['presupuesto']['gastado']);
                $sheet->setCellValue('D' . $row, $seg['presupuesto']['asignado'] - $seg['presupuesto']['gastado']);
                $sheet->setCellValue('E' . $row, $seg['presupuesto']['asignado'] > 0 ? ($seg['presupuesto']['gastado'] / $seg['presupuesto']['asignado']) : 0);
                $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true)->getColor()->setRGB('1E3A8A');
                $sheet->getStyle('A' . $row . ':E' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
                $row++;

                foreach ($seg['complejos'] as $comp) {
                    $sheet->setCellValue('A' . $row, '        ' . $comp['nombre']);
                    $sheet->setCellValue('B' . $row, $comp['presupuesto']['asignado']);
                    $sheet->setCellValue('C' . $row, $comp['presupuesto']['gastado']);
                    $sheet->setCellValue('D' . $row, $comp['presupuesto']['asignado'] - $comp['presupuesto']['gastado']);
                    $sheet->setCellValue('E' . $row, $comp['presupuesto']['asignado'] > 0 ? ($comp['presupuesto']['gastado'] / $comp['presupuesto']['asignado']) : 0);
                    $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setItalic(true);
                    $row++;

                    foreach ($comp['unidades'] as $uni) {
                        $sheet->setCellValue('A' . $row, '            ' . $uni['nombre']);
                        $sheet->setCellValue('B' . $row, $uni['presupuesto']['asignado']);
                        $sheet->setCellValue('C' . $row, $uni['presupuesto']['gastado']);
                        $sheet->setCellValue('D' . $row, $uni['presupuesto']['asignado'] - $uni['presupuesto']['gastado']);
                        $sheet->setCellValue('E' . $row, $uni['presupuesto']['asignado'] > 0 ? ($uni['presupuesto']['gastado'] / $uni['presupuesto']['asignado']) : 0);
                        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);
                        $row++;

                        foreach ($uni['detalles'] as $det) {
                            $sheet->setCellValue('A' . $row, '                ' . $det['etiqueta']);
                            $sheet->setCellValue('B' . $row, $det['asignado']);
                            $sheet->setCellValue('C' . $row, $det['gastado']);
                            $sheet->setCellValue('D' . $row, $det['asignado'] - $det['gastado']);
                            $sheet->setCellValue('E' . $row, $det['asignado'] > 0 ? ($det['gastado'] / $det['asignado']) : 0);
                            $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->getColor()->setRGB('6B7280');
                            $row++;
                        }
                    }
                }
            }
        }

        $sheet->setCellValue('A' . $row, 'TOTAL GENERAL');
        $sheet->setCellValue('B' . $row, $gtAsig);
        $sheet->setCellValue('C' . $row, $gtGast);
        $sheet->setCellValue('D' . $row, $gtAsig - $gtGast);
        $sheet->setCellValue('E' . $row, $gtAsig > 0 ? ($gtGast / $gtAsig) : 0);
        $sheet->setCellValue('F' . $row, $gtBIni);
        $sheet->setCellValue('G' . $row, $gtBFin);
        $sheet->setCellValue('H' . $row, $gtBIni - $gtBFin);
        $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');

        $lastRow = $row;
        $sheet->getStyle('B2:D' . $lastRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('F2:H' . $lastRow)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('E2:E' . $lastRow)->getNumberFormat()->setFormatCode('0.0%');

        // Formato condicional para porcentajes > 100% (columna E en reporte completo)
        for ($i = 2; $i <= $lastRow; $i++) {
            $valorPorcentaje = $sheet->getCell('E' . $i)->getValue();
            if (is_numeric($valorPorcentaje) && $valorPorcentaje > 1) {
                $sheet->getStyle('E' . $i)->getFont()->getColor()->setRGB('FF0000');
            }
        }
        $sheet->getStyle('A1:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $writer = new Xlsx($spreadsheet);
        $filename = 'reporte_consolidado_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }

    public function getReporteCompleto($idPlace, $anio, $mes)
    {
        $unidadModel = new UnidadOperativaModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $grupoModel = new GrupoPresupuestalModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        if (empty($mes)) return $this->respond(['departamentos' => []]);
        $meses = array_map('intval', explode(',', $mes));

        // 1. Obtener Unidades
        $query = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if (!empty($idPlace) && $idPlace != '0') {
            $placeIds = array_map('intval', explode(',', (string)$idPlace));
            $query->whereIn('UnidadOperativa.ID_Place', $placeIds);
        }
        $unidadesRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('UnidadOperativa.Nombre', 'ASC')->findAll();

        if (empty($unidadesRaw)) return $this->respond(['departamentos' => []]);

        $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');

        // 2. Obtener TODOS los Grupos de estas unidades
        $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->findAll();

        // 3. Obtener montos de Presupuesto
        $presupuestosRaw = $presupuestoMensualModel
            ->select('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->selectSum('Monto_Asignado', 'Monto_Asignado')
            ->selectSum('Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('Monto_Ejecutado', 'Monto_Ejecutado')
            ->whereIn('ID_UnidadOperativa', $unidadIds)
            ->where('Anio', $anio)
            ->whereIn('Mes', $meses)
            ->groupBy('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->findAll();

        // 4. Obtener Datos de Bancos
        $rsIds = array_unique(array_filter(array_column($unidadesRaw, 'ID_RazonSocial')));
        $bancos = !empty($rsIds) ? $bancoModel->whereIn('ID_RazonSocial', $rsIds)->findAll() : [];
        $bancoIds = array_column($bancos, 'ID_BancoDpto');
        $saldos = !empty($bancoIds) ? $saldosModel->whereIn('id_bancodpto', $bancoIds)->where('anio', $anio)->whereIn('mes', $meses)->findAll() : [];

        $estructura = [];
        $rsProcesadasBancos = []; 

        foreach ($unidadesRaw as $uni) {
            $idUnidad = (int)$uni['ID_UnidadOperativa'];
            $idRazonSocial = (int)$uni['ID_RazonSocial'];

            $analisisGrupos = [];
            $pAsignado = 0; $pComprometido = 0; $pEjecutado = 0;

            // Iterar sobre TODOS los grupos de la unidad
            foreach ($gruposAll as $g) {
                if ((int)$g['ID_UnidadOperativa'] === $idUnidad) {
                    $monto = array_filter($presupuestosRaw, fn($p) => (int)$p['ID_UnidadOperativa'] === $idUnidad && (int)$p['ID_GrupoPresupuestal'] === (int)$g['ID_GrupoPresupuestal']);
                    $m = reset($monto);

                    $gAsignado = (float)($m['Monto_Asignado'] ?? 0);
                    $gComprometido = (float)($m['Monto_Comprometido'] ?? 0);
                    $gEjecutado = (float)($m['Monto_Ejecutado'] ?? 0);
                    $gGastado  = $gComprometido + $gEjecutado;
                    
                    $pAsignado += $gAsignado;
                    $pComprometido += $gComprometido;
                    $pEjecutado += $gEjecutado;

                    $analisisGrupos[] = [
                        'etiqueta'   => $g['Nombre'],
                        'asignado'   => $gAsignado,
                        'gastado'    => $gGastado,
                        'disponible' => $gAsignado - $gGastado
                    ];
                }
            }

            $bInicial = 0; $bFinal = 0;
            if (!in_array($idRazonSocial, $rsProcesadasBancos)) {
                foreach ($bancos as $b) {
                    if ((int)$b['ID_RazonSocial'] === $idRazonSocial) {
                        $saldosEsteBanco = array_filter($saldos, fn($item) => (int)$item['id_bancodpto'] === (int)$b['ID_BancoDpto'] && in_array((int)$item['mes'], $meses));
                        if (!empty($saldosEsteBanco)) {
                            usort($saldosEsteBanco, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                            $sMin = reset($saldosEsteBanco);
                            $sMax = end($saldosEsteBanco);
                            $bInicial += (float)($sMin['saldo_inicial'] ?? 0);
                            $fVal = (float)($sMax['saldo_final'] ?? 0);
                            if ($fVal == 0 && (float)($sMax['saldo_inicial'] ?? 0) != 0) $fVal = (float)$sMax['saldo_inicial'];
                            $bFinal += $fVal;
                        }
                    }
                }
                $rsProcesadasBancos[] = $idRazonSocial;
            }

            $totalGasto = $pComprometido + $pEjecutado;
            $estructura[] = [
                'ID_UnidadOperativa' => $idUnidad,
                'Nombre' => $uni['Nombre'],
                'PlaceNombre' => $uni['PlaceNombre'],
                'RazonSocialNombre' => $uni['RazonSocialNombre'],
                'SegmentoNombre' => $uni['SegmentoNombre'] ?? 'Sin Segmento',
                'detalles' => $analisisGrupos,
                'presupuesto' => [
                    'asignado' => $pAsignado,
                    'gastado' => $totalGasto,
                    'disponible' => $pAsignado - $totalGasto,
                    'porcentaje' => $pAsignado > 0 ? round(($totalGasto / $pAsignado) * 100, 2) : 0
                ],
                'bancos' => [
                    'inicial' => $bInicial,
                    'final' => $bFinal,
                    'uso' => $bInicial - $bFinal,
                    'porcentaje' => $bInicial > 0 ? round((($bInicial - $bFinal) / $bInicial) * 100, 2) : 0
                ]
            ];
        }

        return $this->respond(['departamentos' => $estructura]);
    }

    public function getComparativo($idPlace, $anio, $mes)
    {
        $unidadModel = new UnidadOperativaModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $grupoModel = new GrupoPresupuestalModel();

        if (empty($mes)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);
        $meses = array_map('intval', explode(',', $mes));

        $query = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if (!empty($idPlace) && $idPlace != '0') {
            $placeIds = array_map('intval', explode(',', (string)$idPlace));
            $query->whereIn('UnidadOperativa.ID_Place', $placeIds);
        }
        $unidadesRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('PlaceNombre', 'ASC')->orderBy('Nombre', 'ASC')->findAll();

        if (empty($unidadesRaw)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);

        $unidadIds = array_column($unidadesRaw, 'ID_UnidadOperativa');

        // Obtener todos los grupos activos
        $gruposAll = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('activo', true)->findAll();

        // Obtener presupuestos
        $presupuestosRaw = $presupuestoMensualModel
            ->select('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->selectSum('Monto_Asignado', 'Monto_Asignado')
            ->selectSum('Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('Monto_Ejecutado', 'Monto_Ejecutado')
            ->whereIn('ID_UnidadOperativa', $unidadIds)
            ->where('Anio', $anio)
            ->whereIn('Mes', $meses)
            ->groupBy('ID_UnidadOperativa, ID_GrupoPresupuestal')
            ->findAll();

        $estructura = [];
        $gtAsignado = 0; $gtComprometido = 0; $gtEjecutado = 0;

        foreach ($unidadesRaw as $uni) {
            $idUnidad = (int)$uni['ID_UnidadOperativa'];
            $analisis = [];
            $tUniAsignado = 0; $tUniComprometido = 0; $tUniEjecutado = 0;

            foreach ($gruposAll as $g) {
                if ((int)$g['ID_UnidadOperativa'] === $idUnidad) {
                    $monto = array_filter($presupuestosRaw, fn($p) => (int)$p['ID_UnidadOperativa'] === $idUnidad && (int)$p['ID_GrupoPresupuestal'] === (int)$g['ID_GrupoPresupuestal']);
                    $m = reset($monto);

                    $asignado = (float)($m['Monto_Asignado'] ?? 0);
                    $comprometido = (float)($m['Monto_Comprometido'] ?? 0);
                    $ejecutado = (float)($m['Monto_Ejecutado'] ?? 0);
                    $totalGasto = $comprometido + $ejecutado;

                    $analisis[] = [
                        'etiqueta'     => $g['Nombre'],
                        'asignado'     => $asignado,
                        'comprometido' => $comprometido,
                        'ejecutado'    => $ejecutado,
                        'disponible'   => $asignado - $totalGasto,
                        'porcentaje'   => $asignado > 0 ? round(($totalGasto / $asignado) * 100, 2) : 0
                    ];

                    $tUniAsignado += $asignado;
                    $tUniComprometido += $comprometido;
                    $tUniEjecutado += $ejecutado;
                }
            }

            $uni['detalles'] = $analisis;
            $tUniGasto = $tUniComprometido + $tUniEjecutado;
            $uni['totales'] = [
                'asignado'     => $tUniAsignado,
                'comprometido' => $tUniComprometido,
                'ejecutado'    => $tUniEjecutado,
                'disponible'   => $tUniAsignado - $tUniGasto,
                'porcentaje'   => $tUniAsignado > 0 ? round(($tUniGasto / $tUniAsignado) * 100, 2) : 0
            ];
            
            // Asegurar campos consistentes para el frontend
            $uni['PlaceNombre'] = $uni['PlaceNombre'] ?? '';
            $uni['RazonSocialNombre'] = $uni['RazonSocialNombre'] ?? '';
            $uni['SegmentoNombre'] = $uni['SegmentoNombre'] ?? 'Sin Segmento';

            $estructura[] = $uni;
            $gtAsignado += $tUniAsignado; $gtComprometido += $tUniComprometido; $gtEjecutado += $tUniEjecutado;
        }

        $gtGasto = $gtComprometido + $gtEjecutado;
        return $this->respond([
            'departamentos' => $estructura,
            'totales_generales' => [
                'asignado'     => $gtAsignado,
                'comprometido' => $gtComprometido,
                'ejecutado'    => $gtEjecutado,
                'disponible'   => $gtAsignado - $gtGasto,
                'porcentaje'   => $gtAsignado > 0 ? round(($gtGasto / $gtAsignado) * 100, 2) : 0
            ]
        ]);
    }

    public function getEstructura($idPlace, $anio, $mes)
    {
        $unidadModel = new UnidadOperativaModel();
        $grupoModel = new GrupoPresupuestalModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();

        // Convertir string de comas a array de IDs para permitir múltiples complejos
        $placeIds = array_map('intval', explode(',', $idPlace));

        $unidadesRaw = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->whereIn('UnidadOperativa.ID_Place', $placeIds)
            ->orderBy('PlaceNombre', 'ASC')
            ->orderBy('UnidadOperativa.Nombre', 'ASC')
            ->findAll();

        if (empty($unidadesRaw)) return $this->respond(['departamentos' => []]);

        // Asegurar que incluimos el nombre del complejo en la unidad para claridad
        $unidades = array_map(function($u) use ($placeIds) {
            if (count($placeIds) > 1) {
                $u['Nombre'] = "({$u['PlaceNombre']}) {$u['Nombre']}";
            }
            return $u;
        }, $unidadesRaw);

        $unidadIds = array_column($unidades, 'ID_UnidadOperativa');
        $presupuestosGuardados = $presupuestoMensualModel->whereIn('ID_UnidadOperativa', $unidadIds)->where('Anio', $anio)->where('Mes', $mes)->findAll();
        $idsGruposConPresupuesto = array_column($presupuestosGuardados, 'ID_GrupoPresupuestal');

        $queryGrupos = $grupoModel->whereIn('ID_UnidadOperativa', $unidadIds)->groupStart()->where('activo', true);
        if (!empty($idsGruposConPresupuesto)) $queryGrupos->orWhereIn('ID_GrupoPresupuestal', $idsGruposConPresupuesto);
        $grupos = $queryGrupos->groupEnd()->orderBy('Nombre', 'ASC')->findAll();

        $estructura = [];
        foreach ($unidades as $uni) {
            $gruposDeLaUnidad = [];
            foreach ($grupos as $grupo) {
                if ((int)$grupo['ID_UnidadOperativa'] === (int)$uni['ID_UnidadOperativa']) {
                    $montoAsignado = ''; $idExistente = null;
                    foreach ($presupuestosGuardados as $presupuesto) {
                        if ((int)$presupuesto['ID_GrupoPresupuestal'] === (int)$grupo['ID_GrupoPresupuestal'] && (int)$presupuesto['ID_UnidadOperativa'] === (int)$uni['ID_UnidadOperativa']) {
                            $montoAsignado = $presupuesto['Monto_Asignado'];
                            $idExistente = $presupuesto['ID_PresupuestoMensual'];
                            break;
                        }
                    }
                    // Validación robusta para Postgres ('t'), MySQL (1) y PHP (true)
                    $valActivo = $grupo['activo'];
                    $esActivo = ($valActivo === true || $valActivo === 't' || $valActivo === 1 || $valActivo === '1');

                    if (!$esActivo && empty($idExistente)) continue;
                    $grupo['Monto_Asignado'] = $montoAsignado;
                    $grupo['ID_PresupuestoMensual'] = $idExistente;
                    $gruposDeLaUnidad[] = $grupo;
                }
            }
            $uni['grupos'] = $gruposDeLaUnidad;
            $estructura[] = $uni;
        }

        // Verificar si hay solicitudes de revisión pendientes para este periodo
        $solicitudModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $revisionesPendientes = $solicitudModel->where([
            'Modulo' => 'PresupuestoMensual',
            'Estado' => 'Pendiente',
            'ID_Afectado' => "{$anio}-{$mes}"
        ])->findAll();

        $bloqueado = false;
        if (!empty($revisionesPendientes)) {
            // Extraer todos los IDs de Unidades Operativas que están seleccionadas actualmente
            $idsUnidadesCargadas = array_column($unidadesRaw, 'ID_UnidadOperativa');

            foreach ($revisionesPendientes as $revision) {
                $payload = json_decode($revision['Datos_Payload'], true);
                if (isset($payload['grupos']) && is_array($payload['grupos'])) {
                    foreach ($payload['grupos'] as $g) {
                        $idUniRevision = (int)($g['id_unidad'] ?? $g['id_dpto']);
                        // Si la unidad de esta revisión está entre las unidades que el usuario está viendo, bloqueamos
                        if (in_array($idUniRevision, $idsUnidadesCargadas)) {
                            $bloqueado = true;
                            break 2; // Salir de ambos bucles
                        }
                    }
                }
            }
        }

        return $this->respond([
            'departamentos' => $estructura,
            'bloqueadoPorRevision' => $bloqueado
        ]);
    }

    public function getCambiosPendientes()
    {
        $model = new \App\Models\SolicitudesCambioPresupuestoModel();
        $cambios = $model->getPendientes();
        
        $db = \Config\Database::connect();
        
        foreach ($cambios as &$c) {
            $c['Datos_Payload'] = $this->enrichPayload($c['Datos_Payload'], $db);
            $c['Datos_Antiguos'] = $this->enrichPayload($c['Datos_Antiguos'], $db);
        }
        
        return $this->respond($cambios);
    }

    private function enrichPayload($json, $db) {
        if (!$json) return $json;
        $data = json_decode($json, true);
        if (!is_array($data)) return $json;

        $mappings = [
            'ID_UnidadOperativa' => ['table' => 'UnidadOperativa', 'col' => 'ID_UnidadOperativa', 'name' => 'Nombre'],
            'id_unidad'          => ['table' => 'UnidadOperativa', 'col' => 'ID_UnidadOperativa', 'name' => 'Nombre'],
            'id_dpto'            => ['table' => 'UnidadOperativa', 'col' => 'ID_UnidadOperativa', 'name' => 'Nombre'],
            'ID_Place'           => ['table' => 'Places',           'col' => 'ID_Place',           'name' => 'Nombre_Corto'],
            'ID_RazonSocial'     => ['table' => 'Razon_Social',     'col' => 'ID_RazonSocial',     'name' => 'Nombre'],
            'id_segmento'        => ['table' => 'segmento_negocio', 'col' => 'id',               'name' => 'nombre'],
            'ID_Segmento'        => ['table' => 'segmento_negocio', 'col' => 'id',               'name' => 'nombre'],
            'id_bancodpto'       => ['table' => 'BancoDpto',        'col' => 'ID_BancoDpto',       'name' => 'Banco'],
            'ID_BancoDpto'       => ['table' => 'BancoDpto',        'col' => 'ID_BancoDpto',       'name' => 'Banco'],
            'ID_Usuario'         => ['table' => 'Usuarios',         'col' => 'ID_Usuario',         'name' => 'Nombre'],
        ];
        
        foreach ($mappings as $key => $map) {
            if (isset($data[$key]) && !empty($data[$key]) && is_numeric($data[$key])) {
                $res = $db->table($map['table'])->where($map['col'], $data[$key])->get()->getRow();
                if ($res) {
                    $nameField = $map['name'];
                    $data[$key] = $res->$nameField . " (#" . $data[$key] . ")";
                }
            }
        }
        return json_encode($data);
    }

    public function dictaminarCambio()
    {
        $json = $this->request->getJSON(true);
        $idSolicitud = $json['ID_SolicitudCambio'] ?? null;
        $estado = $json['Estado'] ?? null;
        $comentarios = $json['Comentarios'] ?? null;

        if (!$idSolicitud || !in_array($estado, ['Aprobado', 'Rechazado'])) {
            return $this->failValidationErrors('Datos de dictamen inválidos.');
        }

        $solicitudModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $solicitud = $solicitudModel->find($idSolicitud);

        if (!$solicitud) return $this->failNotFound('Solicitud no encontrada.');
        if ($solicitud['Estado'] !== 'Pendiente') return $this->fail('La solicitud ya fue procesada.');

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if ($estado === 'Aprobado') {
                $payload = json_decode($solicitud['Datos_Payload'], true);
                $modulo = $solicitud['Modulo'];
                $accion = $solicitud['Accion'];
                $idAfectado = $solicitud['ID_Afectado'];

                switch ($modulo) {
                    case 'SegmentoNegocio':
                        $model = new \App\Models\SegmentoNegocioModel();
                        if ($accion === 'Insertar') $model->insert($payload);
                        elseif ($accion === 'Editar') $model->update($idAfectado, $payload);
                        elseif ($accion === 'Eliminar') $model->delete($idAfectado);
                        break;
                    
                    case 'BancoDpto':
                        $model = new \App\Models\BancoDptoModel();
                        if ($accion === 'Insertar') $model->insert($payload);
                        elseif ($accion === 'Editar') $model->update($idAfectado, $payload);
                        elseif ($accion === 'Eliminar') $model->delete($idAfectado);
                        break;

                    case 'UnidadOperativa':
                        $model = new \App\Models\UnidadOperativaModel();
                        if ($accion === 'Insertar') $model->insert($payload);
                        elseif ($accion === 'Editar') $model->update($idAfectado, $payload);
                        elseif ($accion === 'Eliminar') $model->update($idAfectado, $payload); // payload tiene ['activo' => false]
                        break;

                    case 'GrupoPresupuestal':
                        $model = new \App\Models\GrupoPresupuestalModel();
                        if ($accion === 'Insertar') {
                            $model->insert($payload);
                        } elseif ($accion === 'Editar') {
                            // Replicar lógica de clonación si cambia la unidad
                            $grupoActual = $model->find($idAfectado);
                            $nuevaUnidad = $payload['ID_UnidadOperativa'] ?? null;
                            $unidadAnterior = $grupoActual['ID_UnidadOperativa'] ?? 0;
                            
                            if ($nuevaUnidad != $unidadAnterior && $unidadAnterior != 0) {
                                $model->update($idAfectado, ['activo' => false]);
                                $payload['Nombre'] = $payload['Nombre'] ?: $grupoActual['Nombre'];
                                $payload['Descripcion'] = $payload['Descripcion'] ?: $grupoActual['Descripcion'];
                                $payload['activo'] = true;
                                $model->insert($payload);
                            } else {
                                $model->update($idAfectado, $payload);
                            }
                        } elseif ($accion === 'Eliminar') {
                            $model->update($idAfectado, $payload); // payload tiene ['activo' => false]
                        }
                        break;

                    case 'PresupuestoMensual':
                        $pmModel = new PresupuestoMensualModel();
                        $paModel = new PresupuestoAnualModel();
                        $uniModel = new UnidadOperativaModel();
                        $placesModel = new PlacesModel();
                        
                        $anio = (int) $payload['anio'];
                        $mes = (int) $payload['mes'];
                        $grupos = $payload['grupos'];
                        $rsAfectadas = [];

                        foreach ($grupos as $g) {
                            $idUnidad = (int) ($g['id_unidad'] ?? $g['id_dpto']);
                            $data = [
                                'ID_UnidadOperativa' => $idUnidad,
                                'ID_GrupoPresupuestal' => (int) $g['id_grupo'],
                                'Anio' => $anio, 'Mes' => $mes,
                                'Monto_Asignado' => (float) $g['monto_asignado']
                            ];

                            if (!empty($g['id_existente'])) {
                                $pmModel->update((int) $g['id_existente'], $data);
                            } else {
                                $exists = $pmModel->where(['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => (int)$g['id_grupo'], 'Anio' => $anio, 'Mes' => $mes])->first();
                                if ($exists) $pmModel->update($exists['ID_PresupuestoMensual'], $data);
                                else $pmModel->insert($data);
                            }

                            $unidad = $uniModel->find($idUnidad);
                            if ($unidad) {
                                $place = $placesModel->find($unidad['ID_Place']);
                                if ($place) $rsAfectadas[] = (int) $place['ID_RazonSocial'];
                            }
                        }

                        foreach (array_unique($rsAfectadas) as $idRS) {
                            $q = $db->table('PresupuestoMensual')
                                ->selectSum('PresupuestoMensual.Monto_Asignado', 'total')
                                ->join('UnidadOperativa u', 'u.ID_UnidadOperativa = PresupuestoMensual.ID_UnidadOperativa')
                                ->join('Places p', 'p.ID_Place = u.ID_Place')
                                ->join('GrupoPresupuestal gp', 'gp.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')
                                ->where(['PresupuestoMensual.Anio' => $anio, 'p.ID_RazonSocial' => $idRS, 'gp.activo' => true])
                                ->get()->getRow();
                            $total = $q ? (float) $q->total : 0.0;
                            
                            $pa = (new PresupuestoAnualModel())->where(['Anio' => $anio, 'ID_RazonSocial' => $idRS])->first();
                            $paData = ['ID_RazonSocial' => $idRS, 'Anio' => $anio, 'Monto' => $total];
                            if ($pa) (new PresupuestoAnualModel())->update($pa['ID_PresupuestoAnual'], $paData);
                            else (new PresupuestoAnualModel())->insert($paData);
                        }
                        break;

                    case 'SaldosBancarios':
                        $sModel = new SaldosBancariosModel();
                        $anio = (int) $payload['anio'];
                        $mes = (int) $payload['mes'];
                        
                        foreach ($payload['saldos'] as $s) {
                            $data = [
                                'id_bancodpto' => (int)$s['id_bancodpto'], 
                                'mes' => $mes, 
                                'anio' => $anio, 
                                'saldo_inicial' => (float)$s['saldo_inicial'], 
                                'saldo_final' => (float)$s['saldo_final']
                            ];
                            if (!empty($s['id_existente'])) $sModel->update((int)$s['id_existente'], $data);
                            else $sModel->insert($data);
                        }
                        break;
                }
            }

            // Actualizar la solicitud
            $solicitudModel->update($idSolicitud, [
                'Estado' => $estado,
                'Comentarios_Revisor' => $comentarios
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('Error al aplicar los cambios en la base de datos.');
            }

            return $this->respond(['success' => true, 'message' => "Solicitud de cambio $estado correctamente."]);

        } catch (\Exception $e) {
            $db->transRollback();
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

        // --- EXCEPCIÓN: COPIA DE MES ANTERIOR ---
        // Si se usó la copia, aplicamos directamente sin pasar por revisión
        if ($usoCopia) {
            $db = \Config\Database::connect();
            $db->transStart();
            try {
                $pmModel = new PresupuestoMensualModel();
                $paModel = new PresupuestoAnualModel();
                $uniModel = new UnidadOperativaModel();
                $placesModel = new PlacesModel();
                
                $rsAfectadas = [];

                foreach ($json['grupos'] as $g) {
                    $idUnidad = (int) ($g['id_unidad'] ?? $g['id_dpto']);
                    $idGrupo  = (int) $g['id_grupo'];
                    
                    $data = [
                        'ID_UnidadOperativa'   => $idUnidad,
                        'ID_GrupoPresupuestal' => $idGrupo,
                        'Anio'                 => $anio, 
                        'Mes'                  => $mes,
                        'Monto_Asignado'       => (float) $g['monto_asignado']
                    ];

                    // Siempre buscamos si ya existe por la llave única natural (Unidad, Grupo, Anio, Mes)
                    $exists = $pmModel->where([
                        'ID_UnidadOperativa'   => $idUnidad,
                        'ID_GrupoPresupuestal' => $idGrupo,
                        'Anio'                 => $anio,
                        'Mes'                  => $mes
                    ])->first();

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

                // Recalcular presupuesto anual para las RS afectadas
                foreach (array_unique($rsAfectadas) as $idRS) {
                    $q = $db->table('PresupuestoMensual')
                        ->selectSum('PresupuestoMensual.Monto_Asignado', 'total')
                        ->join('UnidadOperativa u', 'u.ID_UnidadOperativa = PresupuestoMensual.ID_UnidadOperativa')
                        ->join('Places p', 'p.ID_Place = u.ID_Place')
                        ->join('GrupoPresupuestal gp', 'gp.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')
                        ->where(['PresupuestoMensual.Anio' => $anio, 'p.ID_RazonSocial' => $idRS, 'gp.activo' => true])
                        ->get()->getRow();
                    $total = $q ? (float) $q->total : 0.0;
                    
                    $pa = $paModel->where(['Anio' => $anio, 'ID_RazonSocial' => $idRS])->first();
                    $paData = ['ID_RazonSocial' => $idRS, 'Anio' => $anio, 'Monto' => $total];
                    if ($pa) $paModel->update($pa['ID_PresupuestoAnual'], $paData);
                    else $paModel->insert($paData);
                }

                $db->transComplete();
                if ($db->transStatus() === false) throw new \Exception('Error al guardar copia directa.');

                return $this->respond([
                    'success' => true, 
                    'pending_review' => false, 
                    'message' => 'Presupuesto del mes anterior aplicado correctamente ✅'
                ]);

            } catch (\Exception $e) {
                $db->transRollback();
                return $this->failServerError($e->getMessage());
            }
        }

        // --- FLUJO NORMAL: ENVÍO A REVISIÓN ---
        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'PresupuestoMensual',
            'Accion'        => 'Masivo',
            'ID_Afectado'   => "{$anio}-{$mes}",
            'Datos_Payload' => json_encode($json),
            'Datos_Antiguos'=> null,
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->respondCreated([
                'success' => true, 
                'pending_review' => true, 
                'message' => 'Cambios de presupuesto enviados a Dirección para su autorización.'
            ]);
        } else {
            return $this->failServerError('Error al enviar la solicitud de cambios.');
        }
    }

    public function getSaldos()
    {
        $idDpto = $this->request->getVar('id_dpto');
        $idGrupo = $this->request->getVar('id_grupo');
        if (!$idDpto || !$idGrupo) return $this->failValidationErrors('Faltan parámetros.');

        // Encontrar la unidad operativa del departamento
        $dptoModel = new DepartamentosModel();
        $dpto = $dptoModel->find($idDpto);
        if (!$dpto) return $this->failNotFound('Departamento no existe.');
        $idUnidad = $dpto['ID_UnidadOperativa'];

        $mes = (int)date('n'); $anio = (int)date('Y');
        $pmModel = new PresupuestoMensualModel();
        $p = $pmModel->where(['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => $idGrupo, 'Anio' => $anio, 'Mes' => $mes])->first();

        if (!$p) return $this->respond(['success' => true, 'data' => ['Monto_Asignado' => 0, 'Monto_Comprometido' => 0, 'Monto_Ejecutado' => 0, 'Saldos_Disponibles' => 0, 'found' => false]]);

        $asig = (float)$p['Monto_Asignado'];
        $comp = (float)$p['Monto_Comprometido'];
        $ejec = (float)$p['Monto_Ejecutado'];
        return $this->respond(['success' => true, 'data' => ['Monto_Asignado' => $asig, 'Monto_Comprometido' => $comp, 'Monto_Ejecutado' => $ejec, 'Saldos_Disponibles' => $asig - ($comp + $ejec), 'found' => true]]);
    }

    private function getTotalesCero() { return ['asignado' => 0, 'comprometido' => 0, 'ejecutado' => 0, 'disponible' => 0, 'porcentaje' => 0]; }

    // --- RESTO DE MÉTODOS DE BANCOS (SIN CAMBIOS ESTRUCTURALES) ---
    public function getEstructuraSaldos($idRazonSocial, $anio, $mes) {
        $rsModel = new RazonSocialModel(); $bancoModel = new BancoDptoModel(); $saldosModel = new SaldosBancariosModel();
        $rsRaw = $rsModel->find($idRazonSocial); if (!$rsRaw) return $this->respond(['razones' => []]);
        $bancosRaw = $bancoModel->where('ID_RazonSocial', $idRazonSocial)->orderBy('Banco', 'ASC')->findAll();
        if (empty($bancosRaw)) return $this->respond(['razones' => []]);
        $bancoIds = array_column($bancosRaw, 'ID_BancoDpto');
        $saldosRaw = $saldosModel->whereIn('id_bancodpto', $bancoIds)->where(['anio' => $anio, 'mes' => $mes])->findAll();
        
        // Verificar revisiones pendientes
        $solicitudModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        $revisionesPendientes = $solicitudModel->where([
            'Modulo' => 'SaldosBancarios',
            'Estado' => 'Pendiente',
            'ID_Afectado' => "{$anio}-{$mes}"
        ])->findAll();

        $bloqueado = false;
        if (!empty($revisionesPendientes)) {
            foreach ($revisionesPendientes as $revision) {
                $payload = json_decode($revision['Datos_Payload'], true);
                if (isset($payload['saldos']) && is_array($payload['saldos'])) {
                    foreach ($payload['saldos'] as $s) {
                        if (in_array((int)$s['id_bancodpto'], $bancoIds)) {
                            $bloqueado = true;
                            break 2;
                        }
                    }
                }
            }
        }

        $bancosConSaldos = [];
        foreach ($bancosRaw as $b) {
            $si = 0; $sf = 0; $idEx = null;
            foreach ($saldosRaw as $s) { if ((int)$s['id_bancodpto'] === (int)$b['ID_BancoDpto']) { $si = $s['saldo_inicial']; $sf = $s['saldo_final']; $idEx = $s['id']; break; } }
            $b['saldo_inicial'] = $si; $b['saldo_final'] = $sf; $b['id_saldo_existente'] = $idEx;
            $bancosConSaldos[] = $b;
        }
        return $this->respond([
            'razones' => [['ID_RazonSocial' => $idRazonSocial, 'Nombre' => $rsRaw['Nombre'], 'bancos' => $bancosConSaldos]],
            'bloqueadoPorRevision' => $bloqueado
        ]);
    }

    public function saveSaldosMasivo() {
        $json = $this->request->getJSON(true);
        if (!isset($json['saldos'])) return $this->failValidationErrors('Datos incompletos.');

        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();
        
        $anio = (int) ($json['anio'] ?? 0);
        $mes = (int) ($json['mes'] ?? 0);
        $comentarios = $json['comentarios'] ?? null;

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'SaldosBancarios',
            'Accion'        => 'Masivo',
            'ID_Afectado'   => "{$anio}-{$mes}",
            'Datos_Payload' => json_encode($json),
            'Datos_Antiguos'=> null,
            'Estado'        => 'Pendiente',
            'Comentarios_Solicitante' => $comentarios
        ])) {
            return $this->respondCreated([
                'success' => true, 
                'pending_review' => true, 
                'message' => 'Actualización de saldos enviada a Dirección para su autorización.'
            ]);
        } else {
            return $this->failServerError('Error al enviar la solicitud de saldos.');
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

    public function getComparativoBancos($idPlace, $anio, $mes) {
        $uniModel = new UnidadOperativaModel(); $bModel = new BancoDptoModel(); $sModel = new SaldosBancariosModel();
        if (empty($mes)) return $this->respond(['razones' => []]);
        $meses = array_map('intval', explode(',', $mes));
        $unidades = $uniModel->select('UnidadOperativa.ID_Place, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial');
        if (!empty($idPlace) && $idPlace != '0') {
            $placeIds = array_map('intval', explode(',', (string)$idPlace));
            $unidades->whereIn('UnidadOperativa.ID_Place', $placeIds);
        }
        $unidades = $unidades->findAll(); if (empty($unidades)) return $this->respond(['razones' => []]);
        $rsIds = array_unique(array_column($unidades, 'ID_RazonSocial'));
        $bancos = $bModel->whereIn('ID_RazonSocial', $rsIds)->findAll();
        $bancoIds = array_column($bancos, 'ID_BancoDpto');
        $saldos = !empty($bancoIds) ? $sModel->whereIn('id_bancodpto', $bancoIds)->where('anio', $anio)->whereIn('mes', $meses)->findAll() : [];
        $resultado = [];
        foreach ($rsIds as $idRS) {
            $bRS = []; $ti = 0; $tf = 0; $nomRS = '';
            foreach ($bancos as $b) {
                if ((int)$b['ID_RazonSocial'] === (int)$idRS) {
                    $seB = array_filter($saldos, fn($item) => (int)$item['id_bancodpto'] === (int)$b['ID_BancoDpto'] && in_array((int)$item['mes'], $meses));
                    if (!empty($seB)) {
                        usort($seB, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                        $sMin = reset($seB); $sMax = end($seB);
                        $ini = (float)$sMin['saldo_inicial']; $fin = (float)$sMax['saldo_final'];
                        if ($fin == 0 && (float)$sMax['saldo_inicial'] != 0) $fin = (float)$sMax['saldo_inicial'];
                        $bRS[] = ['banco' => $b['Banco'], 'clabe' => $b['Clabe'], 'inicial' => $ini, 'final' => $fin, 'usado' => $ini - $fin, 'porcentaje' => $ini > 0 ? round((($ini - $fin) / $ini) * 100, 2) : 0];
                        $ti += $ini; $tf += $fin;
                    }
                }
            }
            foreach ($unidades as $u) { if ((int)$u['ID_RazonSocial'] === (int)$idRS) $nomRS = $u['RazonSocialNombre']; }
            $resultado[] = ['ID_RazonSocial' => $idRS, 'Nombre' => $nomRS, 'bancos' => $bRS, 'totales' => ['inicial' => $ti, 'final' => $tf, 'usado' => $ti - $tf, 'porcentaje' => $ti > 0 ? round((($ti - $tf) / $ti) * 100, 2) : 0]];
        }
        return $this->respond(['razones' => $resultado]);
    }
}
