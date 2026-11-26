<?php

namespace App\Enums;

enum MockInterviewStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Đang làm dở',
            self::Completed => 'Đã chấm điểm',
        };
    }

    /** Full Tailwind class string for a status badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::InProgress => 'bg-amber-100 text-amber-700',
            self::Completed => 'bg-emerald-100 text-emerald-700',
        };
    }
}
