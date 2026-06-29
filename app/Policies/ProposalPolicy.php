<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function update(User $user, Proposal $proposal): bool
    {
        return $user->id === $proposal->freelancer_id
            && $proposal->status === 'pending';
    }

    public function withdraw(User $user, Proposal $proposal): bool
    {
        return $user->id === $proposal->freelancer_id
            && in_array($proposal->status, ['pending', 'shortlisted']);
    }

    public function shortlist(User $user, Proposal $proposal): bool
    {
        return $user->id === $proposal->project->client_id;
    }

    public function reject(User $user, Proposal $proposal): bool
    {
        return $user->id === $proposal->project->client_id
            && !in_array($proposal->status, ['accepted', 'withdrawn']);
    }

    public function accept(User $user, Proposal $proposal): bool
    {
        return $user->id === $proposal->project->client_id
            && in_array($proposal->status, ['pending', 'shortlisted']);
    }
}
