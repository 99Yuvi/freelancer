<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\MilestoneDelivery;
use App\Models\Payment;
use App\Notifications\MilestoneDelivered;
use App\Notifications\PaymentReceived;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class MilestoneController extends Controller
{
    /* ── Client: create milestone ── */
    public function store(Request $request, Contract $contract)
    {
        $this->authorize('addMilestone', $contract);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'amount'      => ['required', 'numeric', 'min:1'],
            'due_date'    => ['nullable', 'date', 'after:today'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $milestone = DB::transaction(function () use ($contract, $data) {
            \App\Models\Contract::lockForUpdate()->find($contract->id);
            $this->validateTotalAmount($contract, $data['amount']);
            return $contract->milestones()->create($data);
        });

        return response()->json([
            'data'    => $milestone,
            'message' => 'Milestone added.',
        ], 201);
    }

    /* ── Client: edit milestone ── */
    public function update(Request $request, Milestone $milestone)
    {
        $this->authorize('update', $milestone);

        $data = $request->validate([
            'title'      => ['sometimes', 'string', 'max:120'],
            'description'=> ['nullable', 'string'],
            'amount'     => ['sometimes', 'numeric', 'min:1'],
            'due_date'   => ['nullable', 'date'],
        ]);

        if (isset($data['amount'])) {
            $this->validateTotalAmount($milestone->contract, $data['amount'], $milestone->id);
        }

        $milestone->update($data);

        return response()->json(['data' => $milestone->fresh(), 'message' => 'Milestone updated.']);
    }

    /* ── Client: delete milestone ── */
    public function destroy(Milestone $milestone)
    {
        $this->authorize('delete', $milestone);
        $milestone->delete();
        return response()->json(['message' => 'Milestone removed.']);
    }

    /* ── Freelancer: submit delivery ── */
    public function deliver(Request $request, Milestone $milestone)
    {
        $this->authorize('deliver', $milestone);

        $request->validate([
            'note'    => ['required', 'string', 'min:20'],
            'files'   => ['nullable', 'array', 'max:5'],
            'files.*' => [
                File::types(['pdf','zip','png','jpg','jpeg','doc','docx','mp4'])
                    ->max(10 * 1024),
            ],
        ]);

        DB::transaction(function () use ($request, $milestone) {
            $delivery = $milestone->deliveries()->create(['note' => $request->note]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store(
                        'deliveries/' . $milestone->contract_id . '/' . $milestone->id,
                        'private'
                    );
                    $delivery->files()->create([
                        'file_path'     => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type'     => $file->getMimeType(),
                        'file_size'     => $file->getSize(),
                    ]);
                }
            }

            $milestone->update(['status' => 'submitted']);
        });

        $milestone->contract->client->notify(new MilestoneDelivered($milestone->title));

        return response()->json(['message' => 'Work submitted for review.', 'data' => $milestone->fresh()->load('deliveries.files')]);
    }

    /* ── Client: pay for the milestone → create an order at the chosen gateway (money held in escrow) ── */
    public function pay(Request $request, Milestone $milestone)
    {
        $this->authorize('pay', $milestone);

        $data = $request->validate([
            'gateway' => ['required', Rule::in(PaymentService::GATEWAYS)],
        ]);

        $service = app(PaymentService::class);

        if (!in_array($data['gateway'], $service->enabledGateways(), true)) {
            return response()->json(['message' => 'This payment method is not available right now.'], 422);
        }

        $alreadyPaid = Payment::where('milestone_id', $milestone->id)
            ->where('status', 'captured')
            ->exists();

        if ($alreadyPaid) {
            return response()->json(['message' => 'This milestone is already paid.'], 422);
        }

        try {
            return response()->json([
                'data'    => $service->createOrderForMilestone($milestone, $data['gateway']),
                'message' => 'Payment order created.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Payment order creation failed', [
                'gateway'      => $data['gateway'],
                'milestone_id' => $milestone->id,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment gateway error. Please try again or use another payment method.',
            ], 500);
        }
    }

    /* ── Client: confirm a checkout payment right away (the gateway webhook does the same, just later) ── */
    public function verifyPayment(Request $request, Milestone $milestone)
    {
        abort_unless($request->user()->id === $milestone->contract->client_id, 403);

        $data = $request->validate([
            'order_id'   => ['required', 'string'],
            'payment_id' => ['nullable', 'string'],  // Razorpay only
            'signature'  => ['nullable', 'string'],  // Razorpay only
        ]);

        // The order must belong to THIS milestone — never trust the id sent by the browser
        $payment = Payment::where('milestone_id', $milestone->id)
            ->where('gateway_order_id', $data['order_id'])
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found for this milestone.'], 404);
        }

        $service = app(PaymentService::class);

        try {
            $confirmed = $service->gateway($payment->gateway)->confirm($payment, $data);
        } catch (\Throwable $e) {
            Log::warning('Payment verification failed', [
                'gateway'      => $payment->gateway,
                'milestone_id' => $milestone->id,
                'error'        => $e->getMessage(),
            ]);

            return response()->json(['message' => 'We could not confirm this payment yet. If money was deducted it will show up shortly.'], 422);
        }

        $service->markCaptured($payment->gateway_order_id, $confirmed['payment_id'], $confirmed['amount_paise']);

        return response()->json(['message' => 'Payment received.', 'data' => $milestone->fresh()]);
    }

    /* ── Client: release the escrowed payment to the freelancer ── */
    public function release(Milestone $milestone)
    {
        $this->authorize('release', $milestone);

        $payment = Payment::where('milestone_id', $milestone->id)
            ->where('status', 'captured')
            ->first();

        if (!$payment) {
            return response()->json(['message' => 'Pay for this milestone before releasing it.'], 422);
        }

        DB::transaction(function () use ($milestone, $payment) {
            $locked = Milestone::lockForUpdate()->find($milestone->id);

            // Guard against a double-click crediting the freelancer twice
            if ($locked->status !== 'submitted') {
                abort(422, 'This milestone is not waiting for release.');
            }

            $locked->update(['status' => 'paid']);

            $profile = $payment->freelancerProfile();
            $profile->increment('total_earnings', $payment->net_amount);
            $profile->increment('pending_payout', $payment->net_amount);

            $this->checkContractCompletion($locked->contract_id);
        });

        $payment->freelancer->notify(new PaymentReceived(
            number_format((float) $payment->net_amount, 2),
            $milestone->title
        ));

        return response()->json(['message' => 'Payment released to the freelancer.']);
    }

    /* ── Helpers ── */

    private function validateTotalAmount(Contract $contract, float $newAmount, ?int $excludeId = null): void
    {
        $existing = $contract->milestones()
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->sum('amount');

        if ($existing + $newAmount > $contract->total_amount) {
            abort(422, "Milestone amounts cannot exceed contract total of ₹{$contract->total_amount}. " .
                "Already allocated: ₹{$existing}. Available: ₹" . ($contract->total_amount - $existing));
        }
    }

    private function checkContractCompletion(int $contractId): void
    {
        $contract = Contract::with('milestones')->find($contractId);
        if (!$contract) return;

        $allPaid = $contract->milestones->every(fn($m) => in_array($m->status, ['approved', 'paid']));

        if ($allPaid && $contract->status === 'active') {
            $contract->update(['status' => 'completed', 'completed_at' => now()]);
            $contract->project()->update(['status' => 'completed']);
        }
    }
}
