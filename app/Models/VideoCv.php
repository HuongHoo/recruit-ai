<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A candidate's Video CV: a short profile, an attached CV and one recorded answer per question.
 * It is private until the candidate creates a share link; employers they apply to always see it.
 */
#[Fillable(['full_name', 'position', 'intro', 'public_email'])]
class VideoCv extends Model
{
    protected static function booted(): void
    {
        // Answers are removed by the FK cascade, which skips model events, so clean their files here.
        static::deleting(function (VideoCv $videoCv): void {
            $paths = $videoCv->answers()->whereNotNull('file_path')->pluck('file_path')->all();
            Storage::disk('local')->delete(array_filter([...$paths, $videoCv->photo_path]));
        });
    }

    protected function casts(): array
    {
        return [
            'shared_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    /**
     * The candidate's Video CV, created on first visit with the default questions and profile data.
     */
    public static function forUser(User $user): self
    {
        if ($existing = $user->videoCv()->first()) {
            return $existing;
        }

        return DB::transaction(function () use ($user): self {
            $videoCv = new self([
                'full_name' => $user->name,
                'position' => $user->profile()->headline,
            ]);
            $videoCv->user()->associate($user);
            $videoCv->save();

            foreach (config('recruitment.video_cv.default_questions') as $i => $item) {
                $videoCv->answers()->create(['position' => $i + 1, 'question' => $item['question']]);
            }

            return $videoCv;
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * @return HasMany<VideoCvAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(VideoCvAnswer::class)->orderBy('position');
    }

    /**
     * @return Collection<int, VideoCvAnswer>
     */
    public function recordedAnswers(): Collection
    {
        return $this->answers->filter(fn (VideoCvAnswer $answer) => $answer->hasVideo())->values();
    }

    public function hasRecordings(): bool
    {
        return $this->recordedAnswers()->isNotEmpty();
    }

    /**
     * What still blocks sharing, as messages for the candidate (empty when ready).
     *
     * @return list<string>
     */
    public function missingForShare(): array
    {
        return array_values(array_filter([
            $this->hasRecordings() ? null : 'Quay ít nhất một câu trả lời.',
            filled($this->full_name) ? null : 'Nhập họ tên.',
            filled($this->position) ? null : 'Nhập vị trí mong muốn.',
        ]));
    }

    public function isShared(): bool
    {
        return $this->share_token !== null;
    }

    public function shareUrl(): ?string
    {
        return $this->isShared() ? route('video-cv.public', $this->share_token) : null;
    }

    /**
     * Creates the share link, or replaces it so the old link stops working.
     */
    public function regenerateShareLink(): void
    {
        $this->forceFill(['share_token' => Str::random(32), 'shared_at' => now()])->save();
    }

    public function stopSharing(): void
    {
        $this->forceFill(['share_token' => null, 'shared_at' => null])->save();
    }

    public function totalDurationLabel(): string
    {
        $seconds = $this->recordedAnswers()->sum('duration_seconds');

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->full_name ?: $this->user->name));

        return mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    }
}
