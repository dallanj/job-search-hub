<?php

namespace Database\Factories;

use App\Models\ApplicationNote;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationNote> */
class ApplicationNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_application_id' => JobApplication::factory(),
            'user_id' => fn (array $attributes): int => (int) JobApplication::query()
                ->whereKey($attributes['job_application_id'])
                ->value('user_id'),
            'body' => fake()->paragraph(),
        ];
    }
}
