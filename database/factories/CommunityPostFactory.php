<?php

namespace Database\Factories;

use App\Enums\CommunityTopic;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPost>
 */
class CommunityPostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->candidate(),
            'topic' => fake()->randomElement(CommunityTopic::cases()),
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(2, true),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
