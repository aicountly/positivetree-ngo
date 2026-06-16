<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReceiptPdfService
{
    public function renderHtml(array $donation): string
    {
        ob_start();
        $amountInr = number_format($donation['amount_paise'] / 100, 2);
        $donatedAt = $donation['donated_at'];
        include __DIR__ . '/../../templates/receipt.html.php';
        return (string) ob_get_clean();
    }

    public function renderPdf(array $donation): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderHtml($donation));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
