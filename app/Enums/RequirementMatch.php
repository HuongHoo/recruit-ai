<?php

namespace App\Enums;

/**
 * How well a CV satisfies one requirement of a job description.
 */
enum RequirementMatch: string
{
    case Met = 'met';
    case Unclear = 'unclear';
    case Unmet = 'unmet';
    case Missing = 'missing';

    public function label(): string
    {
        return match ($this) {
            self::Met => 'Đáp ứng',
            self::Unclear => 'Cần làm rõ',
            self::Unmet => 'Chưa đáp ứng',
            self::Missing => 'Chưa thấy',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Met => '✓',
            self::Unclear => '!',
            self::Unmet => '✕',
            self::Missing => '?',
        };
    }

    /** Full Tailwind class string for the status chip. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Met => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Unclear => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Unmet => 'bg-red-50 text-red-700 border-red-200',
            self::Missing => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }
}
