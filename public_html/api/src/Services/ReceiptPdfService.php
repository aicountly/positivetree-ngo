<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DocumentSettingsRepository;

class ReceiptPdfService
{
    public function __construct(
        private readonly DocumentSettingsRepository $settings = new DocumentSettingsRepository(),
        private readonly DocumentTemplateService $templates = new DocumentTemplateService(),
        private readonly DocumentPdfService $pdf = new DocumentPdfService(),
    ) {
    }

    public function renderHtml(array $donation): string
    {
        $settings = $this->settings->get();
        return $this->templates->renderReceiptHtml($settings, $donation);
    }

    public function renderPdf(array $donation): string
    {
        $settings = $this->settings->get();
        $html = $this->templates->renderReceiptHtml($settings, $donation);
        return $this->pdf->renderPdf($html, $settings['receipt']['print'] ?? []);
    }

    public function renderCertificateHtml(array $donation): string
    {
        $settings = $this->settings->get();
        return $this->templates->renderCertificateHtml($settings, $donation);
    }

    public function renderCertificatePdf(array $donation): string
    {
        $settings = $this->settings->get();
        $html = $this->templates->renderCertificateHtml($settings, $donation);
        return $this->pdf->renderPdf($html, $settings['certificate']['print'] ?? []);
    }

    public function previewReceiptHtml(): string
    {
        $settings = $this->settings->get();
        return $this->templates->renderReceiptHtml($settings, $this->templates->sampleDonation());
    }

    public function previewReceiptPdf(): string
    {
        $settings = $this->settings->get();
        $html = $this->templates->renderReceiptHtml($settings, $this->templates->sampleDonation());
        return $this->pdf->renderPdf($html, $settings['receipt']['print'] ?? []);
    }

    public function previewCertificateHtml(): string
    {
        $settings = $this->settings->get();
        return $this->templates->renderCertificateHtml($settings, $this->templates->sampleDonation());
    }

    public function previewCertificatePdf(): string
    {
        $settings = $this->settings->get();
        $html = $this->templates->renderCertificateHtml($settings, $this->templates->sampleDonation());
        return $this->pdf->renderPdf($html, $settings['certificate']['print'] ?? []);
    }
}
