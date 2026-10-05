<?php

namespace App\Jobs;

use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRazorpayWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(private readonly array $payload) {}

    public function handle(): void
    {
        $event = $this->payload['event'] ?? null;

        match ($event) {
            'payment.captured' => $this->handleCapture(),
            'payment.failed'   => $this->handleFailed(),
            default            => null,
        };
    }

    private function handleCapture(): void
    {
        $entity  = $this->payload['payload']['payment']['entity'] ?? [];
        $orderId = $entity['order_id'] ?? null;

        if (!$orderId) return;

        // Same code path as the browser-side verify; whichever arrives first wins, the other is a no-op
        app(PaymentService::class)->markCaptured(
            $orderId,
            $entity['id'] ?? null,
            (int) ($entity['amount'] ?? 0)
        );
    }

    private function handleFailed(): void
    {
        $orderId = $this->payload['payload']['payment']['entity']['order_id'] ?? null;
        if (!$orderId) return;

        app(PaymentService::class)->markFailed($orderId);
    }
}
