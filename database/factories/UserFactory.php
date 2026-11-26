<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Candidate,
            'is_active' => true,
        ];
    }

    public function candidate(): static
    {
        return $this->state(fn () => ['role' => UserRole::Candidate]);
    }

    /**
     * An employer whose company has already been verified by an admin (the normal, fully working state).
     */
    public function employer(): static
    {
        return $this->employerWithoutCompany()
            ->afterCreating(fn (User $user) => Company::factory()->verified()->for($user, 'owner')->create());
    }

    public function employerWithoutCompany(): static
    {
        return $this->state(fn () => ['role' => UserRole::Employer]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
