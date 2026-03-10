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

class PresupuestoApiController extends ResourceController
{
    protected $format = 'json';

    public function __construct()
    {
        // Any common setup for budget API can go here
    }

    // --- MÉTODOS PARA SALDOS BANCARIOS ---

    public function getReporteCompleto($idPlace, $anio, $mes)
    {
        $dptoModel = new DepartamentosModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        if (empty($mes)) return $this->respond(['departamentos' => []]);
        $meses = array_map('intval', explode(',', $mes));

        // 1. Obtener estructura base (Importante: incluir ID_RazonSocial)
        $query = $dptoModel->select('Departamentos.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if ($idPlace > 0) $query->where('Departamentos.ID_Place', $idPlace);
        $departamentosRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('Nombre', 'ASC')->findAll();

        if (empty($departamentosRaw)) return $this->respond(['departamentos' => []]);

        // Normalizar departamentos para Postgres
        $departamentos = array_map(function($d) {
            return [
                'ID_Dpto' => $d['ID_Dpto'] ?? $d['id_dpto'] ?? 0,
                'Nombre' => $d['Nombre'] ?? $d['nombre'] ?? '',
                'ID_Place' => $d['ID_Place'] ?? $d['id_place'] ?? 0,
                'ID_RazonSocial' => $d['ID_RazonSocial'] ?? $d['id_razonsocial'] ?? 0,
                'PlaceNombre' => $d['PlaceNombre'] ?? $d['placenombre'] ?? '',
                'RazonSocialNombre' => $d['RazonSocialNombre'] ?? $d['razonsocialnombre'] ?? '',
                'SegmentoNombre' => $d['SegmentoNombre'] ?? $d['segmentonombre'] ?? 'Sin Segmento',
            ];
        }, $departamentosRaw);

        $dptoIds = array_column($departamentos, 'ID_Dpto');

        // 2. Obtener Datos de Presupuesto con Nombres de Grupos (Agrupado por meses)
        $presupuestos = $presupuestoMensualModel
            ->select('PresupuestoMensual.ID_Dpto, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre as GrupoNombre')
            ->selectSum('PresupuestoMensual.Monto_Asignado', 'Monto_Asignado')
            ->selectSum('PresupuestoMensual.Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('PresupuestoMensual.Monto_Ejecutado', 'Monto_Ejecutado')
            ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')
            ->whereIn('PresupuestoMensual.ID_Dpto', $dptoIds)
            ->where('PresupuestoMensual.Anio', $anio)
            ->whereIn('PresupuestoMensual.Mes', $meses)
            ->groupBy('PresupuestoMensual.ID_Dpto, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre')
            ->findAll();

        // Convertir resultados a PascalCase si Postgres los devolvió en minúsculas
        $presupuestos = array_map(function($p) {
            return [
                'ID_Dpto' => $p['ID_Dpto'] ?? $p['id_dpto'] ?? 0,
                'ID_GrupoPresupuestal' => $p['ID_GrupoPresupuestal'] ?? $p['id_grupopresupuestal'] ?? 0,
                'GrupoNombre' => $p['GrupoNombre'] ?? $p['gruponombre'] ?? '',
                'Monto_Asignado' => $p['Monto_Asignado'] ?? $p['monto_asignado'] ?? 0,
                'Monto_Comprometido' => $p['Monto_Comprometido'] ?? $p['monto_comprometido'] ?? 0,
                'Monto_Ejecutado' => $p['Monto_Ejecutado'] ?? $p['monto_ejecutado'] ?? 0,
            ];
        }, $presupuestos);

        // 3. Obtener Datos de Bancos por Razón Social (Rango de meses)
        $rsIds = array_unique(array_filter(array_column($departamentos, 'ID_RazonSocial')));
        $bancosRaw = !empty($rsIds) ? $bancoModel->whereIn('ID_RazonSocial', $rsIds)->findAll() : [];
        
        // Normalizar bancos
        $bancos = array_map(function($b) {
            return [
                'ID_BancoDpto' => $b['ID_BancoDpto'] ?? $b['id_bancodpto'] ?? 0,
                'ID_RazonSocial' => $b['ID_RazonSocial'] ?? $b['id_razonsocial'] ?? 0,
                'Banco' => $b['Banco'] ?? $b['banco'] ?? '',
                'Clabe' => $b['Clabe'] ?? $b['clabe'] ?? '',
            ];
        }, $bancosRaw);

        $bancoIds = array_column($bancos, 'ID_BancoDpto');
        $saldosRaw = !empty($bancoIds) ? $saldosModel->whereIn('id_bancodpto', $bancoIds)->where('anio', $anio)->whereIn('mes', $meses)->findAll() : [];

        // Normalizar claves de saldos para Postgres
        $saldos = array_map(function($s) {
            return [
                'id_bancodpto' => $s['id_bancodpto'] ?? $s['ID_BancoDpto'] ?? 0,
                'mes' => $s['mes'] ?? $s['Mes'] ?? 0,
                'saldo_inicial' => $s['saldo_inicial'] ?? $s['Saldo_Inicial'] ?? 0,
                'saldo_final' => $s['saldo_final'] ?? $s['Saldo_Final'] ?? 0,
            ];
        }, $saldosRaw);

        $minMes = min($meses);
        $maxMes = max($meses);

        $estructura = [];
        $rsProcesadasBancos = []; // Para evitar duplicar saldos bancarios en los totales de JS

        foreach ($departamentos as $dpto) {
            $idDpto = (int)$dpto['ID_Dpto'];
            $idRazonSocial = (int)$dpto['ID_RazonSocial'];

            // Obtener desglose de grupos para este departamento
            $analisisGrupos = [];
            $pAsignado = 0; $pComprometido = 0; $pEjecutado = 0;

            foreach ($presupuestos as $p) {
                if ((int)$p['ID_Dpto'] === $idDpto) {
                    $gAsignado = (float)$p['Monto_Asignado'];
                    $gGastado  = (float)$p['Monto_Comprometido'] + (float)$p['Monto_Ejecutado'];
                    
                    $pAsignado += $gAsignado;
                    $pComprometido += (float)$p['Monto_Comprometido'];
                    $pEjecutado += (float)$p['Monto_Ejecutado'];

                    $analisisGrupos[] = [
                        'etiqueta'   => $p['GrupoNombre'] ?? 'Grupo Desconocido',
                        'asignado'   => $gAsignado,
                        'gastado'    => $gGastado,
                        'disponible' => $gAsignado - $gGastado
                    ];
                }
            }

            // Consolidar Bancos (Solo para el primer departamento de cada Razón Social que encontremos)
            $bInicial = 0; $bFinal = 0;
            if (!in_array($idRazonSocial, $rsProcesadasBancos)) {
                foreach ($bancos as $b) {
                    if ((int)$b['ID_RazonSocial'] === $idRazonSocial) {
                        $saldosEsteBanco = array_filter($saldos, fn($item) => 
                            (int)$item['id_bancodpto'] === (int)$b['ID_BancoDpto'] && 
                            in_array((int)$item['mes'], $meses)
                        );

                        if (!empty($saldosEsteBanco)) {
                            usort($saldosEsteBanco, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                            $sMin = reset($saldosEsteBanco);
                            $sMax = end($saldosEsteBanco);
                            
                            $bInicial += (float)($sMin['saldo_inicial'] ?? 0);
                            
                            $fVal = (float)($sMax['saldo_final'] ?? 0);
                            // Fallback: si el mes más reciente no tiene saldo final (es 0), usamos su inicial como saldo actual
                            if ($fVal == 0 && (float)($sMax['saldo_inicial'] ?? 0) != 0) {
                                $fVal = (float)$sMax['saldo_inicial'];
                            }
                            $bFinal += $fVal;
                        }
                    }
                }
                $rsProcesadasBancos[] = $idRazonSocial;
            }

            $totalGasto = $pComprometido + $pEjecutado;
            $disponible = $pAsignado - $totalGasto;

            $estructura[] = [
                'ID_Dpto' => $idDpto,
                'Nombre' => $dpto['Nombre'],
                'PlaceNombre' => $dpto['PlaceNombre'],
                'RazonSocialNombre' => $dpto['RazonSocialNombre'],
                'SegmentoNombre' => $dpto['SegmentoNombre'] ?? 'Sin Segmento',
                'detalles' => $analisisGrupos,
                'presupuesto' => [
                    'asignado' => $pAsignado,
                    'gastado' => $totalGasto,
                    'disponible' => $disponible,
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

    public function getComparativoBancos($idPlace, $anio, $mes)
    {
        $dptoModel = new DepartamentosModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        if (empty($mes)) return $this->respond(['razones' => []]);
        $meses = array_map('intval', explode(',', $mes));
        $minMes = min($meses);
        $maxMes = max($meses);

        // 1. Departamentos (Para saber qué RS y Places están involucrados)
        $query = $dptoModel->select('Departamentos.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial');

        if ($idPlace > 0) $query->where('Departamentos.ID_Place', $idPlace);
        $departamentosRaw = $query->orderBy('RazonSocialNombre', 'ASC')->findAll();

        if (empty($departamentosRaw)) return $this->respond(['razones' => []]);

        // Normalizar departamentos
        $departamentos = array_map(function($d) {
            return [
                'ID_Dpto' => $d['ID_Dpto'] ?? $d['id_dpto'] ?? 0,
                'ID_RazonSocial' => $d['ID_RazonSocial'] ?? $d['id_razonsocial'] ?? 0,
                'PlaceNombre' => $d['PlaceNombre'] ?? $d['placenombre'] ?? '',
                'RazonSocialNombre' => $d['RazonSocialNombre'] ?? $d['razonsocialnombre'] ?? '',
            ];
        }, $departamentosRaw);

        // 2. Obtener Bancos y Saldos de las Razones Sociales encontradas
        $rsIds = array_unique(array_column($departamentos, 'ID_RazonSocial'));
        $bancosRaw = $bancoModel->whereIn('ID_RazonSocial', $rsIds)->findAll();
        
        // Normalizar bancos
        $bancos = array_map(function($b) {
            return [
                'ID_BancoDpto' => $b['ID_BancoDpto'] ?? $b['id_bancodpto'] ?? 0,
                'ID_RazonSocial' => $b['ID_RazonSocial'] ?? $b['id_razonsocial'] ?? 0,
                'Banco' => $b['Banco'] ?? $b['banco'] ?? '',
                'Clabe' => $b['Clabe'] ?? $b['clabe'] ?? '',
            ];
        }, $bancosRaw);

        $bancoIds = array_column($bancos, 'ID_BancoDpto');
        
        $saldosRaw = !empty($bancoIds) 
            ? $saldosModel->whereIn('id_bancodpto', $bancoIds)->where('anio', $anio)->whereIn('mes', $meses)->findAll() 
            : [];

        // Normalizar saldos para Postgres (claves en minúscula)
        $saldos = array_map(function($s) {
            return [
                'id_bancodpto' => $s['id_bancodpto'] ?? $s['ID_BancoDpto'] ?? 0,
                'mes' => $s['mes'] ?? $s['Mes'] ?? 0,
                'saldo_inicial' => $s['saldo_inicial'] ?? $s['Saldo_Inicial'] ?? 0,
                'saldo_final' => $s['saldo_final'] ?? $s['Saldo_Final'] ?? 0
            ];
        }, $saldosRaw);

        // 3. Estructurar por Razón Social
        $resultado = [];
        foreach ($rsIds as $idRS) {
            $nombreRS = '';
            $bancosRS = [];
            $totalInicial = 0; $totalFinal = 0;

            // Filtrar bancos de esta RS
            foreach ($bancos as $b) {
                if ((int)$b['ID_RazonSocial'] === (int)$idRS) {
                    $saldosEsteBanco = array_filter($saldos, fn($item) => 
                        (int)$item['id_bancodpto'] === (int)$b['ID_BancoDpto'] && 
                        in_array((int)$item['mes'], $meses)
                    );

                    if (!empty($saldosEsteBanco)) {
                        usort($saldosEsteBanco, fn($a, $b) => (int)$a['mes'] <=> (int)$b['mes']);
                        $sMin = reset($saldosEsteBanco);
                        $sMax = end($saldosEsteBanco);
                        
                        $inicial = (float)($sMin['saldo_inicial'] ?? 0);
                        $final = (float)($sMax['saldo_final'] ?? 0);
                        
                        // Fallback para meses en curso sin saldo final cerrado
                        if ($final == 0 && (float)($sMax['saldo_inicial'] ?? 0) != 0) {
                            $final = (float)$sMax['saldo_inicial'];
                        }
                        
                        $usado = $inicial - $final;

                        $bancosRS[] = [
                            'banco'   => $b['Banco'],
                            'clabe'   => $b['Clabe'],
                            'inicial' => $inicial,
                            'final'   => $final,
                            'usado'   => $usado,
                            'porcentaje' => $inicial > 0 ? round(($usado / $inicial) * 100, 2) : 0
                        ];
                        $totalInicial += $inicial;
                        $totalFinal += $final;
                    }
                }
            }

            // Obtener nombre de la RS y sus complejos (Places) para contexto
            $placesContexto = [];
            foreach ($departamentos as $d) {
                if ((int)$d['ID_RazonSocial'] === (int)$idRS) {
                    $nombreRS = $d['RazonSocialNombre'];
                    $placesContexto[] = $d['PlaceNombre'];
                }
            }

            $resultado[] = [
                'ID_RazonSocial' => $idRS,
                'Nombre' => $nombreRS,
                'bancos' => $bancosRS,
                'places' => array_values(array_unique($placesContexto)),
                'totales' => [
                    'inicial' => $totalInicial,
                    'final'   => $totalFinal,
                    'usado'   => $totalInicial - $totalFinal,
                    'porcentaje' => $totalInicial > 0 ? round((($totalInicial - $totalFinal) / $totalInicial) * 100, 2) : 0
                ]
            ];
        }

        return $this->respond(['razones' => $resultado]);
    }

    public function getComparativo($idPlace, $anio, $mes)
    {
        $dptoModel = new DepartamentosModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();

        if (empty($mes)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);
        $meses = array_map('intval', explode(',', $mes));

        // 1. Configurar consulta base de departamentos (Incluir ID_RazonSocial)
        $query = $dptoModel->select('Departamentos.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = Departamentos.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        // Si idPlace es > 0, filtramos por ese lugar. Si es 0, traemos TODO.
        if ($idPlace > 0) {
            $query->where('Departamentos.ID_Place', $idPlace);
        }

        $departamentosRaw = $query->orderBy('RazonSocialNombre', 'ASC')
            ->orderBy('PlaceNombre', 'ASC')
            ->orderBy('Nombre', 'ASC')
            ->findAll();

        if (empty($departamentosRaw)) {
            return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);
        }

        // Normalizar departamentos
        $departamentos = array_map(function($d) {
            return [
                'ID_Dpto' => $d['ID_Dpto'] ?? $d['id_dpto'] ?? 0,
                'Nombre' => $d['Nombre'] ?? $d['nombre'] ?? '',
                'ID_Place' => $d['ID_Place'] ?? $d['id_place'] ?? 0,
                'ID_RazonSocial' => $d['ID_RazonSocial'] ?? $d['id_razonsocial'] ?? 0,
                'PlaceNombre' => $d['PlaceNombre'] ?? $d['placenombre'] ?? '',
                'RazonSocialNombre' => $d['RazonSocialNombre'] ?? $d['razonsocialnombre'] ?? '',
                'SegmentoNombre' => $d['SegmentoNombre'] ?? $d['segmentonombre'] ?? 'Sin Segmento',
            ];
        }, $departamentosRaw);

        $dptoIds = array_column($departamentos, 'ID_Dpto');

        // 2. Traer presupuestos agrupados por meses
        $presupuestos = $presupuestoMensualModel
            ->select('PresupuestoMensual.ID_Dpto, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre as GrupoNombre')
            ->selectSum('PresupuestoMensual.Monto_Asignado', 'Monto_Asignado')
            ->selectSum('PresupuestoMensual.Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('PresupuestoMensual.Monto_Ejecutado', 'Monto_Ejecutado')
            ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')
            ->whereIn('PresupuestoMensual.ID_Dpto', $dptoIds)
            ->where('PresupuestoMensual.Anio', $anio)
            ->whereIn('PresupuestoMensual.Mes', $meses)
            ->groupBy('PresupuestoMensual.ID_Dpto, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre')
            ->findAll();

        // Normalizar nombres de columnas para Postgres
        $presupuestos = array_map(function($p) {
            return [
                'ID_Dpto' => $p['ID_Dpto'] ?? $p['id_dpto'] ?? 0,
                'ID_GrupoPresupuestal' => $p['ID_GrupoPresupuestal'] ?? $p['id_grupopresupuestal'] ?? 0,
                'GrupoNombre' => $p['GrupoNombre'] ?? $p['gruponombre'] ?? '',
                'Monto_Asignado' => $p['Monto_Asignado'] ?? $p['monto_asignado'] ?? 0,
                'Monto_Comprometido' => $p['Monto_Comprometido'] ?? $p['monto_comprometido'] ?? 0,
                'Monto_Ejecutado' => $p['Monto_Ejecutado'] ?? $p['monto_ejecutado'] ?? 0,
            ];
        }, $presupuestos);

        $estructura = [];
        $granTotalAsignado = 0;
        $granTotalComprometido = 0;
        $granTotalEjecutado = 0;

        foreach ($departamentos as $dpto) {
            $analisisDpto = [];
            $totalDptoAsignado = 0;
            $totalDptoComprometido = 0;
            $totalDptoEjecutado = 0;

            foreach ($presupuestos as $pm) {
                if ((int)$pm['ID_Dpto'] === (int)$dpto['ID_Dpto']) {
                    
                    $asignado     = (float)$pm['Monto_Asignado'];
                    $comprometido = (float)$pm['Monto_Comprometido'];
                    $ejecutado    = (float)$pm['Monto_Ejecutado'];
                    $totalGasto   = $comprometido + $ejecutado;
                    $disponible   = $asignado - $totalGasto;
                    $porcentaje   = $asignado > 0 ? ($totalGasto / $asignado) * 100 : 0;

                    $analisisDpto[] = [
                        'etiqueta'     => $pm['GrupoNombre'],
                        'asignado'     => $asignado,
                        'comprometido' => $comprometido,
                        'ejecutado'    => $ejecutado,
                        'disponible'   => $disponible,
                        'porcentaje'   => round($porcentaje, 2)
                    ];

                    $totalDptoAsignado += $asignado;
                    $totalDptoComprometido += $comprometido;
                    $totalDptoEjecutado += $ejecutado;
                }
            }

            $totalDptoGasto = $totalDptoComprometido + $totalDptoEjecutado;
            $dpto['detalles'] = $analisisDpto;
            $dpto['totales'] = [
                'asignado'     => $totalDptoAsignado,
                'comprometido' => $totalDptoComprometido,
                'ejecutado'    => $totalDptoEjecutado,
                'disponible'   => $totalDptoAsignado - $totalDptoGasto,
                'porcentaje'   => $totalDptoAsignado > 0 ? round(($totalDptoGasto / $totalDptoAsignado) * 100, 2) : 0
            ];
            $estructura[] = $dpto;

            $granTotalAsignado += $totalDptoAsignado;
            $granTotalComprometido += $totalDptoComprometido;
            $granTotalEjecutado += $totalDptoEjecutado;
        }

        $granTotalGasto = $granTotalComprometido + $granTotalEjecutado;

        return $this->respond([
            'departamentos' => $estructura,
            'totales_generales' => [
                'asignado'     => $granTotalAsignado,
                'comprometido' => $granTotalComprometido,
                'ejecutado'    => $granTotalEjecutado,
                'disponible'   => $granTotalAsignado - $granTotalGasto,
                'porcentaje'   => $granTotalAsignado > 0 ? round(($granTotalGasto / $granTotalAsignado) * 100, 2) : 0
            ]
        ]);
    }

    private function getTotalesCero()
    {
        return [
            'asignado'     => 0,
            'comprometido' => 0,
            'ejecutado'    => 0,
            'disponible'   => 0,
            'porcentaje'   => 0
        ];
    }

    public function getEstructuraSaldos($idRazonSocial, $anio, $mes)
    {
        $rsModel = new RazonSocialModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        // 1. Obtener la Razón Social
        $razonSocialRaw = $rsModel->find($idRazonSocial);
        if (!$razonSocialRaw) {
            return $this->respond(['razones' => []]);
        }
        
        $razonSocial = [
            'ID_RazonSocial' => $razonSocialRaw['ID_RazonSocial'] ?? $razonSocialRaw['id_razonsocial'] ?? $idRazonSocial,
            'Nombre' => $razonSocialRaw['Nombre'] ?? $razonSocialRaw['nombre'] ?? '',
        ];

        // 2. Bancos de esa Razón Social
        $bancosRaw = $bancoModel->where('ID_RazonSocial', $idRazonSocial)
            ->orderBy('Banco', 'ASC')
            ->findAll();

        if (empty($bancosRaw)) {
            return $this->respond(['razones' => []]);
        }

        $bancos = array_map(function($b) {
            return [
                'ID_BancoDpto' => $b['ID_BancoDpto'] ?? $b['id_bancodpto'] ?? 0,
                'ID_RazonSocial' => $b['ID_RazonSocial'] ?? $b['id_razonsocial'] ?? 0,
                'Banco' => $b['Banco'] ?? $b['banco'] ?? '',
                'Clabe' => $b['Clabe'] ?? $b['clabe'] ?? '',
            ];
        }, $bancosRaw);

        $bancoIds = array_column($bancos, 'ID_BancoDpto');

        // 3. Saldos guardados para el mes/año
        $saldosGuardadosRaw = $saldosModel->whereIn('id_bancodpto', $bancoIds)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->findAll();

        $saldosGuardados = array_map(function($s) {
            return [
                'id' => $s['id'] ?? $s['ID'] ?? 0,
                'id_bancodpto' => $s['id_bancodpto'] ?? $s['ID_BancoDpto'] ?? 0,
                'saldo_inicial' => $s['saldo_inicial'] ?? $s['Saldo_Inicial'] ?? 0,
                'saldo_final' => $s['saldo_final'] ?? $s['Saldo_Final'] ?? 0,
            ];
        }, $saldosGuardadosRaw);

        $bancosConSaldos = [];
        foreach ($bancos as $banco) {
            $saldo_inicial = 0;
            $saldo_final = 0;
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

            $bancosConSaldos[] = $banco;
        }

        // Retornamos una estructura agrupada por Razón Social para compatibilidad
        return $this->respond([
            'razones' => [
                [
                    'ID_RazonSocial' => $razonSocial['ID_RazonSocial'],
                    'Nombre' => $razonSocial['Nombre'],
                    'bancos' => $bancosConSaldos
                ]
            ]
        ]);
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
                // Modificado: Se añade join con GrupoPresupuestal para evitar sumar grupos eliminados
                $queryResult = $presupuestoMensualModel
                    ->selectSum('PresupuestoMensual.Monto_Asignado', 'total')
                    ->join('Departamentos d', 'd.ID_Dpto = PresupuestoMensual.ID_Dpto')
                    ->join('Places p', 'p.ID_Place = d.ID_Place')
                    ->join('GrupoPresupuestal gp', 'gp.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal') // INNER JOIN filtro
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