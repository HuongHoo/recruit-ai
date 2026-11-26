<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

/**
 * An application is visible to the candidate who sent it and to the employer who owns the job.
 * Only that employer may change its status.
 */
class ApplicationPolicy
{
    public function view(User $user, Application $application): bool
    {
        return $this->isApplicant($user, $application) || $this->isJobOwner($user, $application);
    }

    public function update(User $user, Application $application): bool
    {
        return $this->isJobOwner($user, $application);
    }

    private function isApplicant(User $user, Application $application): bool
    {
        return $user->isCandidate() && $application->user_id === $user->id;
    }

    private function isJobOwner(User $user, Application $application): bool
    {
        return $user->isEmployer() && $application->jobPost->user_id === $user->id;
    }
}
