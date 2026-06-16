<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\DocumentSettingsRepository;
use App\Services\ReceiptPdfService;

class DocumentSettingsController
{
    private const ALLOWED_IMAGE_TYPES = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/svg+xml' => 'svg',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly Auth $auth = new Auth(),
        private readonly DocumentSettingsRepository $settings = new DocumentSettingsRepository(),
        private readonly ReceiptPdfService $pdf = new ReceiptPdfService(),
    ) {
    }

    public function show(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin']);
        Response::json(['settings' => $this->settings->get()]);
    }

    public function update(Request $request): void
    {
        $user = $this->auth->requireUser($request, ['superadmin', 'admin']);
        $body = $request->body;

        $saved = $this->settings->save([
            'organization' => $body['organization'] ?? [],
            'receipt' => $body['receipt'] ?? [],
            'certificate' => $body['certificate'] ?? [],
        ], (int) $user['id']);

        Response::json(['settings' => $saved]);
    }

    public function uploadLogo(Request $request): void
    {
        $user = $this->auth->requireUser($request, ['superadmin', 'admin']);

        if (empty($_FILES['logo']) || !is_array($_FILES['logo'])) {
            Response::error('Logo file is required', 422);
            return;
        }

        $file = $_FILES['logo'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::error('Logo upload failed', 422);
            return;
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            Response::error('Logo must be 2MB or smaller', 422);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $extension = self::ALLOWED_IMAGE_TYPES[$mime] ?? null;

        if ($extension === null) {
            Response::error('Logo must be PNG, JPG, SVG, or WebP', 422);
            return;
        }

        $filename = 'logo-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = uploadsDir() . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Response::error('Unable to save logo', 500);
            return;
        }

        $saved = $this->settings->saveLogoFilename($filename, (int) $user['id']);
        Response::json(['settings' => $saved, 'logo_filename' => $filename]);
    }

    public function uploadSignature(Request $request): void
    {
        $user = $this->auth->requireUser($request, ['superadmin', 'admin']);

        if (empty($_FILES['signature']) || !is_array($_FILES['signature'])) {
            Response::error('Signature image is required', 422);
            return;
        }

        $file = $_FILES['signature'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::error('Signature upload failed', 422);
            return;
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            Response::error('Signature image must be 2MB or smaller', 422);
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: '';
        $extension = self::ALLOWED_IMAGE_TYPES[$mime] ?? null;

        if ($extension === null) {
            Response::error('Signature must be PNG, JPG, SVG, or WebP', 422);
            return;
        }

        $filename = 'signature-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = uploadsDir() . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            Response::error('Unable to save signature image', 500);
            return;
        }

        $saved = $this->settings->saveSignatureFilename($filename, (int) $user['id']);
        Response::json(['settings' => $saved, 'signature_filename' => $filename]);
    }

    public function signatureImage(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin']);
        $filename = $this->settings->signatureFilename();

        if ($filename === null) {
            Response::error('Signature image not configured', 404);
            return;
        }

        $path = uploadsDir() . '/' . $filename;
        if (!is_readable($path)) {
            Response::error('Signature image not found', 404);
            return;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=300');
        readfile($path);
    }

    public function previewReceipt(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin']);
        $this->outputPreview($request, 'receipt');
    }

    public function previewCertificate(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin']);
        $this->outputPreview($request, 'certificate');
    }

    private function outputPreview(Request $request, string $type): void
    {
        $format = $request->query['format'] ?? 'pdf';

        if ($type === 'receipt') {
            if ($format === 'html') {
                header('Content-Type: text/html; charset=utf-8');
                echo $this->pdf->previewReceiptHtml();
                return;
            }
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="receipt-preview.pdf"');
            echo $this->pdf->previewReceiptPdf();
            return;
        }

        if ($format === 'html') {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->pdf->previewCertificateHtml();
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="certificate-preview.pdf"');
        echo $this->pdf->previewCertificatePdf();
    }
}
