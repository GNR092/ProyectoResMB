<?php

namespace App\Libraries;

require_once APPPATH . 'ThirdParty/fpdf/fpdf.php';
use setasign\Fpdi\Fpdi;

// Define the PDF class that extends FPDI
class PDF extends Fpdi
{
    protected $headerTitle = '';

    /**
     * Establece el título del encabezado.
     *
     * @param string $title El título a establecer.
     */
    public function setHeaderTitle(string $title)
    {
        $this->headerTitle = mb_convert_encoding($title ?? '', 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Cabecera de la página.
     */
    function Header()
    {
        $this->Image(FCPATH . 'images/Logo_MBSP.png', 10, 5, 30);
        if ($this->headerTitle != '') {
            $this->SetFont('Arial', 'B', 15);
            $this->Cell(0, 35, $this->headerTitle, 0, 0, 'C');
        }
        $this->Ln(20);
    }

    /**
     * Escribe un título.
     *
     * @param string $txt El texto del título.
     * @param int $w El ancho de la celda.
     * @param int $h La altura de la celda.
     * @param int $border Si se debe dibujar un borde.
     * @param int $ln Dónde ir después de la celda.
     * @param string $aling Alineación del texto.
     */
    function Title($txt, $w, $h, $border, $ln, $aling,$style='B',$size=15)
    {
        $this->SetFont('Arial', $style, $size);
        $this->Cell($w, $h, mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8'), $border, $ln, $aling);
    }

    /**
     * Pie de página.
     */
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // --- Función para calcular la altura de una fila con MultiCell ---
    protected $widths;
    protected $aligns;

    /**
     * Establece los anchos de las columnas.
     *
     * @param array $w Un array con los anchos de las columnas.
     */
    function SetWidths($w)
    {
        // Set the array of column widths
        $this->widths = $w;
    }

    /**
     * Obtiene los anchos de las columnas.
     *
     * @return array Un array con los anchos de las columnas.
     */
    function getWidths()
    {
        // Get the array of column widths
        return $this->widths;
    }

    /**
     * Obtiene el activador de salto de página.
     *
     * @return float El valor del activador de salto de página.
     */
    function getPageBreakTrigger()
    {
        return $this->PageBreakTrigger;
    }

    /**
     * Obtiene la orientación actual de la página.
     *
     * @return string La orientación actual de la página.
     */
    function getCurOrientation()
    {
        return $this->CurOrientation;
    }

    /**
     * Calcula el número de líneas que ocupará un MultiCell.
     *
     * @param int $w El ancho del MultiCell.
     * @param string $txt El texto a calcular.
     * @return int El número de líneas.
     */
    function NbLines($w, $txt)
    {
        // Computes the number of lines a MultiCell of width w will take
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = (($w - 2 * $this->cMargin) * 1000) / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c === ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }
}