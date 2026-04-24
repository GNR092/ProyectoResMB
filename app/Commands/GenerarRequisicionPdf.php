<?php

namespace App\Commands;

use App\Controllers\GenerarPDF;
use App\Libraries\FPath;
use App\Libraries\GhostscriptProcessor;
use App\Libraries\PdfValidator;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerarRequisicionPdf extends BaseCommand
{
    protected $group = 'Generacion';
    protected $name = 'generar:requisicion-pdf';
    protected $description = 'Genera o regenera PDFs de requisicion (excluye canceladas)';
    protected $usage = 'generar:requisicion-pdf [--force] [--ids=1,2,3] [--limit=N] [--offset=N]';
    protected $arguments = [];
    protected $options = [
        '--force' => 'Regenera aunque el PDF ya exista (recomendado para corregir adjuntos que antes fallaban).',
        '--ids' => 'IDs de solicitud separados por coma. Ejemplo: --ids=101,102,103',
        '--limit' => 'Limita el total de solicitudes procesadas',
        '--offset' => 'Desplazamiento inicial para procesar por bloques',
        '--skip-preflight' => 'Omite el analisis previo de PDFs adjuntos',
        '--only-incompatible' => 'Procesa solo solicitudes con al menos un adjunto PDF incompatible con FPDI.',
    ];

    public function run(array $params)
    {
        $solicitudModel = new \App\Models\SolicitudModel();
        $controller = new GenerarPDF();

        $force = (bool) CLI::getOption('force');
        $limit = max(0, (int) (CLI::getOption('limit') ?? 0));
        $offset = max(0, (int) (CLI::getOption('offset') ?? 0));
        $skipPreflight = (bool) CLI::getOption('skip-preflight');
        $onlyIncompatible = (bool) CLI::getOption('only-incompatible');
        [$requestedIds, $invalidIds, $idsProvided] = $this->resolveIdsOption();

        if ($skipPreflight && $onlyIncompatible) {
            CLI::error('No puedes usar --skip-preflight junto con --only-incompatible.');
            return;
        }

        if (!empty($invalidIds)) {
            CLI::write('IDs ignorados por formato invalido: ' . implode(', ', $invalidIds), 'yellow');
        }

        if ($idsProvided && empty($requestedIds)) {
            CLI::error('No se detectaron IDs validos en --ids. Ejemplo correcto: --ids=101,102,103');
            return;
        }

        $builder = $solicitudModel
            ->select('ID_Solicitud, No_Folio, Estado, Fecha, Archivo')
            ->where('Estado !=', 'Cancelada')
            ->orderBy('ID_Solicitud', 'ASC');

        if (!empty($requestedIds)) {
            $builder->whereIn('ID_Solicitud', $requestedIds);
        }

        $solicitudes = $limit > 0
            ? $builder->findAll($limit, $offset)
            : $builder->findAll();

        $folderPath = FPath::FPDF;

        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $gsBinary = GhostscriptProcessor::resolveBinary();
        CLI::write(
            'Ghostscript: ' . ($gsBinary !== null ? ('disponible (' . $gsBinary . ')') : 'no disponible'),
            $gsBinary !== null ? 'green' : 'yellow',
        );

        $generados = 0;
        $errores = 0;
        $yaExistian = 0;
        $adjuntosTotal = 0;
        $adjuntosIncompatibles = 0;
        $adjuntosEncriptados = 0;
        $adjuntosInvalidos = 0;
        $omitidosCompatibles = 0;

        CLI::write('Total solicitudes: ' . count($solicitudes));

        foreach ($solicitudes as $sol) {
            $fileName = 'Requisicion-' . $sol['No_Folio'] . '.pdf';
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

            $preflight = null;

            if (!$skipPreflight) {
                $preflight = $this->analyzeSolicitudAttachments($sol);
                $adjuntosTotal += $preflight['total'];
                $adjuntosIncompatibles += $preflight['incompatibles'];
                $adjuntosEncriptados += $preflight['encriptados'];
                $adjuntosInvalidos += $preflight['invalidos'];

                if ($onlyIncompatible && $preflight['incompatibles'] === 0) {
                    $omitidosCompatibles++;
                    continue;
                }
            }

            if ($force || !file_exists($filePath)) {
                try {
                    $controller->generarYGuardarRequisicion((int) $sol['ID_Solicitud'], 0, 1);
                    $generados++;
                    CLI::write('Generado: ' . $sol['No_Folio'], 'green');
                } catch (\Throwable $e) {
                    $errores++;
                    CLI::write("Error {$sol['No_Folio']}: " . $e->getMessage(), 'red');
                }
            } else {
                $yaExistian++;
            }
        }

        CLI::write('=============================', 'white');
        CLI::write('PDFs generados: ' . $generados, 'green');
        CLI::write('Ya existian: ' . $yaExistian, 'yellow');
        if ($onlyIncompatible) {
            CLI::write('Solicitudes omitidas por adjuntos compatibles: ' . $omitidosCompatibles, 'yellow');
        }
        if (!$skipPreflight) {
            CLI::write(
                'Adjuntos PDF: Total ' .
                    $adjuntosTotal .
                    ' | Incompatibles FPDI ' .
                    $adjuntosIncompatibles .
                    ' | Encriptados ' .
                    $adjuntosEncriptados .
                    ' | Invalidos ' .
                    $adjuntosInvalidos,
                ($adjuntosIncompatibles > 0 || $adjuntosInvalidos > 0) ? 'yellow' : 'green',
            );
        }
        CLI::write('Errores: ' . $errores, $errores > 0 ? 'red' : 'green');
    }

    /**
     * @return array{0: array<int>, 1: array<string>, 2: bool}
     */
    private function resolveIdsOption(): array
    {
        $rawValue = CLI::getOption('ids');
        if ($rawValue === null || $rawValue === '') {
            return [[], [], false];
        }

        $parts = array_filter(
            array_map('trim', explode(',', (string) $rawValue)),
            static fn(string $v): bool => $v !== '',
        );

        $ids = [];
        $invalid = [];

        foreach ($parts as $part) {
            if (ctype_digit($part) && (int) $part > 0) {
                $ids[] = (int) $part;
            } else {
                $invalid[] = $part;
            }
        }

        return [array_values(array_unique($ids)), $invalid, true];
    }

    /**
     * @param array<string, mixed> $solicitud
     * @return array{total:int, incompatibles:int, encriptados:int, invalidos:int}
     */
    private function analyzeSolicitudAttachments(array $solicitud): array
    {
        $result = [
            'total' => 0,
            'incompatibles' => 0,
            'encriptados' => 0,
            'invalidos' => 0,
        ];

        $archivo = $solicitud['Archivo'] ?? null;
        $fecha = $solicitud['Fecha'] ?? null;

        if (!is_string($archivo) || trim($archivo) === '' || !is_string($fecha) || trim($fecha) === '') {
            return $result;
        }

        $files = array_filter(array_map('trim', explode(',', $archivo)), static fn(string $v): bool => $v !== '');

        foreach ($files as $fileName) {
            if (strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }

            $fullPath = FPath::FSOLICITUD . $fecha . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($fullPath)) {
                continue;
            }

            $result['total']++;
            $analysis = PdfValidator::analyze($fullPath);

            if (!$analysis['isValid']) {
                $result['invalidos']++;
            }

            if ($analysis['isEncrypted']) {
                $result['encriptados']++;
            }

            if (!$analysis['isFpdiCompatible']) {
                $result['incompatibles']++;
            }
        }

        return $result;
    }
}
