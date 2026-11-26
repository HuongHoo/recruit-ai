<?php

namespace App\Models;

use Database\Factories\CandidateProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'phone', 'date_of_birth', 'gender', 'address', 'headline', 'summary',
    'desired_salary_min', 'desired_salary_max', 'desired_locations', 'skills',
])]
class CandidateProfile extends Model
{
    /** @use HasFactory<CandidateProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'desired_salary_min' => 'integer',
            'desired_salary_max' => 'integer',
            'desired_locations' => 'array',
            'skills' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function genderLabel(): ?string
    {
        return config('recruitment.genders')[$this->gender] ?? null;
    }

    /**
     * Human readable salary expectation, values are stored in million VND.
     */
    public function salaryLabel(): string
    {
        return match (true) {
            $this->desired_salary_min && $this->desired_salary_max => "{$this->desired_salary_min} - {$this->desired_salary_max} triệu",
            (bool) $this->desired_salary_min => "Từ {$this->desired_salary_min} triệu",
            (bool) $this->desired_salary_max => "Đến {$this->desired_salary_max} triệu",
            default => 'Thỏa thuận',
        };
    }
}
