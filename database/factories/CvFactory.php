<?php

namespace Database\Factories;

use App\Enums\CvType;
use App\Models\Cv;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cv>
 */
class CvFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->candidate(),
            'title' => 'CV '.fake()->jobTitle(),
            'type' => CvType::Online,
            'template' => fake()->randomElement(['classic', 'modern', 'minimal']),
            'accent_color' => '#2563eb',
            'objective' => fake()->sentence(12),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }

    public function uploaded(): static
    {
        return $this->state(fn (): array => [
            'type' => CvType::Upload,
            'template' => null,
            'accent_color' => null,
            'objective' => null,
            'file_path' => 'cvs/'.fake()->uuid().'.pdf',
            'original_name' => 'my-cv.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 120_000,
        ]);
    }
}
