<?php

namespace App\Models;

use Database\Factories\CommunityCommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

/**
 * A comment on a community post. Two levels: root comments (parent_id null) and their replies.
 */
#[Fillable(['body', 'parent_id', 'reply_to_user_id'])]
class CommunityComment extends Model
{
    /** @use HasFactory<CommunityCommentFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (CommunityComment $comment): void {
            CommunityReport::query()
                ->where('reportable_type', $comment->getMorphClass())
                ->where(fn ($q) => $q->where('reportable_id', $comment->id)
                    ->orWhereIn('reportable_id', $comment->replies()->select('id')))
                ->delete();
        });
    }

    /**
     * Deletes the comment (and, for a root comment, its replies) and lowers the post's comments_count.
     */
    public function deleteAndRecount(): void
    {
        DB::transaction(function () {
            $removed = 1 + ($this->parent_id === null ? $this->replies()->count() : 0);
            $this->delete();

            $post = CommunityPost::query()->lockForUpdate()->find($this->community_post_id);
            if ($post) {
                $post->comments_count = max(0, $post->comments_count - $removed);
                CommunityPost::withoutTimestamps(fn () => $post->save());
            }
        });
    }

    /**
     * @return BelongsTo<CommunityPost, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<CommunityComment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CommunityComment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_to_user_id');
    }

    /**
     * @return MorphMany<CommunityReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(CommunityReport::class, 'reportable');
    }
}
