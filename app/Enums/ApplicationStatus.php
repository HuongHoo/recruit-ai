<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Viewed = 'viewed';
    case Interview = 'interview';
    case Passed = 'passed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Đã nộp',
            self::Viewed => 'Đã xem',
            self::Interview => 'Mời phỏng vấn',
            self::Passed => 'Đạt',
            self::Rejected => 'Không đạt',
        };
    }

    /** Full Tailwind class string for a status badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Submitted => 'bg-slate-100 text-slate-700',
            self::Viewed => 'bg-sky-100 text-sky-700',
            self::Interview => 'bg-amber-100 text-amber-700',
            self::Passed => 'bg-emerald-100 text-emerald-700',
            self::Rejected => 'bg-red-100 text-red-700',
        };
    }

    /**
     * Statuses an employer may set manually ("Viewed" is set automatically when the application is opened).
     *
     * @return array<int, self>
     */
    public static function employerSettable(): array
    {
        return [self::Viewed, self::Interview, self::Passed, self::Rejected];
    }
}
