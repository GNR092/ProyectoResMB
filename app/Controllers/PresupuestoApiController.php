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
        $unidadModel = new UnidadOperativaModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();
        $bancoModel = new BancoDptoModel();
        $saldosModel = new SaldosBancariosModel();

        if (empty($mes)) return $this->respond(['departamentos' => []]);
        $meses = array_map('intval', explode(',', $mes));

        // 1. Obtener estructura base (Unidad Operativa como pivote)
        $query = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if ($idPlace > 0) $query->where('UnidadOperativa.ID_Place', $idPlace);
        $unidadesRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('UnidadOperativa.Nombre', 'ASC')->findAll();

        if (empty($unidadesRaw)) return $this->respond(['departamentos' => []]);

        // Normalizar unidades
        $unidades = array_map(function($u) {
            return [
                'ID_UnidadOperativa' => $u['ID_UnidadOperativa'] ?? $u['id_unidadoperativa'] ?? 0,
                'Nombre' => $u['Nombre'] ?? $u['nombre'] ?? '',
                'ID_Place' => $u['ID_Place'] ?? $u['id_place'] ?? 0,
                'ID_RazonSocial' => $u['ID_RazonSocial'] ?? $u['id_razonsocial'] ?? 0,
                'PlaceNombre' => $u['PlaceNombre'] ?? $u['placenombre'] ?? '',
                'RazonSocialNombre' => $u['RazonSocialNombre'] ?? $u['razonsocialnombre'] ?? '',
                'SegmentoNombre' => $u['SegmentoNombre'] ?? $u['segmentonombre'] ?? 'Sin Segmento',
            ];
        }, $unidadesRaw);

        $unidadIds = array_column($unidades, 'ID_UnidadOperativa');

        // 2. Obtener Datos de Presupuesto por Unidad Operativa
        $presupuestos = $presupuestoMensualModel
            ->select('PresupuestoMensual.ID_UnidadOperativa, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre as GrupoNombre')
            ->selectSum('PresupuestoMensual.Monto_Asignado', 'Monto_Asignado')
            ->selectSum('PresupuestoMensual.Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('PresupuestoMensual.Monto_Ejecutado', 'Monto_Ejecutado')
            ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')
            ->whereIn('PresupuestoMensual.ID_UnidadOperativa', $unidadIds)
            ->where('PresupuestoMensual.Anio', $anio)
            ->whereIn('PresupuestoMensual.Mes', $meses)
            ->groupBy('PresupuestoMensual.ID_UnidadOperativa, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre')
            ->findAll();

        // Normalizar presupuestos
        $presupuestos = array_map(function($p) {
            return [
                'ID_UnidadOperativa' => $p['ID_UnidadOperativa'] ?? $p['id_unidadoperativa'] ?? 0,
                'ID_GrupoPresupuestal' => $p['ID_GrupoPresupuestal'] ?? $p['id_grupopresupuestal'] ?? 0,
                'GrupoNombre' => $p['GrupoNombre'] ?? $p['gruponombre'] ?? '',
                'Monto_Asignado' => $p['Monto_Asignado'] ?? $p['monto_asignado'] ?? 0,
                'Monto_Comprometido' => $p['Monto_Comprometido'] ?? $p['monto_comprometido'] ?? 0,
                'Monto_Ejecutado' => $p['Monto_Ejecutado'] ?? $p['monto_ejecutado'] ?? 0,
            ];
        }, $presupuestos);

        // 3. Obtener Datos de Bancos (Sigue por Razón Social)
        $rsIds = array_unique(array_filter(array_column($unidades, 'ID_RazonSocial')));
        $bancosRaw = !empty($rsIds) ? $bancoModel->whereIn('ID_RazonSocial', $rsIds)->findAll() : [];
        
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

        $saldos = array_map(function($s) {
            return [
                'id_bancodpto' => $s['id_bancodpto'] ?? $s['ID_BancoDpto'] ?? 0,
                'mes' => $s['mes'] ?? $s['Mes'] ?? 0,
                'saldo_inicial' => $s['saldo_inicial'] ?? $s['Saldo_Inicial'] ?? 0,
                'saldo_final' => $s['saldo_final'] ?? $s['Saldo_Final'] ?? 0,
            ];
        }, $saldosRaw);

        $estructura = [];
        $rsProcesadasBancos = []; 

        foreach ($unidades as $uni) {
            $idUnidad = (int)$uni['ID_UnidadOperativa'];
            $idRazonSocial = (int)$uni['ID_RazonSocial'];

            $analisisGrupos = [];
            $pAsignado = 0; $pComprometido = 0; $pEjecutado = 0;

            foreach ($presupuestos as $p) {
                if ((int)$p['ID_UnidadOperativa'] === $idUnidad) {
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
                'Nombre' => $uni['Nombre'], // Ahora es el nombre de la Unidad Operativa
                'PlaceNombre' => $uni['PlaceNombre'],
                'RazonSocialNombre' => $uni['RazonSocialNombre'],
                'SegmentoNombre' => $uni['SegmentoNombre'],
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

        return $this->respond(['departamentos' => $estructura]); // Mantenemos la llave 'departamentos' para no romper el frontend por ahora
    }

    public function getComparativo($idPlace, $anio, $mes)
    {
        $unidadModel = new UnidadOperativaModel();
        $presupuestoMensualModel = new PresupuestoMensualModel();

        if (empty($mes)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);
        $meses = array_map('intval', explode(',', $mes));

        $query = $unidadModel->select('UnidadOperativa.*, Places.Nombre_Corto as PlaceNombre, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre, segmento_negocio.nombre as SegmentoNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')
            ->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial')
            ->join('segmento_negocio', 'segmento_negocio.id = Places.id_segmento', 'left');

        if ($idPlace > 0) $query->where('UnidadOperativa.ID_Place', $idPlace);
        $unidadesRaw = $query->orderBy('RazonSocialNombre', 'ASC')->orderBy('PlaceNombre', 'ASC')->orderBy('Nombre', 'ASC')->findAll();

        if (empty($unidadesRaw)) return $this->respond(['departamentos' => [], 'totales_generales' => $this->getTotalesCero()]);

        $unidades = array_map(function($u) {
            return [
                'ID_UnidadOperativa' => $u['ID_UnidadOperativa'] ?? $u['id_unidadoperativa'] ?? 0,
                'Nombre' => $u['Nombre'] ?? $u['nombre'] ?? '',
                'ID_Place' => $u['ID_Place'] ?? $u['id_place'] ?? 0,
                'ID_RazonSocial' => $u['ID_RazonSocial'] ?? $u['id_razonsocial'] ?? 0,
                'PlaceNombre' => $u['PlaceNombre'] ?? $u['placenombre'] ?? '',
                'RazonSocialNombre' => $u['RazonSocialNombre'] ?? $u['razonsocialnombre'] ?? '',
                'SegmentoNombre' => $u['SegmentoNombre'] ?? $u['segmentonombre'] ?? 'Sin Segmento',
            ];
        }, $unidadesRaw);

        $unidadIds = array_column($unidades, 'ID_UnidadOperativa');

        $presupuestos = $presupuestoMensualModel
            ->select('PresupuestoMensual.ID_UnidadOperativa, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre as GrupoNombre')
            ->selectSum('PresupuestoMensual.Monto_Asignado', 'Monto_Asignado')
            ->selectSum('PresupuestoMensual.Monto_Comprometido', 'Monto_Comprometido')
            ->selectSum('PresupuestoMensual.Monto_Ejecutado', 'Monto_Ejecutado')
            ->join('GrupoPresupuestal', 'GrupoPresupuestal.ID_GrupoPresupuestal = PresupuestoMensual.ID_GrupoPresupuestal')
            ->whereIn('PresupuestoMensual.ID_UnidadOperativa', $unidadIds)
            ->where('PresupuestoMensual.Anio', $anio)
            ->whereIn('PresupuestoMensual.Mes', $meses)
            ->groupBy('PresupuestoMensual.ID_UnidadOperativa, PresupuestoMensual.ID_GrupoPresupuestal, GrupoPresupuestal.Nombre')
            ->findAll();

        $presupuestos = array_map(function($p) {
            return [
                'ID_UnidadOperativa' => $p['ID_UnidadOperativa'] ?? $p['id_unidadoperativa'] ?? 0,
                'ID_GrupoPresupuestal' => $p['ID_GrupoPresupuestal'] ?? $p['id_grupopresupuestal'] ?? 0,
                'GrupoNombre' => $p['GrupoNombre'] ?? $p['gruponombre'] ?? '',
                'Monto_Asignado' => $p['Monto_Asignado'] ?? $p['monto_asignado'] ?? 0,
                'Monto_Comprometido' => $p['Monto_Comprometido'] ?? $p['monto_comprometido'] ?? 0,
                'Monto_Ejecutado' => $p['Monto_Ejecutado'] ?? $p['monto_ejecutado'] ?? 0,
            ];
        }, $presupuestos);

        $estructura = [];
        $gtAsignado = 0; $gtComprometido = 0; $gtEjecutado = 0;

        foreach ($unidades as $uni) {
            $analisis = [];
            $tUniAsignado = 0; $tUniComprometido = 0; $tUniEjecutado = 0;

            foreach ($presupuestos as $pm) {
                if ((int)$pm['ID_UnidadOperativa'] === (int)$uni['ID_UnidadOperativa']) {
                    $asignado = (float)$pm['Monto_Asignado'];
                    $comprometido = (float)$pm['Monto_Comprometido'];
                    $ejecutado = (float)$pm['Monto_Ejecutado'];
                    $totalGasto = $comprometido + $ejecutado;

                    $analisis[] = [
                        'etiqueta'     => $pm['GrupoNombre'],
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
        return $this->respond($model->getPendientes());
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

        $solicitudesModel = new \App\Models\SolicitudesCambioPresupuestoModel();

        if ($solicitudesModel->insert([
            'ID_Usuario'    => session('id'),
            'Modulo'        => 'PresupuestoMensual',
            'Accion'        => 'Masivo',
            'ID_Afectado'   => "{$anio}-{$mes}",
            'Datos_Payload' => json_encode($json),
            'Datos_Antiguos'=> null, // En masivo, calcular el diff dinámico es mejor en UI
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
        $bancosConSaldos = [];
        foreach ($bancosRaw as $b) {
            $si = 0; $sf = 0; $idEx = null;
            foreach ($saldosRaw as $s) { if ((int)$s['id_bancodpto'] === (int)$b['ID_BancoDpto']) { $si = $s['saldo_inicial']; $sf = $s['saldo_final']; $idEx = $s['id']; break; } }
            $b['saldo_inicial'] = $si; $b['saldo_final'] = $sf; $b['id_saldo_existente'] = $idEx;
            $bancosConSaldos[] = $b;
        }
        return $this->respond(['razones' => [['ID_RazonSocial' => $idRazonSocial, 'Nombre' => $rsRaw['Nombre'], 'bancos' => $bancosConSaldos]]]);
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

    public function getComparativoBancos($idPlace, $anio, $mes) {
        $uniModel = new UnidadOperativaModel(); $bModel = new BancoDptoModel(); $sModel = new SaldosBancariosModel();
        if (empty($mes)) return $this->respond(['razones' => []]);
        $meses = array_map('intval', explode(',', $mes));
        $unidades = $uniModel->select('UnidadOperativa.ID_Place, Places.ID_RazonSocial, Razon_Social.Nombre as RazonSocialNombre')
            ->join('Places', 'Places.ID_Place = UnidadOperativa.ID_Place')->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Places.ID_RazonSocial');
        if ($idPlace > 0) $unidades->where('UnidadOperativa.ID_Place', $idPlace);
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
