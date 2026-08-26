<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users receive dashboard analytics', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(
        fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('summary')
            ->has('attention')
            ->has('upcoming_actions')
            ->has('funnel', 4)
            ->has('activity', 8)
            ->has('response_time')
            ->has('sources'),
    );
});
