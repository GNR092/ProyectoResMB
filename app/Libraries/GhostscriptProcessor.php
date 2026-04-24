<?php

namespace App\Libraries;

class GhostscriptProcessor
{
    public static function isAvailable(): bool
    {
        return self::resolveBinary() !== null;
    }

    public static function convertToFpdiCompatible(string $inputPath, string $outputPath): array
    {
        $binary = self::resolveBinary();
        if ($binary === null) {
            return [
                'success' => false,
                'message' => 'No se encontro Ghostscript en el sistema.',
            ];
        }

        $command = implode(' ', [
            escapeshellarg($binary),
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dDetectDuplicateImages=true',
            '-dCompressFonts=true',
            '-dSubsetFonts=true',
            '-dQUIET',
            '-sOutputFile=' . escapeshellarg($outputPath),
            escapeshellarg($inputPath),
        ]);

        $output = [];
        $code = 1;
        @exec($command . ' 2>&1', $output, $code);

        if ($code !== 0 || !is_file($outputPath)) {
            return [
                'success' => false,
                'message' => 'Ghostscript no pudo convertir el archivo.',
                'code' => $code,
                'output' => implode("\n", $output),
            ];
        }

        return [
            'success' => true,
            'message' => 'Conversion realizada correctamente.',
            'code' => 0,
            'output' => implode("\n", $output),
            'binary' => $binary,
        ];
    }

    public static function resolveBinary(): ?string
    {
        foreach (self::candidateBinaries() as $candidate) {
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

            if (self::commandExists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private static function candidateBinaries(): array
    {
        if (self::isWindows()) {
            return [
                'gswin64c.exe',
                'gswin32c.exe',
                'C:\\Program Files\\gs\\gs*\\bin\\gswin64c.exe',
                'C:\\Program Files (x86)\\gs\\gs*\\bin\\gswin32c.exe',
            ];
        }

        return [
            'gc',
            'gs',
            '/usr/bin/gc',
            '/usr/bin/gs',
            '/usr/local/bin/gc',
            '/usr/local/bin/gs',
        ];
    }

    private static function commandExists(string $command): bool
    {
        if (is_file($command)) {
            return true;
        }

        $lookupCmd = self::isWindows()
            ? 'where ' . escapeshellarg($command)
            : 'command -v ' . escapeshellarg($command);

        $output = [];
        $code = 1;
        @exec($lookupCmd . ' 2>&1', $output, $code);

        return $code === 0;
    }

    private static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
