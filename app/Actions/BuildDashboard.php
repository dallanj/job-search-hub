<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use LogicException;

class BuildDashboard
{
    public function __construct(private readonly GetUpcomingActions $getUpcomingActions) {}

    /** @return array<string, mixed> */
    public function handle(User $user): array
    {
        $applications = $user->jobApplications()
            ->with([
                'company:id,name',
                'statusEvents:id,job_application_id,to_status,changed_at',
                'tasks:id,job_application_id,due_at,completed_at',
                'interviews:id,job_application_id,scheduled_at,outcome',
            ])
            ->get();

        $thisWeek = $applications->filter(
            fn (JobApplication $application): bool => $application->applied_at?->isCurrentWeek() ?? false,
        )->count();
        $lastWeek = $applications->filter(
            fn (JobApplication $application): bool => $application->applied_at?->between(
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ) ?? false,
        )->count();
        $responses = $this->responseTimes($applications);
        $applied = $applications->filter(
            fn (JobApplication $application): bool => $this->reached($application, ApplicationStatus::Applied),
        );
        $interviewed = $applied->filter(
            fn (JobApplication $application): bool => $this->reached($application, ApplicationStatus::Interview),
        )->count();

        return [
            'summary' => [
                'active_applications' => $applications->whereNotIn('status', [
                    ApplicationStatus::Rejected,
                    ApplicationStatus::Withdrawn,
                    ApplicationStatus::Archived,
                ])->count(),
                'applications_this_week' => $thisWeek,
                'applications_week_change' => $thisWeek - $lastWeek,
                'interview_rate' => $this->percentage($interviewed, $applied->count()),
                'median_response_days' => $this->median($responses),
            ],
            'attention' => $this->attention($applications),
            'upcoming_actions' => $this->getUpcomingActions->handle($user, 7)->take(6)->values(),
            'funnel' => $this->funnel($applications),
            'activity' => $this->activity($applications),
            'response_time' => $this->responseTime($applications, $responses),
            'sources' => $this->sources($applications),
        ];
    }

    /**
     * @param  EloquentCollection<int, JobApplication>  $applications
     * @return array<string, int>
     */
    private function attention(EloquentCollection $applications): array
    {
        return [
            'overdue_follow_ups' => $applications->sum(
                fn (JobApplication $application): int => $application->tasks
                    ->filter(fn ($task): bool => $task->completed_at === null && $task->due_at?->isBefore(today()))
                    ->count(),
            ),
            'interviews_next_seven_days' => $applications->sum(
                fn (JobApplication $application): int => $application->interviews
                    ->filter(fn ($interview): bool => $interview->scheduled_at->between(now(), now()->addDays(7)->endOfDay())
                        && ! in_array($interview->outcome?->value, ['passed', 'failed', 'cancelled'], true))
                    ->count(),
            ),
            'awaiting_response_14_days' => $applications->filter(
                fn (JobApplication $application): bool => $application->status === ApplicationStatus::Applied
                    && $application->applied_at?->lte(today()->subDays(14)),
            )->count(),
            'saved_not_applied' => $applications->where('status', ApplicationStatus::Saved)->count(),
            'without_upcoming_action' => $applications->filter(
                fn (JobApplication $application): bool => ! in_array($application->status, [
                    ApplicationStatus::Rejected,
                    ApplicationStatus::Withdrawn,
                    ApplicationStatus::Archived,
                ], true)
                    && ! $application->tasks->contains(fn ($task): bool => $task->completed_at === null)
                    && ! $application->interviews->contains(fn ($interview): bool => $interview->scheduled_at->gte(now())),
            )->count(),
        ];
    }

    /**
     * @param  EloquentCollection<int, JobApplication>  $applications
     * @return list<array<string, int|string>>
     */
    private function funnel(EloquentCollection $applications): array
    {
        $stages = [ApplicationStatus::Applied, ApplicationStatus::Screening, ApplicationStatus::Interview, ApplicationStatus::Offer];
        $previous = null;

        return array_map(function (ApplicationStatus $stage) use ($applications, &$previous): array {
            $count = $applications->filter(fn (JobApplication $application): bool => $this->reached($application, $stage))->count();
            $conversion = $previous === null ? 100 : $this->percentage($count, $previous);
            $previous = $count;

            return ['stage' => $stage->value, 'count' => $count, 'conversion' => $conversion];
        }, $stages);
    }

    /**
     * @param  EloquentCollection<int, JobApplication>  $applications
     * @return list<array{week: string, label: string, applications: int, interviews: int}>
     */
    private function activity(EloquentCollection $applications): array
    {
        return array_values(collect(range(7, 0))->map(function (int $weeksAgo) use ($applications): array {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = $start->copy()->endOfWeek();

            return [
                'week' => $start->toDateString(),
                'label' => $start->format('M j'),
                'applications' => $applications->filter(
                    fn (JobApplication $application): bool => $application->applied_at?->between($start, $end) ?? false,
                )->count(),
                'interviews' => $applications->sum(
                    fn (JobApplication $application): int => $application->interviews
                        ->filter(fn ($interview): bool => $interview->scheduled_at->between($start, $end))
                        ->count(),
                ),
            ];
        })->all());
    }

    /**
     * @param  EloquentCollection<int, JobApplication>  $applications
     * @param  Collection<int, float>  $responses
     * @return array<string, mixed>
     */
    private function responseTime(EloquentCollection $applications, Collection $responses): array
    {
        $buckets = [
            ['label' => '0–3 days', 'count' => $responses->filter(fn (float $days): bool => $days <= 3)->count()],
            ['label' => '4–7 days', 'count' => $responses->filter(fn (float $days): bool => $days > 3 && $days <= 7)->count()],
            ['label' => '8–14 days', 'count' => $responses->filter(fn (float $days): bool => $days > 7 && $days <= 14)->count()],
            ['label' => '15+ days', 'count' => $responses->filter(fn (float $days): bool => $days > 14)->count()],
        ];

        return [
            'median_days' => $this->median($responses),
            'quartile_low' => $this->percentile($responses, 25),
            'quartile_high' => $this->percentile($responses, 75),
            'awaiting' => $applications->filter(
                fn (JobApplication $application): bool => $application->status === ApplicationStatus::Applied,
            )->count(),
            'total_responses' => $responses->count(),
            'buckets' => $buckets,
        ];
    }

    /**
     * @param  EloquentCollection<int, JobApplication>  $applications
     * @return list<array<string, float|int|string|null>>
     */
    private function sources(EloquentCollection $applications): array
    {
        return array_values($applications->filter(fn (JobApplication $application): bool => $application->applied_at !== null)
            ->groupBy(fn (JobApplication $application): string => $application->source ?: 'Not specified')
            ->map(function (EloquentCollection $items, string $source): array {
                $responses = $this->responseTimes($items);
                $responded = $items->filter(fn (JobApplication $application): bool => $this->hasResponse($application))->count();
                $interviews = $items->filter(fn (JobApplication $application): bool => $this->reached($application, ApplicationStatus::Interview))->count();
                $offers = $items->filter(fn (JobApplication $application): bool => $this->reached($application, ApplicationStatus::Offer))->count();

                return [
                    'source' => $source,
                    'applications' => $items->count(),
                    'response_rate' => $this->percentage($responded, $items->count()),
                    'interview_rate' => $this->percentage($interviews, $items->count()),
                    'offer_rate' => $this->percentage($offers, $items->count()),
                    'median_response_days' => $this->median($responses),
                ];
            })->sortByDesc('applications')->values()->all());
    }

    /**
     * @param  EloquentCollection<int, JobApplication>  $applications
     * @return Collection<int, float>
     */
    private function responseTimes(EloquentCollection $applications): Collection
    {
        return $applications->map(function (JobApplication $application): ?float {
            if ($application->applied_at === null) {
                return null;
            }

            $event = $application->statusEvents
                ->filter(fn ($event): bool => in_array($event->to_status, [ApplicationStatus::Screening, ApplicationStatus::Interview, ApplicationStatus::Offer, ApplicationStatus::Rejected], true))
                ->where('changed_at', '>=', $application->applied_at)
                ->sortBy('changed_at')
                ->first();

            return $event === null
                ? null
                : $application->applied_at->diffInSeconds($event->changed_at) / 86_400;
        })->filter(fn (?float $days): bool => $days !== null)
            ->map(fn (float $days): float => $days)
            ->values();
    }

    private function hasResponse(JobApplication $application): bool
    {
        return $this->responseTimes(new EloquentCollection([$application]))->isNotEmpty();
    }

    private function reached(JobApplication $application, ApplicationStatus $target): bool
    {
        $rank = [ApplicationStatus::Saved->value => 0, ApplicationStatus::Applied->value => 1, ApplicationStatus::Screening->value => 2, ApplicationStatus::Interview->value => 3, ApplicationStatus::Offer->value => 4];
        $targetRank = $rank[$target->value] ?? throw new LogicException('Unsupported funnel stage.');

        return $application->statusEvents->contains(
            fn ($event): bool => isset($rank[$event->to_status->value]) && $rank[$event->to_status->value] >= $targetRank,
        );
    }

    /** @param Collection<int, float> $values */
    private function median(Collection $values): ?float
    {
        return $this->percentile($values, 50);
    }

    /** @param Collection<int, float> $values */
    private function percentile(Collection $values, int $percentile): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $index = ($percentile / 100) * ($sorted->count() - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        $value = $sorted[$lower] + (($sorted[$upper] - $sorted[$lower]) * ($index - $lower));

        return round($value, 1);
    }

    private function percentage(int $part, int $whole): int
    {
        return $whole === 0 ? 0 : (int) round(($part / $whole) * 100);
    }
}
