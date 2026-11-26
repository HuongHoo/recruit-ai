<?php

namespace App\Models;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An interview the employer scheduled for one application.
 *
 * @property Carbon $scheduled_at
 * @property InterviewType $type
 * @property InterviewStatus $status
 */
#[Fillable(['scheduled_at', 'duration_minutes', 'type', 'location', 'interviewer', 'note'])]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'scheduled'];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'type' => InterviewType::class,
            'status' => InterviewStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** Interviews for jobs owned by the given employer. */
    #[Scope]
    protected function ownedBy(Builder $query, User $employer): void
    {
        $query->whereRelation('application.jobPost', 'user_id', $employer->id);
    }

    public function endsAt(): Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    public function timeRange(): string
    {
        return $this->scheduled_at->format('H:i').' - '.$this->endsAt()->format('H:i');
    }
}
