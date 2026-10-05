<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\Gateways\CashfreeGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashfreeWebhookController extends Controller
{
    public function handle(Request $request, CashfreeGateway $cashfree, PaymentService $payments)
    {
        $valid = $cashfree->verifyWebhookSignature(
            $request->getContent(),
            $request->header('x-webhook-timestamp'),
            $request->header('x-webhook-signature')
        );

        // Fail-closed: also covers a missing CASHFREE_SECRET_KEY
        if (!$valid) {
            Log::warning('Cashfree webhook: invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->json()->all();
        $type    = $payload['type'] ?? null;
        $orderId = $payload['data']['order']['order_id'] ?? null;
        $payment = $payload['data']['payment'] ?? [];

        Log::info('Cashfree webhook received', ['type' => $type]);

        if (!$orderId) {
            return response()->json(['status' => 'ignored']);
        }

        // Done inline (not queued): the queue only runs once a minute from cron, and this is quick.
        // A failure returns 500 so Cashfree retries the delivery.
        try {
            match ($type) {
                'PAYMENT_SUCCESS_WEBHOOK' => $payments->markCaptured(
                    $orderId,
                    isset($payment['cf_payment_id']) ? (string) $payment['cf_payment_id'] : null,
                    isset($payment['payment_amount']) ? (int) round(((float) $payment['payment_amount']) * 100) : null
                ),
                'PAYMENT_FAILED_WEBHOOK'  => $payments->markFailed($orderId),
                default                   => null,
            };
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json(['status' => 'ok']);
    }
}
