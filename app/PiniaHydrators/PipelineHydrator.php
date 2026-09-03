<?php

namespace App\PiniaHydrators;

use App\Enums\ApplicationStatus;
use App\Filters\ByCompany;
use App\Filters\ByDateRange;
use App\Filters\ByLocation;
use App\Filters\BySearchTerm;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use UnexpectedValueException;

final class PipelineHydrator
{
    /**
     * @return array<int, array{status: string, label: string, applications: array<int, mixed>}>
     */
    public function columns(Request $request, Pipeline $pipeline): array
    {
        $filters = $this->filterValues($request);
        $query = $request->user()->jobApplications()
            ->getQuery()
            ->select([
                'id',
                'company_id',
                'role_title',
                'status',
                'sort_order',
                'location',
                'workplace_type',
                'applied_at',
            ])
            ->with('company:id,name');

        $applications = $this->applyFilters(
            $pipeline,
            $query,
            $filters['search'],
            $filters['company_id'],
            $filters['location'],
            $filters['date_from'],
            $filters['date_to'],
        )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (JobApplication $application): string => $application->status->value);

        return array_map(
            fn (ApplicationStatus $status): array => [
                'status' => $status->value,
                'label' => Str::headline($status->value),
                'applications' => $applications->get($status->value, collect())->values()->toArray(),
            ],
            ApplicationStatus::cases(),
        );
    }

    /**
     * @return array{search: ?string, company_id: ?int, location: ?string, date_from: ?string, date_to: ?string}
     */
    private function filterValues(Request $request): array
    {
        return [
            'search' => $request->filled('search') ? $request->string('search')->toString() : null,
            'company_id' => $request->integer('company_id') ?: null,
            'location' => $request->filled('location') ? $request->string('location')->toString() : null,
            'date_from' => $request->filled('date_from') ? $request->string('date_from')->toString() : null,
            'date_to' => $request->filled('date_to') ? $request->string('date_to')->toString() : null,
        ];
    }

    /**
     * @param  Builder<JobApplication>  $query
     * @return Builder<JobApplication>
     */
    private function applyFilters(
        Pipeline $pipeline,
        Builder $query,
        ?string $search,
        ?int $companyId,
        ?string $location,
        ?string $dateFrom,
        ?string $dateTo,
    ): Builder {
        $filteredQuery = $pipeline->send($query)
            ->through([
                new BySearchTerm($search),
                new ByCompany($companyId),
                new ByLocation($location),
                new ByDateRange($dateFrom, $dateTo),
            ])
            ->thenReturn();

        if (! $filteredQuery instanceof Builder
            || ! $filteredQuery->getModel() instanceof JobApplication) {
            throw new UnexpectedValueException('The application filter pipeline must return a job application query.');
        }

        return $filteredQuery;
    }
}
