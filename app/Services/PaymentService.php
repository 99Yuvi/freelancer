<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Milestone;
use App\Models\Payment;
use Razorpay\Api\Api;

class PaymentService
{
    private Api $api;

    public function __construct()
    {
        $this->api = new Api(
            config('services.razorpay.key_id'),
            config('services.razorpay.key_secret')
        );
    }

    /**
     * Create a Razorpay order for a milestone and store a pending Payment record.
     * Returns the data the frontend needs to open Razorpay Checkout.
     */
    public function createOrderForMilestone(Milestone $milestone): array
    {
        $contract        = $milestone->contract;
        $commissionRate  = $contract->commission_rate;
        $grossAmount     = (float) $milestone->amount;
        $commission      = round($grossAmount * ($commissionRate / 100), 2);
        $netAmount       = round($grossAmount - $commission, 2);

        // Create Razorpay order (amount in paise)
        $order = $this->api->order->create([
            'amount'          => (int) ($grossAmount * 100),
            'currency'        => 'INR',
            'receipt'         => 'ms_' . $milestone->id,
            'payment_capture' => 1,
        ]);

        // Store pending payment record
        Payment::create([
            'contract_id'        => $contract->id,
            'milestone_id'       => $milestone->id,
            'client_id'          => $contract->client_id,
            'freelancer_id'      => $contract->freelancer_id,
            'razorpay_order_id'  => $order['id'],
            'amount'             => $grossAmount,
            'commission_rate'    => $commissionRate,
            'commission_amount'  => $commission,
            'net_amount'         => $netAmount,
            'status'             => 'pending',
        ]);

        return [
            'razorpay_order_id' => $order['id'],
            'amount'            => (string) $grossAmount,
            'amount_paise'      => (int) ($grossAmount * 100),
            'currency'          => 'INR',
            'key_id'            => config('services.razorpay.key_id'),
        ];
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
