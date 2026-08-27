<?php

use App\Enums\ApplicationStatus;
use App\Models\ApplicationNote;
use App\Models\Company;
use App\Models\Contact;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

const TRACKER_HEADER = 'date,company,sector,role,role_type,channel,status,contact_person,fit_rating,notes,cv_file,cover_letter_file,source,deadline';

function trackerCsvDirectory(): string
{
    return storage_path('framework/testing/tracker-'.getmypid());
}

/**
 * Writes a tracker CSV to a temporary file and returns its path.
 *
 * @param  list<string>  $rows
 */
function trackerCsv(array $rows): string
{
    $path = trackerCsvDirectory().'/'.Str::random(12).'.csv';
    File::put($path, implode("\n", [TRACKER_HEADER, ...$rows])."\n");

    return $path;
}

beforeEach(function () {
    File::ensureDirectoryExists(trackerCsvDirectory());
});

afterEach(function () {
    File::deleteDirectory(trackerCsvDirectory());
});

test('it imports a tracker row into a company, application, contact, and notes', function () {
    $user = User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,Fintech,Senior Engineer,full-time,referral,applied,Dana Reed,82,"2026-08-10: applied via referral",cv/acme.tex,letters/acme.tex,https://acme.test/jobs/1,2026-09-01',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $application = JobApplication::query()->sole();
    expect($application->company->name)->toBe('Acme Corp')
        ->and($application->company->industry)->toBe('Fintech')
        ->and($application->role_title)->toBe('Senior Engineer')
        ->and($application->status)->toBe(ApplicationStatus::Applied)
        ->and($application->employment_type)->toBe('full-time')
        ->and($application->source)->toBe('referral')
        ->and($application->job_url)->toBe('https://acme.test/jobs/1')
        ->and($application->applied_at->toDateString())->toBe('2026-08-10')
        ->and($application->deadline->toDateString())->toBe('2026-09-01')
        ->and($application->cv_file_path)->toBe('cv/acme.tex')
        ->and($application->cover_letter_file_path)->toBe('letters/acme.tex');

    $this->assertDatabaseHas('contacts', [
        'user_id' => $user->id,
        'company_id' => $application->company_id,
        'name' => 'Dana Reed',
    ]);
    $this->assertDatabaseHas('application_notes', [
        'job_application_id' => $application->id,
        'body' => 'Fit rating from ai-job-search: 82',
    ]);
    $this->assertDatabaseHas('application_status_events', [
        'job_application_id' => $application->id,
        'from_status' => null,
        'to_status' => ApplicationStatus::Applied->value,
    ]);
});

test('it maps the tracker channel to the source and the tracker source to the posting url', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,,,,https://acme.test/jobs/1,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $application = JobApplication::query()->sole();
    expect($application->source)->toBe('portal')
        ->and($application->job_url)->toBe('https://acme.test/jobs/1');
});

test('it leaves applied_at empty for a drafted row', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,drafted,,,,,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $application = JobApplication::query()->sole();
    expect($application->status)->toBe(ApplicationStatus::Saved)
        ->and($application->applied_at)->toBeNull();
});

test('it accepts the legacy space spellings of the final statuses', function (string $spelling, ApplicationStatus $expected) {
    User::factory()->create();
    $path = trackerCsv([
        "2026-08-10,Acme Corp,,Senior Engineer,,portal,{$spelling},,,,,,,",
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    expect(JobApplication::query()->sole()->status)->toBe($expected);
})->with([
    'legacy no response' => ['no response', ApplicationStatus::NoResponse],
    'legacy offer declined' => ['offer declined', ApplicationStatus::OfferDeclined],
    'canonical no_response' => ['no_response', ApplicationStatus::NoResponse],
    'canonical offer_declined' => ['offer_declined', ApplicationStatus::OfferDeclined],
    'hired' => ['hired', ApplicationStatus::Hired],
]);

test('it splits a notes cell into one back-dated note per dated entry', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,"2026-08-10: applied via referral;2026-08-20: recruiter call booked",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $notes = ApplicationNote::query()->orderBy('created_at')->get();
    expect($notes)->toHaveCount(2)
        ->and($notes[0]->body)->toBe('2026-08-10: applied via referral')
        ->and($notes[0]->created_at->toDateString())->toBe('2026-08-10')
        ->and($notes[1]->body)->toBe('2026-08-20: recruiter call booked')
        ->and($notes[1]->created_at->toDateString())->toBe('2026-08-20');
});

test('it dates an undated entry from its followed up marker', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,"followed up 2026-08-20",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $note = ApplicationNote::query()->sole();
    expect($note->body)->toBe('followed up 2026-08-20')
        ->and($note->created_at->toDateString())->toBe('2026-08-20');
});

test('it creates no duplicate records when the same tracker is imported twice', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,Fintech,Senior Engineer,full-time,referral,applied,Dana Reed,82,"2026-08-10: applied via referral",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();
    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    expect(Company::query()->count())->toBe(1)
        ->and(JobApplication::query()->count())->toBe(1)
        ->and(Contact::query()->count())->toBe(1)
        ->and(ApplicationNote::query()->count())->toBe(2)
        ->and(JobApplication::query()->sole()->statusEvents()->count())->toBe(1);
});

test('it records exactly one status event when a re-import advances the status', function () {
    User::factory()->create();
    $applied = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,,,,,']);
    $interview = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,interview,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $applied])->assertSuccessful();
    $this->artisan('import:ai-job-search-tracker', ['path' => $interview])->assertSuccessful();
    $this->artisan('import:ai-job-search-tracker', ['path' => $interview])->assertSuccessful();

    $application = JobApplication::query()->sole();
    expect($application->status)->toBe(ApplicationStatus::Interview)
        ->and($application->statusEvents()->count())->toBe(2);

    $this->assertDatabaseHas('application_status_events', [
        'job_application_id' => $application->id,
        'from_status' => ApplicationStatus::Applied->value,
        'to_status' => ApplicationStatus::Interview->value,
    ]);
});

test('it moves an application to the end of its new kanban column when the status changes', function () {
    $user = User::factory()->create();
    JobApplication::factory()->for($user)->create([
        'status' => ApplicationStatus::Interview,
        'sort_order' => 7,
    ]);
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,interview,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $imported = JobApplication::query()->where('role_title', 'Senior Engineer')->sole();
    expect($imported->sort_order)->toBe(8);
});

test('it skips a row without a company or a role and keeps importing the rest', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,,,Senior Engineer,,portal,applied,,,,,,,',
        '2026-08-10,Acme Corp,,,,portal,applied,,,,,,,',
        '2026-08-10,Beta Ltd,,Staff Engineer,,portal,applied,,,,,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])
        ->expectsOutputToContain('Line 2: missing company or role, skipped.')
        ->expectsOutputToContain('Line 3: missing company or role, skipped.')
        ->assertSuccessful();

    expect(JobApplication::query()->sole()->role_title)->toBe('Staff Engineer');
});

test('it skips a row whose status is not in the tracker vocabulary', function () {
    User::factory()->create();
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,ghosted,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])
        ->expectsOutputToContain('unrecognised status "ghosted"')
        ->assertSuccessful();

    expect(JobApplication::query()->count())->toBe(0)
        ->and(Company::query()->count())->toBe(0);
});

test('it writes nothing on a dry run', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,Fintech,Senior Engineer,full-time,referral,applied,Dana Reed,82,"2026-08-10: applied",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path, '--dry-run' => true])
        ->expectsOutputToContain('Line 2: Acme Corp / Senior Engineer -> applied')
        ->expectsOutputToContain('Nothing was written.')
        ->assertSuccessful();

    expect(JobApplication::query()->count())->toBe(0)
        ->and(Company::query()->count())->toBe(0)
        ->and(Contact::query()->count())->toBe(0)
        ->and(ApplicationNote::query()->count())->toBe(0);
});

test('it fails when the file cannot be read', function () {
    User::factory()->create();

    $this->artisan('import:ai-job-search-tracker', ['path' => '/does/not/exist.csv'])
        ->expectsOutputToContain('Cannot read file')
        ->assertFailed();
});

test('it fails without importing when the owning user is ambiguous', function () {
    User::factory()->count(2)->create();
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])
        ->expectsOutputToContain('pass --user=<id>')
        ->assertFailed();

    expect(JobApplication::query()->count())->toBe(0);
});

test('it imports for the user given by the user option', function () {
    User::factory()->create();
    $owner = User::factory()->create();
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path, '--user' => $owner->id])
        ->assertSuccessful();

    expect(JobApplication::query()->sole()->user_id)->toBe($owner->id);
});

test('it keeps an imported application editable when the tracker role type is not an employment type', function () {
    $user = User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,Fastansättning,portal,applied,,,,,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $application = JobApplication::query()->sole();
    expect($application->employment_type)->toBeNull();

    $this->assertDatabaseHas('application_notes', [
        'job_application_id' => $application->id,
        'body' => 'Role type from ai-job-search: Fastansättning',
    ]);

    $this->actingAs($user)
        ->put(route('applications.update', $application), [
            'company_id' => $application->company_id,
            'role_title' => $application->role_title,
            'status' => $application->status->value,
            'employment_type' => $application->employment_type,
        ])
        ->assertSessionHasNoErrors();
});

test('it drops a posting url that is not an http address', function () {
    User::factory()->create();
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,,,,pasted from an email,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    expect(JobApplication::query()->sole()->job_url)->toBeNull();
});

test('it backfills a company industry only while it is empty', function () {
    $user = User::factory()->create();
    Company::factory()->for($user)->create(['name' => 'Acme Corp', 'industry' => null]);
    $path = trackerCsv(['2026-08-10,Acme Corp,Fintech,Senior Engineer,,portal,applied,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();
    expect(Company::query()->sole()->industry)->toBe('Fintech');

    $changed = trackerCsv(['2026-08-10,Acme Corp,Healthcare,Senior Engineer,,portal,applied,,,,,,,']);
    $this->artisan('import:ai-job-search-tracker', ['path' => $changed])->assertSuccessful();

    expect(Company::query()->sole()->industry)->toBe('Fintech');
});

test('it matches an existing company, role, and contact regardless of letter case', function () {
    User::factory()->create();
    $first = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,Dana Reed,,,,,,',
    ]);
    $recased = trackerCsv([
        '2026-08-10,ACME CORP,,SENIOR ENGINEER,,portal,applied,DANA REED,,,,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $first])->assertSuccessful();
    $this->artisan('import:ai-job-search-tracker', ['path' => $recased])->assertSuccessful();

    expect(Company::query()->count())->toBe(1)
        ->and(JobApplication::query()->count())->toBe(1)
        ->and(Contact::query()->count())->toBe(1)
        ->and(Company::query()->sole()->name)->toBe('Acme Corp')
        ->and(JobApplication::query()->sole()->role_title)->toBe('Senior Engineer');
});

test('it reports no deprecations while reading a csv', function () {
    User::factory()->create();
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,"a note, with a comma",,,,']);

    $deprecations = [];
    set_error_handler(function (int $level, string $message) use (&$deprecations): bool {
        $deprecations[] = $message;

        return true;
    }, E_DEPRECATED);

    try {
        $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();
    } finally {
        restore_error_handler();
    }

    expect($deprecations)->toBe([])
        ->and(JobApplication::query()->sole()->notes()->sole()->body)->toBe('a note, with a comma');
});

test('it splits a followed up marker into its own note dated by the marker', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-07-02,Cobalt Labs,,Staff Engineer,,referral,applied,,,"2026-07-02: referred by Sam;followed up 2026-07-16",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $notes = ApplicationNote::query()->orderBy('created_at')->get();
    expect($notes)->toHaveCount(2)
        ->and($notes[0]->body)->toBe('2026-07-02: referred by Sam')
        ->and($notes[0]->created_at->toDateString())->toBe('2026-07-02')
        ->and($notes[1]->body)->toBe('followed up 2026-07-16')
        ->and($notes[1]->created_at->toDateString())->toBe('2026-07-16');
});

test('it keeps a date mentioned mid-sentence inside its own entry', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,interview,,,"2026-08-10: applied, interview booked for 2026-09-02",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $note = ApplicationNote::query()->sole();
    expect($note->body)->toBe('2026-08-10: applied, interview booked for 2026-09-02')
        ->and($note->created_at->toDateString())->toBe('2026-08-10');
});

test('it dates a status event from the newest dated note so the response time is not zero', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,interview,,,"2026-08-10: applied;2026-08-20: recruiter replied",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    $application = JobApplication::query()->sole();
    expect($application->statusEvents()->sole()->changed_at->toDateString())->toBe('2026-08-20')
        ->and($application->applied_at->toDateString())->toBe('2026-08-10');
});

test('it dates an applied status event from the date column, not from a later note', function () {
    User::factory()->create();
    $path = trackerCsv([
        '2026-08-10,Acme Corp,,Senior Engineer,,portal,applied,,,"2026-08-10: applied;followed up 2026-08-20",,,,',
    ]);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    expect(JobApplication::query()->sole()->statusEvents()->sole()->changed_at->toDateString())
        ->toBe('2026-08-10');
});

test('it falls back to the applied date when no note dates the status change', function () {
    User::factory()->create();
    $path = trackerCsv(['2026-08-10,Acme Corp,,Senior Engineer,,portal,rejected,,,,,,,']);

    $this->artisan('import:ai-job-search-tracker', ['path' => $path])->assertSuccessful();

    expect(JobApplication::query()->sole()->statusEvents()->sole()->changed_at->toDateString())
        ->toBe('2026-08-10');
});
