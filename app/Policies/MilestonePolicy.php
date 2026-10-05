<?php

namespace App\Policies;

use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
    public function update(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->client_id
            && $milestone->status === 'pending'
            && !$milestone->deliveries()->exists();
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->client_id
            && $milestone->status === 'pending';
    }

    /** Freelancer can only deliver once the client has paid (status moves to in_progress). */
    public function deliver(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->freelancer_id
            && in_array($milestone->status, ['in_progress', 'revision_requested']);
    }

    /** 'submitted' is allowed so milestones delivered under the old pay-after-work flow can still be paid. */
    public function pay(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->client_id
            && in_array($milestone->status, ['pending', 'submitted']);
    }

    public function release(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->client_id
            && $milestone->status === 'submitted';
    }
}
