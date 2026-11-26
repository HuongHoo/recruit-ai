<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * An image attached to a community post, stored on the public disk.
 */
#[Fillable(['path', 'position'])]
class CommunityPostImage extends Model
{
    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    /**
     * @return BelongsTo<CommunityPost, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
