<?php

namespace App\Models;

use App\Enums\CvType;
use Database\Factories\CvFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A CV is either built online from a template (rendered live from the owner's profile)
 * or an uploaded PDF/Word file stored on the private "local" disk.
 */
#[Fillable(['title', 'template', 'accent_color', 'objective'])]
class Cv extends Model
{
    /** @use HasFactory<CvFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleted(function (Cv $cv): void {
            if ($cv->file_path) {
                Storage::disk('local')->delete($cv->file_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'type' => CvType::class,
            'is_default' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOnline(): bool
    {
        return $this->type === CvType::Online;
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function templateName(): ?string
    {
        return config("recruitment.cv.templates.{$this->template}.name");
    }

    public function fileSizeLabel(): string
    {
        return $this->file_size ? number_format($this->file_size / 1024, 0, ',', '.').' KB' : '';
    }
}
