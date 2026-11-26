<?php

namespace App\Enums;

enum InterviewType: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Phone = 'phone';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Trực tuyến',
            self::Offline => 'Tại văn phòng',
            self::Phone => 'Qua điện thoại',
        };
    }
}
