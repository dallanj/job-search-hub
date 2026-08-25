<?php

use App\Enums\ApplicationStatus;
use App\Models\ApplicationStatusEvent;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;

test('guests are redirected to login', function () {
    $application = JobApplication::factory()->create();

    $response = $this->patch(route('pipeline.move', $application), [
        'status' => ApplicationStatus::Interview->value,
        'position' => 0,
    ]);

    $response->assertRedirect(route('login'));
});

test('an application can be reordered within its current stage', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $first = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 0,
    ]);
    $second = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 1,
    ]);
    $third = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 2,
    ]);

    $response = $this->actingAs($user)
        ->from(route('pipeline.index'))
        ->patch(route('pipeline.move', $first), [
            'status' => ApplicationStatus::Applied->value,
            'position' => 2,
        ]);

    $response->assertRedirect(route('pipeline.index'));
    expect(JobApplication::query()
        ->whereBelongsTo($user)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all())->toBe([$second->id, $third->id, $first->id]);
    expect(JobApplication::query()
        ->whereBelongsTo($user)
        ->orderBy('sort_order')
        ->pluck('sort_order')
        ->all())->toBe([0, 1, 2]);
    expect(ApplicationStatusEvent::query()->count())->toBe(0);
});

test('an application can move between stages and both stages are reindexed', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $sourceFirst = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 0,
    ]);
    $movedApplication = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 1,
    ]);
    $sourceLast = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 2,
    ]);
    $targetApplication = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Interview,
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($user)
        ->from(route('pipeline.index'))
        ->patch(route('pipeline.move', $movedApplication), [
            'status' => ApplicationStatus::Interview->value,
            'position' => 0,
        ]);

    $response->assertRedirect(route('pipeline.index'));
    expect(JobApplication::query()
        ->whereBelongsTo($user)
        ->where('status', ApplicationStatus::Applied)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all())->toBe([$sourceFirst->id, $sourceLast->id]);
    expect(JobApplication::query()
        ->whereBelongsTo($user)
        ->where('status', ApplicationStatus::Applied)
        ->orderBy('sort_order')
        ->pluck('sort_order')
        ->all())->toBe([0, 1]);
    expect(JobApplication::query()
        ->whereBelongsTo($user)
        ->where('status', ApplicationStatus::Interview)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all())->toBe([$movedApplication->id, $targetApplication->id]);
    $this->assertDatabaseHas('application_status_events', [
        'job_application_id' => $movedApplication->id,
        'from_status' => ApplicationStatus::Applied->value,
        'to_status' => ApplicationStatus::Interview->value,
    ]);
});

test('positions beyond the end append the application', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $targetApplication = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Interview,
        'sort_order' => 0,
    ]);
    $movedApplication = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($user)->patch(route('pipeline.move', $movedApplication), [
        'status' => ApplicationStatus::Interview->value,
        'position' => 999,
    ]);

    $response->assertRedirect();
    expect(JobApplication::query()
        ->whereBelongsTo($user)
        ->where('status', ApplicationStatus::Interview)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all())->toBe([$targetApplication->id, $movedApplication->id]);
});

test('moving an application rejects invalid pipeline data', function (array $payload, string $errorKey) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($user)->patch(route('pipeline.move', $application), $payload);

    $response->assertSessionHasErrors($errorKey);
    $application->refresh();
    expect($application->status)->toBe(ApplicationStatus::Applied);
    expect($application->sort_order)->toBe(0);
})->with([
    'unknown status' => [['status' => 'unknown', 'position' => 0], 'status'],
    'negative position' => [['status' => 'interview', 'position' => -1], 'position'],
]);

test('another users application returns 404', function () {
    $user = User::factory()->create();
    $privateApplication = JobApplication::factory()->create();

    $response = $this->actingAs($user)->patch(route('pipeline.move', $privateApplication), [
        'status' => ApplicationStatus::Interview->value,
        'position' => 0,
    ]);

    $response->assertNotFound();
});
