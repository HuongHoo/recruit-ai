<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One interview question of a Video CV and the clip that answers it (null until recorded).
 * 'source' is "record" (webcam in the browser) or "upload".
 */
#[Fillable(['position', 'question'])]
class VideoCvAnswer extends Model
{
    protected static function booted(): void
    {
        static::deleted(function (VideoCvAnswer $answer): void {
            if ($answer->file_path) {
                Storage::disk('local')->delete($answer->file_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'file_size' => 'integer',
            'duration_seconds' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VideoCv, $this>
     */
    public function videoCv(): BelongsTo
    {
        return $this->belongsTo(VideoCv::class);
    }

    public function hasVideo(): bool
    {
        return $this->file_path !== null;
    }

    public function durationLabel(): string
    {
        $seconds = (int) $this->duration_seconds;

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public function hint(): ?string
    {
        foreach (config('recruitment.video_cv.default_questions') as $item) {
            if ($item['question'] === $this->question) {
                return $item['hint'];
            }
        }

        return null;
    }
}
