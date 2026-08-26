<?php

namespace App\Actions;

use App\Enums\InterviewOutcome;
use App\Models\Interview;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LogicException;

class GetUpcomingActions
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     kind: string,
     *     title: string,
     *     scheduled_for: string,
     *     is_overdue: bool,
     *     detail: string,
     *     application: array{id: int, role_title: string, company: array{id: int, name: string}}
     * }>
     */
    public function handle(User $user, int $days = 14): Collection
    {
        $end = now()->addDays($days)->endOfDay();
        $applicationIds = $user->jobApplications()->select('id');

        $tasks = Task::query()
            ->whereIn('job_application_id', clone $applicationIds)
            ->whereNull('completed_at')
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<=', $end)
            ->with(['jobApplication:id,company_id,role_title', 'jobApplication.company:id,name'])
            ->get()
            ->map($this->taskAction(...));

        $interviews = Interview::query()
            ->whereIn('job_application_id', clone $applicationIds)
            ->where('scheduled_at', '>=', today())
            ->where('scheduled_at', '<=', $end)
            ->where(function ($query): void {
                $query->whereNull('outcome')
                    ->orWhereNotIn('outcome', [
                        InterviewOutcome::Cancelled->value,
                        InterviewOutcome::Passed->value,
                        InterviewOutcome::Failed->value,
                    ]);
            })
            ->with([
                'jobApplication:id,company_id,role_title',
                'jobApplication.company:id,name',
                'contact:id,name',
            ])
            ->get()
            ->map($this->interviewAction(...));

        return $tasks->merge($interviews)
            ->sortBy(fn (array $action): string => $action['scheduled_for'])
            ->values();
    }

    /**
     * @return array{id: int, kind: 'task', title: string, scheduled_for: string, is_overdue: bool, detail: string, application: array{id: int, role_title: string, company: array{id: int, name: string}}}
     */
    private function taskAction(Task $task): array
    {
        if ($task->due_at === null) {
            throw new LogicException('Upcoming tasks must have a due date.');
        }

        return [
            'id' => $task->id,
            'kind' => 'task',
            'title' => $task->title,
            'scheduled_for' => $task->due_at->toDateString(),
            'is_overdue' => $task->due_at->isBefore(today()),
            'detail' => Str::headline($task->priority->name).' priority',
            'application' => [
                'id' => $task->jobApplication->id,
                'role_title' => $task->jobApplication->role_title,
                'company' => [
                    'id' => $task->jobApplication->company->id,
                    'name' => $task->jobApplication->company->name,
                ],
            ],
        ];
    }

    /**
     * @return array{id: int, kind: 'interview', title: string, scheduled_for: string, is_overdue: false, detail: string, application: array{id: int, role_title: string, company: array{id: int, name: string}}}
     */
    private function interviewAction(Interview $interview): array
    {
        return [
            'id' => $interview->id,
            'kind' => 'interview',
            'title' => Str::headline($interview->type->value).' interview',
            'scheduled_for' => $interview->scheduled_at->toIso8601String(),
            'is_overdue' => false,
            'detail' => $interview->contact_id === null
                ? 'Interviewer not set'
                : $interview->contact->name,
            'application' => [
                'id' => $interview->jobApplication->id,
                'role_title' => $interview->jobApplication->role_title,
                'company' => [
                    'id' => $interview->jobApplication->company->id,
                    'name' => $interview->jobApplication->company->name,
                ],
            ],
        ];
    }
}
