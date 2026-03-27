<?php

namespace App\Commands;

use App\Controllers\GenerarPDF;
use App\Models\CotizacionModel;
use App\Models\OrdenCompraModel;
use App\Models\SolicitudModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RegenerarPdfs extends BaseCommand
{
    protected $group = 'Generacion';

    protected $name = 'regenerar:pdfs';

    protected $description =
        'Regenera PDFs de requisicion, orden de compra y requisicion de pago para solicitudes activas. Usa --help para ejemplos.';

    protected $usage = 'regenerar:pdfs [--ids=1,2,3] [--user-id=ID] [--limit=N] [--offset=N]';

    protected $options = [
        '--ids' => 'IDs de solicitud separados por coma. Ejemplo: --ids=101,102,103',
        '--user-id' => 'ID de usuario a usar para la firma de Orden de Compra (default: 1)',
        '--limit' => 'Limita el total de solicitudes procesadas',
        '--offset' => 'Desplazamiento inicial para procesar por bloques',
    ];

    public function run(array $params)
    {
        $userId = $this->resolveIntOption($params, 'user-id', 1);
        $limit = $this->resolveIntOption($params, 'limit', 0);
        $offset = $this->resolveIntOption($params, 'offset', 0);
        [$requestedIds, $invalidIds, $idsOptionProvided] = $this->resolveIdsOption($params);

        if ($userId <= 0) {
            CLI::error('El valor de --user-id debe ser mayor a 0.');
            return;
        }

        if ($limit < 0 || $offset < 0) {
            CLI::error('Los valores de --limit y --offset no pueden ser negativos.');
            return;
        }

        if (!empty($invalidIds)) {
            CLI::write(
                'IDs ignorados por formato invalido: ' . implode(', ', $invalidIds),
                'yellow',
            );
        }

        if ($idsOptionProvided && empty($requestedIds)) {
            CLI::error('No se detectaron IDs validos en --ids. Ejemplo correcto: --ids=101,102,103');
            return;
        }

        $solicitudModel = new SolicitudModel();
        $cotizacionModel = new CotizacionModel();
        $ordenCompraModel = new OrdenCompraModel();
        $pdfController = new GenerarPDF();

        $solicitudesBuilder = $solicitudModel
            ->select('ID_Solicitud, No_Folio, Estado')
            ->where('Estado !=', 'Cancelada')
            ->orderBy('ID_Solicitud', 'ASC');

        if (!empty($requestedIds)) {
            $solicitudesBuilder->whereIn('ID_Solicitud', $requestedIds);
        }

        if ($limit > 0) {
            $solicitudes = $solicitudesBuilder->findAll($limit, $offset);
        } else {
            $solicitudes = $solicitudesBuilder->findAll();
        }

        if (empty($solicitudes)) {
            CLI::write('No hay solicitudes activas para procesar.', 'yellow');
            return;
        }

        if (!empty($requestedIds)) {
            $foundIds = array_map(static fn(array $row): int => (int) $row['ID_Solicitud'], $solicitudes);
            $notFoundOrInactive = array_values(array_diff($requestedIds, $foundIds));
            if (!empty($notFoundOrInactive)) {
                CLI::write(
                    'IDs no encontrados o cancelados: ' . implode(', ', $notFoundOrInactive),
                    'yellow',
                );
            }
        }

        $cotizaciones = $cotizacionModel->select('ID_Cotizacion, ID_Solicitud')->findAll();
        $ordenes = $ordenCompraModel->select('ID_OrdenCompra, ID_Cotizacion')->findAll();

        $cotizacionPorSolicitud = [];
        foreach ($cotizaciones as $cotizacion) {
            $cotizacionPorSolicitud[(int) $cotizacion['ID_Solicitud']] = (int) $cotizacion['ID_Cotizacion'];
        }

        $cotizacionesConOrden = [];
        foreach ($ordenes as $orden) {
            $cotizacionesConOrden[(int) $orden['ID_Cotizacion']] = true;
        }

        $totales = [
            'solicitudes' => count($solicitudes),
            'requisicion_ok' => 0,
            'requisicion_error' => 0,
            'orden_ok' => 0,
            'orden_error' => 0,
            'orden_skip' => 0,
            'pago_ok' => 0,
            'pago_error' => 0,
            'pago_skip' => 0,
        ];

        CLI::write('Total solicitudes activas a procesar: ' . $totales['solicitudes'], 'white');

        foreach ($solicitudes as $solicitud) {
            $idSolicitud = (int) $solicitud['ID_Solicitud'];
            $folio = $solicitud['No_Folio'] ?? ('ID-' . $idSolicitud);

            CLI::write('Procesando solicitud ' . $idSolicitud . ' (folio: ' . $folio . ')', 'cyan');

            try {
                $reqPath = $pdfController->generarYGuardarRequisicion($idSolicitud, 0, 1);
                if (!empty($reqPath)) {
                    $totales['requisicion_ok']++;
                } else {
                    $totales['requisicion_error']++;
                    CLI::write('  - Error al regenerar requisicion.', 'red');
                }
            } catch (\Throwable $e) {
                $totales['requisicion_error']++;
                CLI::write('  - Error requisicion: ' . $e->getMessage(), 'red');
            }

            $idCotizacion = $cotizacionPorSolicitud[$idSolicitud] ?? null;
            $tieneOrden = $idCotizacion !== null && isset($cotizacionesConOrden[$idCotizacion]);

            if (!$tieneOrden) {
                $totales['orden_skip']++;
                $totales['pago_skip']++;
                CLI::write('  - Sin orden de compra asociada. Se omite OC y requisicion de pago.', 'yellow');
                continue;
            }

            try {
                $ocPath = $pdfController->generarYGuardarOrden($idSolicitud, $userId);
                if (!empty($ocPath)) {
                    $totales['orden_ok']++;
                } else {
                    $totales['orden_error']++;
                    CLI::write('  - Error al regenerar orden de compra.', 'red');
                }
            } catch (\Throwable $e) {
                $totales['orden_error']++;
                CLI::write('  - Error orden de compra: ' . $e->getMessage(), 'red');
            }

            try {
                $pagoPath = $pdfController->generarYGuardarRequisicionPago($idSolicitud);
                if (!empty($pagoPath)) {
                    $totales['pago_ok']++;
                } else {
                    $totales['pago_error']++;
                    CLI::write('  - Error al regenerar requisicion de pago.', 'red');
                }
            } catch (\Throwable $e) {
                $totales['pago_error']++;
                CLI::write('  - Error requisicion de pago: ' . $e->getMessage(), 'red');
            }
        }

        CLI::write(str_repeat('=', 45), 'white');
        CLI::write('Solicitudes procesadas: ' . $totales['solicitudes'], 'white');
        CLI::write(
            'Requisicion: OK ' .
                $totales['requisicion_ok'] .
                ' | Error ' .
                $totales['requisicion_error'],
            $totales['requisicion_error'] > 0 ? 'yellow' : 'green',
        );
        CLI::write(
            'Orden compra: OK ' .
                $totales['orden_ok'] .
                ' | Error ' .
                $totales['orden_error'] .
                ' | Omitidas ' .
                $totales['orden_skip'],
            $totales['orden_error'] > 0 ? 'yellow' : 'green',
        );
        CLI::write(
            'Req. pago: OK ' .
                $totales['pago_ok'] .
                ' | Error ' .
                $totales['pago_error'] .
                ' | Omitidas ' .
                $totales['pago_skip'],
            $totales['pago_error'] > 0 ? 'yellow' : 'green',
        );
        CLI::write('Ayuda: php spark regenerar:pdfs --help', 'white');
    }

    /**
     * @return array{0: array<int>, 1: array<string>, 2: bool}
     */
    private function resolveIdsOption(array $params): array
    {
        $rawValue = CLI::getOption('ids');

        if ($rawValue === null || $rawValue === '') {
            foreach ($params as $key => $value) {
                if (!is_string($key)) {
                    continue;
                }

                $normalized = ltrim($key, '-');
                if ($normalized === 'ids') {
                    $rawValue = (string) $value;
                    break;
                }

                if (str_contains($normalized, '=')) {
                    [$name, $optValue] = explode('=', $normalized, 2);
                    if ($name === 'ids') {
                        $rawValue = $optValue;
                        break;
                    }
                }
            }
        }

        if ($rawValue === null) {
            return [[], [], false];
        }

        $parts = array_filter(array_map('trim', explode(',', (string) $rawValue)), static fn(string $v): bool => $v !== '');

        $ids = [];
        $invalid = [];

        foreach ($parts as $part) {
            if (ctype_digit($part) && (int) $part > 0) {
                $ids[] = (int) $part;
            } else {
                $invalid[] = $part;
            }
        }

        $ids = array_values(array_unique($ids));

        return [$ids, $invalid, true];
    }

    private function resolveIntOption(array $params, string $optionName, int $default): int
    {
        $cliValue = CLI::getOption($optionName);
        if ($cliValue !== null && $cliValue !== '') {
            return (int) $cliValue;
        }

        foreach ($params as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            $normalized = ltrim($key, '-');
            if ($normalized === $optionName) {
                return (int) $value;
            }

            if (str_contains($normalized, '=')) {
                [$name, $rawValue] = explode('=', $normalized, 2);
                if ($name === $optionName) {
                    return (int) $rawValue;
                }
            }
        }

        return $default;
    }
}
