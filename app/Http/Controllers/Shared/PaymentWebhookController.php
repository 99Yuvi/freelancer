<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRazorpayWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');
        $secret    = config('services.razorpay.webhook_secret');

        // Verify HMAC-SHA256 signature
        if ($secret) {
            $expected = hash_hmac('sha256', $rawBody, $secret);
            if (!hash_equals($expected, (string) $signature)) {
                Log::warning('Razorpay webhook: invalid signature');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $payload = $request->json()->all();
        Log::info('Razorpay webhook received', ['event' => $payload['event'] ?? 'unknown']);

        // Return 200 IMMEDIATELY — Razorpay requires a fast response
        ProcessRazorpayWebhook::dispatch($payload);

        return response()->json(['status' => 'queued']);
    }
}
