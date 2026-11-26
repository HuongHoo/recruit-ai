<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Cv;
use App\Models\JobPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_post_id' => JobPost::factory(),
            'cv_id' => Cv::factory(),
            'user_id' => fn (array $attributes): int => Cv::find($attributes['cv_id'])->user_id,
            'cv_title' => fn (array $attributes): string => Cv::find($attributes['cv_id'])->title,
            'cover_letter' => fake()->sentence(15),
            'status' => ApplicationStatus::Submitted,
        ];
    }

    public function status(ApplicationStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status, 'status_changed_at' => now()]);
    }
}
