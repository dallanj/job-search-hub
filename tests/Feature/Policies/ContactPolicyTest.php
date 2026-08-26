<?php

use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('users may list and create contacts', function (string $ability) {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows($ability, Contact::class))->toBeTrue();
})->with(['viewAny', 'create']);

test('owners may act on their contacts', function (string $ability) {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create();

    expect(Gate::forUser($user)->allows($ability, $contact))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('non-owners may not act on contacts', function (string $ability) {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    expect(Gate::forUser($user)->allows($ability, $contact))->toBeFalse();
})->with(['view', 'update', 'delete']);
