<?php

namespace Database\Factories;

use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 years', '-2 years');

        return [
            'user_id' => User::factory()->candidate(),
            'company' => fake()->company(),
            'position' => fake()->jobTitle(),
            'start_date' => $start->format('Y-m-01'),
            'end_date' => (clone $start)->modify('+1 year')->format('Y-m-01'),
            'is_current' => false,
            'description' => fake()->paragraph(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (): array => ['is_current' => true, 'end_date' => null]);
    }
}
