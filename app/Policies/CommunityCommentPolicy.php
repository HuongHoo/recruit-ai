<?php

namespace App\Policies;

use App\Models\CommunityComment;
use App\Models\User;

/**
 * Authors edit their own comments; authors and admins may delete them.
 */
class CommunityCommentPolicy
{
    public function update(User $user, CommunityComment $comment): bool
    {
        return $user->is_active && $comment->user_id === $user->id;
    }

    public function delete(User $user, CommunityComment $comment): bool
    {
        return $user->isAdmin() || ($user->is_active && $comment->user_id === $user->id);
    }
}
