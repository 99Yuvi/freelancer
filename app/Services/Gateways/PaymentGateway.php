<?php

namespace App\Services\Gateways;

use App\Models\Milestone;
use App\Models\Payment;

interface PaymentGateway
{
    public function name(): string;

    /** True when the keys needed to take payments are present in the environment. */
    public function isConfigured(): bool;

    /**
     * Create an order at the gateway.
     * Returns what the browser needs to open that gateway's checkout; always includes 'order_id'.
     */
    public function createOrder(Milestone $milestone, float $amount): array;

    /**
     * The checkout payload for an order that was already created and is still open,
     * or null if it can no longer be used (the caller then creates a fresh order).
     */
    public function reuseOrder(string $orderId, float $amount): ?array;

    /**
     * Confirm with the gateway that this payment was really paid.
     * Throws if it was not (bad signature, still pending, failed...).
     *
     * @param array $data whatever the browser got back from checkout (gateway specific)
     * @return array{payment_id: string, amount_paise: ?int} amount_paise is null when the gateway
     *         doesn't report it here (the signed order already pins the amount)
     */
    public function confirm(Payment $payment, array $data): array;
}
