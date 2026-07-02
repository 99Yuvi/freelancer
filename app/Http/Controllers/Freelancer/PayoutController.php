<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    /**
     * List the authenticated freelancer's own payout requests (paginated, latest first).
     */
    public function index(Request $request)
    {
        $payouts = PayoutRequest::where('freelancer_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($payouts);
    }

    /**
     * Create a new payout request.
     */
    public function store(Request $request)
    {
        $user    = $request->user();
        $profile = $user->freelancerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Freelancer profile not found.'], 404);
        }

        $maxAmount = (float) ($profile->pending_payout ?? 0);

        $data = $request->validate([
            'amount'              => ['required', 'numeric', 'min:100', "max:{$maxAmount}"],
            'bank_account_name'   => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code'           => ['nullable', 'string', 'max:20'],
            'upi_id'              => ['nullable', 'string', 'max:100'],
        ]);

        // Require at least one payment method
        $hasBankDetails = !empty($data['bank_account_name']) && !empty($data['bank_account_number']);
        $hasUpi         = !empty($data['upi_id']);

        if (!$hasBankDetails && !$hasUpi) {
            return response()->json([
                'message' => 'Please provide either bank account details or a UPI ID.',
                'errors'  => [
                    'upi_id' => ['Either bank account details or UPI ID is required.'],
                ],
            ], 422);
        }

        // Block if a pending request already exists
        $alreadyPending = PayoutRequest::where('freelancer_id', $user->id)
            ->pending()
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'message' => 'You already have a pending payout request. Please wait for it to be processed.',
            ], 409);
        }

        $payout = DB::transaction(function () use ($user, $profile, $data) {
            // Deduct from pending_payout immediately
            $profile->decrement('pending_payout', $data['amount']);

            return PayoutRequest::create([
                'freelancer_id'       => $user->id,
                'amount'              => $data['amount'],
                'bank_account_name'   => $data['bank_account_name']   ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'ifsc_code'           => $data['ifsc_code']           ?? null,
                'upi_id'              => $data['upi_id']              ?? null,
                'status'              => 'pending',
            ]);
        });

        return response()->json($payout, 201);
    }

    /**
     * Show a single payout request (must belong to the authenticated freelancer).
     */
    public function show(Request $request, PayoutRequest $payout)
    {
        if ($payout->freelancer_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return response()->json($payout);
    }
}
