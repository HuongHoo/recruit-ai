<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->employerWithoutCompany(),
            'name' => fake()->company(),
            'business_model' => 'in_house',
            'size' => '25-99',
            'industry' => 'IT - Phần mềm',
            'website' => 'https://example.com',
            'phone' => '0901234567',
            'logo_path' => 'company-logos/logo.png',
            'description' => fake()->paragraph(),
            'status' => CompanyStatus::Pending,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Verified, 'verified_at' => now(), 'reviewed_at' => now()]);
    }

    public function rejected(string $reason = 'Thông tin website không khớp với tên công ty.'): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Rejected, 'rejection_reason' => $reason, 'reviewed_at' => now()]);
    }
}
