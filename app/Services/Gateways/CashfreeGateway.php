<?php

namespace App\Services\Gateways;

use App\Models\Milestone;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CashfreeGateway implements PaymentGateway
{
    private const API_VERSION = '2023-08-01';

    /**
     * Cashfree requires a 10-digit customer phone but we don't collect one.
     * Their documented placeholder works; it only affects prefill, not settlement.
     */
    private const PLACEHOLDER_PHONE = '9999999999';

    public function name(): string
    {
        return 'cashfree';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.cashfree.app_id')) && filled(config('services.cashfree.secret_key'));
    }

    private function mode(): string
    {
        return config('services.cashfree.env') === 'production' ? 'production' : 'sandbox';
    }

    private function baseUrl(): string
    {
        return $this->mode() === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
            'x-client-id'     => config('services.cashfree.app_id'),
            'x-client-secret' => config('services.cashfree.secret_key'),
            'x-api-version'   => self::API_VERSION,
        ])->acceptJson()->asJson()->timeout(20);
    }

    public function createOrder(Milestone $milestone, float $amount): array
    {
        $contract = $milestone->contract;
        $client   = $contract->client;

        // Cashfree order ids must be unique per order, so a retry gets a fresh suffix
        $orderId = 'ms_' . $milestone->id . '_' . Str::lower(Str::random(8));

        $meta = [
            'return_url' => rtrim((string) config('app.frontend_url'), '/')
                . '/client/contracts/' . $contract->id . '?order_id={order_id}',
        ];

        // Cashfree only accepts an https webhook URL
        if (str_starts_with((string) config('app.url'), 'https://')) {
            $meta['notify_url'] = url('/api/v1/webhooks/cashfree');
        }

        $res = $this->http()->post($this->baseUrl() . '/orders', [
            'order_id'         => $orderId,
            'order_amount'     => round($amount, 2),
            'order_currency'   => 'INR',
            'order_note'       => Str::limit($milestone->title, 90, ''),
            'customer_details' => [
                'customer_id'    => 'client_' . $client->id,
                'customer_name'  => trim(preg_replace('/[^\pL\pN\s]/u', '', (string) $client->name)) ?: 'Client',
                'customer_email' => $client->email,
                'customer_phone' => self::PLACEHOLDER_PHONE,
            ],
            'order_meta'       => $meta,
        ]);

        if ($res->failed()) {
            throw new RuntimeException('Cashfree order failed: ' . ($res->json('message') ?? $res->status()));
        }

        return $this->checkout($res->json('order_id'), $res->json('payment_session_id'));
    }

    public function reuseOrder(string $orderId, float $amount): ?array
    {
        $res = $this->http()->get($this->baseUrl() . '/orders/' . $orderId);

        if ($res->failed() || $res->json('order_status') !== 'ACTIVE') {
            return null;
        }

        return $this->checkout($orderId, $res->json('payment_session_id'));
    }

    /** Never trust the browser: ask Cashfree whether this order really has a successful payment. */
    public function confirm(Payment $payment, array $data): array
    {
        $res = $this->http()->get($this->baseUrl() . '/orders/' . $payment->gateway_order_id . '/payments');

        if ($res->failed()) {
            throw new RuntimeException('Could not check the payment with Cashfree.');
        }

        $paid = collect($res->json() ?? [])->firstWhere('payment_status', 'SUCCESS');

        if (!$paid) {
            throw new RuntimeException('Payment not completed yet.');
        }

        return [
            'payment_id'   => (string) $paid['cf_payment_id'],
            'amount_paise' => (int) round(((float) $paid['payment_amount']) * 100),
        ];
    }

    /** Cashfree signs webhooks as base64(HMAC-SHA256(timestamp . rawBody, secret_key)). */
    public function verifyWebhookSignature(string $rawBody, ?string $timestamp, ?string $signature): bool
    {
        $secret = config('services.cashfree.secret_key');

        if (!$secret || !$timestamp || !$signature) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $secret, true));

        return hash_equals($expected, $signature);
    }

    private function checkout(string $orderId, string $sessionId): array
    {
        return [
            'order_id'           => $orderId,
            'payment_session_id' => $sessionId,
            'mode'               => $this->mode(),
        ];
    }
}
