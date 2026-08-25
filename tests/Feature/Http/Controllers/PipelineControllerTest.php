<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login', function () {
    $response = $this->get(route('pipeline.index'));

    $response->assertRedirect(route('login'));
});

test('the pipeline renders every stage with only the users ordered applications', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $secondApplication = JobApplication::factory()->for($user)->for($company)->create([
        'role_title' => 'Second application',
        'status' => ApplicationStatus::Applied,
        'sort_order' => 1,
    ]);
    $firstApplication = JobApplication::factory()->for($user)->for($company)->create([
        'role_title' => 'First application',
        'status' => ApplicationStatus::Applied,
        'sort_order' => 0,
    ]);
    JobApplication::factory()->create([
        'status' => ApplicationStatus::Applied,
        'sort_order' => 0,
    ]);

    $response = $this->actingAs($user)->get(route('pipeline.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('pipeline/Index')
        ->has('columns', count(ApplicationStatus::cases()))
        ->where('columns.0.status', ApplicationStatus::Saved->value)
        ->where('columns.1.status', ApplicationStatus::Applied->value)
        ->has('columns.1.applications', 2)
        ->where('columns.1.applications.0.id', $firstApplication->id)
        ->where('columns.1.applications.1.id', $secondApplication->id));
});
