<?php

namespace App\Jobs;

use App\Models\FreelancerProfile;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateFreelancerRatingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $freelancerId) {}

    public function handle(): void
    {
        $profile = FreelancerProfile::where('user_id', $this->freelancerId)->firstOrFail();

        $reviews = Review::where('reviewee_id', $this->freelancerId)
            ->where('is_visible', true)
            ->where('is_hidden', false)
            ->get();

        $count = $reviews->count();
        $minCount = Setting::get('min_reviews_for_rating', 3);

        $profile->update([
            'rating_count' => $count,
            'rating_avg'   => $count >= $minCount
                ? round($reviews->avg('overall'), 2)
                : null,
        ]);
    }
}
