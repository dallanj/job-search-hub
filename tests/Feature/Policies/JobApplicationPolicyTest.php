<?php

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users may list and create applications', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows($ability, JobApplication::class))->toBeTrue();
})->with(['viewAny', 'create']);

test('owners may act on their applications', function (string $ability) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();

    expect(Gate::forUser($user)->allows($ability, $application))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('non-owners may not act on applications', function (string $ability) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->create();

    expect(Gate::forUser($user)->allows($ability, $application))->toBeFalse();
})->with(['view', 'update', 'delete']);
