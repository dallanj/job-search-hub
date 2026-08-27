<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\ApplicationNote;
use App\Models\Company;
use App\Models\Contact;
use App\Models\JobApplication;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportAiJobSearchTrackerRow
{
    /**
     * Tracker status vocabulary from ai-job-search (.claude/commands/outcome.md).
     * The space-form spellings are legacy and accepted on read only.
     *
     * @var array<string, ApplicationStatus>
     */
    private const STATUS_MAP = [
        'drafted' => ApplicationStatus::Saved,
        'applied' => ApplicationStatus::Applied,
        'interview' => ApplicationStatus::Interview,
        'offer' => ApplicationStatus::Offer,
        'hired' => ApplicationStatus::Hired,
        'rejected' => ApplicationStatus::Rejected,
        'no_response' => ApplicationStatus::NoResponse,
        'no response' => ApplicationStatus::NoResponse,
        'offer_declined' => ApplicationStatus::OfferDeclined,
        'offer declined' => ApplicationStatus::OfferDeclined,
        'withdrawn' => ApplicationStatus::Withdrawn,
    ];

    /**
     * The tracker records role_type as free text lifted from a posting, while
     * employment_type is a closed set here. Anything unrecognised is dropped to
     * null and preserved as a note, so an imported application stays editable.
     *
     * @var array<string, string>
     */
    private const EMPLOYMENT_TYPE_MAP = [
        'full-time' => 'full-time',
        'full time' => 'full-time',
        'fulltime' => 'full-time',
        'part-time' => 'part-time',
        'part time' => 'part-time',
        'parttime' => 'part-time',
        'contract' => 'contract',
        'contractor' => 'contract',
        'freelance' => 'contract',
        'temporary' => 'temporary',
        'temp' => 'temporary',
        'internship' => 'internship',
        'intern' => 'internship',
    ];

    /**
     * @param  array<string, string>  $row
     * @return array{outcome: 'created'|'updated'|'previewed'|'skipped', message: string}
     */
    public function handle(User $user, array $row, bool $dryRun = false): array
    {
        $companyName = $this->blankToNull($row['company'] ?? null);
        $roleTitle = $this->blankToNull($row['role'] ?? null);

        if ($companyName === null || $roleTitle === null) {
            return ['outcome' => 'skipped', 'message' => 'missing company or role'];
        }

        $rawStatus = trim($row['status'] ?? '');
        $status = self::STATUS_MAP[mb_strtolower($rawStatus)] ?? null;

        if ($status === null) {
            return [
                'outcome' => 'skipped',
                'message' => "unrecognised status \"{$rawStatus}\" for {$companyName} / {$roleTitle}",
            ];
        }

        $summary = "{$companyName} / {$roleTitle} -> {$status->value}";

        if ($dryRun) {
            return ['outcome' => 'previewed', 'message' => $summary];
        }

        return DB::transaction(function () use ($user, $row, $companyName, $roleTitle, $status, $summary): array {
            $company = $this->resolveCompany($user, $companyName, $this->blankToNull($row['sector'] ?? null));
            $this->resolveContact($user, $company, $this->blankToNull($row['contact_person'] ?? null));

            $application = $this->resolveApplication($user, $company, $roleTitle);

            $existed = $application->exists;
            $previousStatus = $existed ? $application->status : null;
            $appliedAt = $status === ApplicationStatus::Saved
                ? null
                : $this->parseDate($row['date'] ?? null);
            $noteEntries = $this->splitNotes($row['notes'] ?? '');

            $application->fill([
                'status' => $status,
                'employment_type' => $this->mapEmploymentType($row['role_type'] ?? null) ?? $application->employment_type,
                'source' => $this->blankToNull($row['channel'] ?? null) ?? $application->source,
                'job_url' => $this->mapJobUrl($row['source'] ?? null) ?? $application->job_url,
                'applied_at' => $appliedAt ?? $application->applied_at,
                'deadline' => $this->parseDate($row['deadline'] ?? null) ?? $application->deadline,
                'cv_file_path' => $this->blankToNull($row['cv_file'] ?? null) ?? $application->cv_file_path,
                'cover_letter_file_path' => $this->blankToNull($row['cover_letter_file'] ?? null) ?? $application->cover_letter_file_path,
            ]);

            if (! $existed || $previousStatus !== $status) {
                $application->sort_order = $this->nextSortOrder($user, $status);
            }

            $application->save();

            if ($previousStatus !== $status) {
                $application->statusEvents()->create([
                    'from_status' => $previousStatus,
                    'to_status' => $status,
                    'changed_at' => $this->statusChangedAt($status, $appliedAt, $noteEntries),
                    'note' => 'Imported from ai-job-search tracker',
                ]);
            }

            $this->recordNotes($application, $user, $row, $noteEntries);

            return [
                'outcome' => $existed ? 'updated' : 'created',
                'message' => $summary,
            ];
        }, attempts: 3);
    }

    /**
     * The tracker matches rows on company and role case-insensitively, so a row
     * whose casing drifted must update the existing records rather than create a
     * second set of them.
     */
    private function resolveCompany(User $user, string $name, ?string $sector): Company
    {
        $company = Company::query()
            ->where('user_id', $user->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->first()
            ?? Company::create(['user_id' => $user->id, 'name' => $name]);

        if ($company->industry === null && $sector !== null) {
            $company->update(['industry' => $sector]);
        }

        return $company;
    }

    private function resolveApplication(User $user, Company $company, string $roleTitle): JobApplication
    {
        return JobApplication::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->whereRaw('lower(role_title) = ?', [mb_strtolower($roleTitle)])
            ->first()
            ?? new JobApplication([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'role_title' => $roleTitle,
            ]);
    }

    private function resolveContact(User $user, Company $company, ?string $name): void
    {
        if ($name === null) {
            return;
        }

        $exists = Contact::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();

        if (! $exists) {
            Contact::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'name' => $name,
            ]);
        }
    }

    /**
     * Imported applications land at the end of their Kanban column, matching
     * how JobApplicationController assigns sort_order on create.
     */
    private function nextSortOrder(User $user, ApplicationStatus $status): int
    {
        $lastPosition = $user->jobApplications()
            ->where('status', $status->value)
            ->max('sort_order');

        return $lastPosition === null ? 0 : ((int) $lastPosition) + 1;
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<array{body: string, date: ?Carbon}>  $noteEntries
     */
    private function recordNotes(JobApplication $application, User $user, array $row, array $noteEntries): void
    {
        $roleType = $this->blankToNull($row['role_type'] ?? null);

        if ($roleType !== null && $this->mapEmploymentType($roleType) === null) {
            $this->createNoteIfNew($application, $user, "Role type from ai-job-search: {$roleType}");
        }

        if ($fitRating = $this->blankToNull($row['fit_rating'] ?? null)) {
            $this->createNoteIfNew($application, $user, "Fit rating from ai-job-search: {$fitRating}");
        }

        foreach ($noteEntries as $note) {
            $this->createNoteIfNew($application, $user, $note['body'], $note['date']);
        }
    }

    /**
     * The tracker records no date for a status change, but outcome.md appends a
     * dated note whenever it advances a row, so the newest dated note is when the
     * change happened. Without it every imported application would look like it
     * drew a response on the day it was sent.
     *
     * @param  list<array{body: string, date: ?Carbon}>  $noteEntries
     */
    private function statusChangedAt(ApplicationStatus $status, ?Carbon $appliedAt, array $noteEntries): CarbonInterface
    {
        $fallback = $appliedAt ?? now();

        if (in_array($status, [ApplicationStatus::Saved, ApplicationStatus::Applied], true)) {
            return $fallback;
        }

        $latestNote = collect($noteEntries)
            ->pluck('date')
            ->filter(fn (?Carbon $date): bool => $date instanceof Carbon)
            ->max();

        return $latestNote instanceof Carbon && $latestNote->greaterThan($fallback)
            ? $latestNote
            : $fallback;
    }

    /**
     * The tracker's notes cell is an append-only log. An entry starts at a dated
     * header (`2026-08-10: applied via referral`) or at a `followed up
     * YYYY-MM-DD` marker, which outcome.md logs as an event in its own right.
     *
     * @return list<array{body: string, date: ?Carbon}>
     */
    private function splitNotes(string $notes): array
    {
        $notes = trim($notes);

        if ($notes === '') {
            return [];
        }

        $chunks = preg_split(
            '/(?=(?:^|(?<=[\s;|]))(?:\d{4}-\d{2}-\d{2}\s*[:\-]|followed up\s+\d{4}-\d{2}-\d{2}))/ui',
            $notes,
            -1,
            PREG_SPLIT_NO_EMPTY,
        );

        if ($chunks === false) {
            $chunks = [$notes];
        }

        $entries = [];

        foreach ($chunks as $chunk) {
            $body = trim($chunk, " \t\n\r\0\x0B;|");

            if ($body === '') {
                continue;
            }

            $entries[] = ['body' => $body, 'date' => $this->dateForNote($body)];
        }

        return $entries;
    }

    private function dateForNote(string $body): ?Carbon
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $body, $matches) === 1) {
            return $this->parseDate($matches[1]);
        }

        if (preg_match('/followed up\s+(\d{4}-\d{2}-\d{2})/i', $body, $matches) === 1) {
            return $this->parseDate($matches[1]);
        }

        return null;
    }

    private function createNoteIfNew(JobApplication $application, User $user, string $body, ?Carbon $date = null): void
    {
        if ($application->notes()->where('body', $body)->exists()) {
            return;
        }

        $note = new ApplicationNote([
            'job_application_id' => $application->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);

        if ($date instanceof Carbon) {
            $note->created_at = $date;
        }

        $note->save();
    }

    private function mapEmploymentType(?string $value): ?string
    {
        $value = $this->blankToNull($value);

        if ($value === null) {
            return null;
        }

        return self::EMPLOYMENT_TYPE_MAP[mb_strtolower($value)] ?? null;
    }

    /**
     * The tracker's `source` column holds the posting URL. Values that are not
     * http(s) URLs are dropped so imported rows pass the app's own validation.
     */
    private function mapJobUrl(?string $value): ?string
    {
        $value = $this->blankToNull($value);

        if ($value === null || mb_strlen($value) > 2048) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)
                ? $value
                : null;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(?string $value): ?Carbon
    {
        $value = $this->blankToNull($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
