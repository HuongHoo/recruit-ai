<?php

namespace App\Enums;

enum InterviewStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Đã lên lịch',
            self::Completed => 'Đã phỏng vấn',
            self::Cancelled => 'Đã hủy',
        };
    }

    /** Full Tailwind class string for a status badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Scheduled => 'bg-teal-100 text-teal-700',
            self::Completed => 'bg-slate-100 text-slate-600',
            self::Cancelled => 'bg-red-100 text-red-600',
        };
    }
}
