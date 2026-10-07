<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Gateways\CcavenueGateway;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileCcavenuePayments extends Command
{
    protected $signature   = 'operalyn:reconcile-ccavenue';
    protected $description = 'Settle CCAvenue payments whose client paid but never made it back to our return URL';

    public function handle(CcavenueGateway $ccavenue, PaymentService $payments): void
    {
        if (!$ccavenue->isConfigured()) return;

        // CCAvenue has no webhook, so a client who pays and closes the tab is only found here.
        // Skip the last few minutes (they may still be on the payment page) and anything older than a day.
        $pending = Payment::where('gateway', 'ccavenue')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(3))
            ->where('created_at', '>=', now()->subDay())
            ->get();

        $settled = 0;

        foreach ($pending as $payment) {
            try {
                $confirmed = $ccavenue->confirm($payment, []);
            } catch (\Throwable $e) {
                // Not paid (or CCAvenue unreachable) — try again next run
                Log::debug('CCAvenue reconcile: order not settled', [
                    'order_id' => $payment->gateway_order_id,
                    'reason'   => $e->getMessage(),
                ]);
                continue;
            }

            $payments->markCaptured($payment->gateway_order_id, $confirmed['payment_id'], $confirmed['amount_paise']);
            $settled++;
        }

        $this->info("Checked {$pending->count()} pending CCAvenue payment(s), settled {$settled}.");
    }
}
