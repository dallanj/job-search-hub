<?php

use App\Enums\ApplicationStatus;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array<string, mixed> */
function pipelineState(TestResponse $response): array
{
    $payload = json_decode($response->inertiaProps('$pinia'), true, flags: JSON_THROW_ON_ERROR);

    return $payload['modules']['pipeline']['state'];
}

/** @return array<string, mixed> */
function pipelineOptionsState(TestResponse $response): array
{
    $payload = json_decode($response->inertiaProps('$pinia'), true, flags: JSON_THROW_ON_ERROR);

    return $payload['modules']['options']['state'];
}

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
        ->has('$pinia'));

    $payload = json_decode($response->inertiaProps('$pinia'), true, flags: JSON_THROW_ON_ERROR);
    $state = pipelineState($response);

    expect($payload['modules']['pipeline']['mode'])->toBe('replace')
        ->and($state['columns'])->toHaveCount(count(ApplicationStatus::cases()))
        ->and($state['columns'][0]['status'])->toBe(ApplicationStatus::Saved->value)
        ->and($state['columns'][1]['status'])->toBe(ApplicationStatus::Applied->value)
        ->and($state['columns'][1]['applications'])->toHaveCount(2)
        ->and($state['columns'][1]['applications'][0]['id'])->toBe($firstApplication->id)
        ->and($state['columns'][1]['applications'][1]['id'])->toBe($secondApplication->id);
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

    $state = pipelineState($response);

    expect($state['columns'][1]['applications'])->toHaveCount(1)
        ->and($state['columns'][1]['applications'][0]['id'])->toBe($matchingApplication->id)
        ->and($state)->not->toHaveKey('filters');
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

    $state = pipelineState($response);

    expect($state['columns'][1]['applications'])->toHaveCount(1)
        ->and($state['columns'][1]['applications'][0]['id'])->toBe($matchingApplication->id)
        ->and($state)->not->toHaveKey('filters');
});

test('the pipeline exposes only the users companies as filter options', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Owned Company']);
    Company::factory()->create(['name' => 'Private Company']);

    $response = $this->actingAs($user)->get(route('pipeline.index'));

    $state = pipelineOptionsState($response);

    expect($state['companies'])->toHaveCount(1)
        ->and($state['companies'][0]['name'])->toBe('Owned Company');
});

test('partial pipeline searches preserve existing options', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('pipeline.index'), [
        'X-Inertia-Partial-Data' => '$pinia',
    ]);
    $payload = json_decode($response->inertiaProps('$pinia'), true, flags: JSON_THROW_ON_ERROR);

    expect($payload['modules'])->toHaveKey('pipeline')
        ->and($payload['modules'])->not->toHaveKey('options');
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

    $state = pipelineState($response);

    expect($state['columns'][$columnIndex]['status'])->toBe($status->value)
        ->and($state['columns'][$columnIndex]['label'])->toBe($label)
        ->and($state['columns'][$columnIndex]['applications'])->toHaveCount(1)
        ->and($state['columns'][$columnIndex]['applications'][0]['id'])->toBe($application->id);
})->with([
    'hired' => [ApplicationStatus::Hired, 'Hired', 5],
    'no response' => [ApplicationStatus::NoResponse, 'No Response', 7],
    'offer declined' => [ApplicationStatus::OfferDeclined, 'Offer Declined', 8],
]);
