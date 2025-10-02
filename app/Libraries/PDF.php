<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';

// Define the PDF class that extends FPDF
class PDF extends \FPDF
{
    protected $headerTitle = '';

    public function setHeaderTitle(string $title)
    {
        $this->headerTitle = mb_convert_encoding($title ?? '', 'ISO-8859-1', 'UTF-8');
    }

    // Page header
    function Header()
    {
        $this->Image(FCPATH . 'images/Logo_MBSP.png', 10, 5, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 35, $this->headerTitle, 0, 0, 'C');
        $this->Ln(20);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // --- Función para calcular la altura de una fila con MultiCell ---
    protected $widths;
    protected $aligns;

    function SetWidths($w)
    {
        // Set the array of column widths
        $this->widths = $w;
    }

    function getWidths()
    {
        // Get the array of column widths
        return $this->widths;
    }

    function getPageBreakTrigger()
    {
        return $this->PageBreakTrigger;
    }

    function getCurOrientation()
    {
        return $this->CurOrientation;
    }

    function NbLines($w, $txt)
    {
        // Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") $nb--;
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++;
                continue;
            }
            if ($c === ' ') $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) $i++;
                } else {
                    $i = $sep + 1;
                }
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}