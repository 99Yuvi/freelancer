<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->client_id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->client_id
            && !$project->hasAcceptedProposal();
    }

    public function viewProposals(User $user, Project $project): bool
    {
        return $user->id === $project->client_id;
    }
}
