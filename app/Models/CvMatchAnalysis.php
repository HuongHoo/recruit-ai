<?php

namespace App\Models;

use App\Enums\RequirementMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * AI evaluation of how well one CV fits one job description.
 *
 * @property array<int, array{name: string, cv: int, jd: int}> $criteria
 * @property array<int, array{requirement: string, status: string, evidence: string}> $requirements
 */
class CvMatchAnalysis extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'criteria' => 'array',
            'requirements' => 'array',
            'strengths' => 'array',
            'suggestions' => 'array',
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
     * @return BelongsTo<JobPost, $this>
     */
    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    /**
     * @return BelongsTo<Cv, $this>
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * Requirements with their status resolved to the enum.
     *
     * @return Collection<int, array{requirement: string, status: RequirementMatch, evidence: string}>
     */
    public function requirementRows(): Collection
    {
        return collect($this->requirements)->map(fn (array $row): array => [
            'requirement' => $row['requirement'],
            'status' => RequirementMatch::tryFrom($row['status']) ?? RequirementMatch::Missing,
            'evidence' => $row['evidence'] ?? '',
        ]);
    }

    /**
     * @return array<string, int> keyed by RequirementMatch value
     */
    public function statusCounts(): array
    {
        $rows = $this->requirementRows();

        return collect(RequirementMatch::cases())
            ->mapWithKeys(fn (RequirementMatch $status) => [$status->value => $rows->where('status', $status)->count()])
            ->all();
    }

    /**
     * @return array{label: string, classes: string, ring: string}
     */
    public function verdict(): array
    {
        return match (true) {
            $this->score >= 75 => ['label' => 'Rất phù hợp', 'classes' => 'text-emerald-600', 'ring' => '#10b981'],
            $this->score >= 50 => ['label' => 'Khá phù hợp', 'classes' => 'text-blue-600', 'ring' => '#2563eb'],
            $this->score >= 25 => ['label' => 'Phù hợp một phần', 'classes' => 'text-amber-600', 'ring' => '#f59e0b'],
            default => ['label' => 'Chưa phù hợp', 'classes' => 'text-red-600', 'ring' => '#ef4444'],
        };
    }
}
