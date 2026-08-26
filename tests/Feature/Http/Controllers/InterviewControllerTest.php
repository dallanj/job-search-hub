<?php

use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function validInterviewPayload(JobApplication $application, array $overrides = []): array
{
    return array_merge(['job_application_id' => $application->id, 'contact_id' => null, 'type' => InterviewType::Video->value, 'scheduled_at' => '2026-09-01 14:00:00', 'duration_minutes' => 60, 'location_or_url' => 'https://meet.example.com/interview', 'outcome' => InterviewOutcome::Pending->value, 'notes' => 'Prepare system design examples.'], $overrides);
}

test('guests are redirected to login', function () {
    $this->get(route('interviews.index'))->assertRedirect(route('login'));
});

test('the index lists only interviews belonging to the user', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $interview = Interview::factory()->for($application, 'jobApplication')->create();
    Interview::factory()->create();
    $this->actingAs($user)->get(route('interviews.index'))->assertInertia(fn (Assert $page) => $page->component('interviews/Index')->has('interviews.data', 1)->where('interviews.data.0.id', $interview->id));
});

test('a user can schedule and update an interview', function () {
    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $application = JobApplication::factory()->for($user)->for($company)->create();
    $contact = Contact::factory()->for($user)->for($company)->create();
    $response = $this->actingAs($user)->post(route('interviews.store'), validInterviewPayload($application, ['contact_id' => $contact->id]));
    $interview = Interview::query()->sole();
    $response->assertRedirect(route('interviews.show', $interview));
    $this->assertDatabaseHas('interviews', ['id' => $interview->id, 'contact_id' => $contact->id, 'type' => 'video']);
    $this->actingAs($user)->patch(route('interviews.update', $interview), validInterviewPayload($application, ['type' => 'technical', 'outcome' => 'passed']))->assertRedirect(route('interviews.show', $interview));
    $this->assertDatabaseHas('interviews', ['id' => $interview->id, 'type' => 'technical', 'outcome' => 'passed']);
});

test('an interviewer must belong to the application company', function () {
    $user = User::factory()->create();
    $application = JobApplication::factory()->for($user)->create();
    $contact = Contact::factory()->for($user)->create();
    $this->actingAs($user)->post(route('interviews.store'), validInterviewPayload($application, ['contact_id' => $contact->id]))->assertSessionHasErrors('contact_id');
    expect(Interview::query()->count())->toBe(0);
});

test('interview creation rejects another users application and contact', function () {
    $user = User::factory()->create();
    $privateApplication = JobApplication::factory()->create();
    $privateContact = Contact::factory()->create();
    $this->actingAs($user)->post(route('interviews.store'), validInterviewPayload($privateApplication, ['contact_id' => $privateContact->id]))->assertSessionHasErrors(['job_application_id', 'contact_id']);
});

test('another users interview returns 404 for record endpoints', function (string $method, string $routeName) {
    $user = User::factory()->create();
    $interview = Interview::factory()->create();
    $this->actingAs($user)->{$method}(route($routeName, $interview))->assertNotFound();
})->with(['show' => ['get', 'interviews.show'], 'edit' => ['get', 'interviews.edit'], 'delete' => ['delete', 'interviews.destroy']]);
