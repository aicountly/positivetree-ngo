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
    private const ALLOWED_LOGO_TYPES = [
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
        $this->auth->requireUser($request, ['superadmin']);
        Response::json(['settings' => $this->settings->get()]);
    }

    public function update(Request $request): void
    {
        $user = $this->auth->requireUser($request, ['superadmin']);
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
        $user = $this->auth->requireUser($request, ['superadmin']);

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
        $extension = self::ALLOWED_LOGO_TYPES[$mime] ?? null;

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

    public function previewReceipt(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin']);
        $this->outputPreview($request, 'receipt');
    }

    public function previewCertificate(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin']);
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
