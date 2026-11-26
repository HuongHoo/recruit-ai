<?php

namespace App\Enums;

enum JobPostStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Đang tuyển',
            self::Closed => 'Đã đóng',
        };
    }
}
