<?php

namespace Database\Factories\Support;

/**
 * Builds a minimal, structurally valid single-page PDF so demo "download"
 * resources resolve to a real file instead of a 404. The cross-reference table
 * offsets are computed as objects are concatenated, so the result opens in any
 * compliant PDF viewer.
 *
 * The visible text is a short ASCII line: the standard PDF base fonts cannot
 * render Arabic without font embedding, which is out of scope for a placeholder.
 */
class DemoPdf
{
    /**
     * Generate a one-page PDF and return its raw bytes.
     */
    public static function generate(string $label = 'Ruwad - Demo Resource'): string
    {
        $label = preg_replace('/[()\\\\]/', '', $label) ?? 'Demo Resource';

        $content = "BT /F1 24 Tf 72 720 Td ({$label}) Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
