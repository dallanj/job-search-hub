<?php

use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users may list and create tasks', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows($ability, Task::class))->toBeTrue();
})->with(['viewAny', 'create']);

test('owners may manage tasks', function (string $ability) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $task = Task::factory()->for($application, 'jobApplication')->create();

    expect(Gate::forUser($user)->allows($ability, $task))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('non owners may not manage tasks', function (string $ability) {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    expect(Gate::forUser($user)->allows($ability, $task))->toBeFalse();
})->with(['view', 'update', 'delete']);
