<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Company owned by an employer account. Verification fields (status, reviewed_*) are never mass assignable.
 */
#[Fillable(['name', 'business_model', 'size', 'industry', 'website', 'phone', 'contact_email', 'address', 'logo_path', 'description'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Job posts published by this company's employer account.
     *
     * @return HasMany<JobPost, $this>
     */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class, 'user_id', 'user_id');
    }

    public function isVerified(): bool
    {
        return $this->status === CompanyStatus::Verified;
    }

    public function approve(User $admin): void
    {
        $this->forceFill([
            'status' => CompanyStatus::Verified,
            'rejection_reason' => null,
            'verified_at' => now(),
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }

    public function reject(User $admin, string $reason): void
    {
        $this->forceFill([
            'status' => CompanyStatus::Rejected,
            'rejection_reason' => $reason,
            'verified_at' => null,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ])->save();
    }

    /**
     * Sends a rejected company back to the admin queue after the employer corrected it.
     */
    public function resubmit(): void
    {
        $this->forceFill(['status' => CompanyStatus::Pending])->save();
    }

    public function businessModelLabel(): ?string
    {
        return config('recruitment.employer.business_models')[$this->business_model]['name'] ?? null;
    }

    public function sizeLabel(): ?string
    {
        return config('recruitment.employer.company_sizes')[$this->size] ?? null;
    }

    public function logoUrl(): string
    {
        return Storage::disk('public')->url($this->logo_path);
    }
}
