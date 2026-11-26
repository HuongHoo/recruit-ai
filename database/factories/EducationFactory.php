<?php

namespace Database\Factories;

use App\Models\Education;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-10 years', '-4 years');

        return [
            'user_id' => User::factory()->candidate(),
            'school' => 'Đại học '.fake()->city(),
            'major' => fake()->randomElement(['Công nghệ thông tin', 'Quản trị kinh doanh', 'Kế toán', 'Marketing']),
            'degree' => 'Đại học',
            'start_date' => $start->format('Y-m-01'),
            'end_date' => (clone $start)->modify('+4 years')->format('Y-m-01'),
            'description' => null,
        ];
    }
}
