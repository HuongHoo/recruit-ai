<?php

namespace App\Enums;

enum JobType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Internship = 'internship';
    case Freelance = 'freelance';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Toàn thời gian',
            self::PartTime => 'Bán thời gian',
            self::Internship => 'Thực tập',
            self::Freelance => 'Tự do / Freelance',
            self::Remote => 'Làm việc từ xa',
        };
    }
}
