<?php

namespace App\Policies;

use App\Models\MockInterview;
use App\Models\User;

/**
 * Candidates may only see and continue their own practice interviews.
 */
class MockInterviewPolicy
{
    public function view(User $user, MockInterview $interview): bool
    {
        return $this->owns($user, $interview);
    }

    public function update(User $user, MockInterview $interview): bool
    {
        return $this->owns($user, $interview);
    }

    private function owns(User $user, MockInterview $interview): bool
    {
        return $user->isCandidate() && $interview->user_id === $user->id;
    }
}
