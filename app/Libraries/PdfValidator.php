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
            'headerOffset' => -1,
            'warnings' => [],
        ];

        if (!is_file($filePath) || !is_readable($filePath)) {
            $result['warnings'][] = 'Archivo PDF no encontrado o sin permisos de lectura.';
            return $result;
        }

        $result['headerOffset'] = PdfSanitizer::detectHeaderOffset($filePath);

        if ($result['headerOffset'] < 0) {
            $result['warnings'][] = 'Encabezado PDF invalido (no se encontro %PDF- en el archivo).';
            return $result;
        }

        if ($result['headerOffset'] > 0) {
            $result['warnings'][] =
                'El PDF tiene ' . $result['headerOffset'] . ' bytes de datos antes del encabezado %PDF-.';
        }

        $result['isValid'] = true;
        $result['version'] = self::getVersion($filePath, $result['headerOffset']);
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

    public static function getVersion(string $filePath, int $offset = 0): ?string
    {
        $handle = @fopen($filePath, 'rb');
        if ($handle === false) {
            return null;
        }

        if ($offset > 0 && fseek($handle, $offset) !== 0) {
            fclose($handle);
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
        return PdfSanitizer::detectHeaderOffset($filePath) >= 0;
    }

    /**
     * Prueba funcional: intenta abrir el PDF con FPDI y contar paginas.
     *
     * A diferencia de analyze(), que solo inspecciona la version declarada,
     * esto exercise el mismo lector que usa el PDF consolidado. Si FPDI no
     * logra leer el archivo, el PDF no debe almacenarse como evidencia.
     *
     * @return array{success:bool,pages:int,message:string}
     */
    public static function canImportWithFpdi(string $filePath): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'pages' => 0, 'message' => 'Archivo no encontrado o sin permisos de lectura.'];
        }

        try {
            $pdf = new PDF();
            $pages = $pdf->setSourceFile($filePath);
        } catch (\Throwable $e) {
            return ['success' => false, 'pages' => 0, 'message' => $e->getMessage()];
        }

        if (!is_int($pages) || $pages < 1) {
            return ['success' => false, 'pages' => 0, 'message' => 'El PDF no contiene paginas legibles.'];
        }

        return ['success' => true, 'pages' => $pages, 'message' => ''];
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
