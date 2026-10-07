<?php

namespace App\Services\Gateways;

use App\Models\Milestone;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * CCAvenue is a redirect gateway, unlike Razorpay/Cashfree's in-page checkout:
 *  1. we hand the browser an AES-encrypted request and it POSTs it to CCAvenue's hosted page;
 *  2. after payment CCAvenue sends the browser back to OUR return URL with an encrypted response
 *     (see CcavenueReturnController) — there is no separate server-to-server webhook;
 *  3. because a client can pay and close the tab before step 2, pending orders are also
 *     reconciled by asking CCAvenue's Order Status API (confirm() below).
 */
class CcavenueGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'ccavenue';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.ccavenue.merchant_id'))
            && filled(config('services.ccavenue.access_code'))
            && filled(config('services.ccavenue.working_key'));
    }

    private function isProduction(): bool
    {
        return config('services.ccavenue.env') === 'production';
    }

    private function checkoutUrl(): string
    {
        return $this->isProduction()
            ? 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction'
            : 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction';
    }

    private function apiUrl(): string
    {
        return $this->isProduction()
            ? 'https://api.ccavenue.com/apis/servlet/DoWebTrans'
            : 'https://apitest.ccavenue.com/apis/servlet/DoWebTrans';
    }

    /* ── CCAvenue's fixed scheme: AES-128-CBC, key = md5(working key), IV = bytes 0..15, hex output ── */

    private function key(): string
    {
        return hex2bin(md5((string) config('services.ccavenue.working_key')));
    }

    private function iv(): string
    {
        return pack('C*', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15);
    }

    public function encrypt(string $plain): string
    {
        return bin2hex(openssl_encrypt($plain, 'AES-128-CBC', $this->key(), OPENSSL_RAW_DATA, $this->iv()));
    }

    /** Returns false when the payload isn't valid hex or wasn't encrypted with our working key. */
    public function decrypt(string $hex): string|false
    {
        $hex = trim($hex);

        if ($hex === '' || strlen($hex) % 2 !== 0 || !ctype_xdigit($hex)) {
            return false;
        }

        return openssl_decrypt(hex2bin($hex), 'AES-128-CBC', $this->key(), OPENSSL_RAW_DATA, $this->iv());
    }

    public function createOrder(Milestone $milestone, float $amount): array
    {
        $contract = $milestone->contract;
        $client   = $contract->client;

        // Unique per attempt (CCAvenue rejects a reused order id); stays under their 30-char limit
        $orderId = 'ms' . $milestone->id . 'r' . Str::lower(Str::random(8));

        $returnUrl = url('/api/v1/webhooks/ccavenue/return');

        $plain = http_build_query([
            'merchant_id'    => config('services.ccavenue.merchant_id'),
            'order_id'       => $orderId,
            'currency'       => 'INR',
            'amount'         => number_format($amount, 2, '.', ''),
            'redirect_url'   => $returnUrl,
            'cancel_url'     => $returnUrl,
            'language'       => 'EN',
            'billing_name'   => trim(preg_replace('/[^\pL\pN\s]/u', '', (string) $client->name)) ?: 'Client',
            'billing_email'  => $client->email,
            'billing_country'=> 'India',
            'merchant_param1'=> (string) $milestone->id,
        ], '', '&');

        return [
            'order_id'    => $orderId,
            'action_url'  => $this->checkoutUrl(),
            'access_code' => config('services.ccavenue.access_code'),
            'enc_request' => $this->encrypt($plain),
        ];
    }

    /** A redirect order can't be reopened, so every attempt gets a fresh order. */
    public function reuseOrder(string $orderId, float $amount): ?array
    {
        return null;
    }

    /** Never trust the browser: ask CCAvenue whether this order really was paid. */
    public function confirm(Payment $payment, array $data): array
    {
        $status = $this->fetchOrderStatus($payment->gateway_order_id);

        if (!in_array($status['order_status'], ['Successful', 'Shipped'], true)) {
            throw new RuntimeException('Payment not completed yet.');
        }

        return [
            'payment_id'   => (string) ($status['reference_no'] ?? ''),
            'amount_paise' => isset($status['order_amt']) ? (int) round(((float) $status['order_amt']) * 100) : null,
        ];
    }

    /**
     * Order Status Tracker API. Needs this server's IP whitelisted in the CCAvenue dashboard.
     *
     * @return array the order's status fields (order_status, order_amt, reference_no, ...)
     */
    public function fetchOrderStatus(string $orderId): array
    {
        $res = Http::asForm()->timeout(20)->post($this->apiUrl(), [
            'enc_request'   => $this->encrypt(json_encode(['reference_no' => '', 'order_no' => $orderId])),
            'access_code'   => config('services.ccavenue.access_code'),
            'command'       => 'orderStatusTracker',
            'request_type'  => 'JSON',
            'response_type' => 'JSON',
            'version'       => '1.2',
        ]);

        parse_str(trim($res->body()), $out);

        if (($out['status'] ?? '1') !== '0' || empty($out['enc_response'])) {
            // On failure CCAvenue puts a readable reason in enc_response instead of ciphertext
            throw new RuntimeException('CCAvenue status check failed: ' . ($out['enc_response'] ?? 'HTTP ' . $res->status()));
        }

        $plain = $this->decrypt($out['enc_response']);
        $json  = $plain === false ? null : json_decode($plain, true);

        if (!is_array($json)) {
            throw new RuntimeException('CCAvenue status response could not be read.');
        }

        // The result is normally wrapped in Order_Status_Result; accept a flat shape too
        $result = $json['Order_Status_Result'] ?? $json;

        if (!isset($result['order_status'])) {
            throw new RuntimeException('CCAvenue status response had no order_status.');
        }

        return $result;
    }
}
