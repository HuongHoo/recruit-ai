<?php

namespace App\Models;

use App\Enums\CommunityTopic;
use Database\Factories\CommunityPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A member's post in the community forum. Hidden posts are only visible to admins.
 */
#[Fillable(['topic', 'title', 'body'])]
class CommunityPost extends Model
{
    /** @use HasFactory<CommunityPostFactory> */
    use HasFactory;

    public const MAX_IMAGES = 4;

    protected function casts(): array
    {
        return [
            'topic' => CommunityTopic::class,
            'is_hidden' => 'boolean',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (CommunityPost $post): void {
            // Morph relations have no foreign key, so their reports are removed here.
            CommunityReport::query()->whereMorphedTo('reportable', $post)->delete();
            CommunityReport::query()
                ->where('reportable_type', (new CommunityComment)->getMorphClass())
                ->whereIn('reportable_id', $post->comments()->select('id'))
                ->delete();
        });

        static::deleted(function (CommunityPost $post): void {
            Storage::disk('public')->deleteDirectory("community/{$post->id}");
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<CommunityPostImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(CommunityPostImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<CommunityComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class);
    }

    /**
     * @return HasMany<CommunityComment, $this>
     */
    public function rootComments(): HasMany
    {
        return $this->comments()->whereNull('parent_id')->oldest('id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_post_likes')->withTimestamps();
    }

    /**
     * @return MorphMany<CommunityReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(CommunityReport::class, 'reportable');
    }

    /**
     * Admins see every post; everyone else only the ones that are not hidden.
     *
     * @param  Builder<CommunityPost>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?User $user): void
    {
        if (! $user?->isAdmin()) {
            $query->where('is_hidden', false);
        }
    }

    public function isVisibleTo(?User $user): bool
    {
        return ! $this->is_hidden || (bool) $user?->isAdmin();
    }

    public function isLikedBy(?User $user): bool
    {
        return $user !== null && $this->likes()->whereKey($user->id)->exists();
    }

    public function excerpt(int $length = 200): string
    {
        return Str::limit(Str::squish($this->body), $length, '…');
    }
}
