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

    public function deliver(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->freelancer_id
            && in_array($milestone->status, ['pending', 'revision_requested']);
    }

    public function approve(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->client_id
            && $milestone->status === 'submitted';
    }

    public function requestRevision(User $user, Milestone $milestone): bool
    {
        return $user->id === $milestone->contract->client_id
            && $milestone->status === 'submitted';
    }
}
