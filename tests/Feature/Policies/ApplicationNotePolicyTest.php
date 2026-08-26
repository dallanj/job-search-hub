<?php

use App\Models\ApplicationNote;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users may create application notes', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('create', ApplicationNote::class))->toBeTrue();
});

test('authors may manage their application notes', function (string $ability) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $note = ApplicationNote::factory()
        ->for($user)
        ->for($application, 'jobApplication')
        ->create();

    expect(Gate::forUser($user)->allows($ability, $note))->toBeTrue();
})->with(['update', 'delete']);

test('other users may not manage application notes', function (string $ability) {
    $user = User::factory()->create();
    $note = ApplicationNote::factory()->create();

    expect(Gate::forUser($user)->allows($ability, $note))->toBeFalse();
})->with(['update', 'delete']);
