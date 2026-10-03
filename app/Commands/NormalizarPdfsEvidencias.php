<?php

namespace App\Commands;

use App\Libraries\GhostscriptProcessor;
use App\Libraries\PdfValidator;
use App\Models\EventoArchivosModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class NormalizarPdfsEvidencias extends BaseCommand
{
    protected $group = 'Generacion';

    protected $name = 'normalizar:pdfs-evidencias';

    protected $description =
        'Normaliza PDFs de evidencias de la Agenda de Salidas a PDF 1.4 compatible con FPDI. ' .
        'Usa Ghostscript para convertir PDFs incompatibles. ' .
        'Ejemplos: --dry-run --only-incompatible --ids=1,2,3';

    protected $usage = 'normalizar:pdfs-evidencias [--ids=1,2,3] [--limit=N] [--offset=N] [--dry-run] [--only-incompatible] [--skip-encrypted]';

    protected $options = [
        '--ids' => 'IDs de evento separados por coma. Ejemplo: --ids=10,11,12',
        '--limit' => 'Limita el total de archivos a procesar',
        '--offset' => 'Desplazamiento inicial para procesar por bloques',
        '--dry-run' => 'Solo analiza y reporta, no modifica archivos',
        '--only-incompatible' => 'Procesa solo PDFs incompatibles con FPDI',
        '--skip-encrypted' => 'Omite PDFs encriptados (no falla, solo salta y reporta)',
    ];

    public function run(array $params)
    {
        $limit = $this->resolveIntOption($params, 'limit', 0);
        $offset = $this->resolveIntOption($params, 'offset', 0);
        $dryRun = (bool) CLI::getOption('dry-run');
        $onlyIncompatible = (bool) CLI::getOption('only-incompatible');
        $skipEncrypted = (bool) CLI::getOption('skip-encrypted');
        [$eventIds, $invalidIds, $idsOptionProvided] = $this->resolveIdsOption($params);

        if ($limit < 0 || $offset < 0) {
            CLI::error('Los valores de --limit y --offset no pueden ser negativos.');
            return;
        }

        if (!empty($invalidIds)) {
            CLI::write(
                'IDs de evento ignorados por formato invalido: ' . implode(', ', $invalidIds),
                'yellow',
            );
        }

        if ($idsOptionProvided && empty($eventIds)) {
            CLI::error('No se detectaron IDs validos en --ids. Ejemplo correcto: --ids=10,11,12');
            return;
        }

        $model = new EventoArchivosModel();

        $builder = $model->select('id_archivo, id_evento, nombre_archivo, nombre_usuario, fecha_subida')
            ->where("nombre_archivo LIKE '%.pdf'", null, false)
            ->orderBy('fecha_subida', 'ASC');

        if (!empty($eventIds)) {
            $builder->whereIn('id_evento', $eventIds);
        }

        if ($limit > 0) {
            $archivos = $builder->findAll($limit, $offset);
        } else {
            $archivos = $builder->findAll();
        }

        if (empty($archivos)) {
            CLI::write('No hay archivos PDF de evidencia para procesar.', 'yellow');
            return;
        }

        $gsBinary = GhostscriptProcessor::resolveBinary();
        $qpdfBinary = $this->resolveQpdfBinary();
        CLI::write(
            'Ghostscript: ' . ($gsBinary !== null ? ('disponible (' . $gsBinary . ')') : 'no disponible'),
            $gsBinary !== null ? 'green' : 'yellow',
        );
        CLI::write(
            'qpdf: ' . ($qpdfBinary !== null ? ('disponible (' . $qpdfBinary . ')') : 'no disponible'),
            $qpdfBinary !== null ? 'green' : 'yellow',
        );

        if ($dryRun) {
            CLI::write('MODO DRY-RUN: Solo analisis, sin modificar archivos', 'cyan');
        }

        CLI::write('Total archivos PDF a analizar: ' . count($archivos), 'white');

        $totales = [
            'total_analizados' => 0,
            'ya_compatibles' => 0,
            'normalizados_ok' => 0,
            'fallaron_normalizacion' => 0,
            'encriptados_saltados' => 0,
            'archivos_faltantes' => 0,
            'encriptados_intentados' => 0,
            'desencriptados_ok' => 0,
            'fallaron_desencriptar' => 0,
        ];

        $backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'eventos' . DIRECTORY_SEPARATOR . date('Y-m-d_H-i-s');
        if (!$dryRun && !is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        foreach ($archivos as $archivo) {
            $idArchivo = (int) $archivo['id_archivo'];
            $idEvento = (int) $archivo['id_evento'];
            $nombreArchivo = $archivo['nombre_archivo'];
            $nombreUsuario = $archivo['nombre_usuario'] ?? 'N/A';
            $fechaSubida = $archivo['fecha_subida'] ?? 'N/A';

            $fullPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'eventos' . DIRECTORY_SEPARATOR . $nombreArchivo;

            if (!is_file($fullPath)) {
                $totales['archivos_faltantes']++;
                CLI::write(
                    "  [ID:$idArchivo] Evento:$idEvento | $nombreArchivo | ARCHIVO FALTANTE EN DISCO",
                    'red',
                );
                continue;
            }

            $totales['total_analizados']++;

            $analysis = PdfValidator::analyze($fullPath);

            $esIncompatible = !$analysis['isFpdiCompatible'];
            $esEncriptado = $analysis['isEncrypted'];

            if (!$esIncompatible && !$esEncriptado) {
                $totales['ya_compatibles']++;
                if ($onlyIncompatible) {
                    continue;
                }
                CLI::write(
                    "  [ID:$idArchivo] Evento:$idEvento | $nombreArchivo | YA COMPATIBLE (v{$analysis['version']})",
                    'green',
                );
                continue;
            }

            if ($onlyIncompatible && !$esIncompatible) {
                continue;
            }

            CLI::write(
                "  [ID:$idArchivo] Evento:$idEvento | $nombreArchivo | " .
                ($esEncriptado ? 'ENCRIPTADO ' : '') .
                ($esIncompatible ? 'INCOMPATIBLE (v' . ($analysis['version'] ?? '?') . ')' : ''),
                'yellow',
            );

            if ($esEncriptado) {
                $totales['encriptados_intentados']++;
                if ($skipEncrypted) {
                    $totales['encriptados_saltados']++;
                    CLI::write('    -> Saltado por --skip-encrypted', 'yellow');
                    continue;
                }

                if ($qpdfBinary !== null) {
                    CLI::write('    -> Intentando desencriptar con qpdf...', 'cyan');
                    $desencriptado = $this->desencriptarConQpdf($qpdfBinary, $fullPath, $backupDir, $dryRun);
                    if ($desencriptado['success']) {
                        $totales['desencriptados_ok']++;
                        CLI::write('    -> Desencriptado OK', 'green');
                        // Re-analizar después de desencriptar
                        $analysis = PdfValidator::analyze($fullPath);
                        $esEncriptado = false;
                        $esIncompatible = !$analysis['isFpdiCompatible'];
                    } else {
                        $totales['fallaron_desencriptar']++;
                        CLI::write('    -> Fallo al desencriptar: ' . $desencriptado['message'], 'red');
                        continue;
                    }
                } else {
                    $totales['encriptados_saltados']++;
                    CLI::write('    -> Saltado: qpdf no disponible para desencriptar', 'yellow');
                    continue;
                }
            }

            if ($esIncompatible) {
                if ($gsBinary === null) {
                    $totales['fallaron_normalizacion']++;
                    CLI::write('    -> Saltado: Ghostscript no disponible', 'red');
                    continue;
                }

                if ($dryRun) {
                    CLI::write('    -> [DRY-RUN] Se normalizaria con Ghostscript a PDF 1.4', 'cyan');
                    $totales['normalizados_ok']++;
                    continue;
                }

                $tempOut = tempnam(sys_get_temp_dir(), 'pdf_norm_') . '.pdf';
                $result = GhostscriptProcessor::normalizePdfForFpdi($fullPath, $tempOut);

                if ($result['success'] && file_exists($tempOut)) {
                    // Backup original
                    $backupPath = $backupDir . DIRECTORY_SEPARATOR . $nombreArchivo;
                    copy($fullPath, $backupPath);

                    // Reemplazar
                    @unlink($fullPath);
                    rename($tempOut, $fullPath);

                    $totales['normalizados_ok']++;
                    CLI::write('    -> Normalizado OK (backup en ' . basename($backupDir) . ')', 'green');
                } else {
                    $totales['fallaron_normalizacion']++;
                    CLI::write('    -> Fallo normalizacion: ' . ($result['message'] ?? 'error desconocido'), 'red');
                    @unlink($tempOut ?? '');
                }
            }
        }

        CLI::write(str_repeat('=', 55), 'white');
        CLI::write('RESUMEN', 'white');
        CLI::write(str_repeat('=', 55), 'white');
        CLI::write('Total analizados:      ' . $totales['total_analizados']);
        CLI::write('Ya compatibles:        ' . $totales['ya_compatibles'], 'green');
        CLI::write('Normalizados OK:       ' . $totales['normalizados_ok'], $totales['normalizados_ok'] > 0 ? 'green' : 'white');
        CLI::write('Fallaron normalizacion: ' . $totales['fallaron_normalizacion'], $totales['fallaron_normalizacion'] > 0 ? 'red' : 'white');
        CLI::write('Encriptados saltados:  ' . $totales['encriptados_saltados'], 'yellow');
        CLI::write('Archivos faltantes:    ' . $totales['archivos_faltantes'], $totales['archivos_faltantes'] > 0 ? 'red' : 'white');
        if ($totales['encriptados_intentados'] > 0) {
            CLI::write('  Encriptados intentados: ' . $totales['encriptados_intentados']);
            CLI::write('  Desencriptados OK:      ' . $totales['desencriptados_ok'], 'green');
            CLI::write('  Fallaron desencriptar:  ' . $totales['fallaron_desencriptar'], $totales['fallaron_desencriptar'] > 0 ? 'red' : 'white');
        }
        if ($dryRun) {
            CLI::write('', 'white');
            CLI::write('>>> MODO DRY-RUN: Ningun archivo fue modificado <<<', 'cyan');
            CLI::write('Ejecuta sin --dry-run para aplicar cambios', 'white');
        } elseif ($totales['normalizados_ok'] > 0) {
            CLI::write('', 'white');
            CLI::write('Backups guardados en: ' . $backupDir, 'cyan');
        }
        CLI::write('Ayuda: php spark normalizar:pdfs-evidencias --help', 'white');
    }

    /**
     * Intenta desencriptar PDF con qpdf.
     * Retorna array con success, message, y ruta del archivo (original modificado in-place).
     */
    private function desencriptarConQpdf(string $qpdfBinary, string $filePath, string $backupDir, bool $dryRun): array
    {
        if ($dryRun) {
            return ['success' => true, 'message' => '[DRY-RUN] Se intentaria desencriptar con qpdf'];
        }

        $tempOut = tempnam(sys_get_temp_dir(), 'qpdf_dec_') . '.pdf';
        $command = implode(' ', [
            escapeshellarg($qpdfBinary),
            '--decrypt',
            escapeshellarg($filePath),
            escapeshellarg($tempOut),
        ]);

        $output = [];
        $code = 1;
        @exec($command . ' 2>&1', $output, $code);

        if ($code !== 0 || !is_file($tempOut)) {
            @unlink($tempOut ?? '');
            return [
                'success' => false,
                'message' => 'qpdf fallo (code ' . $code . '): ' . implode("\n", $output),
            ];
        }

        // Backup original
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . basename($filePath) . '.enc_backup';
        copy($filePath, $backupPath);

        // Reemplazar original con version desencriptada
        @unlink($filePath);
        rename($tempOut, $filePath);

        return ['success' => true, 'message' => 'Desencriptado con qpdf, backup en ' . basename($backupPath)];
    }

    /**
     * Busca binario qpdf en sistema.
     */
    private function resolveQpdfBinary(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $candidates = [
                'qpdf.exe',
                'C:\\Program Files\\qpdf\\bin\\qpdf.exe',
                'C:\\Program Files (x86)\\qpdf\\bin\\qpdf.exe',
            ];
        } else {
            $candidates = [
                'qpdf',
                '/usr/bin/qpdf',
                '/usr/local/bin/qpdf',
            ];
        }

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '*')) {
                $matches = glob($candidate);
                if (is_array($matches)) {
                    rsort($matches);
                    foreach ($matches as $match) {
                        if (is_file($match)) {
                            return $match;
                        }
                    }
                }
                continue;
            }

            if (is_file($candidate)) {
                return $candidate;
            }

            $output = [];
            $code = 1;
            $lookupCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                ? 'where ' . escapeshellarg($candidate)
                : 'command -v ' . escapeshellarg($candidate);
            @exec($lookupCmd . ' 2>&1', $output, $code);
            if ($code === 0) {
                return $candidate;
            }
        }

        return null;
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