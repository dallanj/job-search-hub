<?php

use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;

test('an owner can complete and reopen a task', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $task = Task::factory()->for($application, 'jobApplication')->create();

    $this->actingAs($user)->patch(route('tasks.completion', $task))->assertRedirect();
    expect($task->refresh()->completed_at)->not->toBeNull();

    $this->actingAs($user)->patch(route('tasks.completion', $task))->assertRedirect();
    expect($task->refresh()->completed_at)->toBeNull();
});

test('another users task cannot be completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $this->actingAs($user)
        ->patch(route('tasks.completion', $task))
        ->assertNotFound();
});
