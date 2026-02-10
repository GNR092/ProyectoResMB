<?php

namespace App\Controllers;
use App\Libraries\PDF;
use App\Libraries\Rest;
use App\Libraries\FPath;
use CodeIgniter\I18n\Time;

class GenerarPDF extends BaseController
{
    protected $api;

    public function __construct()
    {
        $this->api = new Rest();
    }

    function GenerarRequisicion(int $id, int $modo = 0)
    {
        $dictamen = $modo == 1;

        try {
            $response = $dictamen
                ? $this->api->getSolicitudWithCotizacion($id)
                : $this->api->getSolicitudWithProducts($id);
        } catch (\Exception $e) {
            log_message('error', 'Error al conectar con el API: ' . $e->getMessage());
            return 'Error al generar el PDF: No se pudo conectar al API.';
        }

        if (empty($response) || !isset($response['Tipo'])) {
            log_message('error', 'Respuesta de API inválida o vacía para la solicitud ID: ' . $id);
            return 'Error al generar el PDF: No se recibieron datos válidos de la solicitud.';
        }

        $solicitud = $response;

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();

        $this->_generarCabecera($pdf, $solicitud);
        $total = $this->_generarTablaProductos($pdf, $solicitud);
        $this->_generarTotales($pdf, $solicitud, $total);
        $this->_mostrarComentarios($pdf, $solicitud);
        $this->_adjuntarArchivo(
            $pdf,
            FPath::FSOLICITUD . $solicitud['Fecha'] . '/',
            $solicitud['Archivo'],
            'Referencia',
        );

        if ($dictamen && !empty($solicitud['cotizacion']['Cotizacion_Files'])) {
            $cfiles = explode(',', $solicitud['cotizacion']['Cotizacion_Files']);
            foreach ($cfiles as $file) {
                $this->_adjuntarArchivo(
                    $pdf,
                    FPath::FCOTIZACION . $solicitud['Fecha'] . '/',
                    $file,
                    'Cotizacion adjunta',
                );
            }
        }

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output('I', 'Requisicion-' . $solicitud['No_Folio'] . '.pdf');
    }

    /**
     * Genera un PDF de requisición y lo guarda en el servidor.
     *
     * @param int $id El ID de la solicitud.
     * @param int $modo Modo de generación (0 = productos, 1 = cotización).
     * @return string|null La ruta del archivo PDF generado o null si hubo un error.
     */
    public function generarYGuardarRequisicion(int $id, int $modo = 0, int $adjuntar = 0): ?string
    {
        $dictamen = $modo == 1;
        $adjuntararchivo = $adjuntar == 1;

        try {
            $response = $dictamen
                ? $this->api->getSolicitudWithCotizacion($id)
                : $this->api->getSolicitudWithProducts($id);
        } catch (\Exception $e) {
            log_message('error', 'Error al conectar con el API: ' . $e->getMessage());
            return null;
        }

        if (empty($response) || !isset($response['Tipo'])) {
            log_message('error', 'Respuesta de API inválida o vacía para la solicitud ID: ' . $id);
            return null;
        }

        $solicitud = $response;

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();

        $this->_generarCabecera($pdf, $solicitud);
        $total = $this->_generarTablaProductos($pdf, $solicitud);
        $this->_generarTotales($pdf, $solicitud, $total);
        $this->_mostrarComentarios($pdf, $solicitud);
        if ($adjuntararchivo) {
            $this->_adjuntarArchivo(
                $pdf,
                FPath::FSOLICITUD . $solicitud['Fecha'] . '/',
                $solicitud['Archivo'],
                'Referencia',
            );
        }

        if ($dictamen && !empty($solicitud['cotizacion']['Cotizacion_Files'])) {
            $cfiles = explode(',', $solicitud['cotizacion']['Cotizacion_Files']);
            foreach ($cfiles as $file) {
                $this->_adjuntarArchivo(
                    $pdf,
                    FPath::FCOTIZACION . $solicitud['Fecha'] . '/',
                    $file,
                    'Cotizacion adjunta',
                );
            }
        }

        $folderPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'pdf_solicitudes';
        if (!is_dir($folderPath)) {
            if (!mkdir($folderPath, 0777, true)) {
                log_message('error', 'No se pudo crear el directorio para los PDFs de solicitud.');
                return null;
            }
        }

        $fileName = 'Requisicion-' . $solicitud['No_Folio'] . '.pdf';
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        $pdf->Output('F', $filePath);

        return $filePath;
    }

    private function _generarCabecera(PDF $pdf, array $solicitud)
    {
        $titulo = in_array($solicitud['Tipo'], [0, 1])
            ? 'REQUISICIÓN DE COMPRA'
            : 'SOLICITUD DE SERVICIOS Y SUMINISTROS DE INSUMOS';

        $pdf->AddPage();
        $pdf->Title($solicitud['Complejo'], 0, 0, 0, 0, 'C');
        $pdf->Ln(6);
        $pdf->Title($titulo, 0, 0, 0, 0, 'C', 'B', 13);
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Folio: ' . $solicitud['No_Folio'], 0, 1, 'C');
        $pdf->Cell(
            98,
            10,
            'Fecha Solicitud: ' . date('d/m/Y', strtotime($solicitud['Fecha'] ?? 'now')),
            0,
            0,
            'L',
        );
        $pdf->Cell(
            98,
            10,
            'Departamento: ' .
                mb_convert_encoding(
                    ($solicitud['DepartamentoNombre'] ?? '') . ' ' . $solicitud['ID_Place'] ?? '',
                    'ISO-8859-1',
                    'UTF-8',
                ),
            0,
            1,
            'R',
        );
        $pdf->Cell(
            0,
            10,
            'Solicitante: ' .
                mb_convert_encoding($solicitud['UsuarioNombre'] ?? '', 'ISO-8859-1', 'UTF-8'),
            0,
            1,
            'L',
        );
        $pdf->Ln(10);
    }

    private function _generarTablaProductos(PDF $pdf, array $solicitud): float
    {
        $wds = [30, 90, 30, 40];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell($wds[0], 10, 'Codigo', 1, 0, 'C', true);
        $pdf->Cell($wds[1], 10, 'Nombre', 1, 0, 'C', true);
        $pdf->Cell($wds[2], 10, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell($wds[3], 10, 'Importe', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10);
        $total = 0;

        if (isset($solicitud['productos'])) {
            $items = $solicitud['productos'];
            $pdf->SetWidths($wds);
            $isService = $solicitud['Tipo'] == 2;

            foreach ($items as $item) {
                $nombre = mb_convert_encoding($item['Nombre'], 'ISO-8859-1', 'UTF-8');
                $codigo = $isService ? 'N/A' : $item['Codigo'];
                $cantidad = $isService ? 1 : $item['Cantidad'];
                $importe = $item['Importe'];
                $costoFila = $isService ? $importe : $cantidad * $importe;
                $total += $costoFila;

                $lineHeight = 5;
                $widths = $pdf->getWidths();
                $nb = $pdf->NbLines($widths[1], $nombre);
                $rowHeight = $nb * $lineHeight;

                if ($pdf->GetY() + $rowHeight > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage($pdf->getCurOrientation());
                }

                $y0 = $pdf->GetY();
                $x0 = $pdf->GetX();

                $pdf->MultiCell($widths[0], $rowHeight, $codigo, 1, 'C', false);
                $pdf->SetXY($x0 + $widths[0], $y0);
                $pdf->MultiCell($widths[1], $lineHeight, $nombre, 1, 'L', false);
                $pdf->SetXY($x0 + $widths[0] + $widths[1], $y0);
                $pdf->MultiCell($widths[2], $rowHeight, $cantidad, 1, 'C', false);
                $pdf->SetXY($x0 + $widths[0] + $widths[1] + $widths[2], $y0);
                $pdf->MultiCell(
                    $widths[3],
                    $rowHeight,
                    '$' . number_format($costoFila, 2),
                    1,
                    'R',
                    false,
                );
            }
        }
        return $total;
    }

    private function _generarTotales(PDF $pdf, array $solicitud, float $importeAcumulado)
    {
        $nht = 5;
        // Anchos de columnas previos: [30, 90, 30, 40]
        // Sumamos las primeras 3 para alinear la etiqueta a la derecha antes del monto
        $anchoEtiqueta = 30 + 90 + 30; // 150
        $anchoMonto = 40;

        $pdf->SetFont('Arial', 'B', 10);

        // 1. Calcular Montos
        $subtotal = $importeAcumulado;
        $iva = 0;

        // Validamos si tiene IVA (Soporta formato antiguo 't' o nuevo 1/true)
        $tieneIVA = !empty($solicitud['IVA']) && ($solicitud['IVA'] === 't' || $solicitud['IVA'] == 1 || $solicitud['IVA'] === true);

        if ($tieneIVA) {
            $iva = $subtotal * 0.16;
        }

        $granTotal = $subtotal + $iva;

        // 2. Imprimir Subtotal
        $pdf->Cell($anchoEtiqueta, $nht, 'Subtotal', 1, 0, 'R');
        $pdf->Cell($anchoMonto, $nht, '$' . number_format($subtotal, 2), 1, 1, 'R');

        // 3. Imprimir IVA (Solo si aplica)
        if ($tieneIVA) {
            $pdf->Cell($anchoEtiqueta, $nht, 'IVA (16%)', 1, 0, 'R');
            $pdf->Cell($anchoMonto, $nht, '$' . number_format($iva, 2), 1, 1, 'R');
        }

        // 4. Imprimir Total
        $pdf->Cell($anchoEtiqueta, $nht, 'Total', 1, 0, 'R');
        $pdf->Cell($anchoMonto, $nht, '$' . number_format($granTotal, 2), 1, 1, 'R');
    }

    private function _mostrarComentarios(PDF $pdf, array $solicitud)
    {
        if (isset($solicitud['ComentariosUser'])) {
            $comentarios = mb_convert_encoding(
                $solicitud['ComentariosUser'],
                'ISO-8859-1',
                'UTF-8',
            );
            $pdf->Ln(10);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(190, 10, 'Comentarios', 1, 0, 'C', true);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Ln(10);
            $pdf->MultiCell(190, 7, $comentarios, 1, 'L', false);
        }
    }

    private function _adjuntarArchivo(PDF $pdf, string $basePath, ?string $fileName, string $title)
    {
        if (empty($fileName)) {
            return;
        }

        $filePath = $basePath . $fileName;
        if (file_exists($filePath)) {
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $maxFileSize = 10 * 1024 * 1024;

            if (in_array($fileExtension, $imageExtensions)) {
                $maxFileSizeForResize = 500 * 1024;
                $fileSize = filesize($filePath);
                $tempImagePath = null;

                if ($fileSize > $maxFileSizeForResize) {
                    $imageInfo = getimagesize($filePath);
                    $mime = $imageInfo['mime'];
                    $sourceImage = null;

                    switch ($mime) {
                        case 'image/jpeg':
                            $sourceImage = imagecreatefromjpeg($filePath);
                            break;
                        case 'image/png':
                            $sourceImage = imagecreatefrompng($filePath);
                            break;
                        case 'image/gif':
                            $sourceImage = imagecreatefromgif($filePath);
                            break;
                    }

                    if ($sourceImage) {
                        $originalWidth = imagesx($sourceImage);
                        $originalHeight = imagesy($sourceImage);
                        $maxWidth = 1024;

                        if ($originalWidth > $maxWidth) {
                            $aspectRatio = $originalHeight / $originalWidth;
                            $newWidth = $maxWidth;
                            $newHeight = (int) ($newWidth * $aspectRatio);
                            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                            if ($mime == 'image/png') {
                                imagealphablending($resizedImage, false);
                                imagesavealpha($resizedImage, true);
                                $transparent = imagecolorallocatealpha(
                                    $resizedImage,
                                    255,
                                    255,
                                    255,
                                    127,
                                );
                                imagefilledrectangle(
                                    $resizedImage,
                                    0,
                                    0,
                                    $newWidth,
                                    $newHeight,
                                    $transparent,
                                );
                            }
                            imagecopyresampled(
                                $resizedImage,
                                $sourceImage,
                                0,
                                0,
                                0,
                                0,
                                $newWidth,
                                $newHeight,
                                $originalWidth,
                                $originalHeight,
                            );
                            $tempImagePath =
                                tempnam(sys_get_temp_dir(), 'pdf_img_') . '.' . $fileExtension;
                            switch ($mime) {
                                case 'image/jpeg':
                                    imagejpeg($resizedImage, $tempImagePath, 85);
                                    break;
                                case 'image/png':
                                    imagepng($resizedImage, $tempImagePath, 7);
                                    break;
                                case 'image/gif':
                                    imagegif($resizedImage, $tempImagePath);
                                    break;
                            }
                            imagedestroy($sourceImage);
                            imagedestroy($resizedImage);
                        }
                    }
                }

                $imageToEmbed = $tempImagePath ?: $filePath;

                [$width, $height] = getimagesize($imageToEmbed);
                $aspectRatio = $width / $height;
                $maxWidthPdf = 190;
                $maxHeightPdf = 250;

                if ($width / $height > $maxWidthPdf / $maxHeightPdf) {
                    $newWidthPdf = $maxWidthPdf;
                    $newHeightPdf = $maxWidthPdf / $aspectRatio;
                } else {
                    $newHeightPdf = $maxHeightPdf;
                    $newWidthPdf = $maxHeightPdf * $aspectRatio;
                }
                $pdf->AddPage();
                $pdf->Title($title, 0, 0, 0, 0, 'C');
                $pdf->Ln(2);
                $pdf->Image($imageToEmbed, 10, 35, $newWidthPdf, $newHeightPdf);

                if ($tempImagePath && file_exists($tempImagePath)) {
                    unlink($tempImagePath);
                }
            } elseif ($fileExtension === 'pdf') {
                $fileSize = filesize($filePath);
                if ($fileSize > $maxFileSize) {
                    $pdf->AddPage();
                    $pdf->Title($title, 0, 0, 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->MultiCell(
                        190,
                        10,
                        mb_convert_encoding(
                            'El archivo adjunto (PDF) "' .
                                $fileName .
                                '" es demasiado grande (' .
                                round($fileSize / 1024 / 1024, 2) .
                                ' MB) para ser incluido.',
                            'ISO-8859-1',
                            'UTF-8',
                        ),
                        0,
                        'C',
                    );
                    return;
                }

                try {
                    $pageCount = $pdf->setSourceFile($filePath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', $size);
                        $pdf->useTemplate($templateId);
                    }
                    $pdf->Title($title, 0, -35, 0, 0, 'C');
                } catch (\Exception $e) {
                    $pdf->AddPage();
                    $pdf->Title($title, 0, 0, 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->MultiCell(
                        190,
                        10,
                        mb_convert_encoding(
                            'Error al procesar el archivo PDF adjunto: "' . $fileName . '".',
                            'ISO-8859-1',
                            'UTF-8',
                        ),
                        0,
                        'C',
                    );
                }
            } else {
                $pdf->AddPage();
                $pdf->Title($title, 0, 0, 0, 0, 'C');
                $pdf->Ln(10);
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Archivo adjunto no compatible para visualizacion:', 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(0, 10, $fileName, 0, 1);
            }
        } else {
            log_message('warning', '[Adjuntar] Archivo no encontrado en la ruta: ' . $filePath);
        }
    }

    //region Orden de Compra

    public function GenerarOrden(int $id)
    {
        $userid = $this->session->get('id');
        try {
            $orden = $this->api->getOrdenCompra($id);
            $user = $this->api->getUserById($userid);
            $orden['UsuarioSession'] = $user;
            $rzs = $this->api->getRazonSocialByUserID($userid);
            $orden['UsuarioRazon'] = $rzs;
        } catch (\Exception $e) {
            log_message('error', 'Error al conectar con el API: ' . $e->getMessage());
            return 'Error al generar el PDF: No se pudo conectar al API.';
        }

        if (empty($orden)) {
            log_message(
                'error',
                'Respuesta de API inválida o vacía para la orden de compra con ID de solicitud: ' .
                    $id,
            );
            return 'Error al generar el PDF: No se recibieron datos válidos de la orden de compra.';
        }

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $this->_generarCabeceraOrden($pdf, $orden);
        $this->_generarInfoProveedorOrden($pdf, $orden);
        $this->_generarInfoFacturacionOrden($pdf, $orden);
        $subtotal = $this->_generarTablaProductosOrden($pdf, $orden);
        $pdf->Ln(5);
        $this->_generarTotalesOrden($pdf, $orden, $subtotal);
        $this->_generarPieOrden($pdf, $orden);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output('I', 'OrdenCompra-' . $orden['No_Folio'] . '.pdf');
    }

    /**
     * Genera un PDF de Orden de Compra y lo guarda en el servidor.
     *
     * @param int $id El ID de la solicitud (para obtener los datos de la OC).
     * @return string|null La ruta del archivo PDF generado o null si hubo un error.
     */
    public function generarYGuardarOrden(int $id, int $userid): ?string
    {
        try {
            $orden = $this->api->getOrdenCompra($id);
            $user = $this->api->getUserById($userid);
            $orden['UsuarioSession'] = $user;
            $rzs = $this->api->getRazonSocialByUserID($userid);
            $orden['UsuarioRazon'] = $rzs;
        } catch (\Exception $e) {
            log_message(
                'error',
                '[generarYGuardarOrden] Error al conectar con el API: ' . $e->getMessage(),
            );
            return null;
        }

        if (empty($orden)) {
            log_message(
                'error',
                '[generarYGuardarOrden] Respuesta de API inválida para la orden con ID de solicitud: ' .
                    $id,
            );
            return null;
        }

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $this->_generarCabeceraOrden($pdf, $orden);
        $this->_generarInfoProveedorOrden($pdf, $orden);
        $this->_generarInfoFacturacionOrden($pdf, $orden);
        $subtotal = $this->_generarTablaProductosOrden($pdf, $orden);
        $pdf->Ln(5);
        $this->_generarTotalesOrden($pdf, $orden, $subtotal);
        $this->_generarPieOrden($pdf, $orden);

        $folderPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'pdf_ordenes';
        if (!is_dir($folderPath)) {
            if (!mkdir($folderPath, 0777, true)) {
                log_message(
                    'error',
                    'No se pudo crear el directorio para los PDFs de órdenes de compra.',
                );
                return null;
            }
        }

        $fileName = 'OrdenCompra-' . $orden['No_Folio'] . '.pdf';
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        $pdf->Output('F', $filePath);

        return $filePath;
    }

    private function _generarCabeceraOrden(PDF $pdf, array $orden)
    {
        //$pdf->Title()
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $orden['Complejo'], 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(
            0,
            5,
            'Periferico Norte Tablaje 20474 Col. Temozon Norte CP 97302 Merida Yucatan Mx',
            0,
            1,
            'C',
        );
        $pdf->Cell(0, 5, '+', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'ORDEN DE COMPRA', 'T', 1, 'C');
        $pdf->Cell(0, 5, date('d/m/Y', strtotime($orden['Fecha'])), 0, 1, 'R');
        $pdf->Ln(5);
    }

    private function _generarInfoProveedorOrden(PDF $pdf, array $orden)
    {
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'PROVEEDOR:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(70, 5, $orden['proveedor']['RazonSocial'] ?? '', 1, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(35, 5, 'FECHA DE PEDIDO:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 5, date('d/m/Y', strtotime($orden['Fecha'])), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'CONFIRMA PEDIDO:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(70, 5, $orden['proveedor']['Nombre_Contacto'] ?? '', 1, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(35, 5, 'FECHA DE ENTREGA:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 5, '---------', 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'CONDICIONES:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);

        $pdf->Cell(
            70,
            5,
            mb_convert_encoding(
                $orden['MetodoPago'] == 0
                    ? 'CONTADO'
                    : 'CREDITO - ' . $orden['proveedor']['Dias_Credito'] . ' días',
                'ISO-8859-1',
                'UTF-8',
            ),
            1,
            0,
            'L',
        );
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(35, 5, 'NO. COTIZACION:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 5, $orden['cotizacion']['ID_Cotizacion'] ?? '', 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(30, 5, 'NOMBRE ALMACEN:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(70, 5, '---------', 1, 0, 'L');
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(35, 5, 'NO. ALMACEN:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 5, '---------', 1, 1, 'L');
        $pdf->Ln(5);
    }

    private function _generarInfoFacturacionOrden(PDF $pdf, array $orden)
    {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(0, 7, 'DATOS DE FACTURACION', 1, 1, 'C', true);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(40, 5, 'FACTURAR A NOMBRE DE:', 0, 0, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, $orden['Complejo'], 0, 1, 'L');
        $pdf->Ln(1);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->MultiCell(0, 7, $orden['UsuarioRazon']['Ubicacion'] ?? '', 0, 0);

        $current_y = $pdf->GetY();
        $pdf->SetXY(130, $current_y - 13);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(15, 5, 'RFC:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, $orden['ComplejoRFC'], 0, 1, 'L');

        $pdf->SetY($current_y + 5);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(50, 5, 'COTIZACION:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, '', 0, 1, 'L');
        $pdf->Ln(5);
    }

    private function _generarTablaProductosOrden(PDF $pdf, array $orden): float
    {
        $wds = [20, 20, 70, 25, 25, 35];
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell($wds[0], 7, 'CANTIDAD', 1, 0, 'C', true);
        $pdf->Cell($wds[1], 7, 'UNIDAD', 1, 0, 'C', true);
        $pdf->Cell($wds[2], 7, 'CONCEPTO', 1, 0, 'C', true);
        $pdf->Cell($wds[3], 7, 'SKU', 1, 0, 'C', true);
        $pdf->Cell($wds[4], 7, 'PRECIO', 1, 0, 'C', true);
        $pdf->Cell($wds[5], 7, 'IMPORTE', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 8);
        $subtotal = 0;
        $lineHeight = 5;

        $pdf->SetWidths($wds);

        if (isset($orden['productos'])) {
            $items = $orden['productos'];
            $isService = $orden['Tipo'] == 2;

            foreach ($items as $item) {
                $nombre = mb_convert_encoding($item['Nombre'], 'ISO-8859-1', 'UTF-8');
                $sku = $isService ? 'N/A' : mb_convert_encoding($item['Codigo'], 'ISO-8859-1', 'UTF-8');
                $cantidad = $isService ? 1 : $item['Cantidad'];

                // [CORRECCIÓN] Usamos el importe directo. NO REMOVEMOS IVA.
                // El importe guardado en BD es el precio base (Unitario).
                $precio = $item['Importe'];

                $importe = $isService ? $precio : $cantidad * $precio;
                $subtotal += $importe;

                // Calcular el número de líneas para el nombre y el SKU
                $nb_nombre = $pdf->NbLines($wds[2], $nombre);
                $nb_sku = $pdf->NbLines($wds[3], $sku);
                $nb = max($nb_nombre, $nb_sku); // Usar el máximo de líneas
                $rowHeight = $nb * $lineHeight;

                if ($pdf->GetY() + $rowHeight > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage($pdf->getCurOrientation());
                }

                $x0 = $pdf->GetX();
                $y0 = $pdf->GetY();

                $pdf->MultiCell($wds[0], $rowHeight, $cantidad, 1, 'C', false);
                $pdf->SetXY($x0 + $wds[0], $y0);
                $pdf->MultiCell($wds[1], $rowHeight, 'PZ', 1, 'C', false);
                $pdf->SetXY($x0 + $wds[0] + $wds[1], $y0);
                $pdf->MultiCell($wds[2], $lineHeight, $nombre, 1, 'L', false);
                $pdf->SetXY($x0 + $wds[0] + $wds[1] + $wds[2], $y0);
                $pdf->MultiCell($wds[3], $lineHeight, $sku, 1, 'C', false);
                $pdf->SetXY($x0 + $wds[0] + $wds[1] + $wds[2] + $wds[3], $y0);
                $pdf->MultiCell(
                    $wds[4],
                    $rowHeight,
                    '$' . number_format($precio, 2),
                    1,
                    'R',
                    false,
                );
                $pdf->SetXY($x0 + $wds[0] + $wds[1] + $wds[2] + $wds[3] + $wds[4], $y0);
                $pdf->MultiCell(
                    $wds[5],
                    $rowHeight,
                    '$' . number_format($importe, 2),
                    1,
                    'R',
                    false,
                );
            }
        }

        return $subtotal;
    }
    

    private function _generarTotalesOrden(PDF $pdf, array $orden, float $subtotal)
    {
        $x_start = 145;
        $y_start = $pdf->GetY();
        $width = 80;
        $col_width1 = 25;
        $col_width2 = 35;
        $line_height = 5;

        // 1. Primer Subtotal
        $pdf->SetXY($x_start, $y_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'SUBTOTAL', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($subtotal, 2), 1, 1, 'R');

        // 2. Anticipo (Vacio por defecto)
        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'ANTICIPO 50%', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '', 1, 1, 'R');

        // 3. Descuento (Vacio por defecto)
        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'DESCUENTO', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '', 1, 1, 'R');

        // 4. Segundo Subtotal (Base imponible)
        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'SUBTOTAL', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($subtotal, 2), 1, 1, 'R');

        // --- CORRECCIÓN DE IVA AQUÍ ---
        $iva = 0;

        // Verificamos si la orden tiene la marca de IVA (Soporta 't', 1, true)
        $tieneIVA = !empty($orden['IVA']) && ($orden['IVA'] === 't' || $orden['IVA'] == 1 || $orden['IVA'] === true);

        if ($tieneIVA) {
            $iva = $subtotal * 0.16;
        }

        $total = $subtotal + $iva;
        // -----------------------------

        // 5. Renglón de IVA
        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'IVA', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        // Si no tiene IVA, imprimirá $0.00
        $pdf->Cell($col_width2, $line_height, '$' . number_format($iva, 2), 1, 1, 'R');

        // 6. Retenciones (Vacias por defecto)
        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'Retencion ISR', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '', 1, 1, 'R');

        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'Retencion IVA', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '', 1, 1, 'R');

        // 7. Gran Total
        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'TOTAL', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($total, 2), 1, 1, 'R');
    }

    private function _generarPieOrden(PDF $pdf, array $orden)
    {
        $pdf->Ln(5);
        $y = $pdf->GetY();
        $pdf->SetY($y - 40);

        // --- 1. Dibuja la sección de recepción de facturas ---
        // Esta sección siempre aparecerá después de los totales.
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(110, 7, 'RECEPCION DE FACTURAS', 1, 1, 'C', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(110, 7, 'ENVIAR FACTURAS A LOS CORREOS:', 'LR', 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(110, 7, 'compras@campusmerida.com', 'LR', 1, 'C');
        $pdf->Cell(110, 7, 'gfreyre@campusmerida.com', 'LRB', 1, 'C');

        // --- 2. Gestiona y dibuja el bloque de firmas ---

        // Estima la altura requerida solo para el bloque de firmas.
        $altura_firmas = 40; // (Altura de imagen/espacio + texto)

        // Comprueba si hay suficiente espacio para el bloque de firmas. Si no, salta de página.
        if ($pdf->GetY() + $altura_firmas > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage($pdf->getCurOrientation());
        }

        $pdf->Ln(15); // Espacio consistente antes de las firmas.

        // Se captura la posición Y de inicio para el bloque de firmas.
        $y_inicio_firmas = $pdf->GetY();
        $y_linea_firma = $y_inicio_firmas + 20; // La línea de la firma estará 20mm por debajo.
        $y_imagen_firma = $y_inicio_firmas; // La imagen se alinea con el inicio del bloque.
    
        $signatureWidth = 60;
        $x_start = 15; // Posición X inicial absoluta para el primer bloque.

        // --- Dibuja el bloque de tres firmas ---
    
        // FIRMA 1: ELABORADO POR
        $x_elabora = $x_start;
        if (isset($orden['UsuarioSession']['Firma_digital']) && !empty($orden['UsuarioSession']['Firma_digital'])) {
            $firmaPath = FPath::FUSER . $orden['UsuarioSession']['ID_Usuario'] . DIRECTORY_SEPARATOR . $orden['UsuarioSession']['Firma_digital'];
            if (file_exists($firmaPath)) {
                $imageWidth = 50;
                $imageHeight = 20;
                $x_img = $x_elabora + ($signatureWidth - $imageWidth) / 2;
                $pdf->Image($firmaPath, $x_img, $y_imagen_firma, $imageWidth, $imageHeight);
            }
        }
        $pdf->SetXY($x_elabora, $y_linea_firma);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($signatureWidth, 5, 'ELABORADO POR', 'T', 0, 'C');
        $pdf->SetXY($x_elabora, $y_linea_firma + 5);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($signatureWidth, 5, mb_convert_encoding($orden['UsuarioSession']['Nombre'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
        // FIRMA 2: COTIZADO POR
        $x_cotiza = $x_elabora + $signatureWidth + 5;
        $pdf->SetXY($x_cotiza, $y_linea_firma);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($signatureWidth, 5, 'COTIZADO POR', 'T', 0, 'C');
        $pdf->SetXY($x_cotiza, $y_linea_firma + 5);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($signatureWidth, 5, mb_convert_encoding($orden['UsuarioCotizaNombre'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    
        // FIRMA 3: AUTORIZADO POR
        $x_autoriza = $x_cotiza + $signatureWidth + 5;
        $pdf->SetXY($x_autoriza, $y_linea_firma);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($signatureWidth, 5, 'AUTORIZADO POR', 'T', 0, 'C');
        $pdf->SetXY($x_autoriza, $y_linea_firma + 5);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($signatureWidth, 5, mb_convert_encoding($orden['UsuarioAutorizaNombre'] ?? 'AUTORIZADO', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
    }

    //endregion

    /**
     * Genera un PDF de requisición de pago a partir de una solicitud.
     *
     * @param int $id El ID de la solicitud de la cual se generará la requisición de pago.
     * @return string|void Retorna un string con un mensaje de error si falla, o void si el PDF se genera correctamente y se envía al navegador.
     */
    /**
     * Genera un PDF de requisición de pago a partir de una solicitud.
     *
     * @param int $id El ID de la solicitud de la cual se generará la requisición de pago.
     * @return string|void Retorna un string con un mensaje de error si falla, o void si el PDF se genera correctamente y se envía al navegador.
     */
    public function GenerarRequisicionPago(int $id)
    {
        try {
            $solicitud = $this->api->getSolicitudPago($id);

            if (empty($solicitud)) {
                log_message(
                    'error',
                    'API devolvió datos vacíos para la requisición de pago ID: ' . $id,
                );
                return 'Error al generar el PDF: No se recibieron datos válidos de la requisición de pago.';
            }
        } catch (\Exception $e) {
            log_message(
                'error',
                'Excepción al conectar con el API para requisición de pago ID ' .
                    $id .
                    ': ' .
                    $e->getMessage(),
            );
            return 'Error al generar el PDF: No se pudo conectar al API.';
        }

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $this->_generarRequisicionPago($pdf, $solicitud);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output('I', 'RequisicionPago-' . $solicitud['No_Folio'] . '.pdf');
    }

    public function generarYGuardarRequisicionPago(int $id): ?string
    {
        try {
            $solicitud = $this->api->getSolicitudPago($id);
            if (empty($solicitud)) {
                log_message('error', 'API devolvió datos vacíos para la requisición de pago ID: ' . $id);
                return null;
            }
        } catch (\Exception $e) {
            log_message('error', 'Excepción al conectar con el API para requisición de pago ID ' . $id . ': ' . $e->getMessage());
            return null;
        }

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $this->_generarRequisicionPago($pdf, $solicitud);

        $folderPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'pdf_req_pago';
        if (!is_dir($folderPath)) {
            if (!mkdir($folderPath, 0777, true)) {
                log_message('error', 'No se pudo crear el directorio para los PDFs de requisición de pago.');
                return null;
            }
        }

        $fileName = 'RequisicionPago-' . $solicitud['No_Folio'] . '.pdf';
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $fileName;

        $pdf->Output('F', $filePath);

        return $filePath;
    }


    /**
     * Dibuja el contenido de la requisición de pago en el objeto PDF.
     *
     * @param PDF $pdf El objeto PDF en el que se dibujará la requisición.
     * @param array $data Los datos de la requisición de pago.
     * @return void
     */
    private function _generarRequisicionPago(PDF $pdf, array $data)
    {
        $Cwd = 100;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 3, mb_convert_encoding($data['Complejo'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(
            0,
            7,
            mb_convert_encoding('REQUISICIÓN DE PAGO', 'ISO-8859-1', 'UTF-8'),
            0,
            1,
            'C',
        );
        $pdf->Ln(10);

        $pdf->SetFont('Arial', '', 10);
        $x = 15;
        $y = $pdf->GetY();

        $pdf->Rect($x, $y, 5, 5);
        if (isset($data['MetodoPago']) && $data['MetodoPago'] == 0) {
            $pdf->SetFont('ZapfDingbats', '', 10);
            $pdf->Text($x + 1, $y + 4, '4');
            $pdf->SetFont('Arial', '', 10);
        }
        $pdf->Text($x + 7, $y + 4, 'Contado');

        $y += 7;
        $pdf->Rect($x, $y, 5, 5);
        if (isset($data['MetodoPago']) && $data['MetodoPago'] == 1) {
            $pdf->SetFont('ZapfDingbats', '', 10);
            $pdf->Text($x + 1, $y + 4, '4');
            $pdf->SetFont('Arial', '', 10);
        }
        $pdf->Text($x + 7, $y + 4, 'Credito');
        $pdf->SetY($y + 10);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Fecha de Solicitud', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            $Cwd,
            7,
            Time::parse($data['Fecha'])->toLocalizedString('dd MMMM, yyyy'),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Departamento', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            $Cwd,
            7,
            mb_convert_encoding(
                $data['DepartamentoNombre'] . ' ' . $data['ID_Place'],
                'ISO-8859-1',
                'UTF-8',
            ),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Proyecto', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            $Cwd,
            7,
            mb_convert_encoding($data['DepartamentoNombre'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Proveedor', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            $Cwd,
            7,
            mb_convert_encoding($data['ProveedorNombre'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Fecha de Pago', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($Cwd, 7, '', 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Importe Total', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($Cwd, 7, '$' . number_format($data['ImporteTotal'], 2), 1, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(15, 7, 'NO.', 1, 0, 'C');
        $pdf->Cell(40, 7, 'NO. FACTURA', 1, 0, 'C');
        $pdf->Cell(30, 7, 'IMPORTE', 1, 0, 'C');
        $pdf->Cell(110, 7, 'DESCRIPCION DE PAGO', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);

        // Anchos de las columnas
        $wds_pago = [15, 40, 30, 110];
        $lineHeight = 7;

        // Fila 1 (con datos)
        $descripcionPago = mb_convert_encoding($data['DescripcionPago'], 'ISO-8859-1', 'UTF-8');

        // Calcular la altura necesaria para la MultiCell de la descripción de pago
        $nb = $pdf->NbLines($wds_pago[3], $descripcionPago);
        $rowHeight = $nb * $lineHeight;
        if ($rowHeight < $lineHeight) {
            $rowHeight = $lineHeight; // Asegurar que la altura mínima sea lineHeight
        }

        // Chequear si hay que añadir una nueva página
        if ($pdf->GetY() + $rowHeight > $pdf->getPageBreakTrigger()) {
            $pdf->AddPage($pdf->getCurOrientation());
        }

        // Guardar la posición actual para MultiCell
        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY();

        // Celda NO.
        $pdf->MultiCell($wds_pago[0], $rowHeight, '1', 1, 'C', false);
        // Celda NO. FACTURA
        $pdf->SetXY($x0 + $wds_pago[0], $y0);
        $pdf->MultiCell($wds_pago[1], $rowHeight, '', 1, 'C', false);
        // Celda IMPORTE
        $pdf->SetXY($x0 + $wds_pago[0] + $wds_pago[1], $y0);
        $pdf->MultiCell($wds_pago[2], $rowHeight, '$' . number_format($data['ImporteTotal'], 2), 1, 'R', false);
        // Celda DESCRIPCION DE PAGO
        $pdf->SetXY($x0 + $wds_pago[0] + $wds_pago[1] + $wds_pago[2], $y0);
        $pdf->MultiCell($wds_pago[3], $lineHeight, $descripcionPago, 1, 'L', false);

                $pdf->SetY($y0 + $rowHeight); // Actualizar la posición Y después de la fila completa

        

                // Filas 2 a 10 (vacías)

                $rowHeightVacia = $lineHeight; // Para filas vacías, la altura es la de una línea

        

                for ($i = 2; $i <= 10; $i++) {

                    // Chequear si hay que añadir una nueva página

                    if ($pdf->GetY() + $rowHeightVacia > $pdf->getPageBreakTrigger()) {

                        $pdf->AddPage($pdf->getCurOrientation());

                    }

        

                    $x0 = $pdf->GetX();

                    $y0 = $pdf->GetY();

        

                    $pdf->MultiCell($wds_pago[0], $rowHeightVacia, $i, 1, 'C', false);

                    $pdf->SetXY($x0 + $wds_pago[0], $y0);

                    $pdf->MultiCell($wds_pago[1], $rowHeightVacia, '', 1, 'C', false);

                    $pdf->SetXY($x0 + $wds_pago[0] + $wds_pago[1], $y0);

                    $pdf->MultiCell($wds_pago[2], $rowHeightVacia, '', 1, 'C', false);

                    $pdf->SetXY($x0 + $wds_pago[0] + $wds_pago[1] + $wds_pago[2], $y0);

                    $pdf->MultiCell($wds_pago[3], $rowHeightVacia, '', 1, 'L', false);

        

                    $pdf->SetY($y0 + $rowHeightVacia); // Actualizar la posición Y después de la fila completa

                }
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 7, 'BANCO:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            70,
            7,
            mb_convert_encoding(
                isset($data['cuenta_details']) ? 'Cuenta Seleccionada' : $data['Banco'],
                'ISO-8859-1',
                'UTF-8',
            ),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 7, 'CUENTA:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            70,
            7,
            mb_convert_encoding(
                isset($data['cuenta_details']) ? $data['cuenta_details']['Cuenta'] : $data['Cuenta'],
                'ISO-8859-1',
                'UTF-8',
            ),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 7, 'CLABE:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            70,
            7,
            mb_convert_encoding(
                isset($data['cuenta_details']) ? 'N/A' : $data['Clabe'],
                'ISO-8859-1',
                'UTF-8',
            ),
            1,
            1,
            'L',
        );

        $pdf->Ln(10);

        $y_firmas = $pdf->GetY();
        $ancho_firma = 60;
        $alto_firma = 20;

        $pdf->SetXY(15, $y_firmas);
        $pdf->Cell($ancho_firma, 5, 'Solicita', 0, 1, 'C');
        $pdf->SetX(15);
        $pdf->Cell($ancho_firma, $alto_firma, '', 'B', 1, 'C');
        $pdf->SetX(15);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(
            $ancho_firma,
            5,
            mb_convert_encoding($data['Solicita'], 'ISO-8859-1', 'UTF-8'),
            0,
            1,
            'C',
        );

        $pdf->SetXY(75, $y_firmas);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($ancho_firma, 5, 'Vo. Bo', 0, 1, 'C');
        $pdf->SetX(75);
        $pdf->Cell($ancho_firma, $alto_firma, '', 'B', 1, 'C');
        $pdf->SetX(75);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            $ancho_firma,
            5,
            mb_convert_encoding($data['VoBo'], 'ISO-8859-1', 'UTF-8'),
            0,
            1,
            'C',
        );

        $pdf->SetXY(135, $y_firmas);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell($ancho_firma, 5, 'Autoriza', 0, 1, 'C');
        $pdf->SetX(135);
        $pdf->Cell($ancho_firma, $alto_firma, '', 'B', 1, 'C');
        $pdf->SetX(135);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            $ancho_firma,
            5,
            mb_convert_encoding($data['Autoriza'], 'ISO-8859-1', 'UTF-8'),
            0,
            1,
            'C',
        );
        $pdf->Ln(10);
    }

    public function GenerarEntregaMateriales()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setStatusCode(405, 'Method Not Allowed');
        }
        $payload = $this->request->getJSON(true);

        $validation = \Config\Services::validation();

        $validation->setRules([
            'fecha' => 'required|valid_date[Y-m-d]',
            'nombreUsuarioEntrega' => 'required|string|max_length[100]',
            'idDepartamentoRecibe' => 'required|integer',
            'nombreUsuarioRecibe' => 'required|string|max_length[100]',
            'materiales' => 'required',
            'materiales.*.id' => 'required|integer',
            'materiales.*.codigo' => 'required|string',
            'materiales.*.nombre' => 'required|string',
            'materiales.*.cantidad' => 'required|integer|greater_than[0]',
            'materiales.*.existencia' => 'required|integer',
        ]);

        if (!$validation->run($payload)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['errors' => $validation->getErrors()]);
        }

        $departamentosModel = new \App\Models\DepartamentosModel();

        $departamentoEntrega = $this->session->get('departamento_usuario') ?? 'No disponible';
        $departamentoRecibeInfo = $departamentosModel->find($payload['idDepartamentoRecibe']);
        $departamentoRecibe = $departamentoRecibeInfo
            ? $departamentoRecibeInfo['Nombre']
            : 'Desconocido';

        // Estructurar los datos para el PDF
        $data = [
            'fecha' => Time::createFromFormat('Y-m-d', $payload['fecha'])->toLocalizedString(
                'dd MMMM, yyyy',
            ),
            'departamento_entrega' => $departamentoEntrega,
            'nombre_entrega' => $payload['nombreUsuarioEntrega'],
            'departamento_recibe' => $departamentoRecibe,
            'nombre_recibe' => $payload['nombreUsuarioRecibe'],
            'productos' => $payload['materiales'],
        ];

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $this->_generarCabeceraEntrega($pdf);
        $this->_generarInfoEntrega($pdf, $data);
        $this->_generarTablaProductosEntrega($pdf, $data['productos']);
        $this->_generarFirmasEntrega($pdf);

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output('I', 'Entrega-Materiales.pdf');
    }

    private function _generarCabeceraEntrega(PDF $pdf)
    {
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Entrega de productos/materiales', 0, 1, 'C');
        $pdf->Ln(10);
    }

    private function _generarInfoEntrega(PDF $pdf, array $data)
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Entrega', 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 7, 'Fecha: ' . $data['fecha'], 1);
        $pdf->Cell(
            95,
            7,
            'Departamento: ' .
                mb_convert_encoding($data['departamento_entrega'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
        );
        $pdf->Cell(
            190,
            7,
            'Nombre de la persona que entrega: ' .
                mb_convert_encoding($data['nombre_entrega'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
        );
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Recibe', 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            190,
            7,
            'Departamento: ' .
                mb_convert_encoding($data['departamento_recibe'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
        );
        $pdf->Cell(
            190,
            7,
            'Nombre de la persona que recibe: ' .
                mb_convert_encoding($data['nombre_recibe'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
        );
        $pdf->Ln(10);
    }

    private function _generarTablaProductosEntrega(PDF $pdf, array $productos)
    {
        $wds = [30, 80, 40, 40];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(
            $wds[0],
            10,
            mb_convert_encoding('Código', 'ISO-8859-1', 'UTF-8'),
            1,
            0,
            'C',
            true,
        );
        $pdf->Cell($wds[1], 10, 'Nombre', 1, 0, 'C', true);
        $pdf->Cell($wds[2], 10, 'Cantidad a entregar', 1, 0, 'C', true);
        $pdf->Cell($wds[3], 10, 'Existencia actual', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetWidths($wds);
        $lineHeight = 7;

        foreach ($productos as $producto) {
            $nombre = mb_convert_encoding($producto['nombre'], 'ISO-8859-1', 'UTF-8');

            $nb = $pdf->NbLines($wds[1], $nombre);
            $rowHeight = $nb * $lineHeight;

            if ($pdf->GetY() + $rowHeight > $pdf->getPageBreakTrigger()) {
                $pdf->AddPage($pdf->getCurOrientation());
            }

            $x0 = $pdf->GetX();
            $y0 = $pdf->GetY();

            $pdf->MultiCell($wds[0], $rowHeight, $producto['codigo'], 1, 'C', false);
            $pdf->SetXY($x0 + $wds[0], $y0);
            $pdf->MultiCell($wds[1], $lineHeight, $nombre, 1, 'L', false);
            $pdf->SetXY($x0 + $wds[0] + $wds[1], $y0);
            $pdf->MultiCell($wds[2], $rowHeight, $producto['cantidad'], 1, 'C', false);
            $pdf->SetXY($x0 + $wds[0] + $wds[1] + $wds[2], $y0);
            $pdf->MultiCell($wds[3], $rowHeight, $producto['existencia'], 1, 'C', false);
        }
    }

    private function _generarFirmasEntrega(PDF $pdf)
    {
        $pdf->Ln(20);
        $y_firmas = $pdf->GetY();
        $ancho_firma = 80;
        $alto_firma = 20;

        $pdf->SetXY(25, $y_firmas);
        $pdf->Cell($ancho_firma, 5, 'Firma de quien entrega', 0, 1, 'C');
        $pdf->SetX(25);
        $pdf->Cell($ancho_firma, $alto_firma, '', 'B', 1, 'C');

        $pdf->SetXY(115, $y_firmas);
        $pdf->Cell($ancho_firma, 5, 'Firma de quien recibe', 0, 1, 'C');
        $pdf->SetX(115);
        $pdf->Cell($ancho_firma, $alto_firma, '', 'B', 1, 'C');
    }

    /**
     * Genera una factura en PDF para una solicitud de servicio.
     *
     * @param int $idSolicitud El ID de la solicitud de servicio.
     * @return string|null La ruta del archivo PDF generado o null si hubo un error.
     */
    public function GenerarFacturaServicioPDF(int $idSolicitud): ?string
    {
        try {
            $solicitud = $this->api->getSolicitudWithServiceDetails($idSolicitud);

            if (empty($solicitud) || $solicitud['Tipo'] != SolicitudTipo::Servicios) {
                log_message(
                    'error',
                    'Solicitud no encontrada o no es de tipo servicio para ID: ' . $idSolicitud,
                );
                return null;
            }

            $pdf = new PDF('P', 'mm', 'Letter');
            $pdf->AliasNbPages();
            $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(
                0,
                10,
                mb_convert_encoding($solicitud['Complejo'], 'ISO-8859-1', 'UTF-8'),
                0,
                1,
                'C',
            );
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(
                0,
                5,
                mb_convert_encoding($solicitud['UsuarioRazon']['Ubicacion'], 'ISO-8859-1', 'UTF-8'),
                0,
                1,
                'C',
            );
            $pdf->Cell(
                0,
                5,
                mb_convert_encoding('RFC: ' . $solicitud['ComplejoRFC'], 'ISO-8859-1', 'UTF-8'),
                0,
                1,
                'C',
            );
            $pdf->Ln(10);

            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(
                0,
                10,
                mb_convert_encoding('FACTURA DE SERVICIO', 'ISO-8859-1', 'UTF-8'),
                0,
                1,
                'C',
            );
            $pdf->Ln(5);

            // Información de la factura
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(
                95,
                7,
                mb_convert_encoding(
                    'Factura No: ' . $solicitud['No_Folio'] . '-SER',
                    'ISO-8859-1',
                    'UTF-8',
                ),
                0,
                0,
                'L',
            );
            $pdf->Cell(
                95,
                7,
                mb_convert_encoding(
                    'Fecha: ' .
                        date(
                            'd/m/Y',
                            strtotime($solicitud['Fecha_Aprobacion'] ?? $solicitud['Fecha']),
                        ),
                    'ISO-8859-1',
                    'UTF-8',
                ),
                0,
                1,
                'R',
            );
            $pdf->Cell(
                95,
                7,
                mb_convert_encoding(
                    'Solicitud No: ' . $solicitud['No_Folio'],
                    'ISO-8859-1',
                    'UTF-8',
                ),
                0,
                1,
                'L',
            );
            $pdf->Ln(5);

            // Detalles del Proveedor
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 7, mb_convert_encoding('Proveedor:', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(
                0,
                7,
                mb_convert_encoding(
                    $solicitud['Proveedor']['RazonSocial'] ?? 'N/A',
                    'ISO-8859-1',
                    'UTF-8',
                ),
                0,
                1,
                'L',
            );
            $pdf->Cell(
                0,
                7,
                mb_convert_encoding(
                    'RFC: ' . ($solicitud['Proveedor']['RFC'] ?? 'N/A'),
                    'ISO-8859-1',
                    'UTF-8',
                ),
                0,
                1,
                'L',
            );
            $pdf->Ln(5);

            // Tabla de servicios
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(
                150,
                7,
                mb_convert_encoding('Descripción', 'ISO-8859-1', 'UTF-8'),
                1,
                0,
                'C',
                true,
            );
            $pdf->Cell(
                40,
                7,
                mb_convert_encoding('Importe', 'ISO-8859-1', 'UTF-8'),
                1,
                1,
                'C',
                true,
            );

            $pdf->SetFont('Arial', '', 10);
            $totalServicios = 0;
            foreach ($solicitud['servicios'] as $servicio) {
                $pdf->Cell(
                    150,
                    7,
                    mb_convert_encoding($servicio['Nombre'], 'ISO-8859-1', 'UTF-8'),
                    1,
                    0,
                    'L',
                );
                $pdf->Cell(40, 7, '$' . number_format($servicio['Importe'], 2), 1, 1, 'R');
                $totalServicios += $servicio['Importe'];
            }

            // Totales
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(150, 7, 'Subtotal', 1, 0, 'R');
            $pdf->Cell(40, 7, '$' . number_format($totalServicios, 2), 1, 1, 'R');

            $ivaMonto = $solicitud['IVA'] === 't' ? $totalServicios * 0.16 : 0;
            $granTotal = $totalServicios + $ivaMonto;

            if ($solicitud['IVA'] === 't') {
                $pdf->Cell(150, 7, 'IVA (16%)', 1, 0, 'R');
                $pdf->Cell(40, 7, '$' . number_format($ivaMonto, 2), 1, 1, 'R');
            }
            $pdf->Cell(150, 7, 'Total', 1, 0, 'R');
            $pdf->Cell(40, 7, '$' . number_format($granTotal, 2), 1, 1, 'R');
            $pdf->Ln(10);

            // Guardar PDF
            $folderPath = FPath::FFACTURAS_SERVICIOS;
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0777, true);
            }
            $fileName = 'FacturaServicio-' . $solicitud['No_Folio'] . '.pdf';
            $filePath = $folderPath . $fileName;
            $pdf->Output('F', $filePath);

            return $filePath;
        } catch (\Exception $e) {
            log_message(
                'error',
                '[GenerarFacturaServicioPDF] Error al generar factura de servicio: ' .
                    $e->getMessage(),
            );
            return null;
        }
    }
}