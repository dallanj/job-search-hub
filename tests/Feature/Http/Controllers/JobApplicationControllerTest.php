<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function validJobApplicationPayload(array $overrides = []): array
{
    return array_merge([
        'role_title' => 'Senior Laravel Developer',
        'status' => ApplicationStatus::Applied->value,
        'employment_type' => 'full-time',
        'workplace_type' => 'remote',
        'location' => 'Edmonton, AB',
        'source' => 'Company website',
        'job_url' => 'https://example.com/jobs/laravel-developer',
        'salary_min' => 100000,
        'salary_max' => 140000,
        'salary_currency' => 'cad',
        'applied_at' => '2026-08-20',
        'closed_at' => null,
        'description' => 'Build and maintain customer-facing products.',
    ], $overrides);
}

test('guests are redirected to login', function () {
    $response = $this->get(route('applications.index'));

    $response->assertRedirect(route('login'));
});

test('the index lists only the authenticated users applications', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Acme']);
    $application = JobApplication::factory()->for($user)->for($company)->create([
        'role_title' => 'Laravel Developer',
    ]);
    JobApplication::factory()->create(['role_title' => 'Private Role']);

    $response = $this->actingAs($user)->get(route('applications.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('applications/Index')
        ->has('applications.data', 1)
        ->where('applications.data.0.id', $application->id)
        ->where('applications.data.0.company.name', 'Acme'));
});

test('the index filters applications by search and status', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Northstar']);
    $matchingApplication = JobApplication::factory()->for($user)->for($company)->create([
        'role_title' => 'Platform Engineer',
        'status' => ApplicationStatus::Interview,
    ]);
    JobApplication::factory()->for($user)->for($company)->create([
        'role_title' => 'Product Designer',
        'status' => ApplicationStatus::Applied,
    ]);

    $response = $this->actingAs($user)->get(route('applications.index', [
        'search' => 'Northstar',
        'status' => ApplicationStatus::Interview->value,
    ]));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('applications.data', 1)
        ->where('applications.data.0.id', $matchingApplication->id)
        ->where('filters.search', 'Northstar')
        ->where('filters.status', 'interview'));
});

test('the create page includes only the authenticated users companies', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Owned Company']);
    Company::factory()->create(['name' => 'Private Company']);

    $response = $this->actingAs($user)->get(route('applications.create'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('applications/Create')
        ->has('companies', 1)
        ->where('companies.0.name', 'Owned Company'));
});

test('a user can create an application for an existing company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('applications.store'), validJobApplicationPayload([
        'company_id' => $company->id,
    ]));

    $application = JobApplication::query()->sole();
    $response->assertRedirect(route('applications.show', $application));
    $this->assertDatabaseHas('job_applications', [
        'id' => $application->id,
        'user_id' => $user->id,
        'company_id' => $company->id,
        'role_title' => 'Senior Laravel Developer',
        'salary_currency' => 'CAD',
    ]);
});

test('a user can create a company while creating an application', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('applications.store'), validJobApplicationPayload([
        'company_name' => '  New Company  ',
    ]));

    $company = Company::query()->sole();
    $application = JobApplication::query()->sole();
    $response->assertRedirect(route('applications.show', $application));
    expect($company->name)->toBe('New Company');
    expect($application->company->is($company))->toBeTrue();
});

test('application creation rejects invalid input', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('applications.store'), [
        'role_title' => '',
        'status' => 'unknown',
        'job_url' => 'javascript:alert(1)',
        'salary_min' => 150000,
        'salary_max' => 100000,
        'applied_at' => '2026-08-20',
        'closed_at' => '2026-08-19',
    ]);

    $response->assertSessionHasErrors([
        'company_id',
        'company_name',
        'role_title',
        'status',
        'job_url',
        'salary_max',
        'closed_at',
    ]);
    expect(JobApplication::query()->count())->toBe(0);
});

test('a user cannot assign another users company', function () {
    $user = User::factory()->create();
    $privateCompany = Company::factory()->create();

    $response = $this->actingAs($user)->post(route('applications.store'), validJobApplicationPayload([
        'company_id' => $privateCompany->id,
    ]));

    $response->assertSessionHasErrors('company_id');
    expect(JobApplication::query()->count())->toBe(0);
});

test('a user can view and update their application', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $newCompany = Company::factory()->for($user)->create();
    $application = JobApplication::factory()->for($user)->for($company)->create();

    $this->actingAs($user)
        ->get(route('applications.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->component('applications/Show')
            ->where('application.id', $application->id));

    $response = $this->actingAs($user)->patch(
        route('applications.update', $application),
        validJobApplicationPayload([
            'company_id' => $newCompany->id,
            'role_title' => 'Staff Laravel Developer',
            'status' => ApplicationStatus::Interview->value,
        ]),
    );

    $response->assertRedirect(route('applications.show', $application));
    $this->assertDatabaseHas('job_applications', [
        'id' => $application->id,
        'company_id' => $newCompany->id,
        'role_title' => 'Staff Laravel Developer',
        'status' => ApplicationStatus::Interview->value,
    ]);
});

test('a user can delete their application', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('applications.destroy', $application));

    $response->assertRedirect(route('applications.index'));
    $this->assertModelMissing($application);
});

test('another users application returns 404 for every record endpoint', function (string $method, string $routeName) {
    $user = User::factory()->create();
    $privateApplication = JobApplication::factory()->create();

    $response = $this->actingAs($user)->{$method}(route($routeName, $privateApplication), validJobApplicationPayload());

    $response->assertNotFound();
})->with([
    'show' => ['get', 'applications.show'],
    'edit' => ['get', 'applications.edit'],
    'update' => ['patch', 'applications.update'],
    'delete' => ['delete', 'applications.destroy'],
]);
