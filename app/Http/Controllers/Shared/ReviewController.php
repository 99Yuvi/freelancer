<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateFreelancerRatingJob;
use App\Models\Contract;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        abort_unless(
            $request->user()->id === $contract->client_id ||
            $request->user()->id === $contract->freelancer_id,
            403
        );
        abort_unless($contract->status === 'completed', 422, 'Reviews are only available after a contract is completed.');

        $windowDays = Setting::get('review_window_days', 14);
        abort_unless(
            $contract->completed_at?->addDays($windowDays)->isFuture() ?? true,
            422, 'The review window has closed.'
        );

        $alreadyReviewed = Review::where('contract_id', $contract->id)
            ->where('reviewer_id', $request->user()->id)
            ->exists();
        abort_if($alreadyReviewed, 422, 'You have already submitted a review for this contract.');

        $data = $request->validate([
            'communication' => ['required', 'integer', 'min:1', 'max:5'],
            'quality'       => ['required', 'integer', 'min:1', 'max:5'],
            'timeliness'    => ['required', 'integer', 'min:1', 'max:5'],
            'overall'       => ['required', 'integer', 'min:1', 'max:5'],
            'body'          => ['nullable', 'string', 'max:500'],
        ]);

        $isClient     = $request->user()->id === $contract->client_id;
        $revieweeId   = $isClient ? $contract->freelancer_id : $contract->client_id;

        $review = Review::create([
            ...$data,
            'contract_id'  => $contract->id,
            'reviewer_id'  => $request->user()->id,
            'reviewee_id'  => $revieweeId,
            'is_visible'   => false,
        ]);

        // Make visible if both parties have now reviewed
        $this->checkVisibility($contract);

        return response()->json(['data' => $review, 'message' => 'Review submitted.'], 201);
    }

    public function respond(Request $request, Review $review)
    {
        abort_unless($request->user()->id === $review->reviewee_id, 403);
        abort_if($review->response, 422, 'You have already responded to this review.');

        $request->validate(['response' => ['required', 'string', 'max:300']]);

        $review->update([
            'response'    => $request->response,
            'response_at' => now(),
        ]);

        return response()->json(['data' => $review->fresh(), 'message' => 'Response added.']);
    }

    public function forUser(User $user)
    {
        $reviews = Review::where('reviewee_id', $user->id)
            ->where('is_visible', true)
            ->where('is_hidden', false)
            ->with('reviewer:id,name,avatar_path')
            ->latest()
            ->paginate(20);

        return response()->json($reviews);
    }

    /* ── Helpers ── */

    private function checkVisibility(Contract $contract): void
    {
        $reviews = Review::where('contract_id', $contract->id)->get();

        // Make both reviews visible once both parties have submitted
        if ($reviews->count() >= 2) {
            Review::where('contract_id', $contract->id)->update(['is_visible' => true]);

            // Recalculate freelancer rating
            UpdateFreelancerRatingJob::dispatch($contract->freelancer_id);
        }
    }
}
