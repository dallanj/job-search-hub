<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Filters\ByCompany;
use App\Filters\ByDateRange;
use App\Filters\ByLocation;
use App\Filters\BySearchTerm;
use App\Http\Requests\IndexPipelineRequest;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use UnexpectedValueException;

class PipelineController extends Controller
{
    public function __invoke(IndexPipelineRequest $request, Pipeline $pipeline): Response
    {
        $request->validated();
        $search = $request->filled('search') ? $request->string('search')->toString() : null;
        $companyId = $request->integer('company_id') ?: null;
        $location = $request->filled('location') ? $request->string('location')->toString() : null;
        $dateFrom = $request->filled('date_from') ? $request->string('date_from')->toString() : null;
        $dateTo = $request->filled('date_to') ? $request->string('date_to')->toString() : null;

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

        $filteredQuery = $this->applyFilters(
            $pipeline,
            $query,
            $search,
            $companyId,
            $location,
            $dateFrom,
            $dateTo,
        );

        $applications = $filteredQuery
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (JobApplication $application): string => $application->status->value);

        $columns = array_map(
            fn (ApplicationStatus $status): array => [
                'status' => $status->value,
                'label' => Str::headline($status->value),
                'applications' => $applications->get($status->value, collect())->values(),
            ],
            ApplicationStatus::cases(),
        );

        return Inertia::render('pipeline/Index', [
            'columns' => $columns,
            'companies' => $request->user()->companies()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'filters' => [
                'search' => $search,
                'company_id' => $companyId,
                'location' => $location,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
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
