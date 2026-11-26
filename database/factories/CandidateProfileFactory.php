<?php

namespace Database\Factories;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->candidate(),
            'phone' => '0912345678',
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-20 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'address' => fake()->address(),
            'headline' => fake()->jobTitle(),
            'summary' => fake()->paragraph(),
            'desired_salary_min' => 10,
            'desired_salary_max' => 20,
            'desired_locations' => ['Hà Nội'],
            'skills' => ['PHP', 'Laravel', 'MySQL'],
        ];
    }
}
