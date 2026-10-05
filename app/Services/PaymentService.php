<?php

namespace App\Services;

use App\Jobs\GenerateInvoice;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MilestoneFunded;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentFailedAdmin;
use App\Services\Gateways\CashfreeGateway;
use App\Services\Gateways\PaymentGateway;
use App\Services\Gateways\RazorpayGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class PaymentService
{
    public const GATEWAYS = ['razorpay', 'cashfree'];

    public function gateway(string $name): PaymentGateway
    {
        return match ($name) {
            'razorpay' => app(RazorpayGateway::class),
            'cashfree' => app(CashfreeGateway::class),
            default    => throw new InvalidArgumentException("Unknown payment gateway: {$name}"),
        };
    }

    /** Gateways whose keys are present — these are the ones a client may pick from. */
    public function enabledGateways(): array
    {
        return array_values(array_filter(
            self::GATEWAYS,
            fn (string $name) => $this->gateway($name)->isConfigured()
        ));
    }

    /**
     * Create an order for a milestone at the chosen gateway and store a pending Payment record.
     * Returns what the browser needs to open that gateway's checkout.
     *
     * A milestone has exactly one Payment row (unique milestone_id), so a failed attempt — or
     * switching to the other gateway — reuses that row with a fresh order instead of inserting another.
     */
    public function createOrderForMilestone(Milestone $milestone, string $gatewayName): array
    {
        $gateway        = $this->gateway($gatewayName);
        $contract       = $milestone->contract;
        $commissionRate = $contract->commission_rate;
        $grossAmount    = (float) $milestone->amount;
        $commission     = round($grossAmount * ($commissionRate / 100), 2);
        $netAmount      = round($grossAmount - $commission, 2);

        $existing = Payment::where('milestone_id', $milestone->id)->first();

        // Reuse an open order at the same gateway (prevents duplicate orders on double-click / reopen)
        if ($existing && $existing->status === 'pending' && $existing->gateway === $gatewayName) {
            $checkout = $gateway->reuseOrder($existing->gateway_order_id, (float) $existing->amount);

            if ($checkout) {
                return ['gateway' => $gatewayName] + $checkout;
            }
        }

        $checkout = $gateway->createOrder($milestone, $grossAmount);

        $fields = [
            'gateway'            => $gatewayName,
            'gateway_order_id'   => $checkout['order_id'],
            'gateway_payment_id' => null,
            'amount'             => $grossAmount,
            'commission_rate'    => $commissionRate,
            'commission_amount'  => $commission,
            'net_amount'         => $netAmount,
            'status'             => 'pending',
        ];

        if ($existing) {
            $existing->update($fields);
        } else {
            Payment::create($fields + [
                'contract_id'   => $contract->id,
                'milestone_id'  => $milestone->id,
                'client_id'     => $contract->client_id,
                'freelancer_id' => $contract->freelancer_id,
            ]);
        }

        return ['gateway' => $gatewayName] + $checkout;
    }

    /**
     * Mark an order as paid: the money is now held in escrow and the freelancer can start work.
     * Idempotent — the browser-side verify and the gateway webhook both call this and either may win.
     * Earnings are NOT credited here; that happens when the client releases the milestone.
     *
     * @param int|null $capturedPaise amount the gateway reports, when we have it
     */
    public function markCaptured(string $orderId, ?string $paymentId, ?int $capturedPaise = null): void
    {
        $notifyFreelancer = null;
        $notifyMilestone  = null;

        DB::transaction(function () use ($orderId, $paymentId, $capturedPaise, &$notifyFreelancer, &$notifyMilestone) {
            $payment = Payment::where('gateway_order_id', $orderId)->lockForUpdate()->first();

            if (!$payment) {
                Log::warning("Payment capture: no payment for order {$orderId}");
                return;
            }

            if ($payment->status === 'captured') return;

            // Never trust a capture that doesn't match what we asked the gateway to charge
            if ($capturedPaise !== null && $capturedPaise !== (int) round($payment->amount * 100)) {
                Log::error("Payment capture: amount mismatch for order {$orderId}", [
                    'expected_paise' => (int) round($payment->amount * 100),
                    'captured_paise' => $capturedPaise,
                ]);
                return;
            }

            $payment->update([
                'gateway_payment_id' => $paymentId,
                'status'             => 'captured',
                'captured_at'        => now(),
            ]);

            $milestone = $payment->milestone;
            if ($milestone->status === 'pending') {
                $milestone->update(['status' => 'in_progress']);
            }

            $payment->clientProfile()->increment('total_spent', $payment->amount);

            GenerateInvoice::dispatch($payment->id);

            $notifyFreelancer = $payment->freelancer;
            $notifyMilestone  = $milestone->title;
        });

        // After commit, so a notification problem can't roll back a real payment
        if ($notifyFreelancer) {
            $notifyFreelancer->notify(new MilestoneFunded($notifyMilestone));
        }
    }

    /** A payment attempt failed. A later successful attempt on the same order still captures normally. */
    public function markFailed(string $orderId): void
    {
        $payment = Payment::where('gateway_order_id', $orderId)
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

    /**
     * Calculate commission for a given amount — used for display only.
     */
    public static function calcCommission(float $amount, float $rate): array
    {
        $commission = round($amount * ($rate / 100), 2);
        return [
            'gross'      => $amount,
            'commission' => $commission,
            'net'        => round($amount - $commission, 2),
        ];
    }
}
