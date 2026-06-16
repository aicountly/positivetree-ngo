<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Throwable;

class DocumentPdfService
{
    public function __construct(
        private readonly ?GotenbergPdfService $gotenberg = null,
    ) {
    }

    public function renderPdf(string $html, array $printSettings): string
    {
        $gotenberg = $this->gotenberg ?? new GotenbergPdfService();
        if ($gotenberg->isConfigured()) {
            try {
                return $gotenberg->renderPdf($html, $printSettings);
            } catch (Throwable $e) {
                error_log('[DocumentPdfService] Gotenberg failed, falling back to dompdf: ' . $e->getMessage());
            }
        }

        return $this->renderWithDompdf($html, $printSettings);
    }

    /** @param array<string, mixed> $printSettings */
    public function pageCss(array $printSettings, string $accentColor): string
    {
        $gotenberg = $this->gotenberg ?? new GotenbergPdfService();
        if ($gotenberg->isConfigured()) {
            return ":root { --accent: {$accentColor}; }";
        }

        $top = (int) ($printSettings['margin_top_mm'] ?? 0);
        $right = (int) ($printSettings['margin_right_mm'] ?? 0);
        $bottom = (int) ($printSettings['margin_bottom_mm'] ?? 0);
        $left = (int) ($printSettings['margin_left_mm'] ?? 0);

        return "@page { margin: {$top}mm {$right}mm {$bottom}mm {$left}mm; }
                :root { --accent: {$accentColor}; }";
    }

    private function renderWithDompdf(string $html, array $printSettings): string
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
}
