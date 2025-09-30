<?php

namespace App\Controllers;
use App\Libraries\PDF;
use App\Libraries\Rest;

class GenerarPDF extends BaseController
{
    function GenerarRequisicion(int $id)
    {
        try {
            // Realizar la petición al API
            $api = new Rest();
            $response = $api->getSolicitudWithProducts($id);
        } catch (\Exception $e) {
            // Captura cualquier excepción que pueda ocurrir durante la llamada a la API
            log_message('error', 'Error al conectar con el API: ' . $e->getMessage());
            return 'Error al generar el PDF: No se pudo conectar al API.';
        }
        
        if (empty($response) || !isset($response['Tipo'])) {
            log_message('error', 'Respuesta de API inválida o vacía para la solicitud ID: ' . $id);
            return 'Error al generar el PDF: No se recibieron datos válidos de la solicitud.';
        }

        $solicitud = $response; // La respuesta es el array de la solicitud.

        #return "<pre>Debug Info:\n" . print_r($response, true) . '</pre>';

        // Use the PDF class defined outside
        // Determina el título basado en el tipo de solicitud
        $titulo = in_array($solicitud['Tipo'], [0, 1]) ? 'REQUISICIÓN DE COMPRA' : 'SOLICITUD DE SERVICIOS Y SUMINISTROS DE INSUMOS';

        $pdf = new PDF('P', 'mm', 'Letter');
        $pdf->setHeaderTitle($titulo);
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Folio: ' . $solicitud['No_Folio'], 0, 1, 'C');

        // --- Fecha y Departamento en la misma línea ---
        // Celda para la Fecha (izquierda, sin salto de línea)
        $pdf->Cell(98, 10, 'Fecha Solicitud: ' . date('d/m/Y', strtotime($solicitud['Fecha'] ?? 'now')), 0, 0, 'L');
        // Celda para el Departamento (derecha, con salto de línea)
        $pdf->Cell(98, 10, 'Departamento: ' . mb_convert_encoding($solicitud['DepartamentoNombre'] ?? '', 'ISO-8859-1', 'UTF-8'), 0, 1, 'R');

        // Celda para el Solicitante (ocupa toda la línea)
        $pdf->Cell(
            0,
            10,
            'Solicitante: ' .
                mb_convert_encoding($solicitud['UsuarioNombre'] ?? '', 'ISO-8859-1', 'UTF-8'),
            0,
            1,
            'L'
        );
        $pdf->Ln(10);
        $wds = [30, 90, 30, 40];
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(230, 230, 230); // Color de fondo gris claro
        $pdf->Cell($wds[0], 10, 'Codigo', 1, 0, 'C', true);
        $pdf->Cell($wds[1], 10, 'Nombre', 1, 0, 'C', true);
        $pdf->Cell($wds[2], 10, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell($wds[3], 10, 'Importe', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 10);
        $total = 0;

        if (isset($response['productos'])) {
            $items = $response['productos'];
            $pdf->SetWidths($wds); // Anchos de las columnas
            $isService = ($solicitud['Tipo'] == 2);

            foreach ($items as $item) {
                $nombre = mb_convert_encoding($item['Nombre'], 'ISO-8859-1', 'UTF-8');

                // Determinar valores basados en si es producto o servicio
                $codigo = $isService ? 'N/A' : $item['Codigo'];
                $cantidad = $isService ? 1 : $item['Cantidad'];
                $importe = $item['Importe'];
                $costoFila = $isService ? $importe : ($cantidad * $importe);
                $total += $costoFila;

                // --- Lógica mejorada para altura de fila dinámica ---
                $lineHeight = 5; // Altura de línea para MultiCell
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
        $nht = 5;
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($wds[0] + $wds[1] + $wds[2] , $nht, 'Subtotal', 1, 0, 'R');
        $pdf->Cell(40, $nht, '$' . number_format($total, 2), 1, 1, 'R');
        
        if ($solicitud['IVA'] === 't') {
            $iva = $total * 0.16; // El cálculo del IVA debe ser sobre el subtotal
            $granTotal = $total + $iva;
            $pdf->Cell($wds[0] + $wds[1] + $wds[2], $nht, 'IVA (16%)', 1, 0, 'R');
            $pdf->Cell(40, $nht, '$' . number_format($iva, 2), 1, 1, 'R');
            $pdf->Cell($wds[0] + $wds[1] + $wds[2], $nht, 'Total', 1, 0, 'R');
            $pdf->Cell(40, $nht, '$' . number_format($granTotal, 2), 1, 1, 'R');
        }

        $this->response->setHeader('Content-Type', 'application/pdf');
        $pdf->Output('I', 'Requisicion-' . $solicitud['No_Folio'] . '.pdf');
    }
}
