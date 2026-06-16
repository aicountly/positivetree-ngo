<?php

declare(strict_types=1);

namespace App\Services;

class DocumentTemplateService
{
    /** @return array<string, string> */
    public function buildPlaceholders(array $donation, array $organization): array
    {
        $amountInr = number_format(((int) $donation['amount_paise']) / 100, 2);
        $amountWords = $this->amountInWords((int) $donation['amount_paise']);

        return [
            '{{donor_name}}' => (string) ($donation['donor_name'] ?? ''),
            '{{donor_email}}' => (string) ($donation['donor_email'] ?? ''),
            '{{donor_phone}}' => (string) ($donation['donor_phone'] ?? ''),
            '{{amount_inr}}' => '₹' . $amountInr,
            '{{amount_words}}' => $amountWords,
            '{{receipt_number}}' => (string) ($donation['receipt_number'] ?? ''),
            '{{certificate_number}}' => (string) ($donation['certificate_number'] ?? ''),
            '{{cause}}' => (string) ($donation['cause'] ?? ''),
            '{{channel}}' => ucfirst((string) ($donation['channel'] ?? '')),
            '{{payment_method}}' => str_replace('_', ' ', (string) ($donation['payment_method'] ?? '')),
            '{{transaction_ref}}' => (string) ($donation['transaction_ref'] ?? ''),
            '{{donated_at}}' => (string) ($donation['donated_at'] ?? ''),
            '{{organization_name}}' => (string) ($organization['organization_name'] ?? ''),
            '{{organization_phone}}' => (string) ($organization['phone'] ?? ''),
            '{{organization_email}}' => (string) ($organization['email'] ?? ''),
            '{{organization_website}}' => (string) ($organization['website'] ?? ''),
            '{{organization_address}}' => implode(', ', $organization['address_lines'] ?? []),
        ];
    }

    public function resolveText(string $text, array $placeholders): string
    {
        return strtr($text, $placeholders);
    }

    public function logoDataUri(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $path = uploadsDir() . '/' . basename($filename);
        if (!is_readable($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    public function renderReceiptHtml(array $settings, array $donation): string
    {
        $organization = $settings['organization'] ?? [];
        $document = $settings['receipt'] ?? $settings;
        $placeholders = $this->buildPlaceholders($donation, $organization);
        $logoDataUri = $this->logoDataUri($organization['logo_filename'] ?? null);
        $pdfService = new DocumentPdfService();
        $pageCss = $pdfService->pageCss(
            $document['print'] ?? [],
            $document['accent_color'] ?? '#059669'
        );
        $templateService = $this;

        ob_start();
        include __DIR__ . '/../../templates/documents/receipt.php';
        return (string) ob_get_clean();
    }

    public function renderCertificateHtml(array $settings, array $donation): string
    {
        $organization = $settings['organization'] ?? [];
        $document = $settings['certificate'] ?? $settings;
        $placeholders = $this->buildPlaceholders($donation, $organization);
        $logoDataUri = $this->logoDataUri($organization['logo_filename'] ?? null);
        $pdfService = new DocumentPdfService();
        $pageCss = $pdfService->pageCss(
            $document['print'] ?? [],
            $document['accent_color'] ?? '#059669'
        );
        $templateService = $this;

        ob_start();
        include __DIR__ . '/../../templates/documents/certificate.php';
        return (string) ob_get_clean();
    }

    /** @return array<string, mixed> */
    public function sampleDonation(): array
    {
        return [
            'donor_name' => 'Sample Donor',
            'donor_email' => 'donor@example.com',
            'donor_phone' => '+91 9876543210',
            'amount_paise' => 500000,
            'receipt_number' => 'PT-' . date('Y') . '-00001',
            'certificate_number' => 'PT-CERT-' . date('Y') . '-00001',
            'cause' => 'Providing Education to Needy',
            'channel' => 'online',
            'payment_method' => 'razorpay',
            'transaction_ref' => 'pay_SAMPLE123',
            'donated_at' => nowIso(),
            'notes' => 'Sample preview donation',
        ];
    }

    private function amountInWords(int $paise): string
    {
        $rupees = intdiv($paise, 100);
        if ($rupees === 0) {
            return 'Zero Rupees Only';
        }

        return number_format($rupees) . ' Rupees Only';
    }
}
