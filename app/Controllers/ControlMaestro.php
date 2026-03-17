<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\GenerarPDF;
use App\Libraries\Status;
use CodeIgniter\API\ResponseTrait;

class ControlMaestro extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    public function update_master($id_solicitud)
    {
        $post = $this->request->getPost();

        // MAPA DE NIVELES
        $niveles = [
            'En espera'            => 1,
            'Cotizando'            => 2,
            'En revision'          => 3,
            'Aprobacion pendiente' => 3,
            'Aprobada'             => 4, // Sin Orden
            'Espera_Programacion'  => 5, // Existe Orden, Sin Requisición PDF
            'Programada'           => 6, // Existe Orden + Requisición PDF
            'Por Pagar'            => 7,
            'Pagada'               => 8
        ];

        // 0. PRE-LECTURA
        $solicitudOriginal = $this->db->table('Solicitud')
            // AGREGAR 'No_Folio' AL SELECT
            ->select('ID_Solicitud, ID_Dpto, ID_Proveedor, Tipo, ID_RazonSocial, MetodoPago, Fecha, IVA, Estado, No_Folio')
            ->where('ID_Solicitud', $id_solicitud)
            ->get()
            ->getRow();

        if (!$solicitudOriginal) {
            return $this->failNotFound('La solicitud no existe.');
        }

        // --- SNAPSHOT PRESUPUESTAL PREVIO ---
        $nivelAnterior = $niveles[$solicitudOriginal->Estado] ?? 0;
        $esMateriales = (int)$solicitudOriginal->Tipo === 1;
        $montosViejosPorGrupo = [];
        
        if ($esMateriales && $nivelAnterior >= 4) {
            $prodsPrevios = $this->db->table('Solicitud_Producto')->where('ID_Solicitud', $id_solicitud)->get()->getResultArray();
            foreach ($prodsPrevios as $pp) {
                if ($pp['ID_GrupoPresupuestal']) {
                    $montosViejosPorGrupo[$pp['ID_GrupoPresupuestal']] = ($montosViejosPorGrupo[$pp['ID_GrupoPresupuestal']] ?? 0) + (float)$pp['Monto_Comprometido_Original'];
                }
            }
        }

        $this->db->transException(true)->transStart();

        try {
            // ---------------------------------------------------------
            // 1. GESTIÓN DE ESTADOS
            // ---------------------------------------------------------
            $nuevoEstadoStr = $post['Estado'];
            $nivelNuevo     = $niveles[$nuevoEstadoStr] ?? 0;

            $estadoSolicitud = $nuevoEstadoStr;
            $estadoOrden     = null;

            if ($nivelNuevo >= 5) {
                $estadoSolicitud = 'Aprobada';
                $estadoOrden     = $nuevoEstadoStr;
            } elseif ($nivelNuevo == 4) {
                $estadoSolicitud = 'Aprobada';
            }

            // ---------------------------------------------------------
            // 2. DETECCIÓN DE CAMBIOS
            // ---------------------------------------------------------
            $idProveedor = $solicitudOriginal->ID_Proveedor;
            $cambioProveedor = false;

            if (array_key_exists('ID_Proveedor', $post)) {
                $val = $post['ID_Proveedor'];
                if (is_numeric($val)) {
                    $idProveedor = intval($val);
                    if ($idProveedor != $solicitudOriginal->ID_Proveedor) {
                        $cambioProveedor = true;
                    }
                }
            }

            $idRazonSocial = (isset($post['ID_RazonSocial']) && is_numeric($post['ID_RazonSocial'])) ? intval($post['ID_RazonSocial']) : $solicitudOriginal->ID_RazonSocial;
            $idDptoActual = $solicitudOriginal->ID_Dpto ?? null; // Nota: si se permitiera cambiar depto, habría que manejarlo aquí.
            $metodoPago = isset($post['MetodoPago']) ? $post['MetodoPago'] : $solicitudOriginal->MetodoPago;
            $fechaRegistro = $solicitudOriginal->Fecha;
            $fechaRefPago       = empty($post['FechaRefPago']) ? null : $post['FechaRefPago'];
            $fechaPagoRealizado = empty($post['FechaPagoRealizado']) ? null : $post['FechaPagoRealizado'];

            // ---------------------------------------------------------
            // 3. LIMPIEZA DE ARCHIVOS
            // ---------------------------------------------------------
            $rowCot = $this->db->table('Cotizacion')->select('ID_Cotizacion, Cotizacion_Files')->where('ID_Solicitud', $id_solicitud)->get()->getRow();
            $idCotizacion = $rowCot ? $rowCot->ID_Cotizacion : null;

            $rowOrd = null;
            if ($idCotizacion) {
                $rowOrd = $this->db->table('OrdenCompra')->select('ID_OrdenCompra, File_Comprobante, File_Factura, File_ReqPag')->where('ID_Cotizacion', $idCotizacion)->get()->getRow();
            }

            // CASO A: Bajamos de nivel "Espera Programación" (5) -> Eliminamos la Orden completa
            if ($nivelNuevo < 5 && $rowOrd) {
                // Borramos Factura y Ficha
                if (!empty($rowOrd->File_Factura)) @unlink(WRITEPATH . 'uploads/facturas/' . $rowOrd->File_Factura);
                if (!empty($rowOrd->File_Comprobante)) @unlink(WRITEPATH . 'uploads/comprobantes/' . $rowOrd->File_Comprobante);

                $folioPdf = $solicitudOriginal->No_Folio;
                $pathOrdenPdf = WRITEPATH . 'uploads/pdf_ordenes/OrdenCompra-' . $folioPdf . '.pdf';
                if (file_exists($pathOrdenPdf)) {
                    @unlink($pathOrdenPdf);
                }

                $this->db->table('OrdenCompra')->where('ID_OrdenCompra', $rowOrd->ID_OrdenCompra)->delete();
                $rowOrd = null;
            }

            // CASO B: Fase de Orden (Nivel >= 5)
            elseif ($rowOrd) {
                // Limpieza Factura si bajamos de Pagada
                if (!empty($rowOrd->File_Factura)) {
                    if ($cambioProveedor || ($nivelNuevo > 0 && $nivelNuevo < 8)) {
                        @unlink(WRITEPATH . 'uploads/facturas/' . $rowOrd->File_Factura);
                        $this->db->table('OrdenCompra')->where('ID_OrdenCompra', $rowOrd->ID_OrdenCompra)->update(['File_Factura' => null]);
                    }
                }
                // Limpieza Ficha si bajamos de Por Pagar
                if (!empty($rowOrd->File_Comprobante)) {
                    if ($cambioProveedor || ($nivelNuevo > 0 && $nivelNuevo < 7)) {
                        @unlink(WRITEPATH . 'uploads/comprobantes/' . $rowOrd->File_Comprobante);
                        $this->db->table('OrdenCompra')->where('ID_OrdenCompra', $rowOrd->ID_OrdenCompra)->update(['File_Comprobante' => null]);
                    }
                }
            }

            // Limpieza Cotización
            if (($nivelNuevo > 0 && $nivelNuevo < 3) && $rowCot && !empty($rowCot->Cotizacion_Files)) {
                $files = explode(',', $rowCot->Cotizacion_Files);
                foreach ($files as $f) @unlink(WRITEPATH . 'uploads/cotizaciones/' . $solicitudOriginal->Fecha . '/' . trim($f));
                $this->db->table('Cotizacion')->where('ID_Cotizacion', $idCotizacion)->update(['Cotizacion_Files' => null]);
            }

            // ---------------------------------------------------------
            // 4. ACTUALIZAR BD
            // ---------------------------------------------------------
            $ivaState = $solicitudOriginal->IVA;
            $sePuedenEditarProductos = isset($post['productos']) && is_array($post['productos']);
            if ($sePuedenEditarProductos) {
                $isIvaChecked = isset($post['IVA']);
                $ivaState = ($this->db->getPlatform() === 'Postgre') ? ($isIvaChecked ? 't' : 'f') : ($isIvaChecked ? 1 : 0);
            }

            $this->db->table('Solicitud')->where('ID_Solicitud', $id_solicitud)->update([
                'Estado' => $estadoSolicitud, 'MetodoPago' => $metodoPago, 'Fecha' => $fechaRegistro,
                'ID_Proveedor' => $idProveedor, 'IVA' => $ivaState, 'ID_RazonSocial' => $idRazonSocial
            ]);

            // Actualizar Productos
            $factorIva = ($ivaState === 't' || $ivaState === 1 || $ivaState === true) ? 1.16 : 1.0;
            $items = $post['productos'] ?? [];
            if (!empty($items)) {
                foreach ($items as $item) {
                    if(!isset($item['id']) || !isset($item['precio'])) continue;
                    $id_fila = $item['id']; $precio = floatval($item['precio']); $nombre = $item['nombre'] ?? '';
                    if ($solicitudOriginal->Tipo == 2) {
                        $this->db->table('Solicitud_Servicios')->where('ID_SolicitudServ', $id_fila)->update(['Importe' => $precio, 'Nombre' => $nombre]);
                    } else {
                        $cantidad = isset($item['cantidad']) ? floatval($item['cantidad']) : 1;
                        $nuevoMontoComprometido = $cantidad * $precio * $factorIva;
                        
                        $updateProd = ['Cantidad' => $cantidad, 'Importe' => $precio, 'Nombre' => $nombre];
                        // Si ya está en niveles presupuestales, actualizamos el respaldo
                        if ($nivelNuevo >= 4) {
                            $updateProd['Monto_Comprometido_Original'] = $nuevoMontoComprometido;
                        }
                        
                        $this->db->table('Solicitud_Producto')->where('ID_SolicitudProd', $id_fila)->update($updateProd);
                    }
                }
            }

            // Recalcular total para Cotización
            $totalFinal = 0;
            if ($solicitudOriginal->Tipo == 2) {
                $totalRow = $this->db->table('Solicitud_Servicios')->selectSum('Importe', 'total')->where('ID_Solicitud', $id_solicitud)->get()->getRow();
                $totalFinal = $totalRow ? (float)$totalRow->total : 0;
            } else {
                $totalRow = $this->db->table('Solicitud_Producto')->select('Cantidad, Importe')->where('ID_Solicitud', $id_solicitud)->get()->getResultArray();
                foreach($totalRow as $tr) $totalFinal += ((float)$tr['Cantidad'] * (float)$tr['Importe']);
                $totalFinal = ($ivaState === 't' || $ivaState === 1 || $ivaState === true) ? ($totalFinal * 1.16) : $totalFinal;
            }

            if ($idCotizacion) {
                $this->db->table('Cotizacion')->where('ID_Cotizacion', $idCotizacion)->update(['Total' => $totalFinal, 'ID_Proveedor' => $idProveedor]);
            } else if (!empty($idProveedor)) {
                $this->db->table('Cotizacion')->insert(['ID_Solicitud' => $id_solicitud, 'ID_Proveedor' => $idProveedor, 'Total' => $totalFinal, 'ID_Usuario_Cotiza' => session('id')]);
                $idCotizacion = $this->db->insertID();
            }

            // ---------------------------------------------------------
            // 5. SINCRONIZACIÓN DE PRESUPUESTO (EL CEREBRO)
            // ---------------------------------------------------------
            if ($esMateriales) {
                $montosNuevosPorGrupo = [];
                $prodsNuevos = $this->db->table('Solicitud_Producto')->where('ID_Solicitud', $id_solicitud)->get()->getResultArray();
                foreach ($prodsNuevos as $pn) {
                    if ($pn['ID_GrupoPresupuestal']) {
                        $montosNuevosPorGrupo[$pn['ID_GrupoPresupuestal']] = ($montosNuevosPorGrupo[$pn['ID_GrupoPresupuestal']] ?? 0) + (float)$pn['Monto_Comprometido_Original'];
                    }
                }

                // --- CORRECCIÓN: Usar la fecha de aprobación para el periodo presupuestal ---
                $fechaPresupuestoStr = $solicitudOriginal->Fecha_Aprobacion ?? $solicitudOriginal->Fecha;
                $fechaSol = strtotime($fechaPresupuestoStr);
                $mes = (int)date('n', $fechaSol);
                $anio = (int)date('Y', $fechaSol);
                
                // --- CORRECCIÓN: Obtener ID_UnidadOperativa del departamento ---
                $idDpto = $solicitudOriginal->ID_Dpto;
                $rowUnidad = $this->db->table('Departamentos')->select('ID_UnidadOperativa')->where('ID_Dpto', $idDpto)->get()->getRow();
                $idUnidad = $rowUnidad ? $rowUnidad->ID_UnidadOperativa : null;

                $todosLosGrupos = array_unique(array_merge(array_keys($montosViejosPorGrupo), array_keys($montosNuevosPorGrupo)));

                foreach ($todosLosGrupos as $idGrupo) {
                    if (!$idUnidad) continue;

                    $montoViejo = (float)($montosViejosPorGrupo[$idGrupo] ?? 0);
                    $montoNuevo = (float)($montosNuevosPorGrupo[$idGrupo] ?? 0);

                    // Buscamos el presupuesto usando ID_UnidadOperativa
                    $presupuesto = $this->db->table('PresupuestoMensual')
                        ->where(['ID_UnidadOperativa' => $idUnidad, 'ID_GrupoPresupuestal' => $idGrupo, 'Mes' => $mes, 'Anio' => $anio])
                        ->get()->getRow();

                    if ($presupuesto) {
                        $builder = $this->db->table('PresupuestoMensual');
                        $builder->where('ID_PresupuestoMensual', $presupuesto->ID_PresupuestoMensual);

                        // REGLA 1: Se mantiene en Comprometido (4-7)
                        if ($nivelAnterior >= 4 && $nivelAnterior <= 7 && $nivelNuevo >= 4 && $nivelNuevo <= 7) {
                            $diff = $montoNuevo - $montoViejo;
                            $builder->set('Monto_Comprometido', "\"Monto_Comprometido\" + ($diff)", false);
                        }
                        // REGLA 2: Sube de Pre-presupuesto a Comprometido (<4 -> 4-7)
                        elseif ($nivelAnterior < 4 && $nivelNuevo >= 4 && $nivelNuevo <= 7) {
                            $builder->set('Monto_Comprometido', "\"Monto_Comprometido\" + $montoNuevo", false);
                        }
                        // REGLA 3: Sube de Comprometido a Ejecutado (4-7 -> 8)
                        elseif ($nivelAnterior >= 4 && $nivelAnterior <= 7 && $nivelNuevo == 8) {
                            $builder->set('Monto_Comprometido', "GREATEST(0, \"Monto_Comprometido\" - $montoViejo)", false);
                            $builder->set('Monto_Ejecutado', "\"Monto_Ejecutado\" + $montoNuevo", false);
                        }
                        // REGLA 4: Se mantiene en Ejecutado (8 -> 8)
                        elseif ($nivelAnterior == 8 && $nivelNuevo == 8) {
                            $diff = $montoNuevo - $montoViejo;
                            $builder->set('Monto_Ejecutado', "\"Monto_Ejecutado\" + ($diff)", false);
                        }
                        // REGLA 5: Baja de Comprometido a Pre-presupuesto (4-7 -> <4)
                        elseif ($nivelAnterior >= 4 && $nivelAnterior <= 7 && $nivelNuevo < 4) {
                            $builder->set('Monto_Comprometido', "GREATEST(0, \"Monto_Comprometido\" - $montoViejo)", false);
                        }
                        // REGLA 6: Baja de Ejecutado a Comprometido (8 -> 4-7)
                        elseif ($nivelAnterior == 8 && $nivelNuevo >= 4 && $nivelNuevo <= 7) {
                            $builder->set('Monto_Ejecutado', "GREATEST(0, \"Monto_Ejecutado\" - $montoViejo)", false);
                            $builder->set('Monto_Comprometido', "\"Monto_Comprometido\" + $montoNuevo", false);
                        }
                        // REGLA 7: Baja de Ejecutado a Pre-presupuesto (8 -> <4)
                        elseif ($nivelAnterior == 8 && $nivelNuevo < 4) {
                            $builder->set('Monto_Ejecutado', "GREATEST(0, \"Monto_Ejecutado\" - $montoViejo)", false);
                        }

                        $builder->update();
                    } 
                    // Si no existe presupuesto y estamos entrando a nivel comprometido, lo creamos
                    elseif ($nivelNuevo >= 4) {
                        $comp = ($nivelNuevo == 8) ? 0 : $montoNuevo;
                        $ejec = ($nivelNuevo == 8) ? $montoNuevo : 0;
                        $this->db->table('PresupuestoMensual')->insert([
                            'ID_UnidadOperativa' => $idUnidad, 
                            'ID_GrupoPresupuestal' => $idGrupo, 
                            'Mes' => $mes, 
                            'Anio' => $anio,
                            'Monto_Asignado' => 0, 
                            'Monto_Comprometido' => $comp, 
                            'Monto_Ejecutado' => $ejec
                        ]);
                    }
                }
            }

            // 6. ORDEN DE COMPRA Y ARCHIVOS
            if ($idCotizacion && !empty($idProveedor)) {
                $orden = $this->db->table('OrdenCompra')->where('ID_Cotizacion', $idCotizacion)->get()->getRowArray();

                // SOLO CREAR SI NIVEL >= 5
                if ($nivelNuevo >= 5) {
                    if (!$orden) {
                        $estadoInicial = $estadoOrden ? $estadoOrden : 'Espera_Programacion';
                        $this->db->table('OrdenCompra')->insert([
                            'ID_Cotizacion'      => $idCotizacion,
                            'ID_Proveedor'       => $idProveedor,
                            'Estado'             => $estadoInicial,
                            'Fecha'              => date('Y-m-d H:i:s'), // <--- Nace con la fecha y hora de este momento (Aprobación)
                            'FechaRefPago'       => $fechaRefPago,       // <--- NUEVO
                            'FechaPagoRealizado' => $fechaPagoRealizado  // <--- NUEVO
                        ]);
                        $orden = $this->db->table('OrdenCompra')->where('ID_Cotizacion', $idCotizacion)->get()->getRowArray();
                    } else {
                        $datosUpdateOrden = [];
                        if ($estadoOrden) $datosUpdateOrden['Estado'] = $estadoOrden;

                        // Actualizamos solo las nuevas fechas (La 'Fecha' de aprobación original ya no se toca)
                        $datosUpdateOrden['FechaRefPago']       = $fechaRefPago;
                        $datosUpdateOrden['FechaPagoRealizado'] = $fechaPagoRealizado;

                        $this->db->table('OrdenCompra')->where('ID_OrdenCompra', $orden['ID_OrdenCompra'])->update($datosUpdateOrden);
                    }
                }

                if ($orden) {
                    $idOrdenCompra = $orden['ID_OrdenCompra'];
                    $rnd = uniqid();

                    // 1. PROCESAR COMPROBANTE DE PAGO
                    if ($f = $this->request->getFile('File_Comprobante')) {
                        if ($f->isValid() && !$f->hasMoved()) {
                            $n = "Ficha-{$id_solicitud}-{$idCotizacion}-{$idOrdenCompra}-{$idProveedor}-{$rnd}.".$f->getClientExtension();
                            $f->move(WRITEPATH.'uploads/comprobantes/', $n, true);

                            // Automatización: Tomar fecha manual o poner la fecha actual por defecto
                            $fechaRealizada = !empty($post['FechaPagoRealizado']) ? $post['FechaPagoRealizado'] : date('Y-m-d');

                            $this->db->table('OrdenCompra')->where('ID_OrdenCompra', $idOrdenCompra)->update([
                                'File_Comprobante'   => $n,
                                'FechaPagoRealizado' => $fechaRealizada
                            ]);
                        }
                    }

                    // 2. PROCESAR FACTURA
                    if ($f = $this->request->getFile('File_Factura')) {
                        if ($f->isValid() && !$f->hasMoved()) {
                            $n = "Factura-{$id_solicitud}-{$idCotizacion}-{$idOrdenCompra}-{$idProveedor}-{$rnd}.".$f->getClientExtension();
                            $f->move(WRITEPATH.'uploads/facturas/', $n, true);

                            $this->db->table('OrdenCompra')->where('ID_OrdenCompra', $idOrdenCompra)->update([
                                'File_Factura' => $n
                            ]);
                        }
                    }
                }

                // Archivos Cotización
                $allFiles = $this->request->getFiles();
                if (isset($allFiles['cotizacion_files'])) {
                    $cPath = 'uploads/cotizaciones/' . $solicitudOriginal->Fecha . '/';
                    if (!is_dir(WRITEPATH . $cPath)) mkdir(WRITEPATH . $cPath, 0777, true);
                    $tmpN = []; $cnt = 0;
                    $cfiles = is_array($allFiles['cotizacion_files']) ? $allFiles['cotizacion_files'] : [$allFiles['cotizacion_files']];
                    foreach ($cfiles as $cf) if ($cf->isValid() && !$cf->hasMoved()) {
                        $cN = "cotizacion_{$idCotizacion}_{$solicitudOriginal->Fecha}_{$cnt}." . $cf->getClientExtension();
                        if ($cf->move(WRITEPATH . $cPath, $cN, true)) { $tmpN[] = $cN; $cnt++; }
                    }
                    if (!empty($tmpN)) $this->db->table('Cotizacion')->where('ID_Cotizacion', $idCotizacion)->update(['Cotizacion_Files' => implode(',', $tmpN)]);
                }
            }

            // 6. GENERAR PDFS (CONTROLADO)
            try {
                if (class_exists(GenerarPDF::class)) {
                    $pdf = new GenerarPDF();

                    // REGLA NUEVA: Requisición solo si es "Programada" (6) o superior
                    if ($nivelNuevo >= 6) {
                        $pdf->generarYGuardarRequisicion($id_solicitud, 0, 1);
                    }

                    // Orden de Compra: Si existe la orden (Nivel >= 5) y hay proveedor
                    if ($orden && !empty($idProveedor)) {
                        $pdf->generarYGuardarOrden($id_solicitud, session('id') ?? 1);
                    }
                }
            } catch (\Exception $e) {}

            $this->db->transComplete();
            return $this->respond(['success' => true, 'message' => 'Actualizado.']);
        } catch (\Exception $e) {
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
            }
            log_message('error', '[ControlMaestro::update_master] ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Error interno en el servidor: ' . $e->getMessage()
            ]);
        }
    }
}