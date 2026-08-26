<?php

use App\Actions\GetUpcomingActions;
use App\Enums\InterviewOutcome;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Date;

test('it combines due tasks and scheduled interviews chronologically', function () {
    Date::setTestNow('2026-08-26 09:00:00');
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $overdue = Task::factory()->for($application, 'jobApplication')->create([
        'title' => 'Overdue follow-up',
        'due_at' => '2026-08-25',
    ]);
    $today = Interview::factory()->for($application, 'jobApplication')->create([
        'scheduled_at' => '2026-08-26 14:00:00',
    ]);
    $upcoming = Task::factory()->for($application, 'jobApplication')->create([
        'title' => 'Prepare portfolio',
        'due_at' => '2026-08-28',
    ]);

    $actions = app(GetUpcomingActions::class)->handle($user);

    expect($actions)->toHaveCount(3)
        ->and($actions->pluck('id')->all())->toBe([$overdue->id, $today->id, $upcoming->id])
        ->and($actions->first()['is_overdue'])->toBeTrue();
});

test('it excludes completed distant cancelled and other users actions', function () {
    Date::setTestNow('2026-08-26 09:00:00');
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    Task::factory()->for($application, 'jobApplication')->create([
        'due_at' => '2026-08-27',
        'completed_at' => now(),
    ]);
    Task::factory()->for($application, 'jobApplication')->create([
        'due_at' => '2026-10-01',
    ]);
    Interview::factory()->for($application, 'jobApplication')->create([
        'scheduled_at' => '2026-08-27 14:00:00',
        'outcome' => InterviewOutcome::Cancelled,
    ]);
    Task::factory()->create(['due_at' => '2026-08-27']);
    Interview::factory()->create(['scheduled_at' => '2026-08-27 14:00:00']);

    expect(app(GetUpcomingActions::class)->handle($user))->toBeEmpty();
});
