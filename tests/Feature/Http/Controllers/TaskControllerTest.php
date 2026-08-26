<?php

use App\Enums\TaskPriority;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function validTaskPayload(JobApplication $application, array $overrides = []): array
{
    return array_merge([
        'job_application_id' => $application->id,
        'title' => 'Send a thank-you email',
        'due_at' => '2026-09-01',
        'priority' => TaskPriority::High->value,
    ], $overrides);
}

test('guests are redirected to login', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});

test('the index lists only the users tasks', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $task = Task::factory()->for($application, 'jobApplication')->create();
    Task::factory()->create();

    $this->actingAs($user)->get(route('tasks.index'))->assertInertia(
        fn (Assert $page) => $page
            ->component('tasks/Index')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $task->id),
    );
});

test('tasks can be searched and filtered by status', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Acme Labs']);
    $application = JobApplication::factory()->for($user)->for($company)->create();
    $matching = Task::factory()->for($application, 'jobApplication')->create([
        'title' => 'Prepare examples',
        'due_at' => today()->subDay(),
    ]);
    Task::factory()->for($application, 'jobApplication')->create([
        'title' => 'Unrelated item',
        'due_at' => today()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('tasks.index', ['search' => 'Acme', 'status' => 'overdue']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.data.0.id', $matching->id)
            ->where('filters.search', 'Acme')
            ->where('filters.status', 'overdue'));
});

test('a user can create update and delete a task', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(
        route('tasks.store'),
        validTaskPayload($application),
    );
    $task = Task::query()->sole();
    $response->assertRedirect(route('tasks.show', $task));
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'priority' => 3]);

    $this->actingAs($user)->patch(
        route('tasks.update', $task),
        validTaskPayload($application, ['title' => 'Send references']),
    )->assertRedirect(route('tasks.show', $task));
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Send references']);

    $this->actingAs($user)->delete(route('tasks.destroy', $task))
        ->assertRedirect(route('tasks.index'));
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

test('task creation rejects another users application', function () {
    $user = User::factory()->create();
    $privateApplication = JobApplication::factory()->create();

    $this->actingAs($user)
        ->post(route('tasks.store'), validTaskPayload($privateApplication))
        ->assertSessionHasErrors('job_application_id');

    expect(Task::query()->count())->toBe(0);
});

test('another users task returns 404 for record endpoints', function (string $method, string $routeName) {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $this->actingAs($user)->{$method}(route($routeName, $task))->assertNotFound();
})->with([
    'show' => ['get', 'tasks.show'],
    'edit' => ['get', 'tasks.edit'],
    'delete' => ['delete', 'tasks.destroy'],
]);
