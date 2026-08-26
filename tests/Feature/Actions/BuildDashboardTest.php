<?php

use App\Actions\BuildDashboard;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Date;

test('it builds historical conversion and response metrics', function () {
    Date::setTestNow('2026-08-26 09:00:00');
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create([
        'status' => ApplicationStatus::Interview,
        'source' => 'Referral',
        'applied_at' => '2026-08-24',
    ]);
    $application->statusEvents()->createMany([
        ['from_status' => null, 'to_status' => ApplicationStatus::Applied, 'changed_at' => '2026-08-24 09:00:00'],
        ['from_status' => ApplicationStatus::Applied, 'to_status' => ApplicationStatus::Screening, 'changed_at' => '2026-08-27 09:00:00'],
        ['from_status' => ApplicationStatus::Screening, 'to_status' => ApplicationStatus::Interview, 'changed_at' => '2026-08-28 09:00:00'],
    ]);

    $dashboard = app(BuildDashboard::class)->handle($user);

    expect($dashboard['summary'])
        ->toMatchArray([
            'active_applications' => 1,
            'applications_this_week' => 1,
            'interview_rate' => 100,
            'median_response_days' => 3.4,
        ])
        ->and($dashboard['funnel'][0])->toMatchArray(['stage' => 'applied', 'count' => 1])
        ->and($dashboard['funnel'][2])->toMatchArray(['stage' => 'interview', 'count' => 1])
        ->and($dashboard['response_time']['buckets'][1]['count'])->toBe(1)
        ->and($dashboard['sources'][0])->toMatchArray([
            'source' => 'Referral',
            'applications' => 1,
            'response_rate' => 100,
            'interview_rate' => 100,
        ]);
});

test('attention metrics and all dashboard data remain user scoped', function () {
    Date::setTestNow('2026-08-26 09:00:00');
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create([
        'status' => ApplicationStatus::Applied,
        'applied_at' => '2026-08-01',
    ]);
    Task::factory()->for($application, 'jobApplication')->create(['due_at' => '2026-08-20']);
    Task::factory()->create(['due_at' => '2026-08-20']);

    $dashboard = app(BuildDashboard::class)->handle($user);

    expect($dashboard['attention'])
        ->toMatchArray([
            'overdue_follow_ups' => 1,
            'awaiting_response_14_days' => 1,
        ])
        ->and($dashboard['summary']['active_applications'])->toBe(1);
});
