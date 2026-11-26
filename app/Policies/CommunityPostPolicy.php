<?php

namespace App\Policies;

use App\Models\CommunityPost;
use App\Models\User;

/**
 * Any active member may post; authors edit their own posts; authors and admins may delete.
 */
class CommunityPostPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, CommunityPost $post): bool
    {
        return $user->is_active && $post->user_id === $user->id;
    }

    public function delete(User $user, CommunityPost $post): bool
    {
        return $user->isAdmin() || ($user->is_active && $post->user_id === $user->id);
    }

    /** Comment, like or report on a post: the post must be visible to this member. */
    public function interact(User $user, CommunityPost $post): bool
    {
        return $user->is_active && $post->isVisibleTo($user);
    }
}
