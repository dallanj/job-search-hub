<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MoveJobApplication
{
    public function handle(
        JobApplication $application,
        ApplicationStatus $targetStatus,
        int $targetPosition,
    ): void {
        DB::transaction(function () use ($application, $targetStatus, $targetPosition): void {
            $lockedApplication = JobApplication::query()
                ->whereBelongsTo($application->user)
                ->lockForUpdate()
                ->findOrFail($application->id);

            $sourceStatus = $lockedApplication->status;
            $applications = JobApplication::query()
                ->whereBelongsTo($application->user)
                ->whereIn('status', [$sourceStatus->value, $targetStatus->value])
                ->orderBy('status')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'status', 'sort_order']);

            $targetIds = $this->idsForStatus($applications, $targetStatus, $lockedApplication->id);
            $position = min($targetPosition, count($targetIds));
            array_splice($targetIds, $position, 0, [$lockedApplication->id]);

            $this->persistOrder($targetIds, $targetStatus);

            if ($sourceStatus !== $targetStatus) {
                $sourceIds = $this->idsForStatus($applications, $sourceStatus, $lockedApplication->id);
                $this->persistOrder($sourceIds, $sourceStatus);

                $lockedApplication->statusEvents()->create([
                    'from_status' => $sourceStatus,
                    'to_status' => $targetStatus,
                    'changed_at' => now(),
                ]);
            }
        }, attempts: 3);
    }

    /**
     * @param  Collection<int, JobApplication>  $applications
     * @return array<int, int>
     */
    private function idsForStatus(
        Collection $applications,
        ApplicationStatus $status,
        int $excludedApplicationId,
    ): array {
        return $applications
            ->filter(fn (JobApplication $application): bool => $application->status === $status
                && $application->id !== $excludedApplicationId)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int>  $applicationIds
     */
    private function persistOrder(array $applicationIds, ApplicationStatus $status): void
    {
        foreach ($applicationIds as $position => $applicationId) {
            JobApplication::query()
                ->whereKey($applicationId)
                ->update([
                    'status' => $status->value,
                    'sort_order' => $position,
                ]);
        }
    }
}
