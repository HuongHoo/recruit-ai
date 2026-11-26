<?php

namespace App\Models;

use Database\Factories\JobAlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Email subscription for new jobs matching a keyword / category / location.
 */
#[Fillable(['keyword', 'category', 'location', 'is_active'])]
class JobAlert extends Model
{
    /** @use HasFactory<JobAlertFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Visible jobs matching this alert, posted after the last email (or the alert's creation).
     *
     * @return Builder<JobPost>
     */
    public function matchingJobs(): Builder
    {
        return JobPost::query()
            ->visible()
            ->filter(['keyword' => $this->keyword, 'category' => $this->category, 'location' => $this->location])
            ->where('created_at', '>', $this->last_sent_at ?? $this->created_at)
            ->latest();
    }

    public function summary(): string
    {
        $parts = array_filter([$this->keyword ? "\"{$this->keyword}\"" : null, $this->category, $this->location]);

        return $parts ? implode(' · ', $parts) : 'Tất cả việc làm mới';
    }
}
