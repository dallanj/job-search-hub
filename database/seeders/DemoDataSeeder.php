<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewOutcome;
use App\Enums\InterviewType;
use App\Enums\TaskPriority;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public const EMAIL = 'demo@jobsearchhub.test';

    public function run(): void
    {
        DB::transaction(function (): void {
            User::query()->where('email', self::EMAIL)->first()?->delete();

            $user = User::factory()->create([
                'name' => 'Demo Job Seeker',
                'email' => self::EMAIL,
                'password' => 'password',
            ]);

            $companies = collect([
                ['name' => 'Northstar Labs', 'industry' => 'Technology', 'location' => 'Calgary, AB'],
                ['name' => 'Prairie Health', 'industry' => 'Healthcare', 'location' => 'Edmonton, AB'],
                ['name' => 'Summit Financial', 'industry' => 'Finance', 'location' => 'Toronto, ON'],
                ['name' => 'Civic Digital', 'industry' => 'Government', 'location' => 'Ottawa, ON'],
                ['name' => 'Brightpath Learning', 'industry' => 'Education', 'location' => 'Vancouver, BC'],
                ['name' => 'Juniper Consulting', 'industry' => 'Professional services', 'location' => 'Remote'],
            ])->mapWithKeys(function (array $data) use ($user): array {
                $company = Company::factory()->for($user)->create([
                    ...$data,
                    'website' => 'https://'.str($data['name'])->slug().'.example.com',
                ]);

                $company->contacts()->createMany([
                    [
                        'user_id' => $user->id,
                        'name' => fake()->name(),
                        'job_title' => 'Hiring Manager',
                        'email' => fake()->unique()->safeEmail(),
                    ],
                    [
                        'user_id' => $user->id,
                        'name' => fake()->name(),
                        'job_title' => 'Talent Partner',
                        'email' => fake()->unique()->safeEmail(),
                    ],
                ]);

                return [$data['name'] => $company];
            });

            $definitions = [
                ['Northstar Labs', 'Senior Laravel Developer', 'interview', 'LinkedIn', 32, [0, 4, 13]],
                ['Prairie Health', 'Full Stack Engineer', 'screening', 'Referral', 24, [0, 3]],
                ['Summit Financial', 'Platform Developer', 'applied', 'Company website', 18, [0]],
                ['Civic Digital', 'Software Developer', 'offer', 'Government board', 48, [0, 7, 20, 41]],
                ['Brightpath Learning', 'Frontend Engineer', 'rejected', 'Indeed', 39, [0, 12]],
                ['Juniper Consulting', 'Application Developer', 'interview', 'Recruiter', 15, [0, 2, 8]],
                ['Northstar Labs', 'Backend Engineer', 'rejected', 'Company website', 65, [0, 17]],
                ['Prairie Health', 'Systems Analyst', 'applied', 'Indeed', 9, [0]],
                ['Summit Financial', 'Senior PHP Developer', 'screening', 'LinkedIn', 7, [0, 1]],
                ['Civic Digital', 'Web Application Specialist', 'saved', 'Company website', null, []],
                ['Brightpath Learning', 'Vue Developer', 'withdrawn', 'Referral', 73, [0, 6]],
                ['Juniper Consulting', 'Technical Consultant', 'saved', 'Networking', null, []],
            ];

            foreach ($definitions as $index => [$companyName, $role, $status, $source, $daysAgo, $transitions]) {
                $company = $companies->get($companyName);
                $appliedAt = $daysAgo === null ? null : today()->subDays($daysAgo);
                $application = JobApplication::factory()->for($user)->for($company)->create([
                    'role_title' => $role,
                    'status' => ApplicationStatus::from($status),
                    'source' => $source,
                    'applied_at' => $appliedAt,
                    'sort_order' => $index,
                    'location' => $company->location,
                ]);

                $this->addHistory($application, $transitions, $appliedAt, ApplicationStatus::from($status));
                $application->notes()->createMany([
                    ['user_id' => $user->id, 'body' => fake()->paragraph()],
                    ['user_id' => $user->id, 'body' => fake()->sentence(14)],
                ]);

                if (in_array($status, ['screening', 'interview', 'offer'], true)) {
                    $contact = $company->contacts->first();
                    $isScreening = $status === 'screening';
                    $application->interviews()->create([
                        'contact_id' => $contact?->id,
                        'type' => $isScreening ? InterviewType::Phone : InterviewType::Video,
                        'scheduled_at' => in_array($status, ['interview', 'offer'], true)
                            ? now()->addDays(($index % 6) + 1)->setTime(14, 0)
                            : now()->subDays(2)->setTime(10, 0),
                        'duration_minutes' => 60,
                        'location_or_url' => 'https://meet.example.com/demo-interview',
                        'outcome' => $isScreening ? InterviewOutcome::Passed : InterviewOutcome::Pending,
                    ]);
                }

                if (! in_array($status, ['rejected', 'withdrawn'], true)) {
                    $application->tasks()->createMany([
                        [
                            'title' => 'Send a thoughtful follow-up',
                            'due_at' => $index % 3 === 0 ? today()->subDays(2) : today()->addDays(($index % 8) + 1),
                            'priority' => $index % 3 === 0 ? TaskPriority::High : TaskPriority::Normal,
                        ],
                        [
                            'title' => 'Review company and role notes',
                            'due_at' => today()->addDays(($index % 12) + 3),
                            'completed_at' => $index % 2 === 0 ? now()->subDay() : null,
                            'priority' => TaskPriority::Low,
                        ],
                    ]);
                }
            }
        });
    }

    /** @param list<int> $transitions */
    private function addHistory(
        JobApplication $application,
        array $transitions,
        ?CarbonInterface $appliedAt,
        ApplicationStatus $currentStatus,
    ): void {
        if ($appliedAt === null) {
            $application->statusEvents()->create([
                'from_status' => null,
                'to_status' => ApplicationStatus::Saved,
                'changed_at' => $application->created_at,
            ]);

            return;
        }

        $path = [ApplicationStatus::Applied];
        if (in_array($currentStatus, [ApplicationStatus::Screening, ApplicationStatus::Interview, ApplicationStatus::Offer], true)) {
            $path[] = ApplicationStatus::Screening;
        }
        if (in_array($currentStatus, [ApplicationStatus::Interview, ApplicationStatus::Offer], true)) {
            $path[] = ApplicationStatus::Interview;
        }
        if ($currentStatus === ApplicationStatus::Offer) {
            $path[] = ApplicationStatus::Offer;
        } elseif (in_array($currentStatus, [ApplicationStatus::Rejected, ApplicationStatus::Withdrawn], true)) {
            $path[] = $currentStatus;
        }

        $previousStatus = ApplicationStatus::Saved;
        foreach ($path as $position => $status) {
            $application->statusEvents()->create([
                'from_status' => $previousStatus,
                'to_status' => $status,
                'changed_at' => $appliedAt->copy()->addDays($transitions[$position] ?? $position),
            ]);
            $previousStatus = $status;
        }
    }
}
