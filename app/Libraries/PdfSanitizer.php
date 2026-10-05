<?php

namespace App\Libraries;

/**
 * Sanea PDFs que tienen bytes de datos antes del encabezado %PDF-.
 *
 * La especificacion PDF admite hasta 1024 bytes de preambulo, por eso
 * libmime y los navegadores los aceptan, pero Ghostscript los lee
 * posicionalmente e interpreta como PostScript, provocando
 * "syntaxerror in (binary token, type=137)". Quitar ese preambulo deja el
 * archivo legible por Ghostscript y por FPDI.
 */
class PdfSanitizer
{
    /**
     * La especificacion (PDF 32000-1:2008, 7.5.2) admite hasta 1024 bytes de
     * preambulo, pero lectores reales como FPDI toleran mas. Por eso se escanea
     * el archivo completo: marcar como invalido un PDF con preambulo largo
     * rechazaria archivos que si se pueden leer.
     */
    public const MAX_HEADER_SEARCH_BYTES = 1024;

    /**
     * Localiza el byte en el que comienza el encabezado %PDF-.
     *
     * @return int Offset dentro del archivo, o -1 si no contiene %PDF-.
     */
    public static function detectHeaderOffset(string $filePath): int
    {
        $data = @file_get_contents($filePath, false, null, 0, 512);

        if (!is_string($data) || $data === '') {
            return -1;
        }

        $early = strpos($data, '%PDF-');

        if ($early !== false) {
            return $early;
        }

        $full = @file_get_contents($filePath);

        if (!is_string($full) || $full === '') {
            return -1;
        }

        return self::detectHeaderOffsetInString($full);
    }

    /**
     * Localiza el byte en el que comienza el encabezado %PDF- dentro de datos
     * ya leidos.
     *
     * @return int Offset dentro de los datos, o -1 si no se encuentra %PDF-.
     */
    public static function detectHeaderOffsetInString(string $data): int
    {
        $pos = strpos($data, '%PDF-');

        return $pos === false ? -1 : $pos;
    }

    /**
     * Reescribe el archivo eliminando los bytes previos a %PDF-.
     *
     * Es idempotente: si el encabezado ya esta en el byte 0 devuelve
     * success=true con removedBytes=0 y changed=false, sin tocar el archivo.
     *
     * @return array{success:bool,changed:bool,removedBytes:int,message:string}
     */
    public static function stripPreamble(string $filePath): array
    {
        $result = [
            'success' => false,
            'changed' => false,
            'removedBytes' => 0,
            'message' => '',
        ];

        if (!is_file($filePath) || !is_readable($filePath)) {
            $result['message'] = 'Archivo no encontrado o sin permisos de lectura.';

            return $result;
        }

        $original = @file_get_contents($filePath);

        if (!is_string($original) || $original === '') {
            $result['message'] = 'No se pudo leer el archivo.';

            return $result;
        }

        $offset = self::detectHeaderOffsetInString($original);

        if ($offset < 0) {
            $result['message'] = 'No se encontro el encabezado %PDF- en los primeros ' . self::MAX_HEADER_SEARCH_BYTES . ' bytes.';

            return $result;
        }

        if ($offset === 0) {
            $result['success'] = true;
            $result['message'] = 'El encabezado %PDF- ya esta en el byte 0.';

            return $result;
        }

        $clean = substr($original, $offset);
        $written = @file_put_contents($filePath, $clean);

        if ($written === false || $written !== strlen($clean)) {
            @file_put_contents($filePath, $original);
            $result['message'] = 'No se pudo escribir el archivo saneado; se restauro el original.';

            return $result;
        }

        $result['success'] = true;
        $result['changed'] = true;
        $result['removedBytes'] = $offset;
        $result['message'] = 'Se eliminaron ' . $offset . ' bytes de preambulo antes de %PDF-.';

        return $result;
    }
}
