<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\DonationRepository;
use App\Services\ReceiptPdfService;

class PublicReceiptController
{
    public function __construct(
        private readonly DonationRepository $donations = new DonationRepository(),
        private readonly ReceiptPdfService $receiptPdf = new ReceiptPdfService(),
    ) {
    }

    public function show(Request $request, array $params): void
    {
        $token = (string) ($params['token'] ?? '');
        $donation = $this->donations->findByPublicToken($token);

        if ($donation === null) {
            Response::error('Receipt not found', 404);
            return;
        }

        if ($donation['status'] !== 'completed' || empty($donation['receipt_number'])) {
            Response::error('Receipt is not available', 422);
            return;
        }

        $format = $request->query['format'] ?? 'pdf';

        if ($format === 'html') {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->receiptPdf->renderHtml($donation);
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="receipt-' . $donation['receipt_number'] . '.pdf"');
        echo $this->receiptPdf->renderPdf($donation);
    }
}
