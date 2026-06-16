<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\DonationCauses;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\DonationRepository;
use App\Services\CertificateNumberService;
use App\Services\ReceiptNumberService;
use App\Services\ReceiptPdfService;

class DonationsController
{
    private const OFFLINE_METHODS = ['cash', 'cheque', 'bank_transfer', 'upi'];
    private const STATUSES = ['pending', 'completed', 'failed', 'refunded'];

    public function __construct(
        private readonly DonationRepository $donations = new DonationRepository(),
        private readonly Auth $auth = new Auth(),
        private readonly ReceiptNumberService $receiptNumbers = new ReceiptNumberService(),
        private readonly CertificateNumberService $certificateNumbers = new CertificateNumberService(),
        private readonly ReceiptPdfService $receiptPdf = new ReceiptPdfService(),
    ) {
    }

    public function index(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin', 'viewer']);
        Response::json($this->donations->list($request->query));
    }

    public function show(Request $request, array $params): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin', 'viewer']);
        $id = (int) ($params['id'] ?? 0);
        $donation = $this->donations->findById($id);
        if ($donation === null) {
            Response::error('Donation not found', 404);
            return;
        }
        Response::json(['donation' => $donation]);
    }

    public function create(Request $request): void
    {
        $user = $this->auth->requireUser($request, ['superadmin', 'admin']);
        $this->auth->requireWriteAccess($user);

        $body = $request->body;
        $donorName = trim((string) ($body['donor_name'] ?? ''));
        $amountPaise = (int) round(((float) ($body['amount_inr'] ?? 0)) * 100);
        $cause = trim((string) ($body['cause'] ?? ''));
        $paymentMethod = (string) ($body['payment_method'] ?? 'cash');

        if ($donorName === '' || $amountPaise <= 0) {
            Response::error('Donor name and a positive amount are required', 422);
            return;
        }

        if (!DonationCauses::isValid($cause)) {
            Response::error('Invalid cause', 422);
            return;
        }

        if (!in_array($paymentMethod, self::OFFLINE_METHODS, true)) {
            Response::error('Invalid payment method', 422);
            return;
        }

        $donation = $this->donations->create([
            'receipt_number' => $this->receiptNumbers->next(),
            'donor_name' => $donorName,
            'donor_email' => $body['donor_email'] ?? null,
            'donor_phone' => $body['donor_phone'] ?? null,
            'amount_paise' => $amountPaise,
            'channel' => 'offline',
            'cause' => $cause,
            'payment_method' => $paymentMethod,
            'transaction_ref' => $body['transaction_ref'] ?? null,
            'status' => 'completed',
            'notes' => $body['notes'] ?? null,
            'donated_at' => normalizeDonatedAt($body['donated_at'] ?? null),
            'created_by' => $user['id'],
        ]);

        Response::json(['donation' => $donation], 201);
    }

    public function update(Request $request, array $params): void
    {
        $user = $this->auth->requireUser($request, ['superadmin', 'admin']);
        $this->auth->requireWriteAccess($user);

        $id = (int) ($params['id'] ?? 0);
        $existing = $this->donations->findById($id);
        if ($existing === null) {
            Response::error('Donation not found', 404);
            return;
        }

        if ($existing['channel'] === 'online') {
            Response::error('Online donations cannot be edited manually', 422);
            return;
        }

        $body = $request->body;
        $data = [];

        foreach (['donor_name', 'donor_email', 'donor_phone', 'cause', 'payment_method', 'transaction_ref', 'notes'] as $field) {
            if (array_key_exists($field, $body)) {
                $data[$field] = $body[$field];
            }
        }

        if (array_key_exists('donated_at', $body)) {
            $data['donated_at'] = normalizeDonatedAt((string) $body['donated_at']);
        }

        if (isset($data['cause']) && !DonationCauses::isValid((string) $data['cause'])) {
            Response::error('Invalid cause', 422);
            return;
        }

        if (isset($data['payment_method']) && !in_array($data['payment_method'], self::OFFLINE_METHODS, true)) {
            Response::error('Invalid payment method', 422);
            return;
        }

        if (isset($body['amount_inr'])) {
            $amountPaise = (int) round(((float) $body['amount_inr']) * 100);
            if ($amountPaise <= 0) {
                Response::error('Amount must be positive', 422);
                return;
            }
            $data['amount_paise'] = $amountPaise;
        }

        if (array_key_exists('status', $body)) {
            $status = (string) $body['status'];
            if (!in_array($status, self::STATUSES, true)) {
                Response::error('Invalid status', 422);
                return;
            }
            $data['status'] = $status;

            if ($status === 'completed' && empty($existing['receipt_number'])) {
                $data['receipt_number'] = $this->receiptNumbers->next();
            }
        }

        $updated = $this->donations->update($id, $data);
        Response::json(['donation' => $updated]);
    }

    public function receipt(Request $request, array $params): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin', 'viewer']);
        $id = (int) ($params['id'] ?? 0);
        $donation = $this->donations->findRawById($id);

        if ($donation === null) {
            Response::error('Donation not found', 404);
            return;
        }

        if ($donation['status'] !== 'completed' || empty($donation['receipt_number'])) {
            Response::error('Receipt is not available for this donation', 422);
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

    public function certificate(Request $request, array $params): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin', 'viewer']);
        $id = (int) ($params['id'] ?? 0);
        $donation = $this->donations->findRawById($id);

        if ($donation === null) {
            Response::error('Donation not found', 404);
            return;
        }

        if (($donation['certificate_status'] ?? '') !== 'approved' || empty($donation['certificate_number'])) {
            Response::error('Certificate is not available for this donation', 422);
            return;
        }

        $format = $request->query['format'] ?? 'pdf';

        if ($format === 'html') {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->receiptPdf->renderCertificateHtml($donation);
            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="certificate-' . $donation['certificate_number'] . '.pdf"');
        echo $this->receiptPdf->renderCertificatePdf($donation);
    }

    public function approveCertificate(Request $request, array $params): void
    {
        $user = $this->auth->requireUser($request, ['superadmin', 'admin']);
        $this->auth->requireWriteAccess($user);

        $id = (int) ($params['id'] ?? 0);
        $donation = $this->donations->findById($id);

        if ($donation === null) {
            Response::error('Donation not found', 404);
            return;
        }

        if ($donation['status'] !== 'completed') {
            Response::error('Only completed donations can be approved for certificate', 422);
            return;
        }

        if ($donation['certificate_status'] === 'approved') {
            Response::json(['donation' => $donation]);
            return;
        }

        $certificateNumber = $this->certificateNumbers->next();
        $updated = $this->donations->approveCertificate($id, (int) $user['id'], $certificateNumber);

        if ($updated === null) {
            Response::error('Unable to approve certificate', 422);
            return;
        }

        Response::json(['donation' => $updated]);
    }

    public function revokeCertificate(Request $request, array $params): void
    {
        $this->auth->requireUser($request, ['superadmin']);

        $id = (int) ($params['id'] ?? 0);
        $donation = $this->donations->findById($id);

        if ($donation === null) {
            Response::error('Donation not found', 404);
            return;
        }

        $updated = $this->donations->revokeCertificate($id);
        Response::json(['donation' => $updated]);
    }

    public function causes(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin', 'viewer']);
        Response::json(['causes' => DonationCauses::ALL]);
    }
}
