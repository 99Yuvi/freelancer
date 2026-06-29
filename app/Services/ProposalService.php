<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\Setting;
use App\Models\User;

class ProposalService
{
    /** Enforce active-proposal limit before submission */
    public function enforceLimit(User $freelancer): void
    {
        $limit = Setting::get('max_active_proposals', 5);

        $active = Proposal::where('freelancer_id', $freelancer->id)
            ->whereIn('status', ['pending', 'shortlisted'])
            ->count();

        if ($active >= $limit) {
            abort(422, "You already have {$active} active proposals. Withdraw one before submitting another.");
        }
    }

    public function store(User $freelancer, int $projectId, array $data): Proposal
    {
        $this->enforceLimit($freelancer);

        return Proposal::create([
            'project_id'   => $projectId,
            'freelancer_id'=> $freelancer->id,
            'cover_letter' => $data['cover_letter'],
            'bid_amount'   => $data['bid_amount'],
            'duration_days'=> $data['duration_days'] ?? null,
        ]);
    }

    /** Reject all other pending/shortlisted proposals on the same project */
    public function rejectOthers(Proposal $accepted): void
    {
        Proposal::where('project_id', $accepted->project_id)
            ->where('id', '!=', $accepted->id)
            ->whereIn('status', ['pending', 'shortlisted'])
            ->update(['status' => 'rejected']);
    }
}
