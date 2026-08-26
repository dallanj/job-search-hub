<?php

use App\Models\ApplicationNote;
use App\Models\JobApplication;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a user can add a note to an application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();

    $this->actingAs($user)->post(route('application-notes.store'), [
        'job_application_id' => $application->id,
        'body' => 'The hiring manager values platform experience.',
    ])->assertRedirect();

    $this->assertDatabaseHas('application_notes', [
        'job_application_id' => $application->id,
        'user_id' => $user->id,
        'body' => 'The hiring manager values platform experience.',
    ]);
});

test('a note cannot be added to another users application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->create();

    $this->actingAs($user)->post(route('application-notes.store'), [
        'job_application_id' => $application->id,
        'body' => 'Private note',
    ])->assertSessionHasErrors('job_application_id');

    expect(ApplicationNote::query()->count())->toBe(0);
});

test('an owner can edit update and delete a note', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $note = ApplicationNote::factory()
        ->for($user)
        ->for($application, 'jobApplication')
        ->create();

    $this->actingAs($user)->get(route('application-notes.edit', $note))
        ->assertInertia(fn (Assert $page) => $page
            ->component('application-notes/Edit')
            ->where('note.id', $note->id));

    $this->actingAs($user)->patch(route('application-notes.update', $note), [
        'body' => 'Updated context',
    ])->assertRedirect(route('applications.show', $application));
    $this->assertDatabaseHas('application_notes', [
        'id' => $note->id,
        'body' => 'Updated context',
    ]);

    $this->actingAs($user)->delete(route('application-notes.destroy', $note))
        ->assertRedirect();
    $this->assertDatabaseMissing('application_notes', ['id' => $note->id]);
});

test('another users note returns 404 for record endpoints', function (string $method, string $routeName) {
    $user = User::factory()->create();
    $note = ApplicationNote::factory()->create();

    $this->actingAs($user)->{$method}(route($routeName, $note))->assertNotFound();
})->with([
    'edit' => ['get', 'application-notes.edit'],
    'update' => ['patch', 'application-notes.update'],
    'delete' => ['delete', 'application-notes.destroy'],
]);

test('application details include notes newest first', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $older = ApplicationNote::factory()->for($user)->for($application, 'jobApplication')->create([
        'created_at' => now()->subDay(),
    ]);
    $newer = ApplicationNote::factory()->for($user)->for($application, 'jobApplication')->create();

    $this->actingAs($user)->get(route('applications.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->where('application.notes.0.id', $newer->id)
            ->where('application.notes.1.id', $older->id)
            ->where('application.notes.0.user.name', $user->name));
});
