<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewModerationController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['reviewer:id,name', 'reviewee:id,name', 'contract.project:id,title'])
            ->when($request->filled('is_hidden'), fn($q) => $q->where('is_hidden', (bool) $request->is_hidden))
            ->latest()
            ->paginate(25);

        return response()->json($reviews);
    }

    public function hide(Request $request, Review $review)
    {
        $request->validate(['reason' => ['required', 'string']]);

        $review->update(['is_hidden' => true]);
        AuditLog::record($request->user(), 'review.hidden', null, [
            'review_id' => $review->id,
            'reason'    => $request->reason,
        ]);

        return response()->json(['message' => 'Review hidden.']);
    }

    public function unhide(Request $request, Review $review)
    {
        $review->update(['is_hidden' => false]);
        AuditLog::record($request->user(), 'review.unhidden', null, ['review_id' => $review->id]);
        return response()->json(['message' => 'Review restored.']);
    }
}
