<?php

namespace App\Policies;

use App\Models\JobPost;
use App\Models\User;

/**
 * Employers may only manage their own job posts.
 */
class JobPostPolicy
{
    public function update(User $user, JobPost $jobPost): bool
    {
        return $user->isEmployer() && $jobPost->user_id === $user->id;
    }

    public function delete(User $user, JobPost $jobPost): bool
    {
        return $this->update($user, $jobPost);
    }

    /**
     * Candidates can apply only to visible jobs.
     */
    public function apply(User $user, JobPost $jobPost): bool
    {
        return $user->isCandidate() && $jobPost->isVisible();
    }
}
