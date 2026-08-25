<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => fn (array $attributes) => Company::factory()->create([
                'user_id' => $attributes['user_id'],
            ]),
            'role_title' => fake()->jobTitle(),
            'status' => ApplicationStatus::Saved,
            'sort_order' => fake()->numberBetween(0, 10_000),
            'employment_type' => fake()->randomElement(['full-time', 'part-time', 'contract']),
            'workplace_type' => fake()->randomElement(['remote', 'hybrid', 'on-site']),
            'location' => fake()->city().', '.fake()->countryCode(),
            'source' => fake()->randomElement(['Company website', 'Indeed', 'LinkedIn', 'Referral']),
            'job_url' => fake()->url(),
            'salary_min' => fake()->numberBetween(60_000, 100_000),
            'salary_max' => fake()->numberBetween(110_000, 180_000),
            'salary_currency' => 'CAD',
            'applied_at' => null,
            'closed_at' => null,
            'description' => fake()->optional()->paragraphs(3, true),
        ];
    }

    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Applied,
            'applied_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    public function withStatus(ApplicationStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'applied_at' => $status === ApplicationStatus::Saved
                ? null
                : fake()->dateTimeBetween('-3 months', 'now'),
            'closed_at' => in_array($status, [
                ApplicationStatus::Rejected,
                ApplicationStatus::Withdrawn,
                ApplicationStatus::Archived,
            ], true) ? now() : null,
        ]);
    }
}
