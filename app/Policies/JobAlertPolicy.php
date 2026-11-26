<?php

namespace App\Policies;

use App\Models\JobAlert;
use App\Models\User;

/**
 * Candidates may only touch their own job alerts.
 */
class JobAlertPolicy
{
    public function update(User $user, JobAlert $jobAlert): bool
    {
        return $user->isCandidate() && $jobAlert->user_id === $user->id;
    }

    public function delete(User $user, JobAlert $jobAlert): bool
    {
        return $this->update($user, $jobAlert);
    }
}
