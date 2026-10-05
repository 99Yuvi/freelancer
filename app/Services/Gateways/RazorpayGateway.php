<?php

namespace App\Services\Gateways;

use App\Models\Milestone;
use App\Models\Payment;
use Razorpay\Api\Api;
use RuntimeException;

class RazorpayGateway implements PaymentGateway
{
    private ?Api $api = null;

    private function api(): Api
    {
        return $this->api ??= new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
    }

    public function name(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    public function createOrder(Milestone $milestone, float $amount): array
    {
        $order = $this->api()->order->create([
            'amount'          => (int) round($amount * 100),
            'currency'        => 'INR',
            'receipt'         => 'ms_' . $milestone->id,
            'payment_capture' => 1,
        ]);

        return $this->checkout($order['id'], $amount);
    }

    public function reuseOrder(string $orderId, float $amount): ?array
    {
        return $this->checkout($orderId, $amount);
    }

    public function confirm(Payment $payment, array $data): array
    {
        $paymentId = $data['payment_id'] ?? null;
        $signature = $data['signature'] ?? null;

        if (!$paymentId || !$signature) {
            throw new RuntimeException('Missing Razorpay payment details.');
        }

        $this->api()->utility->verifyPaymentSignature([
            'razorpay_order_id'   => $payment->gateway_order_id,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature'  => $signature,
        ]);

        return ['payment_id' => $paymentId, 'amount_paise' => null];
    }

    private function checkout(string $orderId, float $amount): array
    {
        return [
            'order_id'     => $orderId,
            'amount_paise' => (int) round($amount * 100),
            'currency'     => 'INR',
            'key_id'       => config('services.razorpay.key_id'),
        ];
    }
}
