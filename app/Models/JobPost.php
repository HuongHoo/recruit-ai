<?php

namespace App\Models;

use App\Enums\JobPostStatus;
use App\Enums\JobType;
use Database\Factories\JobPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A job posting owned by an employer. Salaries are stored in million VND.
 */
#[Fillable([
    'title', 'company_name', 'category', 'location', 'job_type', 'experience',
    'salary_min', 'salary_max', 'description', 'requirements', 'benefits', 'deadline', 'status',
])]
class JobPost extends Model
{
    /** @use HasFactory<JobPostFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'job_type' => JobType::class,
            'status' => JobPostStatus::class,
            'deadline' => 'date',
            'promoted_until' => 'datetime',
            'promotion_priority' => 'integer',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_jobs')->withTimestamps();
    }

    /**
     * Jobs visible to candidates: open and not past their deadline.
     *
     * @param  Builder<JobPost>  $query
     */
    /** Promoted jobs first (higher package first), then the rest by newest. */
    #[Scope]
    protected function promotedFirst(Builder $query): void
    {
        $query->orderByRaw('case when promoted_until > ? then promotion_priority else 0 end desc', [now()])->latest();
    }

    /**
     * The running promotion package, if any.
     *
     * @return array{tier: string, name: string, features: array<int, string>}|null
     */
    public function activePromotion(): ?array
    {
        if (! $this->promotion_tier || ! $this->promoted_until?->isFuture()) {
            return null;
        }

        $package = config("recruitment.packages.{$this->promotion_tier}");

        return $package ? ['tier' => $this->promotion_tier, 'name' => $package['name'], 'features' => $package['features']] : null;
    }

    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('status', JobPostStatus::Open)
            ->where(fn (Builder $q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
    }

    /**
     * @param  Builder<JobPost>  $query
     * @param  array{keyword?: ?string, category?: ?string, location?: ?string, job_type?: ?string}  $filters
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['keyword'] ?? null, function (Builder $q, string $keyword): void {
                $q->where(fn (Builder $inner) => $inner
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('company_name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%"));
            })
            ->when($filters['category'] ?? null, fn (Builder $q, string $category) => $q->where('category', $category))
            ->when($filters['location'] ?? null, fn (Builder $q, string $location) => $q->where('location', $location))
            ->when($filters['job_type'] ?? null, fn (Builder $q, string $type) => $q->where('job_type', $type));
    }

    /**
     * Jobs whose salary range reaches at least $million (million VND). Unstated salaries are excluded.
     *
     * @param  Builder<JobPost>  $query
     */
    #[Scope]
    protected function minSalary(Builder $query, ?int $million): void
    {
        $query->when($million, fn (Builder $q, int $min) => $q->where(fn (Builder $inner) => $inner
            ->where('salary_max', '>=', $min)
            ->orWhere('salary_min', '>=', $min)));
    }

    public function isVisible(): bool
    {
        return $this->status === JobPostStatus::Open && ($this->deadline === null || $this->deadline->gte(today()));
    }

    public function salaryLabel(): string
    {
        return match (true) {
            $this->salary_min && $this->salary_max => "{$this->salary_min} - {$this->salary_max} triệu",
            (bool) $this->salary_min => "Từ {$this->salary_min} triệu",
            (bool) $this->salary_max => "Đến {$this->salary_max} triệu",
            default => 'Thỏa thuận',
        };
    }
}
