<?php

use App\Enums\TaskPriority;
use App\Models\Task;

test('task fields are cast to domain types', function () {
    $task = Task::factory()->create([
        'due_at' => '2026-09-01',
        'completed_at' => '2026-09-02 10:30:00',
        'priority' => TaskPriority::Urgent,
    ]);

    expect($task->priority)->toBe(TaskPriority::Urgent)
        ->and($task->due_at->toDateString())->toBe('2026-09-01')
        ->and($task->completed_at->toDateTimeString())->toBe('2026-09-02 10:30:00');
});
