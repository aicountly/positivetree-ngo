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
            '{{donor_pan}}' => (string) ($donation['donor_pan'] ?? ''),
            '{{amount_inr}}' => '₹' . $amountInr,
            '{{amount_words}}' => $amountWords,
            '{{receipt_number}}' => (string) ($donation['receipt_number'] ?? ''),
            '{{certificate_number}}' => (string) ($donation['certificate_number'] ?? ''),
            '{{cause}}' => (string) ($donation['cause'] ?? ''),
            '{{channel}}' => ucfirst((string) ($donation['channel'] ?? '')),
            '{{donation_mode}}' => ucfirst((string) ($donation['channel'] ?? '')),
            '{{payment_method}}' => ucfirst(str_replace('_', ' ', (string) ($donation['payment_method'] ?? ''))),
            '{{transaction_ref}}' => (string) ($donation['transaction_ref'] ?? ''),
            '{{donated_at}}' => $this->formatReceiptDate((string) ($donation['donated_at'] ?? '')),
            '{{certificate_date}}' => $this->formatCertificateDate((string) ($donation['donated_at'] ?? '')),
            '{{payment_status}}' => $this->paymentStatusLabel((string) ($donation['status'] ?? 'completed')),
            '{{organization_name}}' => (string) ($organization['organization_name'] ?? ''),
            '{{organization_tagline}}' => (string) ($organization['tagline'] ?? ''),
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

    public function logoDataUri(?string $filename = null): ?string
    {
        return documentLogoDataUri();
    }

    public function uploadImageDataUri(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $path = uploadsDir() . '/' . basename($filename);
        return $this->fileDataUri($path);
    }

    public function renderReceiptHtml(array $settings, array $donation): string
    {
        $organization = $settings['organization'] ?? [];
        $document = $settings['receipt'] ?? $settings;
        $placeholders = $this->buildPlaceholders($donation, $organization);
        $logoDataUri = $this->logoDataUri();
        $signatureDataUri = documentSignatureDataUri($document['signature_filename'] ?? null);
        $pdfService = new DocumentPdfService();
        $pageCss = $pdfService->pageCss(
            $document['print'] ?? [],
            $document['accent_color'] ?? '#20994D'
        );
        $templateService = $this;
        $brandBrown = $document['brand_brown'] ?? '#986326';
        $accentColor = $document['accent_color'] ?? '#20994D';

        ob_start();
        include __DIR__ . '/../../templates/documents/receipt.php';
        return (string) ob_get_clean();
    }

    public function renderCertificateHtml(array $settings, array $donation): string
    {
        $organization = $settings['organization'] ?? [];
        $document = $settings['certificate'] ?? $settings;
        $receiptDocument = $settings['receipt'] ?? [];
        $placeholders = $this->buildPlaceholders($donation, $organization);
        $logoDataUri = $this->logoDataUri();
        $signatureDataUri = documentSignatureDataUri($receiptDocument['signature_filename'] ?? null);
        $pdfService = new DocumentPdfService();
        $pageCss = $pdfService->pageCss(
            $document['print'] ?? [],
            $document['accent_color'] ?? '#20994D'
        );
        $templateService = $this;
        $brandBrown = $document['brand_brown'] ?? '#986326';
        $accentColor = $document['accent_color'] ?? '#20994D';

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
            'donor_pan' => 'ABCDE1234F',
            'amount_paise' => 500000,
            'receipt_number' => 'PT-' . date('Y') . '-00001',
            'certificate_number' => 'PT-CERT-' . date('Y') . '-00001',
            'cause' => 'Providing Education to Needy',
            'channel' => 'online',
            'payment_method' => 'razorpay',
            'transaction_ref' => 'pay_SAMPLE123',
            'status' => 'completed',
            'donated_at' => nowIso(),
            'notes' => 'Sample preview donation',
        ];
    }

    private function fileDataUri(?string $path): ?string
    {
        if ($path === null || !is_readable($path)) {
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

    private function formatReceiptDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($value);
            return $dt->format('Y-m-d H:i:s') . ' UTC';
        } catch (\Exception) {
            return $value;
        }
    }

    private function formatCertificateDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($value);
            return $dt->format('d M Y');
        } catch (\Exception) {
            return $value;
        }
    }

    private function paymentStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Successful',
            'pending' => 'Pending',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($status),
        };
    }

    private function amountInWords(int $paise): string
    {
        $rupees = intdiv($paise, 100);
        if ($rupees === 0) {
            return 'Rupees Zero Only';
        }

        return 'Rupees ' . $this->numberToWords($rupees) . ' Only';
    }

    private function numberToWords(int $number): string
    {
        if ($number < 0) {
            return 'Minus ' . $this->numberToWords(abs($number));
        }

        if ($number === 0) {
            return 'Zero';
        }

        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $parts = [];

        $crores = intdiv($number, 10000000);
        if ($crores > 0) {
            $parts[] = $this->numberToWords($crores) . ' Crore';
            $number %= 10000000;
        }

        $lakhs = intdiv($number, 100000);
        if ($lakhs > 0) {
            $parts[] = $this->numberToWords($lakhs) . ' Lakh';
            $number %= 100000;
        }

        $thousands = intdiv($number, 1000);
        if ($thousands > 0) {
            $parts[] = $this->numberToWords($thousands) . ' Thousand';
            $number %= 1000;
        }

        $hundreds = intdiv($number, 100);
        if ($hundreds > 0) {
            $parts[] = $ones[$hundreds] . ' Hundred';
            $number %= 100;
        }

        if ($number > 0) {
            if ($number < 20) {
                $parts[] = $ones[$number];
            } else {
                $word = $tens[intdiv($number, 10)];
                $unit = $number % 10;
                $parts[] = $unit > 0 ? $word . ' ' . $ones[$unit] : $word;
            }
        }

        return implode(' ', $parts);
    }
}
