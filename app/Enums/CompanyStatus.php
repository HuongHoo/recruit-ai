<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác thực',
            self::Verified => 'Đã xác thực',
            self::Rejected => 'Bị từ chối',
        };
    }

    /** Full Tailwind class string for a status badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Verified => 'bg-emerald-100 text-emerald-700',
            self::Rejected => 'bg-red-100 text-red-700',
        };
    }
}
