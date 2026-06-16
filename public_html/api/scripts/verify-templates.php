<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Repositories\DocumentSettingsRepository;
use App\Services\DocumentPdfService;
use App\Services\DocumentTemplateService;
use App\Services\GotenbergPdfService;

$template = new DocumentTemplateService();
$settings = (new DocumentSettingsRepository())->get();
$donation = $template->sampleDonation();

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$receiptHtml = $template->renderReceiptHtml($settings, $donation);
$certHtml = $template->renderCertificateHtml($settings, $donation);

file_put_contents($dataDir . '/receipt-preview-test.html', $receiptHtml);
file_put_contents($dataDir . '/cert-preview-test.html', $certHtml);

$assertContains = static function (string $haystack, string $needle, string $label): void {
    if (strpos($haystack, $needle) === false) {
        fwrite(STDERR, "MISSING in {$label}: {$needle}\n");
        exit(1);
    }
    echo "ok {$label}: {$needle}\n";
};

echo "-- receipt template --\n";
$assertContains($receiptHtml, 'DONATION RECEIPT', 'receipt');
$assertContains($receiptHtml, 'Sample Donor', 'receipt');
$assertContains($receiptHtml, 'Receipt Number', 'receipt');
$assertContains($receiptHtml, 'Transaction Ref', 'receipt');
$assertContains($receiptHtml, 'Amount Received:', 'receipt');
$assertContains($receiptHtml, 'data:image/png;base64,', 'receipt');
$assertContains($receiptHtml, '@page {', 'receipt');
$assertContains($receiptHtml, 'width: 794px;', 'receipt');

echo "-- certificate template --\n";
$assertContains($certHtml, 'Donation Certificate', 'certificate');
$assertContains($certHtml, 'Sample Donor', 'certificate');
$assertContains($certHtml, 'Donor PAN', 'certificate');
$assertContains($certHtml, 'ABCDE1234F', 'certificate');
$assertContains($certHtml, 'INCOME TAX DEDUCTION ELIGIBILITY', 'certificate');
$assertContains($certHtml, 'To be configured', 'certificate');
$assertContains($certHtml, 'data:image/png;base64,', 'certificate');
$assertContains($certHtml, 'width: 794px;', 'certificate');

echo "-- gotenberg dispatcher --\n";
$gb = new GotenbergPdfService();
echo 'gotenberg_configured=' . ($gb->isConfigured() ? '1' : '0') . "\n";

$pdfService = new DocumentPdfService();
$receiptPdf = $pdfService->renderPdf($receiptHtml, $settings['receipt']['print'] ?? []);
$certPdf = $pdfService->renderPdf($certHtml, $settings['certificate']['print'] ?? []);

if (substr($receiptPdf, 0, 4) !== '%PDF') {
    fwrite(STDERR, "receipt PDF magic header missing\n");
    exit(1);
}
if (substr($certPdf, 0, 4) !== '%PDF') {
    fwrite(STDERR, "certificate PDF magic header missing\n");
    exit(1);
}
echo 'receipt_pdf_bytes=' . strlen($receiptPdf) . "\n";
echo 'certificate_pdf_bytes=' . strlen($certPdf) . "\n";

echo "-- gotenberg fallback path (unreachable URL) --\n";
$gbBad = new GotenbergPdfService('http://127.0.0.1:1');
$dispatcher = new DocumentPdfService($gbBad);
$pdfBytes = $dispatcher->renderPdf($receiptHtml, $settings['receipt']['print'] ?? []);
if (substr($pdfBytes, 0, 4) !== '%PDF') {
    fwrite(STDERR, "fallback PDF magic header missing\n");
    exit(1);
}
echo 'fallback_pdf_bytes=' . strlen($pdfBytes) . "\n";

echo "OK\n";
