<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$uri = documentLogoDataUri();
if ($uri === null) {
    fwrite(STDERR, "documentLogoDataUri returned null\n");
    exit(1);
}

$raw = base64_decode(substr($uri, strpos($uri, ',') + 1), true);
if ($raw === false) {
    fwrite(STDERR, "Failed to decode logo data URI\n");
    exit(1);
}

$outPath = __DIR__ . '/../data/logo-normalized-test.png';
file_put_contents($outPath, $raw);

$img = imagecreatefromstring($raw);
if ($img === false) {
    fwrite(STDERR, "Failed to load normalized PNG\n");
    exit(1);
}

$width = imagesx($img);
$height = imagesy($img);
$transparentPixels = 0;

for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $color = imagecolorat($img, $x, $y);
        if ((($color >> 24) & 0x7F) >= 120) {
            $transparentPixels++;
        }
    }
}

imagedestroy($img);

echo "transparent_pixels={$transparentPixels}\n";

if ($transparentPixels < 100) {
    fwrite(STDERR, "Expected substantial transparent background area\n");
    exit(1);
}

$template = new App\Services\DocumentTemplateService();
$settings = (new App\Repositories\DocumentSettingsRepository())->get();
$donation = $template->sampleDonation();

$receiptHtml = $template->renderReceiptHtml($settings, $donation);
$certHtml = $template->renderCertificateHtml($settings, $donation);

$pdf = new App\Services\DocumentPdfService();
$print = $settings['receipt']['print'] ?? [];

file_put_contents(__DIR__ . '/../data/receipt-preview-test.pdf', $pdf->renderPdf($receiptHtml, $print));
file_put_contents(__DIR__ . '/../data/certificate-preview-test.pdf', $pdf->renderPdf($certHtml, $settings['certificate']['print'] ?? $print));

echo "receipt_pdf_ok\n";
echo "certificate_pdf_ok\n";
