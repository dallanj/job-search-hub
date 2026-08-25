<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;

test('the factory creates a company owned by the application user', function () {
    $application = JobApplication::factory()->create();

    expect($application->company->user->is($application->user))->toBeTrue();
});

test('status and dates are cast to domain types', function () {
    $application = JobApplication::factory()->create([
        'status' => ApplicationStatus::Interview,
        'applied_at' => '2026-08-01',
        'closed_at' => '2026-08-20',
    ]);

    expect($application->status)->toBe(ApplicationStatus::Interview);
    expect($application->applied_at?->toDateString())->toBe('2026-08-01');
    expect($application->closed_at?->toDateString())->toBe('2026-08-20');
});

test('new applications default to the saved pipeline stage', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $application = JobApplication::create([
        'user_id' => $user->id,
        'company_id' => $company->id,
        'role_title' => 'Software Developer',
    ]);

    expect($application->status)->toBe(ApplicationStatus::Saved);
    expect($application->sort_order)->toBe(0);
});

test('the status factory state stores each pipeline stage', function (ApplicationStatus $status) {
    $application = JobApplication::factory()->withStatus($status)->create();

    expect($application->status)->toBe($status);
})->with(ApplicationStatus::cases());
