<?php

namespace App\Libraries;

class PdfValidator
{
    private const FPDI_MAX_VERSION_MAJOR = 1;
    private const FPDI_MAX_VERSION_MINOR = 4;

    public static function analyze(string $filePath): array
    {
        $result = [
            'isValid' => false,
            'version' => null,
            'isEncrypted' => false,
            'isFpdiCompatible' => false,
            'warnings' => [],
        ];

        if (!is_file($filePath) || !is_readable($filePath)) {
            $result['warnings'][] = 'Archivo PDF no encontrado o sin permisos de lectura.';
            return $result;
        }

        if (!self::hasPdfHeader($filePath)) {
            $result['warnings'][] = 'Encabezado PDF invalido (no inicia con %PDF-).';
            return $result;
        }

        $result['isValid'] = true;
        $result['version'] = self::getVersion($filePath);
        $result['isEncrypted'] = self::isEncrypted($filePath);

        if ($result['version'] === null) {
            $result['warnings'][] = 'No se pudo detectar la version del PDF.';
        }

        if ($result['isEncrypted']) {
            $result['warnings'][] = 'El PDF esta protegido/encriptado.';
        }

        if (
            $result['version'] !== null
            && !self::isVersionFpdiCompatible($result['version'])
        ) {
            $result['warnings'][] =
                'Version PDF ' . $result['version'] . ' requiere conversion para FPDI libre.';
        }

        $result['isFpdiCompatible'] =
            $result['isValid']
            && !$result['isEncrypted']
            && $result['version'] !== null
            && self::isVersionFpdiCompatible($result['version']);

        return $result;
    }

    public static function getVersion(string $filePath): ?string
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return null;
        }

        $header = fread($handle, 64);
        fclose($handle);

        if (!is_string($header)) {
            return null;
        }

        if (preg_match('/%PDF-(\d\.\d)/', $header, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    public static function isEncrypted(string $filePath): bool
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        $sample = fread($handle, 1024 * 1024);
        fclose($handle);

        if (!is_string($sample)) {
            return false;
        }

        return strpos($sample, '/Encrypt') !== false;
    }

    private static function hasPdfHeader(string $filePath): bool
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 5);
        fclose($handle);

        return is_string($header) && strpos($header, '%PDF-') === 0;
    }

    private static function isVersionFpdiCompatible(string $version): bool
    {
        $parts = explode('.', $version);
        if (count($parts) !== 2) {
            return false;
        }

        $major = (int) $parts[0];
        $minor = (int) $parts[1];

        if ($major < self::FPDI_MAX_VERSION_MAJOR) {
            return true;
        }

        if ($major > self::FPDI_MAX_VERSION_MAJOR) {
            return false;
        }

        return $minor <= self::FPDI_MAX_VERSION_MINOR;
    }
}
