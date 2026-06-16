<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DonationCauses;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\DonationRepository;
use App\Services\RazorpayService;
use App\Services\ReceiptNumberService;

class PaymentsController
{
    public function __construct(
        private readonly RazorpayService $razorpay = new RazorpayService(),
        private readonly DonationRepository $donations = new DonationRepository(),
        private readonly ReceiptNumberService $receiptNumbers = new ReceiptNumberService(),
    ) {
    }

    public function config(Request $request): void
    {
        Response::json([
            'key_id' => $this->razorpay->publicKeyId(),
            'configured' => $this->razorpay->isConfigured(),
            'causes' => DonationCauses::ALL,
        ]);
    }

    public function createOrder(Request $request): void
    {
        if (!$this->razorpay->isConfigured()) {
            Response::error('Online payments are not configured', 503);
            return;
        }

        $body = $request->body;
        $donorName = trim((string) ($body['donor_name'] ?? ''));
        $donorEmail = trim((string) ($body['donor_email'] ?? ''));
        $cause = trim((string) ($body['cause'] ?? ''));
        $amountPaise = (int) round(((float) ($body['amount_inr'] ?? 0)) * 100);

        if ($donorName === '' || $donorEmail === '' || $amountPaise < 100) {
            Response::error('Donor name, email, and minimum amount of ₹1 are required', 422);
            return;
        }

        if (!filter_var($donorEmail, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address', 422);
            return;
        }

        if (!DonationCauses::isValid($cause)) {
            Response::error('Invalid cause', 422);
            return;
        }

        [$donorPan, $panError] = parseOptionalPanInput($body['donor_pan'] ?? null);
        if ($panError !== null) {
            Response::error($panError, 422);
            return;
        }

        $pendingLabel = 'pending-' . bin2hex(random_bytes(8));
        $order = $this->razorpay->createOrder($amountPaise, $pendingLabel, [
            'cause' => $cause,
            'donor_name' => $donorName,
        ]);

        $donation = $this->donations->create([
            'donor_name' => $donorName,
            'donor_email' => $donorEmail,
            'donor_phone' => $body['donor_phone'] ?? null,
            'donor_pan' => $donorPan,
            'amount_paise' => $amountPaise,
            'channel' => 'online',
            'cause' => $cause,
            'payment_method' => 'razorpay',
            'razorpay_order_id' => $order['id'],
            'status' => 'pending',
            'donated_at' => nowIso(),
        ]);

        Response::json([
            'order_id' => $order['id'],
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'donation_id' => $donation['id'],
            'key_id' => $this->razorpay->publicKeyId(),
        ], 201);
    }

    public function verify(Request $request): void
    {
        $body = $request->body;
        $orderId = (string) ($body['razorpay_order_id'] ?? '');
        $paymentId = (string) ($body['razorpay_payment_id'] ?? '');
        $signature = (string) ($body['razorpay_signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            Response::error('Payment verification data is incomplete', 422);
            return;
        }

        if (!$this->razorpay->verifyPaymentSignature($orderId, $paymentId, $signature)) {
            Response::error('Invalid payment signature', 400);
            return;
        }

        $donation = $this->donations->findByRazorpayOrderId($orderId);
        if ($donation === null) {
            Response::error('Donation not found', 404);
            return;
        }

        $completed = $this->completeDonation((int) $donation['id'], $paymentId, $donation['receipt_number']);
        Response::json(['donation' => $completed]);
    }

    public function webhook(Request $request): void
    {
        $payload = $request->rawBody;
        $signature = $request->header('X-Razorpay-Signature', '');

        if (!$this->razorpay->verifyWebhookSignature($payload, $signature)) {
            Response::error('Invalid webhook signature', 400);
            return;
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            Response::error('Invalid payload', 400);
            return;
        }

        $eventType = $event['event'] ?? '';

        if ($eventType === 'payment.failed') {
            $payment = $event['payload']['payment']['entity'] ?? null;
            if (is_array($payment)) {
                $orderId = (string) ($payment['order_id'] ?? '');
                $donation = $orderId !== '' ? $this->donations->findByRazorpayOrderId($orderId) : null;
                if ($donation !== null && $donation['status'] === 'pending') {
                    $this->donations->markFailed((int) $donation['id']);
                }
            }
            Response::json(['status' => 'failed_noted']);
            return;
        }

        if ($eventType !== 'payment.captured') {
            Response::json(['status' => 'ignored']);
            return;
        }

        $payment = $event['payload']['payment']['entity'] ?? null;
        if (!is_array($payment)) {
            Response::error('Invalid payment payload', 400);
            return;
        }

        $paymentId = (string) ($payment['id'] ?? '');
        $orderId = (string) ($payment['order_id'] ?? '');

        if ($paymentId === '' || $orderId === '') {
            Response::error('Missing payment identifiers', 400);
            return;
        }

        $existing = $this->donations->findByRazorpayPaymentId($paymentId);
        if ($existing !== null && $existing['status'] === 'completed') {
            Response::json(['status' => 'already_processed']);
            return;
        }

        $donation = $this->donations->findByRazorpayOrderId($orderId);
        if ($donation === null) {
            Response::error('Donation not found for order', 404);
            return;
        }

        $this->completeDonation((int) $donation['id'], $paymentId, $donation['receipt_number']);
        Response::json(['status' => 'processed']);
    }

    private function completeDonation(int $id, string $paymentId, ?string $existingReceipt): array
    {
        $receiptNumber = $existingReceipt ?: $this->receiptNumbers->next();
        $completed = $this->donations->completeOnline($id, $receiptNumber, $paymentId);

        if ($completed === null) {
            Response::error('Donation not found', 404);
            exit;
        }

        return $completed;
    }
}
