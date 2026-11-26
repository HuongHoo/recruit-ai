<?php

namespace App\Enums;

enum CommunityTopic: string
{
    case Feedback = 'feedback';
    case Experience = 'experience';
    case Question = 'question';

    public function label(): string
    {
        return match ($this) {
            self::Feedback => 'Góp ý ứng dụng',
            self::Experience => 'Chia sẻ kinh nghiệm',
            self::Question => 'Hỏi đáp',
        };
    }
}
