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

    private function _generarTotales(PDF $pdf, array $solicitud, float $total)
    {
        $nht = 5;
        $wds = [30, 90, 30, 40];
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($wds[0] + $wds[1] + $wds[2], $nht, 'Subtotal', 1, 0, 'R');
        $pdf->Cell(40, $nht, '$' . number_format($total, 2), 1, 1, 'R');

        if ($solicitud['IVA'] === 't') {
            $iva = $total * 0.16;
            $granTotal = $total + $iva;
            $pdf->Cell($wds[0] + $wds[1] + $wds[2], $nht, 'IVA (16%)', 1, 0, 'R');
            $pdf->Cell(40, $nht, '$' . number_format($iva, 2), 1, 1, 'R');
            $pdf->Cell($wds[0] + $wds[1] + $wds[2], $nht, 'Total', 1, 0, 'R');
            $pdf->Cell(40, $nht, '$' . number_format($granTotal, 2), 1, 1, 'R');
        }
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
                    ? 'EFECTIVO'
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
                $sku = $isService ? 'N/A' : $item['Codigo'];
                $cantidad = $isService ? 1 : $item['Cantidad'];
                $precio = $item['Importe'];
                $importe = $isService ? $precio : $cantidad * $precio;
                $subtotal += $importe;

                $nb = $pdf->NbLines($wds[2], $nombre);
                $rowHeight = $nb * $lineHeight;

                if ($pdf->GetY() + $rowHeight > $pdf->getPageBreakTrigger()) {
                    $pdf->AddPage($pdf->getCurOrientation());
                }

                $x0 = $pdf->GetX();
                $y0 = $pdf->GetY();

                $pdf->MultiCell($wds[0], $rowHeight, $cantidad, 1, 'C', false);
                $pdf->SetXY($x0 + $wds[0], $y0);
                $pdf->MultiCell($wds[1], $rowHeight, 'PZ', 1, 'C', false); // Hardcoded from image
                $pdf->SetXY($x0 + $wds[0] + $wds[1], $y0);
                $pdf->MultiCell($wds[2], $lineHeight, $nombre, 1, 'L', false);
                $pdf->SetXY($x0 + $wds[0] + $wds[1] + $wds[2], $y0);
                $pdf->MultiCell($wds[3], $rowHeight, $sku, 1, 'C', false);
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

        $pdf->SetXY($x_start, $y_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'SUBTOTAL', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($subtotal, 2), 1, 1, 'R');

        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'ANTICIPO 50%', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '', 1, 1, 'R');

        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'DESCUENTO', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '', 1, 1, 'R');

        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'SUBTOTAL', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($subtotal, 2), 1, 1, 'R');

        $iva = 0;
        if ($orden['IVA'] === 't') {
            $iva = $subtotal * 0.16;
        }
        $total = $subtotal + $iva;

        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'IVA', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($iva, 2), 1, 1, 'R');

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

        $pdf->SetX($x_start);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($col_width1, $line_height, 'TOTAL', 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell($col_width2, $line_height, '$' . number_format($total, 2), 1, 1, 'R');
    }

    private function _generarPieOrden(PDF $pdf, array $orden)
    {
        $y = $pdf->GetY();
        $pdf->SetY($y - 40);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(110, 7, 'RECEPCION DE FACTURAS', 1, 1, 'C', true);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(110, 7, 'ENVIAR FACTURAS A LOS CORREOS:', 'LR', 1, 'C');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(110, 7, 'compras@campusmerida.com', 'LR', 1, 'C');
        $pdf->Cell(110, 7, 'gfreyre@campusmerida.com', 'LRB', 1, 'C');
        $pdf->SetY($y);
        if (
            isset($orden['UsuarioSession']['Firma_digital']) &&
            !empty($orden['UsuarioSession']['Firma_digital'])
        ) {
            $signatureX = $pdf->GetX();
            $signatureY = $pdf->GetY();
            $signatureWidth = 50;
            $firmaPath =
                FPath::FUSER .
                $orden['UsuarioSession']['ID_Usuario'] .
                DIRECTORY_SEPARATOR .
                $orden['UsuarioSession']['Firma_digital'];
            log_message('info', 'Firma ' . $firmaPath);
            if (file_exists($firmaPath)) {
                $imageWidth = 70;
                $imageHeight = 35;
                $x = $signatureX + ($signatureWidth - $imageWidth) / 2;
                $pdf->Image($firmaPath, $x, $signatureY, $imageWidth, $imageHeight);
                $pdf->SetY($signatureY + $imageHeight);
            }

            $pdf->SetX($signatureX);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell($signatureWidth, 5, 'FIRMA', 'T', 0, 'C');
            $pdf->Ln(5);
            $pdf->SetX($signatureX);
            $pdf->Cell($signatureWidth, 5, $orden['UsuarioSession']['Nombre'] ?? '', 0, 0, 'C');
        } else {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(50, 5, 'FIRMA', 'T', 0, 'C');
            $pdf->Ln(5);
            $pdf->Cell(50, 5, $orden['UsuarioSession']['Nombre'] ?? '', 0, 0, 'C');
        }
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

    /**
     * Dibuja el contenido de la requisición de pago en el objeto PDF.
     *
     * @param PDF $pdf El objeto PDF en el que se dibujará la requisición.
     * @param array $data Los datos de la requisición de pago.
     * @return void
     */
    private function _generarRequisicionPago(PDF $pdf, array $data)
    {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 3, 'MBSP RENTAS SA DE CV', 0, 1, 'C');
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
        if ($data['Tipo'] == 0) {
            $pdf->SetFont('ZapfDingbats', '', 10);
            $pdf->Text($x + 1, $y + 4, '4');
            $pdf->SetFont('Arial', '', 10);
        }
        $pdf->Text($x + 7, $y + 4, 'Efectivo');

        $y += 7;
        $pdf->Rect($x, $y, 5, 5);
        if ($data['Tipo'] == 1) {
            $pdf->SetFont('ZapfDingbats', '', 10);
            $pdf->Text($x + 1, $y + 4, '4');
            $pdf->SetFont('Arial', '', 10);
        }
        $pdf->Text($x + 7, $y + 4, 'Credito');
        $pdf->SetY($y + 10);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Fecha de Solicitud', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(50, 7, date('d-M-Y', strtotime($data['Fecha'])), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Departamento', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            50,
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
            50,
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
            50,
            7,
            mb_convert_encoding($data['ProveedorNombre'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
            'L',
        );

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Fecha de Pago', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(50, 7, '', 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 7, 'Importe Total', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(50, 7, '$' . number_format($data['ImporteTotal'], 2), 1, 1, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(15, 7, 'NO.', 1, 0, 'C');
        $pdf->Cell(40, 7, 'NO. FACTURA', 1, 0, 'C');
        $pdf->Cell(30, 7, 'IMPORTE', 1, 0, 'C');
        $pdf->Cell(110, 7, 'DESCRIPCION DE PAGO', 1, 1, 'C');

        $pdf->SetFont('Arial', '', 10);

        $pdf->Cell(15, 7, '1', 1, 0, 'C');
        $pdf->Cell(40, 7, '', 1, 0, 'C');
        $pdf->Cell(30, 7, '$' . number_format($data['ImporteTotal'], 2), 1, 0, 'R');
        $pdf->Cell(
            110,
            7,
            mb_convert_encoding($data['DescripcionPago'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
            'L',
        );

        for ($i = 2; $i <= 10; $i++) {
            $pdf->Cell(15, 7, $i, 1, 0, 'C');
            $pdf->Cell(40, 7, '', 1, 0, 'C');
            $pdf->Cell(30, 7, '', 1, 0, 'C');
            $pdf->Cell(110, 7, '', 1, 1, 'L');
        }
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 7, 'BANCO:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(70, 7, mb_convert_encoding($data['Banco'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 7, 'CUENTA:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(70, 7, mb_convert_encoding($data['Cuenta'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 7, 'CLABE:', 1, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(70, 7, mb_convert_encoding($data['Clabe'], 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');

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

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, 'Notificar a:', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(
            0,
            10,
            mb_convert_encoding($data['NotificarA'], 'ISO-8859-1', 'UTF-8'),
            1,
            1,
            'L',
        );
    }
}