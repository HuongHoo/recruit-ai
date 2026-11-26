<?php

namespace Database\Factories;

use App\Enums\InterviewType;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'scheduled_at' => now()->addDays(2)->setTime(9, 0),
            'duration_minutes' => 60,
            'type' => InterviewType::Online,
            'location' => 'https://meet.google.com/abc-defg-hij',
            'interviewer' => fake()->name(),
        ];
    }
}
