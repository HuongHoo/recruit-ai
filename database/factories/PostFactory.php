<?php

namespace Database\Factories;

use App\Enums\PostType;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => UserRole::Admin]),
            'type' => PostType::Article,
            'title' => 'Bí quyết '.fake()->unique()->numberBetween(1, 99999).' để viết CV ấn tượng',
            'category' => 'Cẩm nang nghề nghiệp',
            'excerpt' => 'Những lưu ý giúp hồ sơ của bạn nổi bật trước nhà tuyển dụng.',
            'content' => "Đoạn mở đầu.\n\nĐoạn nội dung chính.",
            'is_featured' => false,
            'published_at' => now()->subDay(),
        ];
    }

    public function event(): static
    {
        return $this->state([
            'type' => PostType::Event,
            'category' => 'Ngày hội việc làm',
            'event_date' => now()->addWeek(),
            'event_location' => 'Hà Nội',
        ]);
    }

    public function draft(): static
    {
        return $this->state(['published_at' => null]);
    }
}
