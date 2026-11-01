<?php
// Clase simplificada FPDF para generar PDFs
class FPDF {
    protected $orientation;
    protected $unit;
    protected $format;
    protected $w;
    protected $h;
    protected $wPt;
    protected $hPt;
    protected $k;
    protected $current_font_family;
    protected $current_font_style;
    protected $current_font_size;
    protected $x;
    protected $y;
    protected $page = 0;
    protected $pages = [];
    protected $content = [];
    protected $buffer = '';
    protected $images = [];
    protected $links = [];
    protected $fonts = [];
    protected $line_width;
    protected $text_color = [0, 0, 0];
    protected $fill_color = [255, 255, 255];
    protected $draw_color = [0, 0, 0];
    protected $cell_padding = 0;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        $this->orientation = strtoupper($orientation);
        $this->unit = strtolower($unit);
        $this->format = strtolower($format);
        $this->line_width = 0.2;
        
        $this->_set_format_and_size();
        $this->x = 10;
        $this->y = 10;
        $this->k = ($unit == 'mm') ? 72 / 25.4 : 72;
    }

    protected function _set_format_and_size() {
        $standard_sizes = [
            'A4' => [210, 297],
        ];
        
        if (isset($standard_sizes[$this->format])) {
            list($w, $h) = $standard_sizes[$this->format];
        } else {
            $w = 210;
            $h = 297;
        }
        
        if ($this->orientation == 'P') {
            $this->w = $w;
            $this->h = $h;
        } else {
            $this->w = $h;
            $this->h = $w;
        }
        
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
    }

    public function AddPage($orientation = '') {
        if (empty($orientation)) {
            $orientation = $this->orientation;
        }
        
        $orientation = strtoupper($orientation);
        if ($orientation == 'P') {
            $w = $this->w;
            $h = $this->h;
        } else {
            $w = $this->h;
            $h = $this->w;
        }
        
        $this->page++;
        $this->pages[$this->page] = '';
        $this->current_page = $this->page;
        $this->x = 10;
        $this->y = 10;
    }

    public function SetFont($family = 'Arial', $style = '', $size = 12) {
        $this->current_font_family = $family;
        $this->current_font_style = $style;
        $this->current_font_size = $size;
    }

    public function SetTextColor($r, $g = null, $b = null) {
        if ($g === null) {
            $this->text_color = [$r, $r, $r];
        } else {
            $this->text_color = [$r, $g, $b];
        }
    }

    public function SetFillColor($r, $g = null, $b = null) {
        if ($g === null) {
            $this->fill_color = [$r, $r, $r];
        } else {
            $this->fill_color = [$r, $g, $b];
        }
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false) {
        if ($h == 0) $h = $this->current_font_size / $this->k;
        
        $this->pages[$this->current_page] .= sprintf(
            'BT /F1 %F Tf %F %F Td (%s) Tj ET',
            $this->current_font_size,
            $this->x * $this->k,
            ($this->h - $this->y) * $this->k,
            addslashes($txt)
        ) . "\n";
        
        if ($border) {
            $this->pages[$this->current_page] .= sprintf(
                'q %F w %F %F %F %F re S Q',
                $this->line_width,
                $this->x * $this->k,
                ($this->h - $this->y - $h) * $this->k,
                $w * $this->k,
                $h * $this->k
            ) . "\n";
        }
        
        if ($ln == 0) {
            $this->x += $w;
        } elseif ($ln == 1) {
            $this->y += $h;
            $this->x = 10;
        } else {
            $this->y += $h;
        }
    }

    public function MultiCell($w, $h, $txt = '', $border = 0, $align = '', $fill = false) {
        $this->Cell($w, $h, $txt, $border, 1, $align, $fill);
    }

    public function Ln($h = null) {
        $this->x = 10;
        if ($h !== null) {
            $this->y += $h;
        } else {
            $this->y += $this->current_font_size / $this->k * 1.5;
        }
    }

    public function Output($dest = '', $name = '') {
        // Generar PDF simple con fputcsv como fallback
        $pdf_content = "%PDF-1.4\n";
        $pdf_content .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf_content .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf_content .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . intval($this->wPt) . " " . intval($this->hPt) . "] /Contents 4 0 R >>\nendobj\n";
        $pdf_content .= "4 0 obj\n<< /Length " . strlen(implode("\n", $this->pages)) . " >>\nstream\n";
        $pdf_content .= implode("\n", $this->pages);
        $pdf_content .= "\nendstream\nendobj\nxref\n";
        $pdf_content .= "0 5\n";
        $pdf_content .= "0000000000 65535 f\n";
        $pdf_content .= "0000000009 00000 n\n";
        $pdf_content .= "0000000058 00000 n\n";
        $pdf_content .= "0000000115 00000 n\n";
        $pdf_content .= "0000000206 00000 n\n";
        $pdf_content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $pdf_content .= "startxref\n" . (strlen($pdf_content) - 100) . "\n";
        $pdf_content .= "%%EOF";

        if ($dest == 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            echo $pdf_content;
        } elseif ($dest == 'I') {
            header('Content-Type: application/pdf');
            echo $pdf_content;
        } elseif ($dest == 'F') {
            file_put_contents($name, $pdf_content);
        } else {
            return $pdf_content;
        }
    }
}
?>
