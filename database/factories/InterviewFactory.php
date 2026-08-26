<?php

namespace Database\Factories;

use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_application_id' => JobApplication::factory(),
            'contact_id' => null,
            'type' => fake()->randomElement(InterviewType::cases()),
            'scheduled_at' => fake()->dateTimeBetween('-1 month', '+2 months'),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'location_or_url' => fake()->url(),
            'outcome' => InterviewOutcome::Pending,
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
