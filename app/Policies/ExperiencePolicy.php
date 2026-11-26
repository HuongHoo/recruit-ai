<?php

namespace App\Policies;

use App\Models\Experience;
use App\Models\User;

/**
 * Candidates may only touch their own work experience entries.
 */
class ExperiencePolicy
{
    public function update(User $user, Experience $experience): bool
    {
        return $user->isCandidate() && $experience->user_id === $user->id;
    }

    public function delete(User $user, Experience $experience): bool
    {
        return $this->update($user, $experience);
    }
}
