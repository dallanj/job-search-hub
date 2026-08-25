<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\ApplicationStatusEvent;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStatusEvent>
 */
class ApplicationStatusEventFactory extends Factory
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
            'from_status' => ApplicationStatus::Saved,
            'to_status' => ApplicationStatus::Applied,
            'changed_at' => fake()->dateTimeBetween('-3 months'),
            'note' => null,
        ];
    }
}
