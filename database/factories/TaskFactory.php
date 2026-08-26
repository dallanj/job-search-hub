<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\JobApplication;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'title' => fake()->sentence(5),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'completed_at' => null,
            'priority' => TaskPriority::Normal,
        ];
    }
}
