<?php

namespace App\Console\Commands;

use App\Jobs\UpdateFreelancerRatingJob;
use App\Models\Contract;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Console\Command;

class ExpireReviews extends Command
{
    protected $signature   = 'operalyn:expire-reviews';
    protected $description = 'Make reviews visible for contracts where the review window has closed';

    public function handle(): void
    {
        $windowDays = Setting::get('review_window_days', 14);

        // Find completed contracts where window has passed but reviews aren't all visible
        $contracts = Contract::where('status', 'completed')
            ->where('completed_at', '<', now()->subDays($windowDays))
            ->whereHas('reviews', fn($q) => $q->where('is_visible', false))
            ->with('reviews')
            ->get();

        $affected = 0;

        foreach ($contracts as $contract) {
            Review::where('contract_id', $contract->id)->update(['is_visible' => true]);

            // Recalculate rating for the freelancer
            UpdateFreelancerRatingJob::dispatch($contract->freelancer_id);

            $affected++;
        }

        $this->info("Expired reviews made visible for {$affected} contract(s).");
    }
}
