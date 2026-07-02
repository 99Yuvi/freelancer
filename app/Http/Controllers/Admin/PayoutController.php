<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayoutController extends Controller
{
    /**
     * List all payout requests with freelancer info, filterable by status.
     */
    public function index(Request $request)
    {
        $payouts = PayoutRequest::with([
                'freelancer:id,name,email',
            ])
            ->when(
                $request->status,
                fn($q, $v) => $q->where('status', $v)
            )
            ->latest()
            ->paginate(25);

        return response()->json($payouts);
    }

    /**
     * Approve and mark a payout request as paid.
     */
    public function approve(Request $request, PayoutRequest $payout)
    {
        if (!in_array($payout->status, ['pending', 'approved'])) {
            return response()->json([
                'message' => 'Only pending or approved requests can be marked as paid.',
            ], 422);
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payout->update([
            'status'       => 'paid',
            'admin_notes'  => $data['admin_notes'] ?? $payout->admin_notes,
            'processed_at' => now(),
        ]);

        return response()->json($payout->fresh());
    }

    /**
     * Reject a payout request and refund the amount back to the freelancer's pending_payout.
     */
    public function reject(Request $request, PayoutRequest $payout)
    {
        if ($payout->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be rejected.',
            ], 422);
        }

        $data = $request->validate([
            'admin_notes' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($payout, $data) {
            // Refund the amount back to the freelancer's pending_payout
            $payout->freelancer->freelancerProfile()->increment('pending_payout', $payout->amount);

            $payout->update([
                'status'       => 'rejected',
                'admin_notes'  => $data['admin_notes'],
                'processed_at' => now(),
            ]);
        });

        return response()->json($payout->fresh());
    }
}
