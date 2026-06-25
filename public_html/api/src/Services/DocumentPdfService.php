<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class DocumentPdfService
{
    public function __construct(
        private readonly ?GotenbergPdfService $gotenberg = null,
    ) {
    }

    public function renderPdf(string $html, array $printSettings): string
    {
        $gotenberg = $this->gotenberg ?? new GotenbergPdfService();
        if (!$gotenberg->isConfigured()) {
            throw new RuntimeException(
                'GOTENBERG_BASE_URL is not configured. Set it in .env to your Gotenberg service URL.'
            );
        }

        return $gotenberg->renderPdf($html, $printSettings);
    }

    /** @param array<string, mixed> $printSettings */
    public function pageCss(array $printSettings, string $accentColor): string
    {
        return ":root { --accent: {$accentColor}; }";
    }
}
