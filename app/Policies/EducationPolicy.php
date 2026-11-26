<?php

namespace App\Policies;

use App\Models\Education;
use App\Models\User;

/**
 * Candidates may only touch their own education entries.
 */
class EducationPolicy
{
    public function update(User $user, Education $education): bool
    {
        return $user->isCandidate() && $education->user_id === $user->id;
    }

    public function delete(User $user, Education $education): bool
    {
        return $this->update($user, $education);
    }
}
