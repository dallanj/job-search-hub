<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array<string, mixed> */
function contactsState(TestResponse $response): array
{
    $payload = json_decode($response->inertiaProps('$pinia'), true, flags: JSON_THROW_ON_ERROR);

    return $payload['modules']['contacts']['state'];
}

function validContactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Jordan Lee',
        'job_title' => 'Engineering Manager',
        'email' => 'jordan@example.com',
        'phone' => '+1 780 555 0100',
        'linkedin_url' => 'https://www.linkedin.com/in/jordan-lee',
        'notes' => 'Met through a local Laravel meetup.',
    ], $overrides);
}

test('guests are redirected to login', function () {
    $response = $this->get(route('contacts.index'));

    $response->assertRedirect(route('login'));
});

test('the index lists only the authenticated users contacts', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Acme']);
    $contact = Contact::factory()->for($user)->for($company)->create(['name' => 'Jordan Lee']);
    Contact::factory()->create(['name' => 'Private Contact']);

    $response = $this->actingAs($user)->get(route('contacts.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('contacts/Index')
        ->has('$pinia'));

    $state = contactsState($response);

    expect($state['contacts']['data'])->toHaveCount(1)
        ->and($state['contacts']['data'][0]['id'])->toBe($contact->id)
        ->and($state['contacts']['data'][0]['company']['name'])->toBe('Acme');
});

test('the index searches contact fields and companies and filters by company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create(['name' => 'Northstar Labs']);
    $matchingContact = Contact::factory()->for($user)->for($company)->create([
        'name' => 'Jordan Lee',
        'job_title' => 'Platform Manager',
    ]);
    Contact::factory()->for($user)->create(['name' => 'Unrelated Person']);

    $response = $this->actingAs($user)->get(route('contacts.index', [
        'search' => '  Northstar  ',
        'company_id' => $company->id,
    ]));

    $state = contactsState($response);

    expect($state['contacts']['data'])->toHaveCount(1)
        ->and($state['contacts']['data'][0]['id'])->toBe($matchingContact->id)
        ->and($state)->not->toHaveKey('filters');
});

test('the create page includes only the authenticated users companies', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Owned Company']);
    Company::factory()->create(['name' => 'Private Company']);

    $response = $this->actingAs($user)->get(route('contacts.create'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('contacts/Create')
        ->has('companies', 1)
        ->where('companies.0.name', 'Owned Company'));
});

test('a user can create a contact', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('contacts.store'), validContactPayload([
        'company_id' => $company->id,
    ]));

    $contact = Contact::query()->sole();
    $response->assertRedirect(route('contacts.show', $contact));
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'user_id' => $user->id,
        'company_id' => $company->id,
        'name' => 'Jordan Lee',
        'email' => 'jordan@example.com',
    ]);
});

test('a user can create a company while creating a contact', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('contacts.store'), validContactPayload([
        'company_name' => '  New Company  ',
    ]));

    $company = Company::query()->sole();
    $contact = Contact::query()->sole();
    $response->assertRedirect(route('contacts.show', $contact));
    expect($company->name)->toBe('New Company');
    expect($contact->company->is($company))->toBeTrue();
});

test('contact creation rejects invalid input', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('contacts.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'linkedin_url' => 'javascript:alert(1)',
        'phone' => str_repeat('1', 51),
    ]);

    $response->assertSessionHasErrors(['company_id', 'name', 'email', 'linkedin_url', 'phone']);
    expect(Contact::query()->count())->toBe(0);
});

test('a user cannot assign another users company', function () {
    $user = User::factory()->create();
    $privateCompany = Company::factory()->create();

    $response = $this->actingAs($user)->post(route('contacts.store'), validContactPayload([
        'company_id' => $privateCompany->id,
    ]));

    $response->assertSessionHasErrors('company_id');
    expect(Contact::query()->count())->toBe(0);
});

test('a user can view and update their contact', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $newCompany = Company::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->for($company)->create();

    $this->actingAs($user)
        ->get(route('contacts.show', $contact))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contacts/Show')
            ->where('contact.id', $contact->id));

    $response = $this->actingAs($user)->patch(
        route('contacts.update', $contact),
        validContactPayload([
            'company_id' => $newCompany->id,
            'name' => 'Jordan Patel',
        ]),
    );

    $response->assertRedirect(route('contacts.show', $contact));
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'company_id' => $newCompany->id,
        'name' => 'Jordan Patel',
    ]);
});

test('a user can delete their contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->for($user)->create();

    $response = $this->actingAs($user)->delete(route('contacts.destroy', $contact));

    $response->assertRedirect(route('contacts.index'));
    $this->assertModelMissing($contact);
});

test('another users contact returns 404 for every record endpoint', function (
    string $method,
    string $routeName,
) {
    $user = User::factory()->create();
    $privateContact = Contact::factory()->create();

    $response = $this->actingAs($user)->{$method}(
        route($routeName, $privateContact),
        validContactPayload(),
    );

    $response->assertNotFound();
})->with([
    'show' => ['get', 'contacts.show'],
    'edit' => ['get', 'contacts.edit'],
    'update' => ['patch', 'contacts.update'],
    'delete' => ['delete', 'contacts.destroy'],
]);
