<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Gateways\CcavenueGateway;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CCAvenue sends the client's BROWSER back here (as a POST) after payment, for both
 * redirect_url (finished) and cancel_url (abandoned). We decrypt the response, settle the
 * payment, then bounce the browser on to the contract page.
 */
class CcavenueReturnController extends Controller
{
    public function handle(Request $request, CcavenueGateway $ccavenue, PaymentService $payments)
    {
        $plain = $ccavenue->decrypt((string) $request->input('encResp'));

        // Not encrypted with our working key — don't touch anything
        if ($plain === false || $plain === '') {
            Log::warning('CCAvenue return: response could not be decrypted');
            return $this->bounce('/client/contracts', 'failed');
        }

        parse_str($plain, $r);

        $orderId = $r['order_id'] ?? null;
        $payment = $orderId
            ? Payment::where('gateway', 'ccavenue')->where('gateway_order_id', $orderId)->first()
            : null;

        if (!$payment) {
            Log::warning('CCAvenue return: no payment for order', ['order_id' => $orderId]);
            return $this->bounce('/client/contracts', 'failed');
        }

        $status  = $r['order_status'] ?? '';
        $outcome = match ($status) {
            'Success' => 'success',
            'Aborted' => 'cancelled', // the client backed out — nothing to report
            default   => 'failed',    // Failure, Invalid, Timeout
        };

        try {
            if ($status === 'Success') {
                $payments->markCaptured(
                    $orderId,
                    $r['tracking_id'] ?? null,
                    isset($r['amount']) ? (int) round(((float) $r['amount']) * 100) : null
                );

                // markCaptured refuses an amount mismatch — the money moved but we didn't accept it
                if ($payment->fresh()->status !== 'captured') {
                    $outcome = 'review';
                }
            } elseif ($outcome === 'failed') {
                $payments->markFailed($orderId);
            }
        } catch (\Throwable $e) {
            report($e);
            $outcome = 'review';
        }

        return $this->bounce('/client/contracts/' . $payment->contract_id, $outcome);
    }

    /** 303 turns CCAvenue's POST into a plain GET on the frontend. */
    private function bounce(string $path, string $outcome)
    {
        return redirect()->away(
            rtrim((string) config('app.frontend_url'), '/') . $path . '?payment=' . $outcome,
            303
        );
    }
}
