<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $user->id === $contract->client_id
            || $user->id === $contract->freelancer_id;
    }

    public function addMilestone(User $user, Contract $contract): bool
    {
        return $user->id === $contract->client_id
            && $contract->status === 'active';
    }

    public function approveMilestone(User $user, Contract $contract): bool
    {
        return $user->id === $contract->client_id
            && $contract->status === 'active';
    }

    public function requestRevision(User $user, Contract $contract): bool
    {
        return $user->id === $contract->client_id
            && $contract->status === 'active';
    }

    public function deliver(User $user, Contract $contract): bool
    {
        return $user->id === $contract->freelancer_id
            && $contract->status === 'active';
    }
}
