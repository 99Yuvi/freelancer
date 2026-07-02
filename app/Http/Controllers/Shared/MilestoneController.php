<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Milestone;
use App\Models\MilestoneDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        return response()->json(['message' => 'Work submitted for review.', 'data' => $milestone->fresh()->load('deliveries.files')]);
    }

    /* ── Client: approve delivery → create Razorpay order ── */
    public function approve(Milestone $milestone)
    {
        $this->authorize('approve', $milestone);

        // Guard: already captured/paid — return stub so frontend just refreshes
        $existing = \App\Models\Payment::where('milestone_id', $milestone->id)
            ->whereIn('status', ['captured', 'paid'])
            ->exists();

        if ($existing) {
            return response()->json([
                'data'    => ['stub' => true],
                'message' => 'Milestone already paid.',
            ]);
        }

        // If Razorpay is not configured — dev/stub mode only
        if (!config('services.razorpay.key_id')) {
            $milestone->update(['status' => 'approved']);
            $this->checkContractCompletion($milestone->contract_id);

            return response()->json([
                'data'    => ['stub' => true],
                'message' => 'Milestone approved (payment gateway not configured).',
            ]);
        }

        // Razorpay IS configured — create order, never bypass payment
        try {
            $paymentService = app(\App\Services\PaymentService::class);
            $orderData = $paymentService->createOrderForMilestone($milestone);

            return response()->json([
                'data'    => $orderData,
                'message' => 'Payment order created. Complete payment to approve the milestone.',
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Razorpay order creation failed', [
                'milestone_id' => $milestone->id,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Payment gateway error. Please try again. (' . $e->getMessage() . ')',
            ], 500);
        }
    }

    /* ── Client: request revision ── */
    public function requestRevision(Request $request, Milestone $milestone)
    {
        $this->authorize('requestRevision', $milestone);

        $request->validate(['notes' => ['required', 'string', 'min:10']]);

        $milestone->update(['status' => 'revision_requested']);

        // Store revision notes as a delivery comment for context
        $milestone->deliveries()->create(['note' => '[Revision requested] ' . $request->notes]);

        return response()->json(['message' => 'Revision requested. The freelancer has been notified.', 'data' => $milestone->fresh()]);
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
