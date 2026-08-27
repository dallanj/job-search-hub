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

test('the pipeline searches configured model fields and relationships', function (
    string $term,
    string $roleTitle,
    string $companyName,
) {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => $companyName]);
    $matchingApplication = JobApplication::factory()->for($user)->for($company)->create([
        'role_title' => $roleTitle,
        'status' => ApplicationStatus::Applied,
    ]);
    JobApplication::factory()->for($user)->create([
        'role_title' => 'Unrelated opportunity',
        'status' => ApplicationStatus::Applied,
    ]);

    $response = $this->actingAs($user)->get(route('pipeline.index', ['search' => "  {$term}  "]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('columns.1.applications', 1)
        ->where('columns.1.applications.0.id', $matchingApplication->id)
        ->where('filters.search', $term));
})->with([
    'role title' => ['Platform Engineer', 'Senior Platform Engineer', 'Acme'],
    'company relationship' => ['Northstar', 'Developer', 'Northstar Labs'],
]);

test('the pipeline combines company location and application date filters', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $matchingApplication = JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'location' => 'Edmonton, AB',
        'applied_at' => '2026-08-15',
    ]);
    JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'location' => 'Calgary, AB',
        'applied_at' => '2026-08-15',
    ]);
    JobApplication::factory()->for($user)->for($company)->create([
        'status' => ApplicationStatus::Applied,
        'location' => 'Edmonton, AB',
        'applied_at' => '2026-07-01',
    ]);
    JobApplication::factory()->create([
        'status' => ApplicationStatus::Applied,
        'location' => 'Edmonton, AB',
        'applied_at' => '2026-08-15',
    ]);

    $response = $this->actingAs($user)->get(route('pipeline.index', [
        'company_id' => $company->id,
        'location' => '  Edmonton  ',
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-31',
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('columns.1.applications', 1)
        ->where('columns.1.applications.0.id', $matchingApplication->id)
        ->where('filters.company_id', $company->id)
        ->where('filters.location', 'Edmonton')
        ->where('filters.date_from', '2026-08-01')
        ->where('filters.date_to', '2026-08-31'));
});

test('the pipeline exposes only the users companies as filter options', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Owned Company']);
    Company::factory()->create(['name' => 'Private Company']);

    $response = $this->actingAs($user)->get(route('pipeline.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('companies', 1)
        ->where('companies.0.name', 'Owned Company'));
});

test('the pipeline rejects invalid filters', function (array $filters, string $errorKey) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('pipeline.index'))
        ->get(route('pipeline.index', $filters));

    $response->assertRedirect(route('pipeline.index'));
    $response->assertSessionHasErrors($errorKey);
})->with([
    'unknown company' => [['company_id' => 999999], 'company_id'],
    'malformed start date' => [['date_from' => 'next Tuesday'], 'date_from'],
    'start after end' => [[
        'date_from' => '2026-08-20',
        'date_to' => '2026-08-01',
    ], 'date_from'],
    'search too long' => [['search' => str_repeat('a', 101)], 'search'],
]);

test('the pipeline rejects another users company filter', function () {
    $user = User::factory()->create();
    $privateCompany = Company::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('pipeline.index'))
        ->get(route('pipeline.index', ['company_id' => $privateCompany->id]));

    $response->assertRedirect(route('pipeline.index'));
    $response->assertSessionHasErrors('company_id');
});

test('the pipeline gives every tracker status its own labelled column', function (ApplicationStatus $status, string $label, int $columnIndex) {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create(['status' => $status]);

    $response = $this->actingAs($user)->get(route('pipeline.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('pipeline/Index')
        ->where("columns.{$columnIndex}.status", $status->value)
        ->where("columns.{$columnIndex}.label", $label)
        ->has("columns.{$columnIndex}.applications", 1)
        ->where("columns.{$columnIndex}.applications.0.id", $application->id));
})->with([
    'hired' => [ApplicationStatus::Hired, 'Hired', 5],
    'no response' => [ApplicationStatus::NoResponse, 'No Response', 7],
    'offer declined' => [ApplicationStatus::OfferDeclined, 'Offer Declined', 8],
]);
