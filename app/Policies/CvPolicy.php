<?php

namespace App\Policies;

use App\Models\Cv;
use App\Models\User;

/**
 * Candidates may only touch their own CVs.
 */
class CvPolicy
{
    public function view(User $user, Cv $cv): bool
    {
        return $this->owns($user, $cv);
    }

    public function update(User $user, Cv $cv): bool
    {
        return $this->owns($user, $cv);
    }

    public function delete(User $user, Cv $cv): bool
    {
        return $this->owns($user, $cv);
    }

    private function owns(User $user, Cv $cv): bool
    {
        return $user->isCandidate() && $cv->user_id === $user->id;
    }
}
