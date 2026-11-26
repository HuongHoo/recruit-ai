<?php

namespace App\Enums;

enum CvType: string
{
    case Online = 'online';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'CV trực tuyến',
            self::Upload => 'CV tải lên',
        };
    }
}
