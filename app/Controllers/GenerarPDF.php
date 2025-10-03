<?php

namespace App\Controllers;
use App\Libraries\PDF;
use App\Libraries\Rest;
use App\Libraries\FPath;

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

    private function _generarCabecera(PDF $pdf, array $solicitud)
    {
        $titulo = in_array($solicitud['Tipo'], [0, 1])
            ? 'REQUISICIÓN DE COMPRA'
            : 'SOLICITUD DE SERVICIOS Y SUMINISTROS DE INSUMOS';

        $pdf->AddPage();
        $pdf->Title($titulo, 0, 0, 0, 0, 'C');
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
                mb_convert_encoding($solicitud['DepartamentoNombre'] ?? '', 'ISO-8859-1', 'UTF-8'),
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

            $pdf->AddPage();
            $pdf->Title($title, 0, 0, 0, 0, 'C');
            $pdf->Ln(2);

            if (in_array($fileExtension, $imageExtensions)) {
                [$width, $height] = getimagesize($filePath);
                $aspectRatio = $width / $height;
                $maxWidth = 190;
                $maxHeight = 250;

                if ($width / $height > $maxWidth / $maxHeight) {
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth / $aspectRatio;
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = $maxHeight * $aspectRatio;
                }
                $pdf->Image($filePath, 10, 35, $newWidth, $newHeight);
            } elseif ($fileExtension === 'pdf') {
                $pageCount = $pdf->setSourceFile($filePath);
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['width'] > $size['height'] ? 'L' : 'P', $size);
                    $pdf->useTemplate($templateId);
                }
            } else {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(0, 10, 'Archivo adjunto no compatible para visualizacion:', 0, 1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->Cell(0, 10, $fileName, 0, 1);
            }
        }
    }
}