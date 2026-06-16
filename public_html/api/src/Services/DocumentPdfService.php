<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class DocumentPdfService
{
    public function renderPdf(string $html, array $printSettings): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', uploadsDir());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper(
            $printSettings['paper'] ?? 'A4',
            $printSettings['orientation'] ?? 'portrait'
        );
        $dompdf->render();

        return $dompdf->output();
    }

    /** @param array<string, mixed> $printSettings */
    public function pageCss(array $printSettings, string $accentColor): string
    {
        $top = (int) ($printSettings['margin_top_mm'] ?? 15);
        $right = (int) ($printSettings['margin_right_mm'] ?? 15);
        $bottom = (int) ($printSettings['margin_bottom_mm'] ?? 15);
        $left = (int) ($printSettings['margin_left_mm'] ?? 15);

        return "@page { margin: {$top}mm {$right}mm {$bottom}mm {$left}mm; }
                :root { --accent: {$accentColor}; }";
    }
}
