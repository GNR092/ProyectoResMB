<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CatalogoProductosModel;
use App\Models\GrupoPresupuestalModel;
use App\Models\UnidadOperativaModel;
use App\Models\PlacesModel;
use App\Models\SegmentoNegocioModel;
use App\Models\RazonSocialModel;

class MigracionesController extends ResourceController
{
    protected $format = 'json';

    private function validateJerarquia(array $data, string $prefix = ''): ?string
    {
        // RS, Segmento, Place siempre obligatorios (sin *)
        if (empty($data['rs'])) return "Razón Social es obligatoria";
        if (empty($data['seg'])) return "Segmento es obligatorio";
        if (empty($data['place'])) return "Complejo es obligatorio";

        // Validar existen y relación
        $placeModel = new PlacesModel();
        $place = $placeModel->find((int)$data['place']);
        if (!$place) return "Complejo no existe";
        if ((int)$place['ID_RazonSocial'] !== (int)$data['rs']) return "El complejo no pertenece a la Razón Social seleccionada";
        // id_segmento puede ser null, si place tiene segmento debe coincidir
        if (!empty($place['id_segmento']) && (int)$place['id_segmento'] !== (int)$data['seg']) return "El complejo no pertenece al segmento seleccionado";
        // Segmento debe pertenecer a RS
        $segModel = new SegmentoNegocioModel();
        $seg = $segModel->find((int)$data['seg']);
        if (!$seg) return "Segmento no existe";
        if ((int)($seg['id_razon_social'] ?? $seg['ID_RazonSocial'] ?? 0) !== (int)$data['rs']) return "El segmento no pertenece a la Razón Social";

        // UO validation if provided and not *
        if (isset($data['unidad']) && $data['unidad'] !== null && $data['unidad'] !== '' && $data['unidad'] !== '*' ) {
            $uoModel = new UnidadOperativaModel();
            $uo = $uoModel->find((int)$data['unidad']);
            if (!$uo) return "Área de operación no existe";
            if ((int)$uo['ID_Place'] !== (int)$data['place']) return "El área no pertenece al complejo seleccionado";
            // Grupo validation if provided and not *
            if (isset($data['grupo']) && $data['grupo'] !== null && $data['grupo'] !== '' && $data['grupo'] !== '*') {
                $gModel = new GrupoPresupuestalModel();
                $g = $gModel->find((int)$data['grupo']);
                if (!$g) return "Partida no existe";
                if ((int)$g['ID_UnidadOperativa'] !== (int)$data['unidad']) return "La partida no pertenece al área seleccionada";
            }
        } elseif (isset($data['grupo']) && $data['grupo'] !== null && $data['grupo'] !== '' && $data['grupo'] !== '*') {
            // Grupo sin UO no tiene sentido
            return "Si selecciona partida específica debe seleccionar área específica";
        }
        return null;
    }

    private function getNivel(array $origen): string
    {
        // unidad == * or empty => nivel complejo
        // grupo == * => nivel area
        // else nivel partida
        $unidad = $origen['unidad'] ?? null;
        $grupo = $origen['grupo'] ?? null;
        $isUnidadStar = ($unidad === '*' || $unidad === '' || $unidad === null);
        $isGrupoStar = ($grupo === '*' || $grupo === '' || $grupo === null);
        if ($isUnidadStar) return 'complejo';
        if ($isGrupoStar || $grupo === '*') return 'area';
        return 'partida';
    }

    private function buildPreview(array $origen, array $destino): array
    {
        $db = \Config\Database::connect();
        $nivel = $this->getNivel($origen);
        $result = ['nivel' => $nivel, 'origen' => $origen, 'destino' => $destino, 'escenarios' => []];

        if ($nivel === 'partida') {
            $idGrupoOrig = (int)$origen['grupo'];
            $idGrupoDest = (int)$destino['grupo'];
            // conteos
            $origProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $idGrupoOrig)->get()->getResultArray();
            $destProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $idGrupoDest)->get()->getResultArray();
            $destNames = array_map(fn($p) => mb_strtolower(trim($p['Nombre'])), $destProducts);
            $destNamesSet = array_flip($destNames);
            $aInsertar = [];
            foreach ($origProducts as $p) {
                $key = mb_strtolower(trim($p['Nombre']));
                if (!isset($destNamesSet[$key])) $aInsertar[] = $p['Nombre'];
            }
            $result['escenarios'][] = [
                'tipo' => 'partida',
                'origen_grupo' => $idGrupoOrig,
                'destino_grupo' => $idGrupoDest,
                'origen_count' => count($origProducts),
                'destino_count' => count($destProducts),
                'a_insertar' => count($aInsertar),
                'duplicados_ignorados' => count($origProducts) - count($aInsertar),
                'ejemplos' => array_slice($aInsertar, 0, 20)
            ];
            $result['total_a_insertar'] = count($aInsertar);
            $result['total_origen'] = count($origProducts);
        } elseif ($nivel === 'area') {
            $idUnidadOrig = (int)$origen['unidad'];
            $idUnidadDest = (int)$destino['unidad'];
            $gModel = new GrupoPresupuestalModel();
            $gruposOrig = $gModel->where('ID_UnidadOperativa', $idUnidadOrig)->where('activo', true)->findAll();
            // también incluir inactivos si tienen productos? solo activos por ahora
            if (empty($gruposOrig)) {
                // incluir todos si no hay activos
                $gruposOrig = $gModel->where('ID_UnidadOperativa', $idUnidadOrig)->findAll();
            }
            $gruposDest = $gModel->where('ID_UnidadOperativa', $idUnidadDest)->findAll();
            $destGrupoNames = [];
            foreach ($gruposDest as $g) $destGrupoNames[mb_strtolower(trim($g['Nombre']))] = $g['ID_GrupoPresupuestal'];

            $totalOrig = 0; $totalInsert = 0; $gruposACrear = 0; $detalles = [];
            foreach ($gruposOrig as $gOrig) {
                $origCount = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $gOrig['ID_GrupoPresupuestal'])->countAllResults();
                $totalOrig += $origCount;
                $key = mb_strtolower(trim($gOrig['Nombre']));
                $existeEnDest = isset($destGrupoNames[$key]);
                if (!$existeEnDest) $gruposACrear++;
                $destGrupoId = $existeEnDest ? $destGrupoNames[$key] : null;
                // productos a insertar si ya existe destino
                $aInsertar = 0;
                if ($existeEnDest) {
                    $origProds = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $gOrig['ID_GrupoPresupuestal'])->get()->getResultArray();
                    $destProds = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $destGrupoId)->get()->getResultArray();
                    $destNames = array_flip(array_map(fn($p) => mb_strtolower(trim($p['Nombre'])), $destProds));
                    foreach ($origProds as $p) {
                        if (!isset($destNames[mb_strtolower(trim($p['Nombre']))])) $aInsertar++;
                    }
                } else {
                    $aInsertar = $origCount;
                }
                $totalInsert += $aInsertar;
                $detalles[] = [
                    'partida_origen' => $gOrig['Nombre'],
                    'partida_origen_id' => $gOrig['ID_GrupoPresupuestal'],
                    'existe_en_destino' => $existeEnDest,
                    'productos_origen' => $origCount,
                    'productos_a_insertar' => $aInsertar
                ];
            }
            $result['escenarios'][] = [
                'tipo' => 'area',
                'origen_unidad' => $idUnidadOrig,
                'destino_unidad' => $idUnidadDest,
                'grupos_origen' => count($gruposOrig),
                'grupos_a_crear' => $gruposACrear,
                'total_productos_origen' => $totalOrig,
                'total_a_insertar' => $totalInsert,
                'detalles' => $detalles
            ];
            $result['total_a_insertar'] = $totalInsert;
            $result['total_origen'] = $totalOrig;
        } elseif ($nivel === 'complejo') {
            $idPlaceOrig = (int)$origen['place'];
            $idPlaceDest = (int)$destino['place'];
            $uoModel = new UnidadOperativaModel();
            $unidadesOrig = $uoModel->where('ID_Place', $idPlaceOrig)->findAll();
            $unidadesDest = $uoModel->where('ID_Place', $idPlaceDest)->findAll();
            $destUoNames = [];
            foreach ($unidadesDest as $u) $destUoNames[mb_strtolower(trim($u['Nombre']))] = $u['ID_UnidadOperativa'];

            $totalOrig = 0; $totalInsert = 0; $uosACrear = 0; $gruposACrear = 0; $detalles = [];
            $gModel = new GrupoPresupuestalModel();
            foreach ($unidadesOrig as $uoOrig) {
                $gruposOrig = $gModel->where('ID_UnidadOperativa', $uoOrig['ID_UnidadOperativa'])->findAll();
                $keyUo = mb_strtolower(trim($uoOrig['Nombre']));
                $existeUo = isset($destUoNames[$keyUo]);
                if (!$existeUo) $uosACrear++;
                $destUoId = $existeUo ? $destUoNames[$keyUo] : null;

                foreach ($gruposOrig as $gOrig) {
                    $origCount = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $gOrig['ID_GrupoPresupuestal'])->countAllResults();
                    $totalOrig += $origCount;
                    if (!$existeUo) {
                        // si UO no existe, todo se insertará
                        $totalInsert += $origCount;
                        $gruposACrear++;
                    } else {
                        // UO existe, verificar grupo
                        $destGrupos = $gModel->where('ID_UnidadOperativa', $destUoId)->findAll();
                        $destGrupoNames = [];
                        foreach ($destGrupos as $dg) $destGrupoNames[mb_strtolower(trim($dg['Nombre']))] = $dg['ID_GrupoPresupuestal'];
                        $keyG = mb_strtolower(trim($gOrig['Nombre']));
                        $existeG = isset($destGrupoNames[$keyG]);
                        if (!$existeG) {
                            $gruposACrear++;
                            $totalInsert += $origCount;
                        } else {
                            $destGId = $destGrupoNames[$keyG];
                            $origProds = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $gOrig['ID_GrupoPresupuestal'])->get()->getResultArray();
                            $destProds = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $destGId)->get()->getResultArray();
                            $destNames = array_flip(array_map(fn($p) => mb_strtolower(trim($p['Nombre'])), $destProds));
                            $cnt = 0;
                            foreach ($origProds as $p) if (!isset($destNames[mb_strtolower(trim($p['Nombre']))])) $cnt++;
                            $totalInsert += $cnt;
                        }
                    }
                }
                $detalles[] = [
                    'area_origen' => $uoOrig['Nombre'],
                    'area_origen_id' => $uoOrig['ID_UnidadOperativa'],
                    'existe_en_destino' => $existeUo,
                    'grupos_en_area' => count($gruposOrig)
                ];
            }
            $result['escenarios'][] = [
                'tipo' => 'complejo',
                'origen_place' => $idPlaceOrig,
                'destino_place' => $idPlaceDest,
                'unidades_origen' => count($unidadesOrig),
                'unidades_a_crear' => $uosACrear,
                'grupos_a_crear' => $gruposACrear,
                'total_productos_origen' => $totalOrig,
                'total_a_insertar' => $totalInsert,
                'detalles' => $detalles
            ];
            $result['total_a_insertar'] = $totalInsert;
            $result['total_origen'] = $totalOrig;
        }

        // hash para idempotencia simple
        $result['preview_hash'] = md5(json_encode([$origen, $destino, $nivel, time()]));
        return $result;
    }

    public function preview()
    {
        $json = $this->request->getJSON(true);
        if (!$json || !isset($json['origen']) || !isset($json['destino'])) {
            return $this->failValidationErrors('Se requiere origen y destino');
        }
        $origen = $json['origen'];
        $destino = $json['destino'];

        // Normalizar '*' sentinel: empty unidad/grupo = '*'
        // El frontend envía '*' explicito, lo respetamos
        $err = $this->validateJerarquia($origen, 'origen');
        if ($err) return $this->failValidationErrors("Origen: $err");
        $err = $this->validateJerarquia($destino, 'destino');
        if ($err) return $this->failValidationErrors("Destino: $err");

        $nivelOrig = $this->getNivel($origen);
        $nivelDest = $this->getNivel($destino);

        // Validar compatibilidad niveles
        if ($nivelOrig === 'partida' && $nivelDest !== 'partida') {
            return $this->failValidationErrors('Si el origen es una partida específica, el destino debe ser también una partida específica');
        }
        if ($nivelOrig === 'area' && $nivelDest === 'complejo') {
            return $this->failValidationErrors('Si el origen es un área (todas las partidas), el destino debe ser un área específica, no un complejo entero');
        }
        // si origen complejo, destino debe ser complejo (ambos con * en area)
        if ($nivelOrig === 'complejo' && $nivelDest !== 'complejo') {
            // destino complejo significa area=* . Si destino es area o partida es error
            return $this->failValidationErrors('Si el origen es un complejo completo, el destino debe ser también un complejo (deje área y partida en "Migrar desde este punto")');
        }

        // No auto-migración mismo origen=destino exacto en nivel partida
        if ($nivelOrig === 'partida' && (int)$origen['grupo'] === (int)$destino['grupo']) {
            return $this->failValidationErrors('Origen y destino no pueden ser la misma partida');
        }
        if ($nivelOrig === 'area' && (int)$origen['unidad'] === (int)$destino['unidad']) {
            return $this->failValidationErrors('Origen y destino no pueden ser la misma área');
        }
        if ($nivelOrig === 'complejo' && (int)$origen['place'] === (int)$destino['place']) {
            return $this->failValidationErrors('Origen y destino no pueden ser el mismo complejo');
        }

        $preview = $this->buildPreview($origen, $destino);
        // guardar hash en sesión para validar ejecutar
        session()->set('migracion_preview_' . $preview['preview_hash'], json_encode(['origen' => $origen, 'destino' => $destino]));

        return $this->respond(['success' => true, 'preview' => $preview]);
    }

    public function ejecutar()
    {
        $json = $this->request->getJSON(true);
        if (!$json || !isset($json['origen']) || !isset($json['destino'])) {
            return $this->failValidationErrors('Se requiere origen y destino');
        }
        $origen = $json['origen'];
        $destino = $json['destino'];
        $previewHash = $json['preview_hash'] ?? null;

        // Validar jerarquías de nuevo
        $err = $this->validateJerarquia($origen, 'origen');
        if ($err) return $this->failValidationErrors("Origen: $err");
        $err = $this->validateJerarquia($destino, 'destino');
        if ($err) return $this->failValidationErrors("Destino: $err");

        $nivel = $this->getNivel($origen);

        // Verificar preview previo si se proporciona hash
        if ($previewHash) {
            $stored = session()->get('migracion_preview_' . $previewHash);
            if (!$stored) {
                // No es fatal, solo advertencia, continuamos
                log_message('warning', "[Migraciones] Preview hash no encontrado en sesión, continuando: $previewHash");
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $res = $this->doEjecutar($origen, $destino, $nivel);
            $db->transComplete();
            if ($db->transStatus() === false) throw new \Exception('Error en transacción');

            // limpiar preview
            if ($previewHash) session()->remove('migracion_preview_' . $previewHash);

            // auditoría
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion' => 'MIGRACION_CATALOGO',
                'modulo' => 'Catalogos',
                'estado' => 'exito',
                'valores_nuevos' => json_encode(['nivel' => $nivel, 'origen' => $origen, 'destino' => $destino, 'resultado' => $res])
            ]);

            return $this->respond(['success' => true, 'message' => 'Migración completada', 'resultado' => $res]);
        } catch (\Throwable $e) {
            $db->transRollback();
            \CodeIgniter\Events\Events::trigger('auditoria', [
                'tipo_accion' => 'MIGRACION_CATALOGO',
                'modulo' => 'Catalogos',
                'estado' => 'fallido',
                'valores_nuevos' => json_encode(['error' => $e->getMessage(), 'origen' => $origen, 'destino' => $destino])
            ]);
            log_message('error', '[Migraciones ejecutar] ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    private function doEjecutar(array $origen, array $destino, string $nivel): array
    {
        $db = \Config\Database::connect();

        $destRs = (int)$destino['rs'];
        $destSeg = (int)$destino['seg'];
        $destPlace = (int)$destino['place'];

        $stats = ['nivel' => $nivel, 'insertados' => 0, 'grupos_creados' => 0, 'unidades_creadas' => 0, 'duplicados_ignorados' => 0];

        // Helper para insertar producto sin pasar por Model (evita AuditTrait / PK issues)
        $insertProducto = function(array $data) use ($db) {
            $db->table('Catalogo_Productos')->insert($data);
        };
        // Helper para crear GrupoPresupuestal con PK manual (tabla sin auto-increment)
        $crearGrupo = function(array $data) use ($db) {
            $next = (int)($db->table('GrupoPresupuestal')->selectMax('ID_GrupoPresupuestal','mx')->get()->getRow()->mx ?? 0) + 1;
            // Evitar colisión si otro proceso insertó entre SELECT y INSERT
            while ($db->table('GrupoPresupuestal')->where('ID_GrupoPresupuestal', $next)->countAllResults() > 0) $next++;
            $data['ID_GrupoPresupuestal'] = $next;
            $db->table('GrupoPresupuestal')->insert($data);
            try { $db->query("SELECT setval(pg_get_serial_sequence('\"GrupoPresupuestal\"','ID_GrupoPresupuestal'), (SELECT MAX(\"ID_GrupoPresupuestal\") FROM \"GrupoPresupuestal\"), true)"); } catch(\Throwable $e){}
            return $next;
        };
        $crearUnidad = function(array $data) use ($db) {
            $db->table('UnidadOperativa')->insert($data);
            $id = $db->insertID();
            try { $db->query("SELECT setval(pg_get_serial_sequence('\"UnidadOperativa\"','ID_UnidadOperativa'), (SELECT MAX(\"ID_UnidadOperativa\") FROM \"UnidadOperativa\"), true)"); } catch(\Throwable $e){}
            return $id;
        };

        if ($nivel === 'partida') {
            $idGrupoOrig = (int)$origen['grupo'];
            $idGrupoDest = (int)$destino['grupo'];
            $destUnidad = (int)$destino['unidad'];

            $origProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $idGrupoOrig)->get()->getResultArray();
            $destProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $idGrupoDest)->get()->getResultArray();
            $destNames = array_flip(array_map(fn($p) => mb_strtolower(trim($p['Nombre'])), $destProducts));

            foreach ($origProducts as $p) {
                $key = mb_strtolower(trim($p['Nombre']));
                if (isset($destNames[$key])) {
                    $stats['duplicados_ignorados']++;
                    continue;
                }
                $insertProducto([
                    'ID_RazonSocial' => $destRs,
                    'id_segmento' => $destSeg,
                    'ID_Place' => $destPlace,
                    'ID_Dpto' => $destUnidad,
                    'ID_GrupoPresupuestal' => $idGrupoDest,
                    'Nombre' => $p['Nombre']
                ]);
                $stats['insertados']++;
            }
        } elseif ($nivel === 'area') {
            $idUnidadOrig = (int)$origen['unidad'];
            $idUnidadDest = (int)$destino['unidad'];

            $gruposOrig = $db->table('GrupoPresupuestal')->where('ID_UnidadOperativa', $idUnidadOrig)->get()->getResultArray();
            if (empty($gruposOrig)) throw new \Exception('El área origen no tiene partidas');

            // Mapa destino grupos por nombre
            $gruposDest = $db->table('GrupoPresupuestal')->where('ID_UnidadOperativa', $idUnidadDest)->get()->getResultArray();
            $destGrupoMap = [];
            foreach ($gruposDest as $g) $destGrupoMap[mb_strtolower(trim($g['Nombre']))] = $g;

            foreach ($gruposOrig as $gOrig) {
                $key = mb_strtolower(trim($gOrig['Nombre']));
                $gDest = $destGrupoMap[$key] ?? null;
                $idGrupoDest = null;
                if (!$gDest) {
                    // Crear partida con PK manual
                    $idGrupoDest = $crearGrupo([
                        'Nombre' => $gOrig['Nombre'],
                        'Descripcion' => $gOrig['Descripcion'] ?? null,
                        'ID_UnidadOperativa' => $idUnidadDest,
                        'activo' => $gOrig['activo'] ?? true,
                        'es_manual' => $gOrig['es_manual'] ?? false
                    ]);
                    $stats['grupos_creados']++;
                    // actualizar mapa
                    $destGrupoMap[$key] = ['ID_GrupoPresupuestal' => $idGrupoDest];
                } else {
                    $idGrupoDest = $gDest['ID_GrupoPresupuestal'] ?? $gDest['id_grupopresupuestal'] ?? null;
                }

                // Copiar productos de esta partida
                $origProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $gOrig['ID_GrupoPresupuestal'])->get()->getResultArray();
                $destProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $idGrupoDest)->get()->getResultArray();
                $destNames = array_flip(array_map(fn($p) => mb_strtolower(trim($p['Nombre'])), $destProducts));
                foreach ($origProducts as $p) {
                    $k = mb_strtolower(trim($p['Nombre']));
                    if (isset($destNames[$k])) { $stats['duplicados_ignorados']++; continue; }
                    $insertProducto([
                        'ID_RazonSocial' => $destRs,
                        'id_segmento' => $destSeg,
                        'ID_Place' => $destPlace,
                        'ID_Dpto' => $idUnidadDest,
                        'ID_GrupoPresupuestal' => $idGrupoDest,
                        'Nombre' => $p['Nombre']
                    ]);
                    $stats['insertados']++;
                    $destNames[$k] = 1;
                }
            }
        } elseif ($nivel === 'complejo') {
            $idPlaceOrig = (int)$origen['place'];
            $idPlaceDest = (int)$destino['place'];

            $unidadesOrig = $db->table('UnidadOperativa')->where('ID_Place', $idPlaceOrig)->get()->getResultArray();
            if (empty($unidadesOrig)) throw new \Exception('El complejo origen no tiene áreas');

            $unidadesDest = $db->table('UnidadOperativa')->where('ID_Place', $idPlaceDest)->get()->getResultArray();
            $destUoMap = [];
            foreach ($unidadesDest as $u) $destUoMap[mb_strtolower(trim($u['Nombre']))] = $u;

            foreach ($unidadesOrig as $uoOrig) {
                $keyUo = mb_strtolower(trim($uoOrig['Nombre']));
                $uoDest = $destUoMap[$keyUo] ?? null;
                $idUnidadDest = null;
                if (!$uoDest) {
                    $idUnidadDest = $crearUnidad([
                        'Nombre' => $uoOrig['Nombre'],
                        'ID_Place' => $idPlaceDest,
                        'activo' => $uoOrig['activo'] ?? true
                    ]);
                    $stats['unidades_creadas']++;
                    // Crear mapa temporal
                    $destUoMap[$keyUo] = ['ID_UnidadOperativa' => $idUnidadDest, 'Nombre' => $uoOrig['Nombre']];
                } else {
                    $idUnidadDest = $uoDest['ID_UnidadOperativa'] ?? $uoDest['id_unidadoperativa'] ?? null;
                }

                // Clonar partidas de esta UO
                $gruposOrig = $db->table('GrupoPresupuestal')->where('ID_UnidadOperativa', $uoOrig['ID_UnidadOperativa'])->get()->getResultArray();
                $gruposDest = $db->table('GrupoPresupuestal')->where('ID_UnidadOperativa', $idUnidadDest)->get()->getResultArray();
                $destGrupoMap = [];
                foreach ($gruposDest as $g) $destGrupoMap[mb_strtolower(trim($g['Nombre']))] = $g;

                foreach ($gruposOrig as $gOrig) {
                    $keyG = mb_strtolower(trim($gOrig['Nombre']));
                    $gDest = $destGrupoMap[$keyG] ?? null;
                    $idGrupoDest = null;
                    if (!$gDest) {
                        $idGrupoDest = $crearGrupo([
                            'Nombre' => $gOrig['Nombre'],
                            'Descripcion' => $gOrig['Descripcion'] ?? null,
                            'ID_UnidadOperativa' => $idUnidadDest,
                            'activo' => $gOrig['activo'] ?? true,
                            'es_manual' => $gOrig['es_manual'] ?? false
                        ]);
                        $stats['grupos_creados']++;
                        $destGrupoMap[$keyG] = ['ID_GrupoPresupuestal' => $idGrupoDest];
                    } else {
                        $idGrupoDest = $gDest['ID_GrupoPresupuestal'] ?? $gDest['id_grupopresupuestal'] ?? null;
                    }

                    $origProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $gOrig['ID_GrupoPresupuestal'])->get()->getResultArray();
                    $destProducts = $db->table('Catalogo_Productos')->where('ID_GrupoPresupuestal', $idGrupoDest)->get()->getResultArray();
                    $destNames = array_flip(array_map(fn($p) => mb_strtolower(trim($p['Nombre'])), $destProducts));
                    foreach ($origProducts as $p) {
                        $k = mb_strtolower(trim($p['Nombre']));
                        if (isset($destNames[$k])) { $stats['duplicados_ignorados']++; continue; }
                        $insertProducto([
                            'ID_RazonSocial' => $destRs,
                            'id_segmento' => $destSeg,
                            'ID_Place' => $idPlaceDest,
                            'ID_Dpto' => $idUnidadDest,
                            'ID_GrupoPresupuestal' => $idGrupoDest,
                            'Nombre' => $p['Nombre']
                        ]);
                        $stats['insertados']++;
                        $destNames[$k] = 1;
                    }
                }
            }
        }

        return $stats;
    }
}
