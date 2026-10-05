<?php

namespace App\Jobs;

use App\Jobs\GenerateInvoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentFailedAdmin;
use App\Notifications\PaymentReceived;
use Illuminate\Support\Facades\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        $entity    = $this->payload['payload']['payment']['entity'] ?? [];
        $orderId   = $entity['order_id']  ?? null;
        $paymentId = $entity['id']        ?? null;

        if (!$orderId) return;

        // Capture data needed for post-transaction notification
        $notifyFreelancer  = null;
        $notifyNetAmount   = null;
        $notifyMilestone   = null;

        DB::transaction(function () use ($orderId, $paymentId, &$notifyFreelancer, &$notifyNetAmount, &$notifyMilestone) {
            $payment = Payment::where('razorpay_order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                Log::warning("Webhook: payment not found for order {$orderId}");
                return;
            }

            // Idempotency — skip if already processed
            if ($payment->status === 'captured') return;

            $payment->update([
                'razorpay_payment_id' => $paymentId,
                'status'              => 'captured',
                'captured_at'         => now(),
            ]);

            $payment->milestone->update(['status' => 'paid']);

            // Update aggregated totals
            $payment->freelancerProfile()->increment('total_earnings', $payment->net_amount);
            if (Schema::hasColumn('freelancer_profiles', 'pending_payout')) {
                $payment->freelancerProfile()->increment('pending_payout', $payment->net_amount);
            }
            $payment->clientProfile()->increment('total_spent', $payment->amount);

            // Auto-complete contract if all milestones are paid
            $contract = $payment->contract->load('milestones');
            if ($contract->milestones->every(fn($m) => $m->status === 'paid')) {
                $contract->update(['status' => 'completed', 'completed_at' => now()]);
                $contract->project()->update(['status' => 'completed']);
            }

            // Dispatch invoice generation asynchronously
            GenerateInvoice::dispatch($payment->id);

            // Collect data for post-transaction notification
            $notifyFreelancer = $payment->freelancer;
            $notifyNetAmount  = number_format((float) $payment->net_amount, 2);
            $notifyMilestone  = $payment->milestone->title;
        });

        // Send notification AFTER transaction commits (avoid deadlock on notifications table)
        if ($notifyFreelancer) {
            $notifyFreelancer->notify(new PaymentReceived($notifyNetAmount, $notifyMilestone));
        }
    }

    private function handleFailed(): void
    {
        $orderId = $this->payload['payload']['payment']['entity']['order_id'] ?? null;
        if (!$orderId) return;

        $payment = Payment::where('razorpay_order_id', $orderId)
            ->where('status', 'pending')
            ->first();

        if (!$payment) return;

        $payment->update(['status' => 'failed']);

        $payment->client->notify(new PaymentFailed($payment->milestone->title));

        // Admins should also know about platform-level payment failures
        try {
            Notification::send(
                User::where('role', 'admin')->get(),
                new PaymentFailedAdmin($payment->client->name, $payment->milestone->title)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
