<?php

use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users may list and create interviews', function (string $ability) {
    $user = User::factory()->create();
    expect(Gate::forUser($user)->allows($ability, Interview::class))->toBeTrue();
})->with(['viewAny', 'create']);
test('owners may manage interviews', function (string $ability) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $interview = Interview::factory()->for($application, 'jobApplication')->create();
    expect(Gate::forUser($user)->allows($ability, $interview))->toBeTrue();
})->with(['view', 'update', 'delete']);
test('non owners may not manage interviews', function (string $ability) {
    $user = User::factory()->create();
    $interview = Interview::factory()->create();
    expect(Gate::forUser($user)->allows($ability, $interview))->toBeFalse();
})->with(['view', 'update', 'delete']);
