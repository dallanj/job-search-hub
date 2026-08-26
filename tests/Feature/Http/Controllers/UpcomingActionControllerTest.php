<?php

use App\Models\JobApplication;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login', function () {
    $this->get(route('upcoming-actions.index'))->assertRedirect(route('login'));
});

test('the upcoming actions page uses the selected window', function () {
    Date::setTestNow('2026-08-26 09:00:00');
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $included = Task::factory()->for($application, 'jobApplication')->create([
        'due_at' => '2026-09-15',
    ]);

    $this->actingAs($user)
        ->get(route('upcoming-actions.index', ['days' => 30]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('upcoming-actions/Index')
            ->has('actions', 1)
            ->where('actions.0.id', $included->id)
            ->where('filters.days', 30));
});

test('the upcoming action window is validated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('upcoming-actions.index', ['days' => 365]))
        ->assertSessionHasErrors('days');
});
