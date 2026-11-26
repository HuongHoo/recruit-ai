<?php

namespace App\Enums;

enum PostType: string
{
    case Article = 'article';
    case Event = 'event';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Bài viết',
            self::Event => 'Sự kiện',
        };
    }
}
