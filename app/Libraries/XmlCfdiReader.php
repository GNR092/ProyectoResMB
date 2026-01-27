<?php

namespace App\Libraries;

use Exception;

class XmlCfdiReader
{
    /**
     * Parsea un archivo XML (CFDI) y extrae la información clave para inventario.
     * * @param string $filePath Ruta física del archivo temporal subido.
     * @return array Estructura de datos limpia.
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("El archivo XML no fue encontrado.");
        }

        $content = file_get_contents($filePath);
        // Silenciar errores de XML mal formados para manejarlos con try-catch
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            throw new Exception("El archivo no es un XML válido.");
        }

        // Manejo de Namespaces
        $ns = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('cfdi', $ns['cfdi']);
        if (isset($ns['tfd'])) {
            $xml->registerXPathNamespace('tfd', $ns['tfd']);
        }

        // Extraer Datos del Encabezado
        $fecha = (string)$xml['Fecha'];
        $folio = (string)$xml['Folio'];
        $serie = (string)$xml['Serie'];

        // Extraer Emisor (y Receptor
        $emisor = $xml->xpath('//cfdi:Emisor')[0];
        $receptor = $xml->xpath('//cfdi:Receptor')[0];

        $data = [
            'meta' => [
                'fecha'        => $fecha,
                'serie_folio'  => $serie . '-' . $folio,
                'rfc_emisor'   => (string)$emisor['Rfc'],
                'nombre_emisor'=> (string)$emisor['Nombre'],
                'rfc_receptor' => (string)$receptor['Rfc'], // Para validar si es para tu complejo
                'uuid'         => null // Lo llenaremos abajo
            ],
            'conceptos' => []
        ];

        // Extraer UUID
        if (isset($ns['tfd'])) {
            $timbre = $xml->xpath('//tfd:TimbreFiscalDigital');
            if (!empty($timbre)) {
                $data['meta']['uuid'] = (string)$timbre[0]['UUID'];
            }
        }

        // Extraer Producto
        $conceptos = $xml->xpath('//cfdi:Conceptos/cfdi:Concepto');

        foreach ($conceptos as $concepto) {
            // Buscamos SKU o usamos la Descripción
            $noIdentificacion = (string)$concepto['NoIdentificacion'];
            $descripcion = (string)$concepto['Descripcion'];

            // Si no trae código, usamos la descripción como identificador único
            $identificador = empty($noIdentificacion) ? $descripcion : $noIdentificacion;

            $data['conceptos'][] = [
                'cantidad'       => (float)$concepto['Cantidad'],
                'unidad_medida'  => (string)$concepto['ClaveUnidad'], // Ej: H87
                'descripcion'    => $descripcion,
                'identificador'  => $identificador,
            ];
        }

        return $data;
    }
}