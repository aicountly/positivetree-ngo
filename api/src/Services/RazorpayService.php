<?php

declare(strict_types=1);

namespace App\Services;

use Razorpay\Api\Api;

class RazorpayService
{
    private ?Api $api = null;

    public function isConfigured(): bool
    {
        return config('RAZORPAY_KEY_ID') && config('RAZORPAY_KEY_SECRET');
    }

    public function publicKeyId(): ?string
    {
        return config('RAZORPAY_KEY_ID');
    }

    public function client(): Api
    {
        if ($this->api === null) {
            $keyId = config('RAZORPAY_KEY_ID');
            $keySecret = config('RAZORPAY_KEY_SECRET');
            if (!$keyId || !$keySecret) {
                throw new \RuntimeException('Razorpay is not configured');
            }
            $this->api = new Api($keyId, $keySecret);
        }

        return $this->api;
    }

    /** @return array<string, mixed> */
    public function createOrder(int $amountPaise, string $receiptLabel, array $notes = []): array
    {
        $order = $this->client()->order->create([
            'receipt' => $receiptLabel,
            'amount' => $amountPaise,
            'currency' => 'INR',
            'notes' => $notes,
        ]);

        return $order->toArray();
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = config('RAZORPAY_KEY_SECRET');
        if (!$secret) {
            return false;
        }

        $payload = $orderId . '|' . $paymentId;
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('RAZORPAY_WEBHOOK_SECRET');
        if (!$secret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
